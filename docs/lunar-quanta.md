# Lunar Quanta - PHP Framework

## Vue d'ensemble

Lunar Quanta est un framework PHP 8.3+ moderne, conçu avec une philosophie **zero-dependency** pour le runtime. Il offre une architecture complète pour créer des applications web performantes.

## Philosophie

- **Zero Dependencies** : Aucune dépendance externe en production
- **PHP 8.3+** : Utilisation des fonctionnalités modernes (attributs, enums, types stricts)
- **Stockage JSON** : Pas de base de données requise par défaut
- **Génération statique** : Blog et contenu générés en HTML statique

## Installation

```bash
git clone https://github.com/yrbane/lunar-quanta.git
cd lunar-quanta
composer install
php bin/console server:start
```

## Architecture

```
lunar-quanta/
├── bin/
│   └── console              # CLI principal
├── src/
│   ├── Attribute/           # Attributs PHP 8 (#[Route])
│   ├── Command/             # Commandes CLI
│   ├── Controller/          # Contrôleurs
│   ├── Entity/              # Entités (User, Post, Tag, Image)
│   ├── Exception/           # Exceptions personnalisées
│   └── Service/             # Services métier
├── template/                # Templates HTML (.html.tpl)
├── assets/                  # CSS, JS, images
│   └── css/lunar-aurora/    # Framework CSS
├── public/                  # Point d'entrée web
├── data/                    # Stockage JSON
├── tests/                   # Tests PHPUnit
└── docs/                    # Documentation
```

## Services

### Core

| Service | Description |
|---------|-------------|
| `Router` | Routing basé sur attributs PHP 8 |
| `Container` | Injection de dépendances |
| `Request` | Abstraction requête HTTP |
| `Response` | Abstraction réponse HTTP |

### Storage

```php
use Lunar\Service\Storage\FileStorage;
use Lunar\Service\Storage\JsonStorage;

// Stockage fichiers
$storage = new FileStorage('data/posts');
$storage->save('post-1.json', $data);
$content = $storage->get('post-1.json');

// Stockage JSON typé
$jsonStorage = new JsonStorage('data/users');
$jsonStorage->put('user-1', ['name' => 'John']);
$user = $jsonStorage->get('user-1');
```

### Security

```php
use Lunar\Service\Security\AuthService;
use Lunar\Service\Security\CsrfService;

// Authentification
$auth = new AuthService($userService, $sessionService);
$auth->attempt($email, $password);
$user = $auth->user();

// Protection CSRF
$csrf = new CsrfService($sessionService);
$token = $csrf->generateToken();
$csrf->validateToken($token); // throws on invalid
```

### Blog

```php
use Lunar\Service\Blog\PostService;
use Lunar\Service\StaticSite\StaticGenerator;

// Gestion articles
$postService = new PostService(new FileStorage('data/blog/posts'));
$post = $postService->create('Titre', '# Contenu Markdown');
$postService->publish($post->getId());

// Génération statique
$generator = new StaticGenerator($postService, new MarkdownParser(), 'public/blog', 'template/blog');
$generator->generateAll();
```

### Content

```php
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\Content\HtmlSanitizer;

// Markdown → HTML
$parser = new MarkdownParser();
$html = $parser->parse('# Titre\n\n**Gras** et *italique*');

// Sanitization XSS
$sanitizer = new HtmlSanitizer();
$safeHtml = $sanitizer->sanitize($untrustedInput);
```

### Autres services

| Service | Description |
|---------|-------------|
| `CacheService` | Cache fichier avec TTL |
| `SessionService` | Gestion sessions PHP |
| `Validator` | Validation fluent |
| `EventDispatcher` | Événements et listeners |
| `Queue` / `Worker` | File d'attente asynchrone |
| `Translator` | Internationalisation |
| `Logger` | Logging PSR-3 inspired |

## Routing

### Attributs PHP 8

```php
use Lunar\Attribute\Route;

#[Route('/blog')]
class BlogController
{
    #[Route('', methods: ['GET'], name: 'blog.index')]
    public function index(): Response
    {
        return $this->render('blog/index');
    }

    #[Route('/{slug}', methods: ['GET'], name: 'blog.show')]
    public function show(string $slug): Response
    {
        $post = $this->postService->findBySlug($slug);
        return $this->render('blog/post', ['post' => $post]);
    }

    #[Route('/create', methods: ['POST'], name: 'blog.create')]
    public function create(Request $request): Response
    {
        // ...
    }
}
```

### Paramètres de route

```php
#[Route('/user/{id}')]           // Paramètre simple
#[Route('/post/{id:\d+}')]       // Avec regex
#[Route('/file/{path:.*}')]      // Capture tout
```

## Templates

### Syntaxe Lunar Template

```html
<!-- Variables -->
[[ title ]]
[[ post.author.name ]]

<!-- Conditions -->
[% if user %]
    <p>Bonjour [[ user.name ]]</p>
[% else %]
    <p>Bonjour visiteur</p>
[% endif %]

<!-- Boucles -->
[% for post in posts %]
    <article>
        <h2>[[ post.title ]]</h2>
        [% if post.excerpt %]
            <p>[[ post.excerpt ]]</p>
        [% endif %]
    </article>
[% endfor %]

<!-- Héritage -->
[% extends 'base.html.tpl' %]

[% block content %]
    <main>Contenu...</main>
[% endblock %]
```

### Fichiers templates

Extension `.html.tpl` pour tous les templates :

```
template/
├── base.html.tpl
├── blog/
│   ├── index.html.tpl
│   ├── post.html.tpl
│   └── category.html.tpl
└── admin/
    ├── base.html.tpl
    └── blog/
        ├── index.html.tpl
        └── form.html.tpl
```

## Entités

### User

```php
use Lunar\Entity\User;

$user = new User();
$user->setEmail('john@example.com');
$user->setPassword($hashedPassword);
$user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
$user->enable2FA($secret);
```

### Post

```php
use Lunar\Entity\Post;
use Lunar\Entity\PostStatus;

$post = new Post('Mon Article', '# Contenu');
$post->setExcerpt('Description courte');
$post->setAuthor('John Doe');
$post->addTag('php');
$post->setCategoryId('tutorials');

// Cycle de vie
$post->publish();   // DRAFT → PUBLISHED
$post->unpublish(); // PUBLISHED → DRAFT
$post->archive();   // → ARCHIVED
```

### Tag & Image

```php
use Lunar\Entity\Tag;
use Lunar\Entity\Image;
use Lunar\Entity\ImageSource;

$tag = new Tag('php', 'PHP');
$tag->setColor('#8892BF');

$image = new Image('photo.jpg', ImageSource::UPLOAD);
$image->setDimensions(1920, 1080);
```

## CLI

### Commandes disponibles

```bash
# Serveur de développement
php bin/console server:start [--port=8000]

# Cache
php bin/console cache:clear

# Blog
php bin/console blog:generate      # Génère le site statique
php bin/console blog:list          # Liste les articles

# Utilisateurs
php bin/console user:create        # Créer un utilisateur
php bin/console user:list          # Lister les utilisateurs
```

### Créer une commande

```php
use Lunar\Command\Command;

class MyCommand extends Command
{
    protected string $name = 'my:command';
    protected string $description = 'Ma commande personnalisée';

    public function execute(array $args): int
    {
        $this->info('Exécution...');
        // ...
        $this->success('Terminé !');
        return 0;
    }
}
```

## Validation

```php
use Lunar\Service\Validation\Validator;

$validator = new Validator();
$errors = $validator->validate($data, [
    'email' => 'required|email',
    'password' => 'required|min:8',
    'age' => 'integer|min:18|max:120',
    'website' => 'url',
]);

if ($errors) {
    // Gérer les erreurs
}
```

### Règles disponibles

| Règle | Description |
|-------|-------------|
| `required` | Champ obligatoire |
| `email` | Email valide |
| `url` | URL valide |
| `min:n` | Longueur/valeur minimum |
| `max:n` | Longueur/valeur maximum |
| `integer` | Nombre entier |
| `numeric` | Nombre |
| `alpha` | Lettres uniquement |
| `alphanumeric` | Lettres et chiffres |
| `regex:pattern` | Expression régulière |

## Tests

```bash
# Tous les tests
./vendor/bin/phpunit

# Un fichier spécifique
./vendor/bin/phpunit tests/Service/Blog/PostServiceTest.php

# Avec couverture
./vendor/bin/phpunit --coverage-html coverage/

# Mode verbeux
./vendor/bin/phpunit --testdox
```

Structure des tests :

```
tests/
├── Controller/
├── Entity/
└── Service/
    ├── Blog/
    ├── Content/
    ├── Security/
    └── Storage/
```

## Analyse statique

```bash
# PHPStan niveau max
./vendor/bin/phpstan analyse src

# Avec baseline
./vendor/bin/phpstan analyse src --generate-baseline
```

## Configuration

### Environnement

Variables d'environnement dans `.env` :

```env
APP_ENV=dev
APP_DEBUG=true
APP_SECRET=your-secret-key

# OAuth (optionnel)
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GITHUB_CLIENT_ID=...
GITHUB_CLIENT_SECRET=...
```

### Structure des données

```
data/
├── blog/
│   ├── posts/        # Articles JSON
│   ├── categories/   # Catégories
│   ├── tags/         # Tags
│   └── images/       # Métadonnées images
├── user/             # Utilisateurs
├── session/          # Sessions
└── cache/            # Cache applicatif
```

## Sécurité

### Fonctionnalités

- **Authentification** : Login/logout avec sessions
- **2FA** : Authentification à deux facteurs (TOTP)
- **OAuth** : Google, GitHub
- **CSRF** : Protection des formulaires
- **XSS** : Sanitization HTML automatique
- **Password** : Hachage bcrypt/argon2

### Bonnes pratiques

```php
// Toujours valider les entrées
$validator->validate($input, [...]);

// Toujours échapper les sorties
[[ variable ]] // Auto-escaped dans les templates

// Utiliser le CSRF dans les formulaires
<input type="hidden" name="_csrf" value="[[ csrf_token ]]">

// Vérifier les permissions
if (!$auth->hasRole('ROLE_ADMIN')) {
    throw new AccessDeniedException();
}
```

## Lunar Aurora CSS

Le framework inclut **Lunar Aurora**, un système CSS moderne avec :

- 30+ thèmes pré-configurés
- Design tokens OKLCH
- Composants UI réutilisables
- Support dark mode automatique
- Accessibilité intégrée

Voir [docs/lunar-aurora.md](./lunar-aurora.md) pour la documentation complète.

## Ressources

- [Documentation Blog](./blog-system.md)
- [Documentation Admin](./admin-interface.md)
- [Documentation CSS](./lunar-aurora.md)
- [GitHub](https://github.com/yrbane/lunar-quanta)
