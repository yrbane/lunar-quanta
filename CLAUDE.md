# Lunar Quanta Development Guidelines

Framework PHP moderne sans dépendances runtime externes.

## Technologies

- **PHP 8.3+** avec `strict_types`
- **Zero Dependencies** (Constitution II)
- Stockage JSON (FileStorage/JsonStorage)
- Templates avec syntaxe personnalisée `[% %]` et `[[ ]]`

## Structure

```
src/
├── Attribute/          # Attributs PHP 8 (#[Route])
├── Command/            # Commandes CLI
├── Controller/         # Contrôleurs (DefaultController, Admin/*)
├── Entity/             # Entités (User, Post, Tag, Image)
├── Exception/          # Exceptions personnalisées
└── Service/
    ├── Api/            # Helpers réponses API
    ├── Blog/           # PostService, TagService, SlugGenerator
    ├── Cache/          # CacheService
    ├── Content/        # MarkdownParser, HtmlSanitizer
    ├── Core/           # Router, Container, Request, Response
    ├── Database/       # QueryBuilder, Migrations
    ├── Event/          # EventDispatcher
    ├── I18n/           # Translator
    ├── Logging/        # Logger PSR-3 inspired
    ├── Media/          # ImageStorageService, AvatarService
    ├── Queue/          # Queue & Worker
    ├── Security/       # Auth, CSRF, OAuth, 2FA
    ├── Session/        # SessionService
    ├── StaticSite/     # StaticGenerator, RssGenerator, SitemapGenerator
    ├── Storage/        # FileStorage, JsonStorage
    └── Validation/     # Validator avec règles fluent

tests/                  # PHPUnit tests (1218 tests)
template/               # Templates HTML
├── admin/              # Interface admin
├── auth/               # Authentification
├── blog/               # Blog statique
└── 2fa/                # Two-factor auth

docs/                   # Documentation
├── blog-system.md
└── admin-interface.md
```

## Commandes

```bash
# Tests
./vendor/bin/phpunit --testdox

# Analyse statique
./vendor/bin/phpstan analyse src

# Serveur dev
php bin/console server:start

# Cache
php bin/console cache:clear
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

$result = $generator->generateAll();
// Génère: posts/*.html, index.html, feed.xml, sitemap.xml
```

### Cycle de vie des articles

```
DRAFT → publish() → PUBLISHED → unpublish() → DRAFT
                  → archive()  → ARCHIVED
```

## Code Style

- **Classes**: PascalCase
- **Méthodes**: camelCase
- **Constantes**: UPPER_SNAKE_CASE
- **PHPDoc** pour les types complexes
- **Attributs PHP 8** pour le routing (#[Route])

## Tests

```bash
# Tous les tests
./vendor/bin/phpunit

# Un fichier
./vendor/bin/phpunit tests/Service/Blog/PostServiceTest.php

# Avec couverture
./vendor/bin/phpunit --coverage-html coverage/
```

## Milestones

- **v1.0**: Framework baseline (Router, Container, Auth)
- **v1.1**: Middleware, Session, CSRF, Authentication
- **v1.2**: Blog System, Admin UI, Infrastructure (Validation, DB, Events, Queue, i18n, Logging)
