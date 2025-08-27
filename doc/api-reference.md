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

**Exemple :**
```php
$request = new Request();
$method = $request->getMethod(); // 'GET', 'POST', etc.
$uri = $request->getUri();       // '/blog/123'
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

### AdvancedTemplateEngine

**Namespace :** `App\Service\Core\Template\AdvancedTemplateEngine`  
**Fichier :** `src/Service/Core/Template/AdvancedTemplateEngine.php`

Moteur de templates avancé avec héritage, blocs et macros.

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
$engine = new AdvancedTemplateEngine('template');
$html = $engine->render('blog/show', [
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
$engine->registerMacro('url', function($routeName) {
    return Router::generateUrl($routeName);
});
```

##### `callMacro(string $name, array $args): mixed`
Appelle une macro enregistrée.

**Paramètres :**
- `$name` : Nom de la macro
- `$args` : Arguments à passer à la macro

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

##### `__construct(string $path, string $name = '', array $methods = ['GET'])`

**Paramètres :**
- `$path` : Chemin de la route (peut contenir des paramètres {id})
- `$name` : Nom de la route (optionnel)
- `$methods` : Méthodes HTTP acceptées

**Exemple :**
```php
#[Route('/blog/{id}', name: 'blog_show', methods: ['GET', 'POST'])]
public function show(Request $request): Response
{
    // Logique du contrôleur
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

Cette référence couvre les principales classes du framework. Pour des détails d'implémentation, consultez directement les fichiers sources qui sont abondamment documentés.