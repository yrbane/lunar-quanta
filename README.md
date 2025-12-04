# Lunar Quanta Framework

**Lunar Quanta** est un framework PHP 8.3+ moderne et léger, conçu avec une architecture MVC respectant les standards PSR et les principes SOLID. Il offre un système de routage automatique, un moteur de templates avancé, une console CLI intégrée et une approche pragmatique du développement web.

## Table des matières

- [🚀 Aperçu](#-aperçu)
- [✨ Fonctionnalités principales](#-fonctionnalités-principales)
- [🏗️ Architecture](#️-architecture)
- [📋 Prérequis](#-prérequis)
- [⚡ Installation rapide](#-installation-rapide)
- [🔧 Configuration](#-configuration)
- [🎯 Utilisation](#-utilisation)
  - [Interface Web](#interface-web)
  - [Console CLI](#console-cli)
  - [Développement](#développement)
- [📖 Documentation détaillée](#-documentation-détaillée)
- [🛠️ Outils de développement](#️-outils-de-développement)
- [📝 Conventions de code](#-conventions-de-code)
- [🤝 Contribution](#-contribution)
- [📄 Licence](#-licence)

## 🚀 Aperçu

Lunar Quanta est né du besoin d'avoir un framework PHP moderne, léger et efficace, sans la complexité excessive des solutions existantes. Il privilégie :

- **La simplicité** : API intuitive et courbe d'apprentissage douce
- **Les performances** : Architecture optimisée avec système de cache intelligent
- **La flexibilité** : Composants modulaires et extensibles
- **Les standards** : Respect strict des PSR et bonnes pratiques PHP 8.3+

## ✨ Fonctionnalités principales

### 🌐 Framework Web
- **Routage automatique** par attributs PHP 8 avec cache intelligent
- **Contrôleurs MVC** avec injection de dépendances
- **Moteur de templates avancé** (héritage, blocs, macros)
- **Gestion d'erreurs** centralisée avec pages personnalisées

### 🖥️ Console CLI
- **11 commandes intégrées** pour le développement et la maintenance
- **Générateurs automatiques** de contrôleurs et commandes
- **Serveur de développement** intégré
- **Outils de debugging** (routes, arborescence, etc.)

### 🔒 Sécurité & Authentification
- **Système d'authentification complet** avec sessions sécurisées
- **Protection CSRF** avec tokens automatiques
- **Middleware d'autorisation** basé sur les rôles
- **Chiffrement AES-256** pour les données sensibles
- **Stockage JSON sécurisé** avec chiffrement automatique
- **Configuration d'environnement** avec fichiers `.env`

### 🔄 Middleware
- **Pipeline de middlewares** PSR-15 inspiré
- **Middlewares route-level** via attributs
- **Middlewares intégrés** : Auth, Guest, Role, CSRF, Session

### ⚡ Performances
- **Cache intelligent** des routes et templates
- **Container DI léger** avec résolution récursive
- **Compilation de templates** pour des performances optimales

## 🏗️ Architecture

Le framework suit une architecture MVC moderne avec une séparation claire des responsabilités :

### Structure des répertoires

```
lunar-quanta/
├── 📁 src/                          # Code source principal
│   ├── 📁 Attribute/               # Attributs PHP (Route, Command)
│   ├── 📁 Command/                 # 11 commandes CLI disponibles
│   ├── 📁 Controller/              # Contrôleurs MVC (4 contrôleurs)
│   ├── 📁 Entity/                  # Entités métier (User, etc.)
│   └── 📁 Service/                 # Services organisés par domaine
│       ├── 📁 Cache/               # Gestion du cache
│       ├── 📁 Command/             # Infrastructure CLI  
│       ├── 📁 Core/                # Cœur du framework
│       ├── 📁 Generator/           # Générateurs de code
│       ├── 📁 Router/              # Service de routage
│       ├── 📁 Security/            # Sécurité (chiffrement, auth, CSRF)
│       │   ├── 📁 Auth/            # Authentification & autorisation
│       │   └── 📁 Csrf/            # Protection CSRF
│       ├── 📁 Server/              # Serveur de développement
│       ├── 📁 Session/             # Gestion des sessions
│       └── 📁 Storage/             # Stockage JSON
├── 📁 public/                      # Point d'entrée web + assets
├── 📁 template/                    # Templates avec système d'héritage
├── 📁 config/                      # Configuration JSON
├── 📁 cache/                       # Cache des routes/templates
├── 📁 bin/                         # Point d'entrée CLI
├── 📁 tools/                       # Outils de développement isolés
└── 📁 doc/                         # Documentation technique
```

### Composants principaux

#### 🎯 Core Framework
- **FrontController** (`src/Service/Core/FrontController.php:31`) : Point d'entrée qui orchestre le cycle de vie des requêtes
- **Router** (`src/Service/Core/Router.php:103`) : Routage automatique avec cache et scan des attributs `#[Route]`
- **Container** (`src/Service/Core/Container.php:30`) : Injection de dépendances avec résolution récursive
- **BaseController** : Contrôleur de base avec intégration du moteur de templates

#### 🎨 Moteur de Templates
- **[lunar/template](https://github.com/yrbane/lunar-template)** : Package externe autonome intégré via `LunarTemplateAdapter`
  - Syntaxe intuitive `[[ variable ]]`, `[% if condition %]`, `[% for item in items %]`
  - Héritage de templates avec `[% extends 'parent.tpl' %]`
  - Système de blocs `[% block content %]`
  - Macros réutilisables `##macroName(args)##`
  - Compilation et cache automatiques

#### 🔐 Sécurité & Storage
- **EncryptionService** : Chiffrement AES-256-CBC pour les données sensibles
- **JsonStorage** : Stockage sécurisé avec chiffrement automatique des entités

#### 🖥️ Console CLI
- **Console** (`bin/console`) : Interface en ligne de commande avec 11 commandes
- **CommandFactory** : Factory pattern pour l'instanciation des commandes
- **Générateurs** : Création automatique de contrôleurs et commandes

## 📋 Prérequis

- **PHP 8.3+** avec extensions : `mbstring`, `json`, `openssl`
- **Composer** pour la gestion des dépendances
- **Git** (optionnel, pour le développement)

## ⚡ Installation rapide

### 1. Installation des dépendances

```bash
# Dépendances principales
composer install

# Outils de développement (optionnel)
composer install --working-dir=tools
```

### 2. Configuration initiale

```bash
# Création des répertoires de cache
mkdir -p cache/template public/cache/template

# Permissions (si nécessaire)
chmod 755 cache/ log/ -R
```

### 3. Test de l'installation

```bash
# Serveur de développement
bin/console server:start

# Ou via PHP built-in server
php -S localhost:8000 -t public/
```

Visitez `http://localhost:8000` pour voir la page d'accueil.

## 🔧 Configuration

### Variables d'environnement

Créez un fichier `.env` à la racine du projet :

```env
APP_ENV=dev
APP_DEBUG=true
APP_SECRET=your-secret-key-here
```

### Configuration avancée

Les fichiers de configuration sont dans `config/` :

- `cache.json` : Configuration du cache
- `template.json` : Configuration du moteur de templates

```json
// config/cache.json
{
  "cache": {
    "dir": "cache",
    "enabled": true
  }
}
```

## 🎯 Utilisation

### Interface Web

#### Créer un nouveau contrôleur

```php
<?php
namespace App\Controller;

use App\Attribute\Route;
use App\Service\Core\BaseController;
use App\Service\Core\Http\Request;
use App\Service\Core\Http\Response;

class BlogController extends BaseController
{
    #[Route('/blog', name: 'blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return new Response($this->render('blog/index', [
            'title' => 'Mon Blog',
            'posts' => $this->getPosts()
        ]));
    }

    #[Route('/blog/{id}', name: 'blog_show', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $id = $request->getParameter('id');
        return new Response($this->render('blog/show', [
            'post' => $this->getPost($id)
        ]));
    }

    // Route protégée avec middleware d'authentification
    #[Route('/blog/create', name: 'blog_create', methods: ['GET', 'POST'],
            middlewares: [AuthMiddleware::class])]
    public function create(Request $request): Response
    {
        $user = $request->getAttribute('user'); // Utilisateur authentifié
        return new Response($this->render('blog/create', ['user' => $user]));
    }
}
```

#### Templates avancés

```html
<!-- template/blog/show.html.tpl -->
[% extends 'base.html.tpl' %]

[% block title %][[ post.title ]] - Mon Blog[% endblock %]

[% block content %]
    <article>
        <h1>[[ post.title ]]</h1>
        
        [% if post.published %]
            <time>[[ post.publishedAt ]]</time>
        [% endif %]
        
        <div class="content">
            [[ post.content ]]
        </div>
        
        [% if post.tags %]
            <div class="tags">
                [% for tag in post.tags %]
                    <span class="tag">[[ tag ]]</span>
                [% endfor %]
            </div>
        [% endif %]
        
        <a href="##url('blog_index')##">← Retour au blog</a>
    </article>
[% endblock %]
```

### Console CLI

#### Commandes disponibles

```bash
# Lister toutes les commandes
bin/console

# Démarrer le serveur de développement  
bin/console server:start

# Voir les routes enregistrées
bin/console router:debug

# Vider le cache
bin/console cache:clear

# Générer un nouveau contrôleur
bin/console make:controller BlogController

# Générer une nouvelle commande
bin/console make:command blog:import

# Voir l'arborescence du projet
bin/console filesystem:tree
```

#### Créer une commande personnalisée

```php
<?php
namespace App\Command;

use App\Attribute\Command;
use App\Service\Command\AbstractCommand;
use App\Service\Command\CommandInterface;
use App\Service\Command\ConsoleHelper as C;

#[Command(
    name: "blog:import", 
    description: "Importe des articles de blog"
)]
class BlogImportCommand extends AbstractCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        C::info("🚀 Début de l'import...");
        
        // Votre logique d'import ici
        
        C::success("✅ Import terminé avec succès !");
        return 0;
    }
    
    public function getHelp(): string
    {
        return "Cette commande importe des articles depuis une source externe.";
    }
}
```

### Développement

#### Structure d'un projet type

```
votre-app/
├── src/Controller/
│   ├── HomeController.php      # Page d'accueil
│   ├── BlogController.php      # Section blog
│   └── ApiController.php       # API REST
├── src/Entity/
│   ├── User.php               # Entité utilisateur
│   └── Post.php               # Entité article
├── template/
│   ├── base.html.tpl          # Template de base
│   ├── home.html.tpl          # Page d'accueil
│   └── blog/
│       ├── index.html.tpl     # Liste des articles
│       └── show.html.tpl      # Affichage d'un article
└── public/
    ├── css/style.css          # Styles personnalisés
    └── js/app.js              # JavaScript
```

## 📖 Documentation détaillée

### Guides spécialisés

- **[Commandes CLI](doc/command.md)** : Guide complet pour créer des commandes personnalisées
- **Architecture interne** : Comprendre le fonctionnement du framework
- **API Reference** : Documentation des classes et méthodes principales

### Exemples pratiques

#### API REST
```php
class ApiController extends BaseController
{
    #[Route('/api/users', methods: ['GET'])]
    public function getUsers(Request $request): Response
    {
        $users = $this->userRepository->findAll();
        return new Response(
            json_encode($users), 
            200, 
            ['Content-Type' => 'application/json']
        );
    }
}
```

#### Middlewares personnalisés

```php
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // Avant le contrôleur
        $start = microtime(true);

        $response = $next($request);

        // Après le contrôleur
        $duration = microtime(true) - $start;
        error_log("Request {$request->getUri()} took {$duration}s");

        return $response;
    }
}

// Utilisation sur une route
#[Route('/api/data', middlewares: [LoggingMiddleware::class])]
public function getData(Request $request): Response { /* ... */ }
```

#### Sessions et messages flash

```php
use Lunar\Service\Session\SessionInterface;

class CartController extends BaseController
{
    #[Route('/cart/add', methods: ['POST'])]
    public function addToCart(Request $request): Response
    {
        /** @var SessionInterface $session */
        $session = $request->getAttribute('session');

        // Stocker dans la session
        $cart = $session->get('cart', []);
        $cart[] = $request->getPostParams()['product_id'];
        $session->set('cart', $cart);

        // Message flash (affiché une seule fois)
        $session->flash('success', 'Produit ajouté au panier !');

        return new Response('', 302, ['Location: /cart']);
    }

    #[Route('/cart')]
    public function viewCart(Request $request): Response
    {
        $session = $request->getAttribute('session');

        return new Response($this->render('cart', [
            'items' => $session->get('cart', []),
            'message' => $session->getFlash('success') // Consommé après lecture
        ]));
    }
}
```

#### Protection CSRF

```php
use Lunar\Service\Security\Csrf\CsrfMiddleware;
use Lunar\Service\Security\Csrf\CsrfTokenManagerInterface;

class ContactController extends BaseController
{
    // Le middleware CSRF valide automatiquement les requêtes POST/PUT/DELETE
    #[Route('/contact', methods: ['GET', 'POST'],
            middlewares: [SessionMiddleware::class, CsrfMiddleware::class])]
    public function contact(Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
            // Le token a été validé par le middleware
            $this->sendEmail($request->getPostParams());
            return new Response('Message envoyé !');
        }

        // Générer un token pour le formulaire
        /** @var CsrfTokenManagerInterface $csrf */
        $csrf = $request->getAttribute('csrf');
        $token = $csrf->generate('csrf');

        return new Response($this->render('contact', ['csrf_token' => $token]));
    }
}
```

Template avec token CSRF :
```html
<form method="POST" action="/contact">
    <input type="hidden" name="_csrf_token" value="[[ csrf_token ]]">
    <input type="email" name="email" required>
    <textarea name="message" required></textarea>
    <button type="submit">Envoyer</button>
</form>
```

#### Authentification complète

```php
use Lunar\Service\Security\Auth\Authenticator;
use Lunar\Service\Security\Auth\AuthMiddleware;
use Lunar\Service\Security\Auth\GuestMiddleware;
use Lunar\Service\Security\Auth\RoleMiddleware;

class AuthController extends BaseController
{
    public function __construct(private Authenticator $auth) {}

    // Accessible uniquement aux invités (redirige si connecté)
    #[Route('/login', methods: ['GET', 'POST'],
            middlewares: [GuestMiddleware::class])]
    public function login(Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
            $params = $request->getPostParams();
            $user = $this->auth->attempt($params['email'], $params['password']);

            if ($user) {
                $request->getAttribute('session')->flash('success', 'Bienvenue !');
                return new Response('', 302, ['Location: /dashboard']);
            }

            return new Response($this->render('login', [
                'error' => 'Identifiants invalides'
            ]));
        }

        return new Response($this->render('login'));
    }

    // Accessible uniquement aux utilisateurs connectés
    #[Route('/dashboard', middlewares: [AuthMiddleware::class])]
    public function dashboard(Request $request): Response
    {
        $user = $request->getAttribute('user');
        return new Response($this->render('dashboard', ['user' => $user]));
    }

    // Accessible uniquement aux administrateurs
    #[Route('/admin', middlewares: [RoleMiddleware::class])]
    public function admin(Request $request): Response
    {
        // RoleMiddleware configuré avec ['ROLE_ADMIN']
        return new Response($this->render('admin'));
    }

    #[Route('/logout', methods: ['POST'])]
    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return new Response('', 302, ['Location: /']);
    }
}
```

#### Configuration de l'authentification

```php
// Dans votre bootstrap ou container
use Lunar\Service\Security\Auth\Authenticator;
use Lunar\Service\Security\Auth\PasswordHasher;
use Lunar\Service\Security\Auth\InMemoryUserProvider;
use Lunar\Service\Session\SessionService;

// Provider simple en mémoire (pour tests/prototypage)
$hasher = PasswordHasher::bcrypt();
$userProvider = new InMemoryUserProvider();
$userProvider->createUser(1, 'admin@example.com', 'secret123', $hasher, ['ROLE_ADMIN']);
$userProvider->createUser(2, 'user@example.com', 'password', $hasher, ['ROLE_USER']);

// Ou implémentez UserProviderInterface pour charger depuis une base de données
$session = new SessionService();
$auth = new Authenticator($userProvider, $hasher, $session);

// Vérifier si connecté
if ($auth->check()) {
    $user = $auth->user();
    echo "Connecté en tant que " . $user->getIdentifier();
}
```

#### Formulaires et validation
```php
#[Route('/contact', methods: ['POST'])]
public function submitContact(Request $request): Response
{
    $data = $request->getPostParams();

    if (empty($data['email']) || empty($data['message'])) {
        return new Response($this->render('contact', [
            'error' => 'Tous les champs sont requis'
        ]), 400);
    }

    // Traitement du formulaire...

    return new Response($this->render('contact', [
        'success' => 'Message envoyé avec succès !'
    ]));
}
```

## 🛠️ Outils de développement

Le framework sépare les outils de développement des dépendances de production via le dossier `tools/`.

### Installation des outils

```bash
composer install --working-dir=tools
```

### Outils disponibles

- **PHPStan** : Analyse statique (niveau 7)
- **PHP CS Fixer** : Formatage automatique du code
- **PHPUnit** : Tests unitaires

### Utilisation

```bash
# Analyse statique
./tools/vendor/bin/phpstan analyse src/

# Correction du style de code
./tools/vendor/bin/php-cs-fixer fix --config=./.php-cs-fixer.dist.php

# Tests (si configurés)
./tools/vendor/bin/phpunit
```

### Liens symboliques (optionnel)

```bash
# Créer des raccourcis dans bin/
ln -s ../tools/vendor/bin/php-cs-fixer bin/php-cs-fixer
ln -s ../tools/vendor/bin/phpstan bin/phpstan
```

## 📝 Conventions de code

### Standards appliqués
- **PSR-1 & PSR-12** : Standards de codage PHP
- **PSR-4** : Autoloading avec namespace `App\`
- **Typage strict** : `declare(strict_types=1)` obligatoire
- **PHP 8.3+** : Utilisation des dernières fonctionnalités

### Règles du projet
- **Code en anglais** : Variables, méthodes, classes
- **Documentation en français** : Commentaires phpDoc
- **Principes SOLID** : Architecture orientée objet
- **Tests** : Couverture recommandée pour les nouvelles fonctionnalités

### Configuration Git

```gitignore
# Dépendances
/vendor/
/tools/vendor/

# Cache et logs
/cache/
/log/
*.log

# Configuration locale
.env
.env.local
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Voici comment participer au développement :

### Workflow de contribution

1. **Fork** le projet sur GitHub
2. **Créez** une branche pour votre fonctionnalité (`git checkout -b feature/amazing-feature`)
3. **Commitez** vos modifications (`git commit -m 'feat: add amazing feature'`)
4. **Pushez** vers la branche (`git push origin feature/amazing-feature`)
5. **Ouvrez** une Pull Request

### Standards de contribution

- **Tests** : Ajoutez des tests pour les nouvelles fonctionnalités
- **Documentation** : Mettez à jour la documentation si nécessaire
- **Style** : Respectez les conventions de code (PSR-12, PHPStan niveau 7)
- **Commits** : Utilisez des messages clairs et descriptifs

### Types de contributions recherchées

- 🐛 **Corrections de bugs**
- ✨ **Nouvelles fonctionnalités**
- 📚 **Amélioration de la documentation**
- 🔧 **Optimisations de performance**
- 🧪 **Tests et qualité**

## 📄 Licence

Ce projet est distribué sous la [licence MIT](LICENSE).

```
MIT License

Copyright (c) 2024 Lunar Quanta Framework

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

## 🚀 Prêt à commencer ?

```bash
git clone https://github.com/your-username/lunar-quanta
cd lunar-quanta
composer install
bin/console server:start
```

**Visitez `http://localhost:8000` et commencez à développer !**

---

### 💫 Philosophie

*Lunar Quanta croit en la simplicité élégante, la performance sans compromis et la joie de développer. Chaque ligne de code est pensée pour vous faire gagner du temps tout en maintenant la qualité et la maintenabilité.*

**Happy coding! 🎉**



