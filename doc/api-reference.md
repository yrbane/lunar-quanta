# API Reference - Lunar Quanta Framework

Cette référence documente les principales classes et méthodes du framework Lunar Quanta.

## Core Framework

### FrontController

**Namespace :** `App\Service\Core\FrontController`  
**Fichier :** `src/Service/Core/FrontController.php`

Point d'entrée principal de l'application web.

#### Méthodes publiques

##### `run(): void`
Exécute le cycle complet de la requête HTTP.

**Responsabilités :**
- Chargement des variables d'environnement
- Configuration du reporting d'erreurs
- Chargement de la configuration
- Dispatch de la requête vers le routeur
- Gestion des exceptions globales

**Exemple :**
```php
$frontController = new FrontController();
$frontController->run();
```

---

### Router

**Namespace :** `App\Service\Core\Router`  
**Fichier :** `src/Service/Core/Router.php`

Gère le routage automatique avec scan des attributs et mise en cache.

#### Méthodes publiques

##### `__construct()`
Initialise le routeur et charge les routes depuis le cache ou scanne les contrôleurs.

##### `dispatch(Request $request): Response`
Dispatch une requête vers la route correspondante.

**Paramètres :**
- `$request` : Objet Request contenant les données de la requête

**Retour :** Objet Response avec le résultat du traitement

**Exemple :**
```php
$router = new Router();
$response = $router->dispatch($request);
```

##### `getRegisteredRoutes(): array`
Retourne la liste des routes enregistrées.

**Retour :** Tableau associatif des routes avec leurs métadonnées

##### `static getRouteByName(string $name): ?array`
Récupère une route par son nom.

**Paramètres :**
- `$name` : Nom de la route

**Retour :** Tableau de la route ou `null` si non trouvée

---

### Container

**Namespace :** `App\Service\Core\Container`  
**Fichier :** `src/Service/Core/Container.php`

Container d'injection de dépendances ultra-léger.

#### Méthodes publiques

##### `get(string $className): object`
Instancie une classe en résolvant récursivement ses dépendances.

**Paramètres :**
- `$className` : Nom complet de la classe à instancier

**Retour :** Instance de la classe avec dépendances injectées

**Exceptions :**
- `RuntimeException` : Si la classe n'est pas instanciable ou si les dépendances ne peuvent être résolues

**Exemple :**
```php
$container = new Container();
$blogService = $container->get(BlogService::class);
```

---

## HTTP

### Request

**Namespace :** `App\Service\Core\Http\Request`  
**Fichier :** `src/Service/Core/Http/Request.php`

Représente une requête HTTP entrante.

#### Méthodes publiques

##### `getMethod(): string`
Retourne la méthode HTTP de la requête.

##### `getUri(): string`
Retourne l'URI de la requête.

##### `getParameter(string $key): mixed`
Récupère un paramètre de la requête.

##### `getParsedBody(): array`
Retourne les données POST parsées.

##### `getHeaders(): array`
Retourne tous les en-têtes de la requête.

##### `setAttribute(string $name, mixed $value): void`
Définit un attribut de requête (utilisé par les middlewares).

##### `getAttribute(string $name, mixed $default = null): mixed`
Récupère un attribut de requête.

##### `getAttributes(): array`
Retourne tous les attributs de la requête.

**Exemple :**
```php
$request = new Request();
$method = $request->getMethod(); // 'GET', 'POST', etc.
$uri = $request->getUri();       // '/blog/123'

// Attributs (définis par les middlewares)
$session = $request->getAttribute('session'); // SessionInterface
$user = $request->getAttribute('user');       // UserInterface|null
$csrf = $request->getAttribute('csrf');       // CsrfTokenManagerInterface
```

---

### Response

**Namespace :** `App\Service\Core\Http\Response`  
**Fichier :** `src/Service/Core/Http/Response.php`

Représente une réponse HTTP sortante.

#### Constructeur

##### `__construct(string $content = '', int $statusCode = 200, array $headers = [])`

**Paramètres :**
- `$content` : Contenu de la réponse
- `$statusCode` : Code de statut HTTP (par défaut 200)
- `$headers` : En-têtes HTTP additionnels

#### Méthodes publiques

##### `send(): void`
Envoie la réponse au client.

##### `setHeader(string $name, string $value): self`
Définit un en-tête HTTP.

##### `setStatusCode(int $code): self`
Définit le code de statut.

**Exemple :**
```php
$response = new Response('Hello World', 200, [
    'Content-Type' => 'text/plain'
]);
$response->send();
```

---

## Templates

Le système de templates utilise le package externe **[lunar/template](https://github.com/yrbane/lunar-template)** via un adaptateur.

### LunarTemplateAdapter

**Namespace :** `App\Service\Core\Template\LunarTemplateAdapter`
**Fichier :** `src/Service/Core/Template/LunarTemplateAdapter.php`

Adaptateur pour intégrer le moteur de templates Lunar dans le framework.

#### Constructeur

##### `__construct(string $templatePath)`

**Paramètres :**
- `$templatePath` : Chemin vers le répertoire des templates

#### Méthodes publiques

##### `render(string $template, array $variables = []): string`
Rend un template avec les variables fournies.

**Paramètres :**
- `$template` : Nom du template (sans extension .tpl)
- `$variables` : Variables à injecter dans le template

**Retour :** HTML généré

**Exceptions :**
- `Exception` : Si le template n'existe pas

**Exemple :**
```php
$adapter = new LunarTemplateAdapter('template');
$html = $adapter->render('blog/show', [
    'post' => $post,
    'title' => 'Mon Article'
]);
```

##### `registerMacro(string $name, callable $callback): void`
Enregistre une macro réutilisable.

**Paramètres :**
- `$name` : Nom de la macro
- `$callback` : Fonction à exécuter pour la macro

**Exemple :**
```php
$adapter->registerMacro('url', function($routeName) {
    return Router::generateUrl($routeName);
});
```

##### `callMacro(string $name, array $args): mixed`
Appelle une macro enregistrée.

**Paramètres :**
- `$name` : Nom de la macro
- `$args` : Arguments à passer à la macro

##### `getEngine(): Lunar\Template\AdvancedTemplateEngine`
Retourne l'instance du moteur Lunar pour les utilisations avancées.

**Retour :** Instance du moteur de templates sous-jacent

---

## Controllers

### BaseController

**Namespace :** `App\Service\Core\BaseController`  
**Fichier :** `src/Service/Core/BaseController.php`

Contrôleur de base avec intégration du moteur de templates.

#### Méthodes protégées

##### `render(string $template, array $variables = []): string`
Rend un template via le moteur intégré.

**Paramètres :**
- `$template` : Nom du template
- `$variables` : Variables du template

**Retour :** HTML généré

**Exemple :**
```php
class BlogController extends BaseController
{
    public function show(Request $request): Response
    {
        $html = $this->render('blog/show', [
            'post' => $this->getPost($request->getParameter('id'))
        ]);
        
        return new Response($html);
    }
}
```

---

## Console CLI

### CommandInterface

**Namespace :** `App\Service\Command\CommandInterface`  
**Fichier :** `src/Service/Command/CommandInterface.php`

Interface que doivent implémenter toutes les commandes.

#### Méthodes requises

##### `execute(array $args): int`
Exécute la commande.

**Paramètres :**
- `$args` : Arguments de la ligne de commande

**Retour :** Code de sortie (0 = succès, >0 = erreur)

##### `getHelp(): string`
Retourne l'aide de la commande.

**Retour :** Texte d'aide formaté

---

### AbstractCommand

**Namespace :** `App\Service\Command\AbstractCommand`  
**Fichier :** `src/Service/Command/AbstractCommand.php`

Classe de base pour les commandes avec utilitaires.

#### Méthodes protégées utiles

##### `wantsHelp(array $args): bool`
Vérifie si l'utilisateur demande l'aide.

##### `parseNamedArgs(array $args): array`
Parse les arguments nommés (--key=value).

**Exemple :**
```php
class MyCommand extends AbstractCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        if ($this->wantsHelp($args)) {
            ConsoleHelper::info($this->getHelp());
            return 0;
        }
        
        $namedArgs = $this->parseNamedArgs($args);
        $verbose = $namedArgs['verbose'] ?? false;
        
        // Logique de la commande...
        
        return 0;
    }
}
```

---

### ConsoleHelper

**Namespace :** `App\Service\Command\ConsoleHelper`  
**Fichier :** `src/Service/Command/ConsoleHelper.php`

Utilitaires pour l'affichage coloré en console.

#### Méthodes statiques

##### `info(string $message): void`
Affiche un message d'information en bleu.

##### `success(string $message): void`
Affiche un message de succès en vert.

##### `error(string $message): void`
Affiche un message d'erreur en rouge.

##### `warning(string $message): void`
Affiche un message d'avertissement en jaune.

**Exemple :**
```php
use App\Service\Command\ConsoleHelper as C;

C::info("ℹ️ Début du traitement...");
C::success("✅ Traitement terminé avec succès !");
C::warning("⚠️ Attention : fichier non trouvé");
C::error("❌ Erreur critique détectée");
```

---

## Configuration

### Config

**Namespace :** `App\Service\Core\Config\Config`  
**Fichier :** `src/Service/Core/Config/Config.php`

Gestionnaire de configuration avec cache.

#### Méthodes statiques

##### `load(string $configDir, string $cacheFile = null): void`
Charge la configuration depuis un répertoire.

**Paramètres :**
- `$configDir` : Répertoire contenant les fichiers JSON
- `$cacheFile` : Fichier de cache (optionnel)

##### `get(string $key, mixed $default = null): mixed`
Récupère une valeur de configuration.

**Paramètres :**
- `$key` : Clé en notation pointée (ex: 'cache.dir')
- `$default` : Valeur par défaut si la clé n'existe pas

**Exemple :**
```php
Config::load('/path/to/config', '/path/to/cache/config.php');
$cacheDir = Config::get('cache.dir', 'cache');
$dbHost = Config::get('database.host', 'localhost');
```

##### `getProjectRoot(): string`
Retourne le chemin racine du projet.

---

## Sécurité

### EncryptionService

**Namespace :** `App\Service\Security\EncryptionService`  
**Fichier :** `src/Service/Security/EncryptionService.php`

Service de chiffrement AES-256 avec authentification.

#### Méthodes publiques

##### `encrypt(string $data): string`
Chiffre des données.

**Paramètres :**
- `$data` : Données à chiffrer

**Retour :** Données chiffrées encodées en base64

##### `decrypt(string $encryptedData): string`
Déchiffre des données.

**Paramètres :**
- `$encryptedData` : Données chiffrées en base64

**Retour :** Données déchiffrées

**Exceptions :**
- `Exception` : Si le déchiffrement échoue

**Exemple :**
```php
$encryption = new EncryptionService();
$encrypted = $encryption->encrypt('données sensibles');
$decrypted = $encryption->decrypt($encrypted);
```

---

## Stockage

### JsonStorage

**Namespace :** `App\Service\Storage\JsonStorage`  
**Fichier :** `src/Service/Storage/JsonStorage.php`

Stockage JSON avec chiffrement optionnel.

#### Méthodes publiques

##### `save(string $key, mixed $data): void`
Sauvegarde des données.

**Paramètres :**
- `$key` : Clé de stockage
- `$data` : Données à sauvegarder

##### `load(string $key): mixed`
Charge des données.

**Paramètres :**
- `$key` : Clé de stockage

**Retour :** Données chargées ou `null` si inexistantes

**Exemple :**
```php
$storage = new JsonStorage();
$storage->save('users', $userData);
$users = $storage->load('users');
```

---

## Attributs

### Route

**Namespace :** `App\Attribute\Route`  
**Fichier :** `src/Attribute/Route.php`

Attribut pour définir une route sur une méthode de contrôleur.

#### Constructeur

##### `__construct(string $path, array $methods = ['GET'], ?string $name = null, array $middlewares = [])`

**Paramètres :**
- `$path` : Chemin de la route (peut contenir des paramètres {id})
- `$methods` : Méthodes HTTP acceptées (par défaut `['GET']`)
- `$name` : Nom de la route (optionnel)
- `$middlewares` : Classes de middlewares à exécuter (optionnel)

**Exemples :**
```php
// Route simple
#[Route('/blog/{id}', name: 'blog_show', methods: ['GET', 'POST'])]
public function show(Request $request): Response
{
    // Logique du contrôleur
}

// Route avec middlewares
use Lunar\Service\Security\Auth\AuthMiddleware;
use Lunar\Service\Security\Csrf\CsrfMiddleware;

#[Route('/blog/create', methods: ['GET', 'POST'],
        middlewares: [AuthMiddleware::class, CsrfMiddleware::class])]
public function create(Request $request): Response
{
    $user = $request->getAttribute('user'); // Injecté par AuthMiddleware
    // ...
}
```

### Command

**Namespace :** `App\Attribute\Command`  
**Fichier :** `src/Attribute/Command.php`

Attribut pour définir une commande CLI.

#### Constructeur

##### `__construct(string $name, string $description)`

**Paramètres :**
- `$name` : Nom de la commande
- `$description` : Description de la commande

**Exemple :**
```php
#[Command(name: "cache:clear", description: "Vide tous les caches")]
class CacheClearCommand extends AbstractCommand implements CommandInterface
{
    // Implémentation de la commande
}
```

---

## Middleware

### MiddlewareInterface

**Namespace :** `Lunar\Service\Core\Middleware\MiddlewareInterface`
**Fichier :** `src/Service/Core/Middleware/MiddlewareInterface.php`

Interface pour tous les middlewares du framework.

#### Méthodes requises

##### `process(Request $request, callable $next): Response`
Traite la requête et retourne une réponse.

**Paramètres :**
- `$request` : Requête HTTP entrante
- `$next` : Prochain handler dans la chaîne

**Retour :** Réponse HTTP

**Exemple :**
```php
class TimingMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $start;

        return new Response(
            $response->getBody(),
            $response->getStatusCode(),
            array_merge($response->getHeaders(), ["X-Duration: {$duration}s"])
        );
    }
}
```

---

### MiddlewareStack

**Namespace :** `Lunar\Service\Core\Middleware\MiddlewareStack`
**Fichier :** `src/Service/Core/Middleware/MiddlewareStack.php`

Gère une pile de middlewares et leur exécution en chaîne.

#### Méthodes publiques

##### `add(MiddlewareInterface $middleware): self`
Ajoute un middleware à la pile.

##### `handle(Request $request, callable $finalHandler): Response`
Exécute la pile de middlewares puis le handler final.

**Exemple :**
```php
$stack = new MiddlewareStack();
$stack->add(new SessionMiddleware())
      ->add(new CsrfMiddleware($tokenManager));

$response = $stack->handle($request, fn($req) => $controller->action($req));
```

---

## Session

### SessionInterface

**Namespace :** `Lunar\Service\Session\SessionInterface`
**Fichier :** `src/Service/Session/SessionInterface.php`

Interface de gestion des sessions.

#### Méthodes requises

| Méthode | Description |
|---------|-------------|
| `start(): void` | Démarre la session |
| `get(string $key, mixed $default = null): mixed` | Récupère une valeur |
| `set(string $key, mixed $value): void` | Définit une valeur |
| `has(string $key): bool` | Vérifie si une clé existe |
| `remove(string $key): void` | Supprime une valeur |
| `flash(string $key, mixed $value): void` | Définit un message flash |
| `getFlash(string $key, mixed $default = null): mixed` | Récupère et supprime un flash |
| `regenerate(): void` | Régénère l'ID de session |
| `destroy(): void` | Détruit la session |
| `all(): array` | Retourne toutes les données |

---

### SessionService

**Namespace :** `Lunar\Service\Session\SessionService`
**Fichier :** `src/Service/Session/SessionService.php`

Implémentation complète de `SessionInterface`.

#### Constructeur

##### `__construct(bool $testMode = false)`

**Paramètres :**
- `$testMode` : Si `true`, utilise un stockage en mémoire (pour tests PHPUnit)

**Exemple :**
```php
// Production
$session = new SessionService();
$session->start();

// Tests PHPUnit
$session = new SessionService(testMode: true);
$session->set('user', 'test');
$this->assertSame('test', $session->get('user'));
```

---

### SessionMiddleware

**Namespace :** `Lunar\Service\Session\SessionMiddleware`
**Fichier :** `src/Service/Session/SessionMiddleware.php`

Middleware qui démarre la session et l'attache à la requête.

#### Constructeur

##### `__construct(?SessionInterface $session = null)`

**Exemple :**
```php
// Avec session par défaut
$middleware = new SessionMiddleware();

// Avec session personnalisée
$middleware = new SessionMiddleware(new SessionService());

// Dans le contrôleur
$session = $request->getAttribute('session');
$session->set('visited', true);
```

---

## CSRF Protection

### CsrfTokenManagerInterface

**Namespace :** `Lunar\Service\Security\Csrf\CsrfTokenManagerInterface`
**Fichier :** `src/Service/Security/Csrf/CsrfTokenManagerInterface.php`

Interface de gestion des tokens CSRF.

#### Méthodes requises

| Méthode | Description |
|---------|-------------|
| `generate(string $tokenId): string` | Génère un token |
| `isValid(string $tokenId, string $token): bool` | Valide un token |
| `remove(string $tokenId): void` | Supprime un token |

---

### CsrfTokenManager

**Namespace :** `Lunar\Service\Security\Csrf\CsrfTokenManager`
**Fichier :** `src/Service/Security/Csrf/CsrfTokenManager.php`

Gestionnaire de tokens CSRF avec stockage en session.

#### Constructeur

##### `__construct(SessionInterface $session)`

**Exemple :**
```php
$csrf = new CsrfTokenManager($session);

// Générer un token pour un formulaire
$token = $csrf->generate('contact_form');

// Valider le token soumis (timing-safe)
if ($csrf->isValid('contact_form', $_POST['_csrf_token'])) {
    // Token valide, traiter le formulaire
}
```

---

### CsrfMiddleware

**Namespace :** `Lunar\Service\Security\Csrf\CsrfMiddleware`
**Fichier :** `src/Service/Security/Csrf/CsrfMiddleware.php`

Middleware de validation CSRF automatique.

#### Constructeur

##### `__construct(?CsrfTokenManagerInterface $tokenManager = null)`

#### Factory

##### `static withSession(SessionInterface $session): self`
Crée un middleware avec un token manager basé sur la session.

#### Constantes

| Constante | Valeur | Description |
|-----------|--------|-------------|
| `TOKEN_FIELD` | `_csrf_token` | Nom du champ POST |
| `TOKEN_HEADER` | `X-CSRF-Token` | Nom du header HTTP |
| `TOKEN_ID` | `csrf` | ID du token par défaut |

**Exemple :**
```php
// Via factory
$middleware = CsrfMiddleware::withSession($session);

// Le token manager est accessible dans le contrôleur
$csrf = $request->getAttribute('csrf');
$token = $csrf->generate(CsrfMiddleware::TOKEN_ID);
```

---

## Authentication

### UserInterface

**Namespace :** `Lunar\Service\Security\Auth\UserInterface`
**Fichier :** `src/Service/Security/Auth/UserInterface.php`

Interface pour les entités utilisateur.

#### Méthodes requises

| Méthode | Description |
|---------|-------------|
| `getId(): string\|int` | Retourne l'ID unique |
| `getIdentifier(): string` | Retourne l'identifiant (email, username) |
| `getPassword(): string` | Retourne le mot de passe hashé |
| `getRoles(): array` | Retourne les rôles (`['ROLE_USER']`) |

**Exemple d'implémentation :**
```php
class User implements UserInterface
{
    public function __construct(
        private int $id,
        private string $email,
        private string $passwordHash,
        private array $roles = ['ROLE_USER']
    ) {}

    public function getId(): int { return $this->id; }
    public function getIdentifier(): string { return $this->email; }
    public function getPassword(): string { return $this->passwordHash; }
    public function getRoles(): array { return $this->roles; }
}
```

---

### UserProviderInterface

**Namespace :** `Lunar\Service\Security\Auth\UserProviderInterface`
**Fichier :** `src/Service/Security/Auth/UserProviderInterface.php`

Interface pour les fournisseurs d'utilisateurs.

#### Méthodes requises

##### `loadByIdentifier(string $identifier): ?UserInterface`
Charge un utilisateur par son identifiant.

##### `loadById(string|int $id): ?UserInterface`
Charge un utilisateur par son ID.

---

### InMemoryUserProvider

**Namespace :** `Lunar\Service\Security\Auth\InMemoryUserProvider`
**Fichier :** `src/Service/Security/Auth/InMemoryUserProvider.php`

Fournisseur d'utilisateurs en mémoire (tests et prototypage).

#### Méthodes publiques

##### `addUser(InMemoryUser $user): self`
Ajoute un utilisateur.

##### `createUser(string|int $id, string $identifier, string $plainPassword, PasswordHasherInterface $hasher, array $roles = ['ROLE_USER']): self`
Crée et ajoute un utilisateur avec hashage du mot de passe.

**Exemple :**
```php
$hasher = PasswordHasher::bcrypt();
$provider = new InMemoryUserProvider();

$provider->createUser(1, 'admin@example.com', 'admin123', $hasher, ['ROLE_ADMIN'])
         ->createUser(2, 'user@example.com', 'user123', $hasher);

$user = $provider->loadByIdentifier('admin@example.com');
```

---

### PasswordHasherInterface

**Namespace :** `Lunar\Service\Security\Auth\PasswordHasherInterface`
**Fichier :** `src/Service/Security/Auth/PasswordHasherInterface.php`

Interface de hashage de mots de passe.

#### Méthodes requises

| Méthode | Description |
|---------|-------------|
| `hash(string $plainPassword): string` | Hashe un mot de passe |
| `verify(string $plainPassword, string $hashedPassword): bool` | Vérifie un mot de passe |
| `needsRehash(string $hashedPassword): bool` | Vérifie si rehashage nécessaire |

---

### PasswordHasher

**Namespace :** `Lunar\Service\Security\Auth\PasswordHasher`
**Fichier :** `src/Service/Security/Auth/PasswordHasher.php`

Hashage sécurisé avec bcrypt ou Argon2id.

#### Constructeur

##### `__construct(string $algorithm = PASSWORD_BCRYPT, array $options = [])`

#### Factory Methods

##### `static bcrypt(int $cost = PASSWORD_BCRYPT_DEFAULT_COST): self`
Crée un hasher bcrypt.

##### `static argon2id(int $memoryCost = ..., int $timeCost = ..., int $threads = ...): self`
Crée un hasher Argon2id.

**Exemple :**
```php
$hasher = PasswordHasher::bcrypt(12);
// ou
$hasher = PasswordHasher::argon2id();

$hash = $hasher->hash('secret');
$valid = $hasher->verify('secret', $hash); // true

if ($hasher->needsRehash($oldHash)) {
    $newHash = $hasher->hash('secret');
    // Mettre à jour en base de données
}
```

---

### Authenticator

**Namespace :** `Lunar\Service\Security\Auth\Authenticator`
**Fichier :** `src/Service/Security/Auth/Authenticator.php`

Service principal d'authentification.

#### Constructeur

##### `__construct(UserProviderInterface $userProvider, PasswordHasherInterface $passwordHasher, SessionInterface $session)`

#### Méthodes publiques

| Méthode | Description |
|---------|-------------|
| `attempt(string $identifier, string $password): ?UserInterface` | Authentifie et connecte |
| `login(UserInterface $user): void` | Connecte un utilisateur |
| `logout(): void` | Déconnecte l'utilisateur |
| `user(): ?UserInterface` | Retourne l'utilisateur connecté |
| `check(): bool` | Vérifie si connecté |
| `guest(): bool` | Vérifie si non connecté |
| `id(): string\|int\|null` | Retourne l'ID de l'utilisateur |
| `validate(string $identifier, string $password): bool` | Valide sans connecter |

**Exemple :**
```php
$auth = new Authenticator($userProvider, $hasher, $session);

// Tentative de connexion
if ($user = $auth->attempt('email@example.com', 'password')) {
    echo "Bienvenue " . $user->getIdentifier();
} else {
    echo "Identifiants invalides";
}

// Vérifications
if ($auth->check()) {
    $currentUser = $auth->user();
}

// Déconnexion
$auth->logout();
```

---

### AuthMiddleware

**Namespace :** `Lunar\Service\Security\Auth\AuthMiddleware`
**Fichier :** `src/Service/Security/Auth/AuthMiddleware.php`

Middleware qui requiert une authentification.

#### Constructeur

##### `__construct(Authenticator $authenticator, ?string $redirectUrl = null)`

**Paramètres :**
- `$authenticator` : Service d'authentification
- `$redirectUrl` : URL de redirection si non authentifié (sinon 401)

**Comportement :**
- Retourne 401 si non authentifié et pas de `$redirectUrl`
- Redirige (302) si `$redirectUrl` défini
- Attache `user` et `auth` à la requête si authentifié

**Exemple :**
```php
// Sans redirection (API)
$middleware = new AuthMiddleware($auth);

// Avec redirection (Web)
$middleware = new AuthMiddleware($auth, '/login');

// Dans le contrôleur
$user = $request->getAttribute('user');
$auth = $request->getAttribute('auth');
```

---

### GuestMiddleware

**Namespace :** `Lunar\Service\Security\Auth\GuestMiddleware`
**Fichier :** `src/Service/Security/Auth/GuestMiddleware.php`

Middleware qui requiert un utilisateur NON authentifié.

#### Constructeur

##### `__construct(Authenticator $authenticator, string $redirectUrl = '/')`

**Comportement :**
- Redirige vers `$redirectUrl` si utilisateur connecté
- Laisse passer si non connecté

**Exemple :**
```php
// Pour la page de login
$middleware = new GuestMiddleware($auth, '/dashboard');

#[Route('/login', middlewares: [GuestMiddleware::class])]
public function login() { /* ... */ }
```

---

### RoleMiddleware

**Namespace :** `Lunar\Service\Security\Auth\RoleMiddleware`
**Fichier :** `src/Service/Security/Auth/RoleMiddleware.php`

Middleware de vérification des rôles.

#### Constructeur

##### `__construct(Authenticator $authenticator, array $requiredRoles, bool $requireAll = false)`

**Paramètres :**
- `$authenticator` : Service d'authentification
- `$requiredRoles` : Rôles requis (`['ROLE_ADMIN']`)
- `$requireAll` : `true` = TOUS les rôles, `false` = AU MOINS UN

**Comportement :**
- Retourne 401 si non authentifié
- Retourne 403 si rôles insuffisants

**Exemple :**
```php
// Au moins un des rôles
$middleware = new RoleMiddleware($auth, ['ROLE_ADMIN', 'ROLE_MODERATOR']);

// Tous les rôles requis
$middleware = new RoleMiddleware($auth, ['ROLE_USER', 'ROLE_VERIFIED'], requireAll: true);
```

---

Cette référence couvre les principales classes du framework. Pour des détails d'implémentation, consultez directement les fichiers sources qui sont abondamment documentés.