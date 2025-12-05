# Système de Blog Statique

## Vue d'ensemble

Le système de blog de Lunar permet de créer, gérer et publier des articles qui sont ensuite générés en fichiers HTML statiques. Cette approche combine les avantages d'un CMS (interface d'administration) avec les performances d'un site statique.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Administration                            │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────────────┐  │
│  │ PostController│ → │ PostService │ → │ FileStorage (JSON)  │  │
│  └─────────────┘    └─────────────┘    └─────────────────────┘  │
│         │                                                        │
│         ▼                                                        │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │               StaticGenerator                            │    │
│  │  ┌──────────────┐    ┌────────────────┐                 │    │
│  │  │MarkdownParser│ → │ Templates HTML │ → public/blog/  │    │
│  │  └──────────────┘    └────────────────┘                 │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

## Entités

### Post

L'entité centrale représentant un article de blog.

```php
use Lunar\Entity\Post;

$post = new Post('Mon Article', '# Contenu Markdown');
$post->setExcerpt('Description courte');
$post->setAuthor('John Doe');
$post->addTag('php');
$post->setCategoryId('tutorials');

// Cycle de vie
$post->publish();   // DRAFT → PUBLISHED
$post->unpublish(); // PUBLISHED → DRAFT
$post->archive();   // * → ARCHIVED
```

**Propriétés :**
- `id` : UUID unique
- `title` : Titre de l'article
- `slug` : URL-friendly (auto-généré)
- `content` : Contenu Markdown
- `excerpt` : Description courte (SEO)
- `author` : Nom de l'auteur
- `status` : DRAFT | PUBLISHED | ARCHIVED
- `tags` : Liste de tags
- `categoryId` : Catégorie
- `createdAt`, `updatedAt`, `publishedAt` : Dates

### PostStatus (Enum)

```php
use Lunar\Entity\PostStatus;

PostStatus::DRAFT;     // Brouillon (défaut)
PostStatus::PUBLISHED; // Publié
PostStatus::ARCHIVED;  // Archivé
```

### Tag

```php
use Lunar\Entity\Tag;

$tag = new Tag('php', 'PHP');
$tag->setDescription('Articles sur PHP');
$tag->setColor('#8892BF');
```

### Image

```php
use Lunar\Entity\Image;
use Lunar\Entity\ImageSource;

$image = new Image('photo.jpg', ImageSource::UPLOAD);
$image->setAlt('Description');
$image->setDimensions(1920, 1080);
```

**Sources d'images :**
- `UPLOAD` : Upload utilisateur
- `PEXELS` : API Pexels
- `DALLE` : Génération DALL-E
- `IMAGEN` : Génération Google Imagen

## Services

### PostService

Gestion CRUD des articles.

```php
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

$service = new PostService(
    new FileStorage('data/blog/posts')
);

// Créer
$post = $service->create('Titre', 'Contenu');

// Rechercher
$post = $service->find($id);
$post = $service->findBySlug('mon-article');

// Lister
$all = $service->all();
$published = $service->findPublished();
$drafts = $service->findDrafts();
$recent = $service->findRecent(5);
$byTag = $service->findByTag('php');

// Publier
$service->publish($id);
$service->unpublish($id);
$service->archive($id);

// Supprimer
$service->delete($id);
```

### MarkdownParser

Convertit le Markdown en HTML (zéro dépendance).

```php
use Lunar\Service\Content\MarkdownParser;

$parser = new MarkdownParser();
$html = $parser->parse('# Titre\n\n**Gras** et *italique*');
```

**Syntaxe supportée :**
- Titres : `# H1` à `###### H6`
- Emphase : `**gras**`, `*italique*`, `~~barré~~`
- Liens : `[texte](url)`
- Images : `![alt](url)`
- Listes : `-` ou `1.`
- Code : `` `inline` `` et ` ```bloc``` `
- Citations : `> texte`
- Lignes horizontales : `---`

### HtmlSanitizer

Nettoie le HTML pour prévenir les XSS.

```php
use Lunar\Service\Content\HtmlSanitizer;

$sanitizer = new HtmlSanitizer();
$safe = $sanitizer->sanitize($untrustedHtml);
```

**Tags autorisés :** p, h1-h6, ul, ol, li, a, img, code, pre, blockquote, strong, em, etc.

**Tags supprimés :** script, style, iframe, object, embed, form, input, etc.

### StaticGenerator

Génère les fichiers HTML statiques.

```php
use Lunar\Service\StaticSite\StaticGenerator;

$generator = new StaticGenerator(
    $postService,
    new MarkdownParser(),
    'public/blog',      // Sortie
    'template/blog'     // Templates
);

// Générer un article
$generator->generatePost($post);

// Générer l'index
$generator->generateIndex();

// Tout générer
$result = $generator->generateAll();
// ['posts' => 10, 'index' => true]

// Régénérer (clean + generate)
$generator->regenerate();

// Callback à la publication
$generator->onPublish(function($post) {
    // Notifier, invalider cache, etc.
});
```

## Administration

### Routes

| Méthode | URL | Action |
|---------|-----|--------|
| GET | `/admin/blog` | Liste des articles |
| GET | `/admin/blog/create` | Formulaire création |
| POST | `/admin/blog/create` | Créer article |
| GET | `/admin/blog/{id}/edit` | Formulaire édition |
| POST | `/admin/blog/{id}/edit` | Modifier article |
| POST | `/admin/blog/{id}/publish` | Publier |
| POST | `/admin/blog/{id}/unpublish` | Dépublier |
| POST | `/admin/blog/{id}/archive` | Archiver |
| POST | `/admin/blog/{id}/delete` | Supprimer |
| POST | `/admin/blog/preview` | Aperçu Markdown (AJAX) |
| POST | `/admin/blog/regenerate` | Régénérer tout |

### Interface

L'admin propose :

- **Dashboard** : Statistiques (total, publiés, brouillons)
- **Filtres** : Tous / Publiés / Brouillons
- **Actions rapides** : Publier, dépublier, supprimer
- **Éditeur** : Markdown avec prévisualisation live
- **Métadonnées** : Auteur, extrait, slug

### Templates

```
template/
├── admin/
│   ├── base.html.tpl      # Layout admin
│   └── blog/
│       ├── index.html.tpl # Liste articles
│       └── form.html.tpl  # Formulaire
└── blog/
    ├── index.html         # Index statique
    └── post.html          # Article statique
```

## Stockage

Les données sont stockées en JSON dans `data/blog/` :

```
data/blog/
├── posts/           # Articles (un fichier par article)
│   ├── abc123.json
│   └── def456.json
├── categories/      # Catégories
├── tags/           # Tags
└── images/         # Métadonnées images
```

Les fichiers uploadés sont dans `public/uploads/blog/`.

## Génération statique

Les fichiers générés vont dans `public/blog/` :

```
public/blog/
├── index.html           # Liste des articles
└── posts/
    ├── mon-article.html
    └── autre-article.html
```

### Templates statiques

Les templates utilisent une syntaxe simple :

```html
<!-- Variables -->
{{ title }}
{{ content }}

<!-- Conditions -->
{% if author %}
<p>Par {{ author }}</p>
{% endif %}

<!-- Boucles -->
{% for post in posts %}
<article>
    <h2>{{ post.title }}</h2>
    <p>{{ post.excerpt }}</p>
</article>
{% endfor %}
```

## Exemple complet

```php
// 1. Créer un article
$post = $postService->create(
    'Introduction à PHP 8',
    file_get_contents('article.md')
);
$post->setExcerpt('Découvrez les nouveautés de PHP 8');
$post->setAuthor('John Doe');
$postService->update($post);

// 2. Publier
$postService->publish($post->getId());

// 3. Générer le HTML
$generator->generatePost($post);
$generator->generateIndex();

// L'article est maintenant accessible à :
// /blog/posts/introduction-a-php-8.html
```

## Configuration Nginx

Pour servir les fichiers statiques :

```nginx
location /blog {
    root /var/www/html/public;
    try_files $uri $uri/ =404;
}
```

## Bonnes pratiques

1. **Toujours régénérer après modification** : Les fichiers statiques ne se mettent pas à jour automatiquement

2. **Utiliser les callbacks** : Pour invalider le cache, notifier, etc.

3. **Sauvegarder régulièrement** : Les données JSON dans `data/blog/`

4. **Valider le HTML** : Le HtmlSanitizer protège contre les XSS

5. **Optimiser les images** : Avant upload pour des pages rapides
