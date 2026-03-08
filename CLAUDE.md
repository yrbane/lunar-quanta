# Lunar Quanta Development Guidelines

Framework PHP moderne sans dépendances runtime externes.

## Technologies

- **PHP 8.3+** avec `strict_types`
- **Zero Dependencies** (Constitution II)
- Stockage JSON (FileStorage/JsonStorage avec chiffrement AES-256-CBC)
- Templates avec syntaxe personnalisée `[% %]` et `[[ ]]`

## Structure

```
src/
├── Attribute/          # Attributs PHP 8 (#[Route])
├── Command/            # Commandes CLI (AbstractBlogCommand + 27 commandes blog)
├── Controller/         # Contrôleurs (DefaultController, Admin/*)
├── Entity/             # Entités (User, Post, Tag, Image, Category)
├── Exception/          # Exceptions personnalisées
└── Service/
    ├── Api/            # Helpers réponses API
    ├── Blog/           # PostService, TagService, CategoryService, SlugGenerator
    ├── Cache/          # CacheService
    ├── Content/        # MarkdownParser, HtmlSanitizer
    ├── Core/           # Router, Container, Request, Response
    ├── Database/       # QueryBuilder, Migrations
    ├── Event/          # EventDispatcher
    ├── I18n/           # Translator
    ├── Logging/        # Logger PSR-3 inspired
    ├── Media/          # ImageStorageService, AvatarService
    ├── Queue/          # Queue & Worker
    ├── Security/       # Auth, CSRF, OAuth, 2FA, EncryptionService
    ├── Session/        # SessionService
    ├── StaticSite/     # StaticGenerator, RssGenerator, SitemapGenerator
    ├── Storage/        # FileStorage, JsonStorage (chiffré)
    └── Validation/     # Validator avec règles fluent

tests/                  # PHPUnit tests (1780+ tests)
template/               # Templates HTML (.html.tpl)
├── admin/              # Interface admin
├── auth/               # Authentification
├── blog/               # Blog statique (_layout, index, post, tag, category)
└── 2fa/                # Two-factor auth

docs/                   # Documentation
├── blog-system.md      # Architecture blog
├── admin-interface.md  # Interface admin
├── security.md         # Architecture de sécurité
├── performance.md      # Patterns d'optimisation
├── lunar-aurora.md     # Framework CSS
└── lunar-quanta.md     # Vue d'ensemble
```

## Prérequis

### Variable d'environnement APP_KEY

**Obligatoire** pour toute fonctionnalité utilisant le chiffrement (JsonStorage, utilisateurs).

```bash
# Générer une clé
php -r "echo bin2hex(random_bytes(32));"

# Configurer dans .env ou l'environnement
export APP_KEY=votre_cle_generee
```

Sans APP_KEY, le constructeur de JsonStorage lance une `RuntimeException`.

## Commandes

```bash
# Tests (avec xdebug désactivé pour éviter les warnings)
XDEBUG_MODE=off ./vendor/bin/phpunit --no-coverage

# Tests d'un fichier
XDEBUG_MODE=off ./vendor/bin/phpunit tests/Service/Blog/PostServiceTest.php --no-coverage

# Analyse statique
./vendor/bin/phpstan analyse src

# Serveur dev
php bin/console server:start

# Cache
php bin/console cache:clear
```

### Commandes Blog CLI

Les commandes blog héritent de `AbstractBlogCommand` qui fournit la résolution
d'article par ID ou slug et l'instanciation du PostService.

```bash
# Publier / Dépublier / Archiver
php bin/console blog:publish <id|slug>
php bin/console blog:unpublish <id|slug>
php bin/console blog:archive <id|slug>

# Supprimer (nécessite --force)
php bin/console blog:delete <id|slug> --force

# Régénérer le site statique
php bin/console blog:regenerate
```

## Blog System

### Admin Routes

| Route | Action |
|-------|--------|
| GET /admin/blog | Liste articles |
| GET /admin/blog/create | Création |
| POST /admin/blog/{id}/publish | Publier |
| POST /admin/blog/regenerate | Régénérer site statique |

### Génération statique

```php
$generator = new StaticGenerator(
    $postService,
    new MarkdownParser(),
    'public/blog',
    'template/blog',
    'https://example.com'  // Active RSS + Sitemap
);

// Optionnel : catégories (active le cache de catégories)
$generator->setCategoryService($categoryService);

$result = $generator->generateAll();
// Génère: posts/*.html, index.html, tag/*.html, category/*.html, feed.xml, sitemap.xml
```

### Cycle de vie des articles

```
DRAFT → publish() → PUBLISHED → unpublish() → DRAFT
                  → archive()  → ARCHIVED
```

### Patterns de performance

- **Mémoïsation** : PostService et CategoryService cachent `all()` en mémoire, invalidé à chaque écriture
- **Category Cache** : StaticGenerator pré-charge toutes les catégories pour des lookups O(1)
- **Tag Index** : Index inversé de tags pour le calcul d'articles similaires en O(k) au lieu de O(n²)

Voir `docs/performance.md` pour les détails.

## Architecture de sécurité

- **Chiffrement** : AES-256-CBC + HMAC (encrypt-then-MAC) via EncryptionService
- **APP_KEY** : clé obligatoire, pas de valeur par défaut
- **IV** : généré par `random_bytes()` (CSPRNG)
- **Path Traversal** : sanitization whitelist dans FileStorage et PasswordResetService
- **OAuth** : SSL forcé (verify_peer), timeout 10s, pas de self-signed

Voir `docs/security.md` pour les détails.

## Code Style

- **Classes**: PascalCase
- **Méthodes**: camelCase
- **Constantes**: UPPER_SNAKE_CASE
- **PHPDoc** pour les types complexes, avec `@see docs/*.md` pour les renvois
- **Attributs PHP 8** pour le routing (#[Route])
- **Commentaires** : expliquer le "pourquoi", pas le "quoi"

## Milestones

- **v1.0**: Framework baseline (Router, Container, Auth)
- **v1.1**: Middleware, Session, CSRF, Authentication
- **v1.2**: Blog System, Admin UI, Infrastructure (Validation, DB, Events, Queue, i18n, Logging)
- **v1.3**: Audit sécurité, optimisations performance, accessibilité (ARIA), qualité code
