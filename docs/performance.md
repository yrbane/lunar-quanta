# Performance - Lunar Quanta

## Vue d'ensemble

Lunar Quanta utilise trois patterns d'optimisation majeurs pour éviter les accès filesystem redondants et les algorithmes quadratiques. Chaque pattern est conçu pour un contexte précis : la mémoïsation pour les services CRUD, le cache de catégories pour la génération statique, et l'index inversé de tags pour la recherche d'articles similaires.

## 1. Mémoïsation (PostService, CategoryService)

### Le problème

`PostService` et `CategoryService` stockent leurs données dans des fichiers JSON individuels. Chaque appel à `all()` doit :
1. Lister les fichiers du répertoire (`glob()`)
2. Lire chaque fichier (`file_get_contents()`)
3. Décoder le JSON (`json_decode()`)
4. Hydrater les entités (`Post::fromArray()`)

Or, dans une requête typique, `all()` est appelé plusieurs fois indirectement : par `findPublished()`, `findByTag()`, `findRecent()`, etc.

```
  Sans mémoïsation :

  findPublished()  ──→ all() ──→ glob + lecture N fichiers
  findByTag()      ──→ all() ──→ glob + lecture N fichiers  (doublon !)
  findRecent()     ──→ all() ──→ glob + lecture N fichiers  (doublon !)

  Coût : 3 × N lectures filesystem
```

### Le pattern

```php
// src/Service/Blog/PostService.php

final class PostService
{
    /** @var Post[]|null */
    private ?array $cachedAll = null;

    /**
     * Retourne tous les articles.
     * Premier appel : lecture filesystem. Appels suivants : mémoire.
     */
    public function all(): array
    {
        if ($this->cachedAll !== null) {
            return $this->cachedAll;
        }

        $this->cachedAll = array_map(
            fn($data) => Post::fromArray($data),
            $this->storage->all()
        );

        return $this->cachedAll;
    }

    /**
     * Invalide le cache après toute écriture.
     */
    private function invalidateCache(): void
    {
        $this->cachedAll = null;
    }
}
```

### Avec mémoïsation

```
  findPublished()  ──→ all() ──→ glob + lecture N fichiers
                                      │
                                      ▼
                                 $cachedAll = [Post, Post, ...]
                                      │
  findByTag()      ──→ all() ──→ return $cachedAll  (mémoire !)
  findRecent()     ──→ all() ──→ return $cachedAll  (mémoire !)

  Coût : 1 × N lectures filesystem + 2 accès mémoire
```

### Quand invalider ?

Le cache est invalidé à chaque opération d'écriture. C'est essentiel pour garantir la cohérence :

```php
public function create(string $title, string $content): Post
{
    // ... création ...
    $this->storage->save($post->getId(), $post->toArray());
    $this->invalidateCache();  // ← Le prochain all() relira le filesystem
    return $post;
}

public function update(Post $post): void
{
    $this->storage->save($post->getId(), $post->toArray());
    $this->invalidateCache();  // ← Idem
}

public function delete(string $id): void
{
    $this->storage->delete($id);
    $this->invalidateCache();  // ← Idem
}
```

**Pourquoi `null` plutôt qu'un tableau vide ?** La valeur `null` distingue "jamais chargé" de "chargé mais vide" (un blog sans articles). Un tableau vide `[]` est un résultat valide qui ne doit pas déclencher un rechargement.

### Complexité

| Opération | Sans cache | Avec cache |
|-----------|-----------|------------|
| Premier `all()` | O(n) filesystem | O(n) filesystem |
| `all()` suivants | O(n) filesystem | O(1) mémoire |
| Après `create/update/delete` | O(n) filesystem | O(n) filesystem (1 fois) |

Le même pattern est appliqué dans `CategoryService` :

```php
// src/Service/Blog/CategoryService.php

final class CategoryService
{
    /** @var Category[]|null */
    private ?array $cachedAll = null;

    public function all(): array
    {
        if ($this->cachedAll !== null) {
            return $this->cachedAll;
        }

        $all = $this->storage->all();
        $categories = array_map(
            fn(array $data) => Category::fromArray($data),
            $all
        );

        // Trier par sortOrder puis par nom
        usort($categories, function (Category $a, Category $b) {
            if ($a->getSortOrder() !== $b->getSortOrder()) {
                return $a->getSortOrder() <=> $b->getSortOrder();
            }
            return $a->getName() <=> $b->getName();
        });

        $this->cachedAll = $categories;
        return $this->cachedAll;
    }
}
```

## 2. Cache de catégories (StaticGenerator)

### Le problème

Lors de la génération statique, chaque article peut avoir une catégorie. Pour afficher le nom de la catégorie sur la page d'un article, il faut la récupérer :

```
  Sans cache de catégories :

  Article 1 → categoryService->find("cat-a") → lecture filesystem
  Article 2 → categoryService->find("cat-b") → lecture filesystem
  Article 3 → categoryService->find("cat-a") → lecture filesystem (doublon !)
  Article 4 → categoryService->find("cat-a") → lecture filesystem (doublon !)
  ...
  Article N → categoryService->find("cat-b") → lecture filesystem (doublon !)

  Coût pour N articles et C catégories : N appels à find()
  Même si CategoryService a son propre cache, chaque find() reconstruit
  l'entité à chaque appel.
```

### Le pattern

Le `StaticGenerator` pré-charge toutes les catégories dans un tableau indexé par ID :

```php
// src/Service/StaticSite/StaticGenerator.php

/** @var array<string, Category> Pre-loaded category cache */
private array $categoryCache = [];

public function setCategoryService(CategoryService $categoryService): void
{
    $this->categoryService = $categoryService;
    $this->warmCategoryCache();  // ← Pré-chargement immédiat
}

private function warmCategoryCache(): void
{
    $this->categoryCache = [];
    if ($this->categoryService !== null) {
        foreach ($this->categoryService->all() as $category) {
            $this->categoryCache[$category->getId()] = $category;
        }
    }
}

private function getCachedCategory(?string $categoryId): ?Category
{
    if ($categoryId === null) {
        return null;
    }
    return $this->categoryCache[$categoryId] ?? null;
}
```

### Avec le cache de catégories

```
  warmCategoryCache()
       │
       ▼
  categoryService->all()  ← 1 seul appel
       │
       ▼
  $categoryCache = [
      "cat-a" => Category("PHP"),
      "cat-b" => Category("DevOps"),
  ]

  Article 1 → getCachedCategory("cat-a") → $categoryCache["cat-a"]  O(1)
  Article 2 → getCachedCategory("cat-b") → $categoryCache["cat-b"]  O(1)
  Article 3 → getCachedCategory("cat-a") → $categoryCache["cat-a"]  O(1)
  ...

  Coût : 1 appel all() + N lookups O(1) en mémoire
```

### Complexité

| Approche | Coût total |
|----------|-----------|
| `find()` par article | O(N x C) dans le pire cas |
| `warmCategoryCache()` | O(C) init + O(1) par article = O(N + C) |

Avec 100 articles et 10 catégories : 100 appels `find()` vs 1 appel `all()` + 100 lookups dans un tableau PHP (hashmap).

## 3. Index inversé de tags pour les articles similaires

### Le problème

Pour chaque article, le `StaticGenerator` cherche les articles similaires (même catégorie ou tags communs). L'approche naïve est quadratique :

```
  Approche naïve O(n²) :

  Pour chaque article (N articles) :
      Pour chaque autre article (N-1 articles) :
          Comparer les tags (k tags par article)

  Coût : O(n² × k)

  Avec 200 articles et 5 tags/article : 200 × 199 × 5 = 199 000 comparaisons
```

### Le pattern : index inversé

Le `StaticGenerator` construit un index inversé qui mappe chaque tag à la liste des articles qui l'utilisent :

```php
// src/Service/StaticSite/StaticGenerator.php

/** @var array<string, array<string, true>> Tag index: tag => [postId => true] */
private array $tagIndex = [];

/** @var array<string, Post> Post index: id => Post */
private array $postIndex = [];

private bool $relatedIndexBuilt = false;

private function buildRelatedIndex(): void
{
    if ($this->relatedIndexBuilt) {
        return;
    }

    $allPosts = $this->postService->findPublished();
    foreach ($allPosts as $post) {
        $this->postIndex[$post->getId()] = $post;
        foreach ($post->getTags() as $tag) {
            $this->tagIndex[$tag][$post->getId()] = true;
        }
    }
    $this->relatedIndexBuilt = true;
}
```

### Structure de l'index inversé

```
  Articles et leurs tags :
  ════════════════════════

  Post A : [php, security, web]
  Post B : [php, api]
  Post C : [security, encryption]
  Post D : [php, web, performance]

  Index inversé construit :
  ═════════════════════════

  $tagIndex = [
      "php"        => ["A" => true, "B" => true, "D" => true],
      "security"   => ["A" => true, "C" => true],
      "web"        => ["A" => true, "D" => true],
      "api"        => ["B" => true],
      "encryption" => ["C" => true],
      "performance"=> ["D" => true],
  ]

  $postIndex = [
      "A" => Post A,
      "B" => Post B,
      "C" => Post C,
      "D" => Post D,
  ]
```

### Recherche d'articles similaires avec l'index

```php
private function findRelatedPosts(Post $currentPost, int $limit = 4): array
{
    $this->buildRelatedIndex();

    $currentId = $currentPost->getId();
    $currentTags = $currentPost->getTags();
    $currentCategoryId = $currentPost->getCategoryId();

    // Collecter les candidats depuis l'index de tags
    $scores = [];
    foreach ($currentTags as $tag) {
        if (isset($this->tagIndex[$tag])) {
            foreach ($this->tagIndex[$tag] as $postId => $_) {
                if ($postId === $currentId) {
                    continue;
                }
                $scores[$postId] = ($scores[$postId] ?? 0) + 5;
            }
        }
    }

    // Bonus de catégorie
    if ($currentCategoryId !== null) {
        foreach ($this->postIndex as $postId => $post) {
            if ($postId !== $currentId
                && $post->getCategoryId() === $currentCategoryId) {
                $scores[$postId] = ($scores[$postId] ?? 0) + 10;
            }
        }
    }

    // Trier par score décroissant
    arsort($scores);

    // Retourner les top résultats
    // ...
}
```

### Exemple concret de scoring

```
  Recherche d'articles similaires pour Post A [php, security, web] (catégorie: tuto)
  ══════════════════════════════════════════════════════════════════════════════════════

  Étape 1 : Parcourir les tags de Post A dans l'index

  Tag "php"      → Post B (+5), Post D (+5)
  Tag "security" → Post C (+5)
  Tag "web"      → Post D (+5)

  Étape 2 : Bonus catégorie (tuto)

  Post D est aussi catégorie "tuto" → Post D (+10)

  Scores finaux :
  ┌────────┬───────────────────────────────┬───────┐
  │ Post   │ Détail                        │ Score │
  ├────────┼───────────────────────────────┼───────┤
  │ Post D │ +5 (php) +5 (web) +10 (cat)  │   20  │
  │ Post B │ +5 (php)                      │    5  │
  │ Post C │ +5 (security)                 │    5  │
  └────────┴───────────────────────────────┴───────┘

  Résultat : [Post D, Post B, Post C] (trié par score)
```

### Complexité

| Étape | Approche naïve | Index inversé |
|-------|---------------|---------------|
| Construction de l'index | - | O(N x k) une seule fois |
| Recherche pour 1 article | O(N x k) | O(k x m) ou m = articles par tag |
| Recherche pour N articles | O(N² x k) | O(N x k x m) |
| Lookup d'un article par ID | O(N) scan | O(1) hashmap |

Avec 200 articles, 5 tags/article, et en moyenne 10 articles/tag :
- **Naif** : 200 x 200 x 5 = **200 000** comparaisons
- **Index** : construction O(200 x 5) = 1 000, puis par article O(5 x 10) = 50, total : 1 000 + 200 x 50 = **11 000** opérations

### Pourquoi `true` comme valeur dans l'index ?

```php
$this->tagIndex[$tag][$post->getId()] = true;
```

On utilise un tableau associatif `[postId => true]` plutôt qu'un tableau indexé `[postId, postId, ...]` pour deux raisons :

1. **Dédoublication automatique** : si un article est ajouté deux fois (bug), la clé écrase l'ancienne valeur sans créer de doublon.
2. **Lookup O(1)** : `isset($this->tagIndex[$tag][$postId])` est O(1) avec un tableau associatif, alors que `in_array($postId, $this->tagIndex[$tag])` serait O(n).

### Construction paresseuse (lazy)

```php
private bool $relatedIndexBuilt = false;

private function buildRelatedIndex(): void
{
    if ($this->relatedIndexBuilt) {
        return;
    }
    // ... construction ...
    $this->relatedIndexBuilt = true;
}
```

L'index n'est construit qu'au premier appel à `findRelatedPosts()`. Si on ne génère qu'un seul article (`generatePost()`), l'index est construit une fois. Si on génère tous les articles (`generateAll()`), l'index est construit une fois et réutilisé N fois.

```
  generateAll() avec 200 articles :
  ═════════════════════════════════

  generatePost(article1)
       └→ findRelatedPosts()
              └→ buildRelatedIndex()  ← Construction (1 fois)
                     ↓
  generatePost(article2)
       └→ findRelatedPosts()
              └→ buildRelatedIndex()  ← $relatedIndexBuilt = true, skip !
                     ↓
  ...
  generatePost(article200)
       └→ findRelatedPosts()
              └→ buildRelatedIndex()  ← $relatedIndexBuilt = true, skip !
```

## Résumé des patterns

| Pattern | Classe | Problème | Solution | Gain |
|---------|--------|----------|----------|------|
| Mémoïsation | PostService, CategoryService | Relecture filesystem à chaque `all()` | Cache `?array` + invalidation sur écriture | O(1) pour les appels répétés |
| Warm cache | StaticGenerator | N appels `find()` pour N articles | Pré-chargement dans un tableau indexé | O(1) lookup par catégorie |
| Index inversé | StaticGenerator | O(N²) pour trouver les articles similaires | Hashmap tag → articles | O(k x m) par article |
