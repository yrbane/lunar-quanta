<?php
/**
 * Lunar Quanta Framework - Composant Router (Routeur HTTP).
 *
 * =============================================================================
 * QU'EST-CE QU'UN ROUTEUR ? (Router)
 * =============================================================================
 *
 * Un ROUTEUR est comme un aiguilleur de train ou un standard téléphonique.
 * Quand une requête HTTP arrive (ex: GET /users), le routeur décide QUEL CODE
 * doit traiter cette requête.
 *
 * ANALOGIE : Imaginez un grand hôtel avec plusieurs services
 * - Le réceptionniste (routeur) reçoit les visiteurs (requêtes)
 * - Selon leur demande, il les oriente vers le bon service (contrôleur)
 * - "Vous voulez réserver ?" → Service réservation
 * - "Vous cherchez le restaurant ?" → Service restauration
 * - "Problème avec votre chambre ?" → Service technique
 *
 * ```
 *  REQUÊTE HTTP                        ROUTEUR                         CONTRÔLEUR
 *
 *  GET /                          ┌────────────────┐
 *  ─────────────────────────────► │                │ ─────────────► HomeController::index()
 *                                 │    ROUTEUR     │
 *  GET /users                     │                │ ─────────────► UserController::list()
 *  ─────────────────────────────► │  Quelle route  │
 *                                 │  correspond ?  │
 *  POST /login                    │                │ ─────────────► AuthController::login()
 *  ─────────────────────────────► │                │
 *                                 │                │
 *  GET /page-inexistante          │                │ ─────────────► ErrorController::index(404)
 *  ─────────────────────────────► └────────────────┘
 * ```
 *
 * =============================================================================
 * COMMENT DÉFINIT-ON LES ROUTES ?
 * =============================================================================
 *
 * Ce framework utilise les ATTRIBUTS PHP 8 pour définir les routes.
 * Un attribut est une annotation spéciale qui ajoute des métadonnées au code.
 *
 * ```php
 * // Dans un contrôleur (ex: UserController.php)
 *
 * class UserController
 * {
 *     // Cette méthode répond à GET /users
 *     #[Route('/users', name: 'users_list', methods: ['GET'])]
 *     public function list(Request $request): Response
 *     {
 *         return new Response('Liste des utilisateurs');
 *     }
 *
 *     // Cette méthode répond à POST /users
 *     #[Route('/users', name: 'users_create', methods: ['POST'])]
 *     public function create(Request $request): Response
 *     {
 *         return new Response('Créer un utilisateur', 201);
 *     }
 * }
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UN ATTRIBUT PHP ? (PHP Attributes)
 * =============================================================================
 *
 * Les ATTRIBUTS (introduits en PHP 8) sont des annotations structurées.
 * Ils permettent d'ajouter des métadonnées au code de façon standardisée.
 *
 * Syntaxe : #[NomAttribut(paramètre1: valeur1, paramètre2: valeur2)]
 *
 * ```php
 * // Avant PHP 8 : commentaires DocBlock (non typés, pas de validation)
 * /**
 *  * @Route("/users", methods={"GET"})
 *  * /
 *
 * // Depuis PHP 8 : attributs (typés, validés par PHP)
 * #[Route('/users', methods: ['GET'])]
 * ```
 *
 * AVANTAGES DES ATTRIBUTS :
 * - Analysables par PHP via la Reflection API
 * - Typés et validés à l'exécution
 * - Autocomplétion dans les IDEs
 * - Plus lisibles et maintenables
 *
 * =============================================================================
 * CYCLE DE VIE D'UNE REQUÊTE AVEC LE ROUTEUR
 * =============================================================================
 *
 * ```
 *  1. REQUÊTE ARRIVE
 *     GET /users HTTP/1.1
 *         │
 *         ▼
 *  2. ROUTEUR ANALYSE LA REQUÊTE
 *     ┌─────────────────────────────────────────────┐
 *     │ URL demandée : /users                       │
 *     │ Méthode HTTP : GET                          │
 *     └─────────────────────────────────────────────┘
 *         │
 *         ▼
 *  3. ROUTEUR CHERCHE UNE ROUTE CORRESPONDANTE
 *     ┌─────────────────────────────────────────────┐
 *     │ Route 'home'       : GET /        → Non     │
 *     │ Route 'users_list' : GET /users   → OUI !   │
 *     └─────────────────────────────────────────────┘
 *         │
 *         ▼
 *  4. EXÉCUTE LES MIDDLEWARES (si présents)
 *     ┌─────────────────────────────────────────────┐
 *     │ SessionMiddleware → AuthMiddleware → ...    │
 *     └─────────────────────────────────────────────┘
 *         │
 *         ▼
 *  5. APPELLE LE CONTRÔLEUR
 *     ┌─────────────────────────────────────────────┐
 *     │ UserController::list($request)              │
 *     │ → return new Response('Liste utilisateurs') │
 *     └─────────────────────────────────────────────┘
 *         │
 *         ▼
 *  6. RÉPONSE RENVOYÉE AU NAVIGATEUR
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UN CONTRÔLEUR ? (Controller)
 * =============================================================================
 *
 * Un CONTRÔLEUR est une classe PHP qui contient la logique de traitement
 * des requêtes. Chaque méthode publique (appelée "action") gère une route.
 *
 * RESPONSABILITÉS D'UN CONTRÔLEUR :
 * - Recevoir la requête (objet Request)
 * - Appeler les services métier (base de données, API, etc.)
 * - Construire et retourner une réponse (objet Response)
 *
 * Le contrôleur est le "chef d'orchestre" qui coordonne le traitement.
 * Il ne fait PAS le travail lui-même (délègue aux services).
 *
 * =============================================================================
 * QU'EST-CE QU'UN MIDDLEWARE ?
 * =============================================================================
 *
 * Un MIDDLEWARE est un filtre qui s'exécute AVANT ou APRÈS le contrôleur.
 * C'est comme des checkpoints de sécurité avant d'atteindre votre destination.
 *
 * EXEMPLES DE MIDDLEWARES :
 * - SessionMiddleware : Démarre/restaure la session utilisateur
 * - AuthMiddleware : Vérifie que l'utilisateur est connecté
 * - CsrfMiddleware : Protège contre les attaques CSRF
 * - RateLimitMiddleware : Limite le nombre de requêtes
 *
 * ```
 *  Requête → [Middleware 1] → [Middleware 2] → Contrôleur
 *                                                  │
 *  Réponse ← [Middleware 1] ← [Middleware 2] ← ────┘
 * ```
 *
 * =============================================================================
 * LE CACHE DES ROUTES
 * =============================================================================
 *
 * Scanner tous les contrôleurs à chaque requête serait LENT.
 * Le routeur utilise un CACHE : un fichier PHP qui stocke les routes.
 *
 * ```
 * cache/router.php contient :
 *
 * return [
 *     'home' => [
 *         'path' => '/',
 *         'method' => 'GET',
 *         'controller' => 'Lunar\Controller\HomeController',
 *         'action' => 'index',
 *     ],
 *     'users_list' => [
 *         'path' => '/users',
 *         'method' => 'GET',
 *         'controller' => 'Lunar\Controller\UserController',
 *         'action' => 'list',
 *     ],
 *     // ...
 * ];
 * ```
 *
 * Le cache est automatiquement regénéré quand un fichier contrôleur est modifié.
 *
 * =============================================================================
 * ROUTES NOMMÉES
 * =============================================================================
 *
 * Chaque route peut avoir un NOM unique. C'est utile pour générer des URLs
 * sans hardcoder les chemins.
 *
 * ```php
 * // Sans route nommée (fragile) :
 * $url = '/users';  // Si l'URL change, il faut modifier partout
 *
 * // Avec route nommée (robuste) :
 * $route = Router::getRouteByName('users_list');
 * $url = $route['path'];  // Si l'URL change, seul l'attribut change
 * ```
 *
 * @package    Lunar\Service\Core
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see Route L'attribut utilisé pour définir les routes
 * @see Request L'objet représentant la requête HTTP entrante
 * @see Response L'objet représentant la réponse HTTP sortante
 * @see MiddlewareInterface Interface des middlewares
 */
declare(strict_types=1);

namespace Lunar\Service\Core;

use Lunar\Attribute\Route;
use Lunar\Config\Config;
use Lunar\Controller\ErrorController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;
use Lunar\Service\Core\Middleware\MiddlewareStack;

/**
 * Routeur HTTP basé sur les attributs PHP 8.
 *
 * Cette classe est le CŒUR du système de routage du framework.
 * Elle analyse les requêtes HTTP entrantes et les dirige vers
 * les contrôleurs appropriés.
 *
 * =============================================================================
 * FONCTIONNEMENT INTERNE
 * =============================================================================
 *
 * 1. DÉCOUVERTE DES ROUTES (au démarrage ou quand le cache expire)
 *    - Scanne le dossier src/Controller/
 *    - Utilise la Reflection API pour lire les attributs #[Route]
 *    - Stocke les routes dans un tableau PHP
 *    - Sauvegarde en cache (fichier PHP) pour les prochaines requêtes
 *
 * 2. DISPATCH (à chaque requête)
 *    - Lit les routes depuis le cache
 *    - Compare la requête à chaque route
 *    - Si correspondance : exécute middlewares + contrôleur
 *    - Si aucune correspondance : retourne erreur 404
 *
 * =============================================================================
 * QU'EST-CE QUE LA REFLECTION API ?
 * =============================================================================
 *
 * La REFLECTION API est un ensemble de classes PHP permettant d'analyser
 * le code PHP lui-même. On peut :
 * - Lister les méthodes d'une classe
 * - Lire les paramètres d'une fonction
 * - Accéder aux attributs (annotations)
 *
 * ```php
 * // Exemple simplifié de Reflection
 * $refClass = new ReflectionClass(UserController::class);
 *
 * foreach ($refClass->getMethods() as $method) {
 *     $attributes = $method->getAttributes(Route::class);
 *     foreach ($attributes as $attribute) {
 *         $route = $attribute->newInstance();
 *         echo "Route: " . $route->path;
 *     }
 * }
 * ```
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // Le FrontController utilise le Router comme ceci :
 *
 * $router = new Router();  // Charge les routes (cache ou scan)
 * $request = Request::fromGlobals();  // Crée la requête
 * $response = $router->dispatch($request);  // Trouve et exécute la route
 * $response->send();  // Envoie la réponse au navigateur
 * ```
 *
 * ```php
 * // Définir une route dans un contrôleur :
 *
 * #[Route('/api')]  // Préfixe pour toutes les routes de ce contrôleur
 * class ApiController
 * {
 *     #[Route('/status', name: 'api_status', methods: ['GET'])]
 *     public function status(Request $request): Response
 *     {
 *         // Accessible via GET /api/status
 *         return new Response(
 *             json_encode(['status' => 'ok']),
 *             200,
 *             ['Content-Type: application/json']
 *         );
 *     }
 *
 *     #[Route('/users', name: 'api_users', methods: ['GET', 'POST'],
 *             middlewares: [AuthMiddleware::class])]
 *     public function users(Request $request): Response
 *     {
 *         // Accessible via GET ou POST /api/users
 *         // AuthMiddleware vérifie l'authentification avant
 *         // ...
 *     }
 * }
 * ```
 *
 * @package Lunar\Service\Core
 */
class Router implements RouterInterface
{
    /**
     * Chemin vers le fichier de cache des routes.
     *
     * =========================================================================
     * QU'EST-CE QUE LE CACHE DES ROUTES ?
     * =========================================================================
     *
     * Le CACHE est un fichier PHP qui stocke la liste des routes découvertes.
     * Au lieu de scanner tous les contrôleurs à chaque requête (lent),
     * le routeur lit ce fichier (rapide).
     *
     * FORMAT DU FICHIER CACHE :
     * ```php
     * // cache/router.php
     * <?php return [
     *     'home' => [
     *         'name' => 'home',
     *         'path' => '/',
     *         'method' => 'GET',
     *         'controller' => 'Lunar\Controller\HomeController',
     *         'action' => 'index',
     *         'middlewares' => [],
     *     ],
     *     // ... autres routes
     * ];
     * ```
     *
     * Ce fichier est automatiquement regénéré quand :
     * - Il n'existe pas
     * - Un fichier contrôleur a été modifié depuis la dernière génération
     *
     * @var string Chemin absolu vers cache/router.php
     */
    private string $cacheFile;

    /**
     * Liste des routes enregistrées.
     *
     * =========================================================================
     * STRUCTURE D'UNE ROUTE
     * =========================================================================
     *
     * Chaque route est un tableau associatif contenant :
     *
     * ```php
     * [
     *     'name' => 'users_list',           // Nom unique de la route
     *     'path' => '/users',               // Chemin URL
     *     'method' => 'GET',                // Méthode HTTP (GET, POST, etc.)
     *     'controller' => 'Lunar\Controller\UserController',  // Classe
     *     'action' => 'list',               // Méthode à appeler
     *     'middlewares' => [                // Middlewares à exécuter avant
     *         'Lunar\Service\Session\SessionMiddleware',
     *     ],
     * ]
     * ```
     *
     * TABLEAU ASSOCIATIF
     * ------------------
     * Les routes sont stockées avec le nom de la route comme clé.
     * Cela permet un accès rapide par nom : $this->routes['users_list']
     *
     * @var array<string, array<string, mixed>> Tableau [nom_route => infos_route]
     */
    private array $routes = [];

    /**
     * Table de hachage des routes nommées (accès statique).
     *
     * =========================================================================
     * POURQUOI UNE PROPRIÉTÉ STATIQUE ?
     * =========================================================================
     *
     * Cette propriété est STATIQUE, c'est-à-dire qu'elle appartient à la CLASSE
     * et non à une instance particulière. Elle est partagée par tout le code.
     *
     * POURQUOI ?
     * On veut pouvoir accéder aux routes nommées depuis n'importe où dans
     * l'application, sans avoir besoin de l'instance du routeur.
     *
     * ```php
     * // Depuis un template ou un service :
     * $route = Router::getRouteByName('users_list');
     * $url = $route['path'];  // '/users'
     *
     * // Pas besoin de :
     * $router = new Router();
     * $route = $router->getRouteByName('users_list');
     * ```
     *
     * ATTENTION AU MOT-CLÉ "static"
     * -----------------------------
     * - Propriété statique : accès via NomClasse::$propriete
     * - Propriété d'instance : accès via $objet->propriete
     *
     * - Méthode statique : accès via NomClasse::methode()
     * - Méthode d'instance : accès via $objet->methode()
     *
     * @var array<string, array<string, mixed>> Tableau [nom_route => infos_route]
     */
    private static array $namedRoutes = [];

    /**
     * Namespace (espace de noms) des contrôleurs.
     *
     * =========================================================================
     * QU'EST-CE QU'UN NAMESPACE ? (Espace de noms)
     * =========================================================================
     *
     * Un NAMESPACE est comme une "adresse postale" pour les classes PHP.
     * Il permet d'organiser le code et d'éviter les conflits de noms.
     *
     * ANALOGIE : Pensez aux rues d'une ville
     * - "Jean Dupont, 12 rue de Paris" → une personne précise
     * - "Jean Dupont, 5 rue de Lyon" → une autre personne
     * - Le même nom, mais des adresses différentes !
     *
     * ```php
     * // Sans namespace (risque de conflit)
     * class User { }  // Quelle classe User ? Il peut y en avoir plusieurs !
     *
     * // Avec namespace (pas de conflit)
     * namespace Lunar\Entity;
     * class User { }  // C'est Lunar\Entity\User
     *
     * namespace Vendor\Library;
     * class User { }  // C'est Vendor\Library\User (différent !)
     * ```
     *
     * Ici, on indique que tous les contrôleurs sont dans Lunar\Controller\
     *
     * @var string Namespace complet avec le backslash final
     */
    private string $controllerNamespace = 'Lunar\Controller\\';

    /**
     * Chemin physique du dossier des contrôleurs.
     *
     * =========================================================================
     * DIFFÉRENCE ENTRE NAMESPACE ET CHEMIN PHYSIQUE
     * =========================================================================
     *
     * NAMESPACE = Organisation logique (comment PHP voit les classes)
     * CHEMIN    = Organisation physique (où sont les fichiers sur le disque)
     *
     * ```
     * NAMESPACE                        CHEMIN PHYSIQUE
     * ─────────                        ───────────────
     * Lunar\Controller\HomeController  src/Controller/HomeController.php
     * Lunar\Controller\Api\UserController  src/Controller/Api/UserController.php
     * ```
     *
     * L'autoloader PSR-4 fait le lien entre les deux automatiquement.
     *
     * @var string Chemin absolu vers src/Controller/
     */
    private string $controllerDir;

    /**
     * Crée une nouvelle instance du routeur.
     *
     * =========================================================================
     * QUE FAIT LE CONSTRUCTEUR ?
     * =========================================================================
     *
     * 1. Détermine le chemin du fichier cache
     * 2. Trouve le dossier des contrôleurs
     * 3. Charge les routes :
     *    - Si le cache existe et est valide → charge depuis le cache
     *    - Sinon → scanne les contrôleurs et crée le cache
     *
     * VÉRIFICATION DE LA FRAÎCHEUR DU CACHE
     * -------------------------------------
     * Le cache est considéré "périmé" si un fichier contrôleur
     * a été modifié après la création du cache.
     *
     * ```
     *  Cache créé le : 2024-01-15 10:00:00
     *
     *  UserController.php modifié le : 2024-01-15 09:00:00  → Cache valide
     *  HomeController.php modifié le : 2024-01-15 11:00:00  → Cache périmé !
     * ```
     *
     * @example
     * ```php
     * // Création simple (charge ou génère les routes)
     * $router = new Router();
     *
     * // Ensuite on peut dispatcher des requêtes
     * $response = $router->dispatch($request);
     * ```
     */
    public function __construct()
    {
        // Récupère le chemin du fichier cache (ex: /projet/cache/router.php)
        $this->cacheFile = self::getCacheFile();

        // Trouve le dossier des contrôleurs (ex: /projet/src/Controller)
        // realpath() retourne false si le dossier n'existe pas
        $controllerDir = realpath(Config::getProjectRoot().'/src/Controller');
        $this->controllerDir = false === $controllerDir ? '' : $controllerDir;

        // Charge les routes depuis le cache ou scanne les contrôleurs
        if (is_file($this->cacheFile) && !$this->isCacheStale()) {
            // Cache valide → on charge directement
            /** @var array<string, array<string, mixed>> $cachedRoutes */
            $cachedRoutes = include $this->cacheFile;
            $this->routes = $cachedRoutes;
        } else {
            // Pas de cache ou cache périmé → on scanne les contrôleurs
            $this->registerAllControllerRoutes();
        }
    }

    /**
     * Récupère le chemin du fichier de cache des routes.
     *
     * =========================================================================
     * MÉTHODE STATIQUE
     * =========================================================================
     *
     * Cette méthode est STATIQUE car elle peut être appelée sans créer
     * d'instance du routeur. Utile pour :
     * - Vérifier si le cache existe
     * - Supprimer le cache manuellement
     * - Outils de débogage
     *
     * ```php
     * // Supprimer le cache des routes (force la regénération)
     * $cacheFile = Router::getCacheFile();
     * if (file_exists($cacheFile)) {
     *     unlink($cacheFile);
     * }
     * ```
     *
     * @return string Chemin absolu vers le fichier cache (ex: /projet/cache/router.php)
     */
    public static function getCacheFile(): string
    {
        // Récupère le dossier de cache depuis la configuration
        $cacheDir = Config::get('cache', 'cache.dir', 'cache');
        $cacheDirStr = is_string($cacheDir) ? $cacheDir : 'cache';

        // Retourne le chemin complet vers router.php
        return Config::resolvePath($cacheDirStr.'/router.php');
    }

    /**
     * Retourne une route par son nom.
     *
     * =========================================================================
     * UTILISATION DES ROUTES NOMMÉES
     * =========================================================================
     *
     * Les routes nommées permettent de référencer une route sans connaître
     * son URL. C'est une bonne pratique car :
     *
     * - Si l'URL change, seul l'attribut #[Route] change
     * - Le reste du code utilise toujours le même nom
     * - Moins d'erreurs, code plus maintenable
     *
     * ```php
     * // Dans un contrôleur :
     * #[Route('/membres', name: 'users_list', methods: ['GET'])]
     * // On peut changer '/membres' en '/utilisateurs' sans rien casser !
     *
     * // Ailleurs dans l'application :
     * $route = Router::getRouteByName('users_list');
     * if ($route) {
     *     $url = $route['path'];  // '/membres' ou '/utilisateurs'
     * }
     * ```
     *
     * @param string $name Le nom unique de la route à rechercher
     *
     * @return null|array<string, mixed> Le tableau décrivant la route, ou null si non trouvée
     *
     * @example
     * ```php
     * // Générer un lien vers une route nommée
     * $route = Router::getRouteByName('home');
     * if ($route !== null) {
     *     echo '<a href="' . $route['path'] . '">Accueil</a>';
     * }
     *
     * // Vérifier si une route existe
     * if (Router::getRouteByName('admin_dashboard') === null) {
     *     echo "La route admin_dashboard n'existe pas";
     * }
     * ```
     */
    public static function getRouteByName(string $name): ?array
    {
        // Opérateur null coalescent : retourne la valeur ou null si absente
        return self::$namedRoutes[$name] ?? null;
    }

    /**
     * Dispatch (distribue) la requête vers la route correspondante.
     *
     * =========================================================================
     * QU'EST-CE QUE LE DISPATCH ?
     * =========================================================================
     *
     * DISPATCH signifie "distribuer", "aiguiller". Cette méthode est le
     * point d'entrée principal du routeur. Elle :
     *
     * 1. Cherche une route correspondant à la requête
     * 2. Si trouvée → exécute les middlewares et le contrôleur
     * 3. Si non trouvée → essaie de rescanner les contrôleurs
     * 4. Si toujours non trouvée → retourne une erreur 404
     *
     * ```
     *  dispatch($request)
     *        │
     *        ▼
     *  ┌─────────────────┐
     *  │ Chercher route  │──────────────────────┐
     *  │ dans le cache   │                      │
     *  └─────────────────┘                      │
     *        │                                  │
     *        │ Trouvée ?                        │ Non trouvée
     *        │                                  │
     *        ▼ Oui                              ▼
     *  ┌─────────────────┐            ┌─────────────────┐
     *  │ Exécuter route  │            │ Rescanner les   │
     *  │ + middlewares   │            │ contrôleurs     │
     *  └─────────────────┘            └─────────────────┘
     *        │                                  │
     *        ▼                                  │ Chercher à nouveau
     *  ┌─────────────────┐                      │
     *  │    Response     │◄─────────────────────┤
     *  └─────────────────┘                      │
     *                                           │ Toujours non trouvée
     *                                           ▼
     *                                   ┌─────────────────┐
     *                                   │ Erreur 404      │
     *                                   │ Page non trouvée│
     *                                   └─────────────────┘
     * ```
     *
     * POURQUOI RESCANNER ?
     * Le cache peut être désynchronisé si un nouveau contrôleur a été
     * ajouté mais que le cache n'a pas encore été regénéré.
     *
     * @param Request $request L'objet Request représentant la requête entrante
     *
     * @return Response L'objet Response à envoyer au navigateur
     *
     * @example
     * ```php
     * // Dans le FrontController
     * $router = new Router();
     * $request = Request::fromGlobals();
     *
     * $response = $router->dispatch($request);
     *
     * $response->send();  // Envoie au navigateur
     * ```
     */
    public function dispatch(Request $request): Response
    {
        // Première tentative : chercher dans les routes en cache
        $response = $this->searchRoute($request);
        if ($response instanceof Response) {
            return $response;
        }

        // Deuxième tentative : rescanner les contrôleurs (nouveau code ?)
        $this->registerAllControllerRoutes();
        $response = $this->searchRoute($request);
        if ($response instanceof Response) {
            return $response;
        }

        // Aucune route trouvée → erreur 404
        $errorController = new ErrorController();

        return $errorController->index($request, 404);
    }

    /**
     * Recherche une route correspondant à la requête.
     *
     * =========================================================================
     * ALGORITHME DE RECHERCHE
     * =========================================================================
     *
     * Cette méthode parcourt TOUTES les routes enregistrées et compare
     * chacune à la requête. Dès qu'une correspondance est trouvée,
     * elle exécute la route et retourne la réponse.
     *
     * CRITÈRES DE CORRESPONDANCE :
     * - L'URL de la requête doit matcher le path de la route
     * - La méthode HTTP doit correspondre (GET, POST, etc.)
     *
     * ```
     *  Requête : GET /users
     *
     *  Route 1 : GET /           → /users ≠ /        → NON
     *  Route 2 : POST /users     → GET ≠ POST        → NON
     *  Route 3 : GET /users      → /users = /users   → OUI !
     *                              GET = GET
     *                              → Exécuter cette route
     * ```
     *
     * @param Request $request La requête à analyser
     *
     * @return bool|Response Response si une route correspond, false sinon
     */
    public function searchRoute(Request $request): bool|Response
    {
        // Parcourt chaque route enregistrée
        foreach ($this->routes as $route) {
            // Vérifie si cette route correspond à la requête
            if ($this->matchRoute($route, $request)) {
                // Correspondance ! Exécute la route
                return $this->executeRoute($route, $request);
            }
        }

        // Aucune route ne correspond
        return false;
    }

    /**
     * Exécute une route avec ses middlewares.
     *
     * =========================================================================
     * ORDRE D'EXÉCUTION
     * =========================================================================
     *
     * 1. Instancie le contrôleur
     * 2. Crée le "handler final" (la fonction qui appelle le contrôleur)
     * 3. Si des middlewares sont définis :
     *    - Construit une pile (stack) de middlewares
     *    - Exécute la pile qui finira par appeler le handler final
     * 4. Si pas de middlewares :
     *    - Appelle directement le handler final
     *
     * ```
     *  AVEC MIDDLEWARES                    SANS MIDDLEWARE
     *
     *  Requête                             Requête
     *     │                                   │
     *     ▼                                   │
     *  Middleware 1                           │
     *     │                                   │
     *     ▼                                   │
     *  Middleware 2                           │
     *     │                                   │
     *     ▼                                   ▼
     *  Contrôleur ◄───────────────────── Contrôleur
     *     │                                   │
     *     ▼                                   │
     *  Middleware 2                           │
     *     │                                   │
     *     ▼                                   │
     *  Middleware 1                           │
     *     │                                   │
     *     ▼                                   ▼
     *  Réponse                             Réponse
     * ```
     *
     * QU'EST-CE QU'UNE CLOSURE ?
     * --------------------------
     * La variable $finalHandler est une CLOSURE (fonction anonyme).
     * C'est une fonction définie "à la volée", sans nom.
     *
     * ```php
     * // Fonction classique
     * function maFonction($x) { return $x * 2; }
     *
     * // Closure (fonction anonyme)
     * $maFonction = function($x) { return $x * 2; };
     *
     * // Les deux s'utilisent pareil
     * echo maFonction(5);      // 10
     * echo $maFonction(5);     // 10
     * ```
     *
     * @param array<string, mixed> $route   La route correspondante
     * @param Request              $request La requête entrante
     *
     * @return Response La réponse générée par le contrôleur (via les middlewares)
     */
    private function executeRoute(array $route, Request $request): Response
    {
        // Instancie le contrôleur
        // $route['controller'] contient le nom complet de la classe
        // Ex: 'Lunar\Controller\UserController'
        $controller = new $route['controller']();
        $action = $route['action'];  // Ex: 'list'

        // Récupère les paramètres de route extraits (ex: ['id' => '42'])
        $routeParams = $this->lastMatchedParams;

        // Ajoute les paramètres à la requête pour qu'ils soient accessibles
        $request = $this->addParamsToRequest($request, $routeParams);

        // Crée le "handler final" : la fonction qui appelle le contrôleur
        // C'est une closure qui "capture" $controller, $action et les paramètres
        $finalHandler = function (Request $req) use ($controller, $action, $routeParams): Response {
            // Appelle la méthode du contrôleur avec les paramètres de route
            // Ex: $controller->show($req, '42') pour /user/{id}
            $result = $this->invokeControllerAction($controller, $action, $req, $routeParams);

            // Si le contrôleur retourne une Response, on la retourne
            if ($result instanceof Response) {
                return $result;
            }

            // Sinon, on encapsule le résultat dans une Response
            return new Response(is_string($result) ? $result : '');
        };

        // Récupère les middlewares de la route (peut être un tableau vide)
        /** @var array<class-string> $middlewareClasses */
        $middlewareClasses = $route['middlewares'] ?? [];

        // S'il n'y a pas de middlewares, on appelle directement le contrôleur
        if (empty($middlewareClasses)) {
            return $finalHandler($request);
        }

        // Construit et exécute la pile de middlewares
        $stack = new MiddlewareStack();
        foreach ($middlewareClasses as $middlewareClass) {
            // Vérifie que la classe existe
            if (class_exists($middlewareClass)) {
                $middleware = new $middlewareClass();
                // Vérifie que c'est bien un middleware valide
                if ($middleware instanceof MiddlewareInterface) {
                    $stack->add($middleware);
                }
            }
        }

        // Exécute la pile (les middlewares puis le handler final)
        return $stack->handle($request, $finalHandler);
    }

    /**
     * Retourne la liste des routes enregistrées.
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * Cette méthode est utile pour :
     * - Déboguer les routes (voir ce qui est enregistré)
     * - Générer une documentation automatique des routes
     * - Outils d'administration
     *
     * ```php
     * $router = new Router();
     * $routes = $router->getRegisteredRoutes();
     *
     * foreach ($routes as $name => $route) {
     *     echo "{$route['method']} {$route['path']} → {$name}\n";
     * }
     *
     * // Affiche :
     * // GET / → home
     * // GET /users → users_list
     * // POST /users → users_create
     * // ...
     * ```
     *
     * @return array<string, array<string, mixed>> Tableau [nom_route => infos_route]
     */
    public function getRegisteredRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Alias de getRegisteredRoutes() pour la compatibilité.
     *
     * @return array<string, array<string, mixed>> Tableau des routes
     *
     * @see getRegisteredRoutes() Méthode principale
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Vérifie si le cache des routes est périmé.
     *
     * =========================================================================
     * LOGIQUE DE FRAÎCHEUR DU CACHE
     * =========================================================================
     *
     * Le cache est périmé si :
     * - Le fichier cache n'existe pas
     * - La date du fichier cache est illisible
     * - Au moins UN fichier contrôleur a été modifié APRÈS le cache
     *
     * ```
     *  Exemple de timeline :
     *
     *  10:00 - UserController.php modifié
     *  10:30 - Cache généré (router.php)
     *  11:00 - HomeController.php modifié  ← APRÈS le cache !
     *
     *  → Le cache est périmé, il faut le regénérer
     * ```
     *
     * Cette vérification permet de :
     * - Garder les bonnes performances (pas de scan inutile)
     * - Toujours avoir des routes à jour (nouvelles routes détectées)
     *
     * @return bool true si le cache doit être regénéré, false s'il est valide
     */
    private function isCacheStale(): bool
    {
        // Pas de fichier cache → périmé (il faut le créer)
        if (!is_file($this->cacheFile)) {
            return true;
        }

        // Récupère la date de modification du cache
        $cacheTime = filemtime($this->cacheFile);
        if (false === $cacheTime) {
            return true;  // Impossible de lire la date → on regénère
        }

        // Vérifie si des contrôleurs ont été modifiés après le cache
        return $this->hasNewerControllerFiles($cacheTime);
    }

    /**
     * Vérifie si des fichiers contrôleurs sont plus récents que le cache.
     *
     * =========================================================================
     * PARCOURS RÉCURSIF DES FICHIERS
     * =========================================================================
     *
     * Cette méthode utilise RecursiveIteratorIterator pour parcourir
     * TOUS les fichiers du dossier contrôleurs, y compris les sous-dossiers.
     *
     * QU'EST-CE QU'UN ITERATOR ?
     * Un ITERATOR (itérateur) est un objet qui permet de parcourir
     * une collection élément par élément avec foreach.
     *
     * ```php
     * // RecursiveDirectoryIterator : parcourt un dossier récursivement
     * // RecursiveIteratorIterator : "aplatit" pour utiliser avec foreach
     *
     * $iterator = new RecursiveIteratorIterator(
     *     new RecursiveDirectoryIterator('/chemin/dossier')
     * );
     *
     * foreach ($iterator as $file) {
     *     echo $file->getFilename();  // Chaque fichier, un par un
     * }
     * ```
     *
     * @param int $cacheTime Timestamp Unix de la date du cache
     *
     * @return bool true si au moins un fichier est plus récent
     */
    private function hasNewerControllerFiles(int $cacheTime): bool
    {
        // Pas de dossier contrôleurs → pas de fichiers plus récents
        if ('' === $this->controllerDir || !is_dir($this->controllerDir)) {
            return false;
        }

        // Crée un itérateur pour parcourir tous les fichiers
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->controllerDir)
        );

        // Parcourt chaque fichier
        foreach ($iterator as $file) {
            // Vérifie que c'est bien un SplFileInfo
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            // On ne s'intéresse qu'aux fichiers PHP
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $fileTime = $file->getMTime();  // Date de modification
                if ($fileTime > $cacheTime) {
                    // Ce fichier est plus récent que le cache !
                    return true;
                }
            }
        }

        // Aucun fichier plus récent trouvé
        return false;
    }

    /**
     * Scanne tous les contrôleurs pour enregistrer leurs routes.
     *
     * =========================================================================
     * PROCESSUS DE DÉCOUVERTE DES ROUTES
     * =========================================================================
     *
     * 1. Liste tous les fichiers PHP dans src/Controller/
     * 2. Pour chaque fichier, détermine le nom de classe (namespace + nom)
     * 3. Utilise la Reflection pour lire les attributs #[Route]
     * 4. Enregistre chaque route dans $this->routes
     * 5. Sauvegarde le tout dans le fichier cache
     *
     * Cette méthode est appelée :
     * - À la première requête (pas de cache)
     * - Quand le cache est périmé
     * - Quand une route n'est pas trouvée (au cas où nouveau contrôleur)
     */
    private function registerAllControllerRoutes(): void
    {
        // Récupère la liste des classes contrôleur
        $controllerClasses = $this->getControllerClasses();

        // Enregistre les routes de chaque contrôleur
        foreach ($controllerClasses as $controllerClass) {
            $this->registerControllerRoutes($controllerClass);
        }
    }

    /**
     * Liste toutes les classes contrôleur du dossier Controller.
     *
     * =========================================================================
     * CONVERSION CHEMIN → NOM DE CLASSE
     * =========================================================================
     *
     * Cette méthode parcourt les fichiers et convertit leur chemin
     * en nom de classe PHP complet (avec namespace).
     *
     * ```
     *  CHEMIN FICHIER                    NOM DE CLASSE PHP
     *  ──────────────                    ─────────────────
     *  src/Controller/HomeController.php     Lunar\Controller\HomeController
     *  src/Controller/Api/UserController.php Lunar\Controller\Api\UserController
     * ```
     *
     * ÉTAPES DE CONVERSION :
     * 1. Récupère le chemin relatif (enlève le préfixe du dossier)
     * 2. Remplace les séparateurs de chemin par des \
     * 3. Enlève l'extension .php
     * 4. Ajoute le namespace de base
     *
     * @return array<int, string> Liste des noms complets de classes
     */
    private function getControllerClasses(): array
    {
        $classes = [];

        // Pas de dossier → pas de classes
        if (false == $this->controllerDir) {
            return $classes;
        }

        // Parcourt récursivement tous les fichiers
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->controllerDir)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            // On ne traite que les fichiers PHP
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $realPath = $file->getRealPath();
                if (false === $realPath) {
                    continue;
                }

                // Convertit le chemin en nom de classe
                // Ex: /projet/src/Controller/Api/UserController.php
                //     → Api\UserController
                $relativePath = str_replace(
                    $this->controllerDir.DIRECTORY_SEPARATOR,
                    '',
                    $realPath
                );

                // Remplace / ou \ par \\ (séparateur de namespace)
                $relativeClass = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

                // Construit le nom complet
                // Ex: Lunar\Controller\Api\UserController
                $className = $this->controllerNamespace.str_replace('.php', '', $relativeClass);

                // Vérifie que la classe existe vraiment
                if (class_exists($className)) {
                    $classes[] = $className;
                }
            }
        }

        return $classes;
    }

    /**
     * Enregistre les routes d'un contrôleur via ses attributs.
     *
     * =========================================================================
     * LECTURE DES ATTRIBUTS AVEC REFLECTION
     * =========================================================================
     *
     * Cette méthode utilise la Reflection API pour :
     *
     * 1. Lire les attributs #[Route] sur la CLASSE (préfixe optionnel)
     * 2. Lire les attributs #[Route] sur chaque MÉTHODE publique
     * 3. Combiner préfixe + chemin pour le chemin final
     *
     * ```php
     * #[Route('/api')]  // Préfixe de classe
     * class ApiController
     * {
     *     #[Route('/users')]  // Chemin de méthode
     *     public function users() { }
     *     // → Route finale : /api/users
     *
     *     #[Route('/products')]
     *     public function products() { }
     *     // → Route finale : /api/products
     * }
     * ```
     *
     * QU'EST-CE QUE ReflectionClass ?
     * -------------------------------
     * ReflectionClass est une classe PHP qui permet d'inspecter
     * une autre classe : ses méthodes, propriétés, attributs, etc.
     *
     * ```php
     * $ref = new ReflectionClass(UserController::class);
     *
     * // Lister les méthodes
     * foreach ($ref->getMethods() as $method) {
     *     echo $method->getName();  // index, list, create...
     * }
     *
     * // Lire les attributs
     * $attributes = $ref->getAttributes(Route::class);
     * ```
     *
     * @param string $controllerClass Nom complet de la classe contrôleur
     */
    private function registerControllerRoutes(string $controllerClass): void
    {
        // Crée un objet Reflection pour analyser la classe
        /** @var class-string $controllerClass */
        $refClass = new \ReflectionClass($controllerClass);
        $basePath = '';

        // Vérifie si la classe a un attribut #[Route] (préfixe)
        $classAttributes = $refClass->getAttributes(Route::class);
        if (!empty($classAttributes)) {
            /** @var Route $routeAttribute */
            $routeAttribute = $classAttributes[0]->newInstance();
            $basePath = $routeAttribute->path;  // Ex: '/api'
        }

        // Parcourt toutes les méthodes publiques
        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // Récupère les attributs #[Route] de cette méthode
            $attributes = $method->getAttributes(Route::class);

            foreach ($attributes as $attribute) {
                /** @var Route $routeAttr */
                $routeAttr = $attribute->newInstance();

                // Combine le préfixe de classe et le chemin de méthode
                $fullPath = $basePath.$routeAttr->path;  // Ex: '/api' + '/users' = '/api/users'

                // Une route peut avoir plusieurs méthodes HTTP (GET, POST...)
                foreach ($routeAttr->methods as $httpMethod) {
                    // Construit le tableau de la route
                    $route = [
                        'name' => $routeAttr->name,
                        'path' => $fullPath,
                        'method' => strtoupper($httpMethod),  // Normalise en majuscules
                        'controller' => $controllerClass,
                        'action' => $method->getName(),
                        'middlewares' => $routeAttr->middlewares,
                    ];

                    // Enregistre la route
                    $this->routes[$routeAttr->name] = $route;

                    // Ajoute aux routes nommées (accès statique)
                    if (!empty($routeAttr->name)) {
                        self::$namedRoutes[$routeAttr->name] = $route;
                    }
                }
            }
        }

        // Sauvegarde le cache
        // var_export() génère une représentation PHP du tableau
        file_put_contents(
            $this->cacheFile,
            '<?php return '.var_export($this->routes, true).';'
        );
    }

    /**
     * Vérifie si une route correspond à la requête.
     *
     * =========================================================================
     * CRITÈRES DE CORRESPONDANCE
     * =========================================================================
     *
     * Une route "correspond" (match) à une requête si :
     *
     * 1. La MÉTHODE HTTP est identique
     *    - Route : GET   → Requête : GET ✓
     *    - Route : POST  → Requête : GET ✗
     *
     * 2. Le CHEMIN (path) est identique
     *    - Route : /users → Requête : /users ✓
     *    - Route : /users → Requête : /user  ✗
     *
     * NOTE : Cette implémentation fait une comparaison EXACTE.
     * Elle ne supporte pas les paramètres dynamiques comme /users/{id}.
     *
     * ```
     *  Route : GET /users
     *
     *  GET /users      → ✓ MATCH (méthode et chemin identiques)
     *  POST /users     → ✗ (méthode différente)
     *  GET /users/123  → ✗ (chemin différent)
     *  GET /USERS      → ✗ (chemin sensible à la casse)
     * ```
     *
     * @param array<string, mixed> $route   La route à tester
     * @param Request              $request La requête entrante
     *
     * @return bool true si la route correspond, false sinon
     */
    /**
     * Vérifie si une route correspond à la requête et extrait les paramètres.
     *
     * ==========================================================================
     * ROUTES DYNAMIQUES
     * ==========================================================================
     *
     * Cette méthode supporte les paramètres dynamiques dans les routes :
     *
     * ```
     * PATTERN                 URL                    PARAMÈTRES
     * /user/{id}              /user/42               ['id' => '42']
     * /blog/{slug}            /blog/mon-article      ['slug' => 'mon-article']
     * /api/{version}/users    /api/v2/users          ['version' => 'v2']
     * ```
     *
     * COMMENT ÇA MARCHE ?
     *
     * ```
     * 1. Pattern: /user/{id}
     *    │
     *    ▼
     * 2. Regex: #^/user/(?P<id>[^/]+)$#
     *    │
     *    ▼
     * 3. Match contre /user/42
     *    │
     *    ▼
     * 4. Extraction: ['id' => '42']
     * ```
     *
     * @param array<string, mixed> $route   La route à tester
     * @param Request              $request La requête entrante
     *
     * @return bool true si la route correspond, false sinon
     */
    private function matchRoute(array $route, Request $request): bool
    {
        // Vérifie d'abord la méthode HTTP
        if ($route['method'] !== strtoupper($request->getMethod())) {
            return false;
        }

        // Extrait les paramètres (stockés pour utilisation ultérieure)
        $params = $this->extractRouteParams($route['path'], $request->getUri());

        if ($params === null) {
            return false;
        }

        // Stocke les paramètres extraits dans la route
        $this->lastMatchedParams = $params;

        return true;
    }

    /**
     * Paramètres extraits de la dernière route correspondante.
     *
     * @var array<string, string>
     */
    private array $lastMatchedParams = [];

    /**
     * Extrait les paramètres d'une URL selon un pattern de route.
     *
     * ==========================================================================
     * ALGORITHME D'EXTRACTION
     * ==========================================================================
     *
     * ```php
     * // Pattern: /user/{id}/posts/{postId}
     * // URL:     /user/42/posts/123
     *
     * // Étape 1: Trouver les placeholders
     * preg_match_all('/{(\w+)}/', $pattern, $matches);
     * // $matches[1] = ['id', 'postId']
     *
     * // Étape 2: Convertir en regex
     * $regex = '#^/user/(?P<id>[^/]+)/posts/(?P<postId>[^/]+)$#';
     *
     * // Étape 3: Extraire les valeurs
     * preg_match($regex, $url, $matches);
     * // $matches = ['id' => '42', 'postId' => '123', ...]
     * ```
     *
     * @param string $pattern Le pattern de la route (ex: /user/{id})
     * @param string $uri     L'URI de la requête (ex: /user/42)
     *
     * @return null|array<string, string> Les paramètres extraits ou null si pas de correspondance
     */
    private function extractRouteParams(string $pattern, string $uri): ?array
    {
        // Route statique (pas de paramètres)
        if (!str_contains($pattern, '{')) {
            return $pattern === $uri ? [] : null;
        }

        // Convertit le pattern en regex
        // {param} devient (?P<param>[^/]+)
        $regex = preg_replace(
            '/\{(\w+)\}/',
            '(?P<$1>[^/]+)',
            $pattern
        );
        $regex = '#^' . $regex . '$#';

        // Teste la correspondance
        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        // Extrait uniquement les paramètres nommés (pas les indices numériques)
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    // =========================================================================
    // MÉTHODES DE L'INTERFACE RouterInterface
    // =========================================================================

    /**
     * Enregistre une nouvelle route manuellement.
     *
     * ==========================================================================
     * QUAND UTILISER addRoute() ?
     * ==========================================================================
     *
     * Normalement, les routes sont découvertes automatiquement via les attributs
     * #[Route] sur les contrôleurs. Mais parfois, on veut ajouter des routes
     * dynamiquement ou depuis un fichier de configuration.
     *
     * ```php
     * // Exemple : Route ajoutée dynamiquement
     * $router->addRoute(
     *     '/api/status',
     *     'Lunar\Controller\ApiController',
     *     'status',
     *     ['GET'],
     *     'api_status'
     * );
     * ```
     *
     * ==========================================================================
     * DIFFÉRENCE AVEC LES ATTRIBUTS
     * ==========================================================================
     *
     * ```
     * ATTRIBUT #[Route]                    addRoute()
     *
     * - Découvert automatiquement         - Ajouté manuellement
     * - Mis en cache                      - Pas mis en cache
     * - Dans le code du contrôleur        - Depuis n'importe où
     * ```
     *
     * @param string        $path       Chemin URL (ex: '/users/{id}')
     * @param string        $controller Classe du contrôleur (FQCN)
     * @param string        $action     Nom de la méthode à appeler
     * @param array<string> $methods    Méthodes HTTP acceptées (défaut: ['GET'])
     * @param null|string   $name       Nom optionnel de la route
     */
    public function addRoute(
        string $path,
        string $controller,
        string $action,
        array $methods = ['GET'],
        ?string $name = null
    ): void {
        // Génère un nom unique si non fourni
        $routeName = $name ?? $controller . '::' . $action;

        // Ajoute une route pour chaque méthode HTTP
        foreach ($methods as $method) {
            $this->routes[$routeName . '_' . $method] = [
                'name' => $routeName,
                'path' => $path,
                'method' => strtoupper($method),
                'controller' => $controller,
                'action' => $action,
                'middlewares' => [],
            ];
        }

        // Met à jour la table des routes nommées (accès statique)
        self::$namedRoutes[$routeName] = [
            'name' => $routeName,
            'path' => $path,
            'methods' => $methods,
            'controller' => $controller,
            'action' => $action,
        ];
    }

    /**
     * Recherche une route correspondant à la requête.
     *
     * ==========================================================================
     * DIFFÉRENCE AVEC dispatch()
     * ==========================================================================
     *
     * ```
     * match()                              dispatch()
     *
     * - Retourne les infos de la route    - Exécute la route
     * - N'appelle pas le contrôleur       - Appelle le contrôleur
     * - Retourne null si pas trouvé       - Retourne une 404 si pas trouvé
     * - Utile pour l'introspection        - Utilisé pour le traitement normal
     * ```
     *
     * STRUCTURE DU RETOUR :
     * ```php
     * [
     *     'controller' => 'Lunar\Controller\UserController',
     *     'action' => 'show',
     *     'parameters' => ['id' => '42']
     * ]
     * ```
     *
     * @param Request $request La requête HTTP entrante
     *
     * @return null|array{controller: string, action: string, parameters: array<string, mixed>}
     *         Informations de la route ou null si aucune correspondance
     */
    public function match(Request $request): ?array
    {
        // Réinitialise les paramètres
        $this->lastMatchedParams = [];

        // Parcourt toutes les routes enregistrées
        foreach ($this->routes as $route) {
            // Vérifie si cette route correspond
            if ($this->matchRoute($route, $request)) {
                return [
                    'controller' => $route['controller'],
                    'action' => $route['action'],
                    'parameters' => $this->lastMatchedParams,
                ];
            }
        }

        // Aucune route ne correspond
        return null;
    }

    /**
     * Retourne les paramètres extraits de la dernière route correspondante.
     *
     * @return array<string, string> Les paramètres de route
     */
    public function getLastMatchedParams(): array
    {
        return $this->lastMatchedParams;
    }

    /**
     * Ajoute les paramètres de route à la requête.
     *
     * ==========================================================================
     * TRANSMISSION DES PARAMÈTRES
     * ==========================================================================
     *
     * Cette méthode stocke les paramètres de route extraits dans la requête
     * pour qu'ils soient accessibles dans le contrôleur.
     *
     * ```php
     * // Route: /user/{id}/post/{postId}
     * // URL:   /user/42/post/123
     *
     * // Dans le contrôleur :
     * $userId = $request->getRouteParam('id');      // '42'
     * $postId = $request->getRouteParam('postId'); // '123'
     * ```
     *
     * @param Request              $request La requête HTTP
     * @param array<string, string> $params Les paramètres extraits de la route
     *
     * @return Request La même requête (avec les attributs ajoutés)
     */
    private function addParamsToRequest(Request $request, array $params): Request
    {
        // Stocke tous les paramètres sous l'attribut '_route_params'
        $request->setAttribute('_route_params', $params);

        // Stocke aussi chaque paramètre individuellement pour un accès rapide
        foreach ($params as $name => $value) {
            $request->setAttribute('_route_' . $name, $value);
        }

        return $request;
    }

    /**
     * Appelle la méthode du contrôleur avec les bons paramètres.
     *
     * ==========================================================================
     * INJECTION DES PARAMÈTRES
     * ==========================================================================
     *
     * Cette méthode analyse la signature de la méthode du contrôleur et
     * injecte les paramètres de route correspondants.
     *
     * ```php
     * // Route: /user/{id}
     * // Méthode: public function show(Request $request, string $id)
     *
     * // L'appel sera : $controller->show($request, '42')
     * ```
     *
     * ORDRE DES PARAMÈTRES :
     *
     * 1. Si le premier paramètre est typé Request → passe $request
     * 2. Pour chaque autre paramètre :
     *    - Si son nom correspond à un paramètre de route → utilise la valeur
     *    - Sinon → utilise null ou la valeur par défaut
     *
     * @param object               $controller  L'instance du contrôleur
     * @param string               $action      Le nom de la méthode à appeler
     * @param Request              $request     La requête HTTP
     * @param array<string, string> $routeParams Les paramètres de route
     *
     * @return mixed Le résultat de l'appel au contrôleur
     */
    private function invokeControllerAction(
        object $controller,
        string $action,
        Request $request,
        array $routeParams
    ): mixed {
        // Utilise la Reflection pour analyser les paramètres de la méthode
        $refMethod = new \ReflectionMethod($controller, $action);
        $parameters = $refMethod->getParameters();

        // Construit le tableau des arguments à passer
        $args = [];

        foreach ($parameters as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            // Si c'est le paramètre Request → passe la requête
            if ($paramType instanceof \ReflectionNamedType
                && $paramType->getName() === Request::class) {
                $args[] = $request;
                continue;
            }

            // Si un paramètre de route correspond → utilise sa valeur
            if (isset($routeParams[$paramName])) {
                $args[] = $routeParams[$paramName];
                continue;
            }

            // Si le paramètre a une valeur par défaut → utilise-la
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            // Sinon → null (ou erreur si non nullable)
            if ($paramType instanceof \ReflectionNamedType && $paramType->allowsNull()) {
                $args[] = null;
            } else {
                // Le paramètre est requis mais non fourni → erreur
                $controllerClass = $controller::class;
                throw new \InvalidArgumentException(
                    "Paramètre requis '$paramName' manquant pour $controllerClass::$action()"
                );
            }
        }

        // Appelle la méthode avec les arguments préparés
        return $refMethod->invokeArgs($controller, $args);
    }
}
