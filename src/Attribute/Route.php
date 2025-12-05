<?php
/**
 * Lunar Quanta Framework - Attribut de Définition de Route.
 *
 * =============================================================================
 * QU'EST-CE QU'UN ATTRIBUT PHP ?
 * =============================================================================
 *
 * Un ATTRIBUT (ou annotation) est une MÉTADONNÉE attachée à du code PHP.
 * C'est une façon de "taguer" une classe, une méthode ou une propriété
 * avec des informations supplémentaires.
 *
 * ANALOGIE : Les étiquettes sur les vêtements
 *
 * Imaginez une étiquette sur un vêtement qui indique "Lavage à 30°C".
 * Le vêtement fonctionne sans cette étiquette, mais elle donne des
 * informations utiles sur comment le traiter.
 *
 * ```
 * ATTRIBUTS PHP (depuis PHP 8.0)
 *
 *    Avant PHP 8.0 (annotations PHPDoc) :
 *    /**
 *     * @Route("/users", methods={"GET"})
 *     * /
 *    public function list() { }
 *
 *    Depuis PHP 8.0 (attributs natifs) :
 *    #[Route('/users', methods: ['GET'])]
 *    public function list() { }
 *
 *    → Même concept, mais syntaxe native et typée !
 * ```
 *
 * =============================================================================
 * QUE FAIT L'ATTRIBUT ROUTE ?
 * =============================================================================
 *
 * L'attribut #[Route] permet de définir les routes DIRECTEMENT sur les
 * méthodes des contrôleurs, au lieu de les configurer dans un fichier séparé.
 *
 * AVANTAGES :
 *
 * 1. CO-LOCALISATION : La route est définie là où se trouve le code
 * 2. REFACTORING : Si on déplace le contrôleur, la route suit
 * 3. LISIBILITÉ : On voit immédiatement quelle URL appelle quelle méthode
 *
 * ```
 * COMPARAISON DES APPROCHES
 *
 *    ❌ Configuration séparée (ancien style) :
 *
 *    // routes.php (fichier séparé)
 *    $routes = [
 *        '/users' => ['controller' => UserController::class, 'method' => 'list'],
 *        '/users/{id}' => ['controller' => UserController::class, 'method' => 'show'],
 *    ];
 *
 *    // UserController.php
 *    public function list() { }
 *    public function show($id) { }
 *
 *    → Il faut synchroniser deux fichiers !
 *
 *    ✅ Attributs (moderne) :
 *
 *    // UserController.php (tout au même endroit)
 *    #[Route('/users', methods: ['GET'], name: 'user_list')]
 *    public function list() { }
 *
 *    #[Route('/users/{id}', methods: ['GET'], name: 'user_show')]
 *    public function show($id) { }
 *
 *    → Tout est ensemble, facile à maintenir !
 * ```
 *
 * =============================================================================
 * LES PARAMÈTRES DE L'ATTRIBUT ROUTE
 * =============================================================================
 *
 * ┌─────────────────┬──────────────────────────────────────────────────────────┐
 * │  Paramètre      │  Description                                             │
 * ├─────────────────┼──────────────────────────────────────────────────────────┤
 * │  path           │  Le chemin URL de la route (obligatoire)                 │
 * │                 │  Ex: '/users', '/articles/{id}', '/api/v1/products'      │
 * ├─────────────────┼──────────────────────────────────────────────────────────┤
 * │  methods        │  Les méthodes HTTP acceptées (défaut: ['GET'])           │
 * │                 │  Ex: ['GET'], ['POST'], ['GET', 'POST'], ['DELETE']      │
 * ├─────────────────┼──────────────────────────────────────────────────────────┤
 * │  name           │  Nom unique de la route (optionnel)                      │
 * │                 │  Utile pour générer des URLs: route('user_show', ['id']) │
 * ├─────────────────┼──────────────────────────────────────────────────────────┤
 * │  middlewares    │  Liste des middlewares à appliquer (optionnel)           │
 * │                 │  Ex: [SessionMiddleware::class, AuthMiddleware::class]   │
 * └─────────────────┴──────────────────────────────────────────────────────────┘
 *
 * =============================================================================
 * LES MÉTHODES HTTP EXPLIQUÉES
 * =============================================================================
 *
 * ```
 * MÉTHODES HTTP COURANTES
 *
 *    GET     → Récupérer des données (lecture seule)
 *              Ex: Afficher la liste des utilisateurs
 *
 *    POST    → Créer une nouvelle ressource
 *              Ex: Créer un nouvel utilisateur (formulaire d'inscription)
 *
 *    PUT     → Remplacer entièrement une ressource
 *              Ex: Mettre à jour toutes les infos d'un utilisateur
 *
 *    PATCH   → Modifier partiellement une ressource
 *              Ex: Changer uniquement l'email d'un utilisateur
 *
 *    DELETE  → Supprimer une ressource
 *              Ex: Supprimer un utilisateur
 * ```
 *
 * =============================================================================
 * LES PARAMÈTRES DYNAMIQUES DANS LES ROUTES
 * =============================================================================
 *
 * Les accolades {} définissent des paramètres dynamiques :
 *
 * ```
 * EXEMPLES DE ROUTES DYNAMIQUES
 *
 *    Route                      URL correspondante         Paramètre capturé
 *    ─────                      ───────────────────        ─────────────────
 *    /users/{id}                /users/42                  id = "42"
 *    /articles/{slug}           /articles/mon-article      slug = "mon-article"
 *    /users/{id}/posts/{postId} /users/5/posts/123         id = "5", postId = "123"
 *
 *    Le paramètre est passé à la méthode du contrôleur :
 *
 *    #[Route('/users/{id}')]
 *    public function show(Request $request, string $id): Response
 *    {                                        └── Reçoit "42"
 *        // $id contient la valeur capturée
 *    }
 * ```
 *
 * @package    Lunar\Attribute
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see \Lunar\Service\Core\Router Le routeur qui lit ces attributs
 * @see \Lunar\Service\Core\Middleware\MiddlewareInterface Interface des middlewares
 */
declare(strict_types=1);

namespace Lunar\Attribute;

/**
 * Attribut pour définir les routes sur les méthodes de contrôleur.
 *
 * Cet attribut permet de configurer le routage directement sur les méthodes
 * des contrôleurs en utilisant la syntaxe des attributs PHP 8.
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * class UserController extends BaseController
 * {
 *     // Route simple (GET par défaut)
 *     #[Route('/users')]
 *     public function list(Request $request): Response
 *     {
 *         return new Response('Liste des utilisateurs');
 *     }
 *
 *     // Route avec paramètre dynamique
 *     #[Route('/users/{id}', methods: ['GET'], name: 'user_show')]
 *     public function show(Request $request, string $id): Response
 *     {
 *         return new Response("Utilisateur #$id");
 *     }
 *
 *     // Route POST avec nom
 *     #[Route('/users', methods: ['POST'], name: 'user_create')]
 *     public function create(Request $request): Response
 *     {
 *         $data = $request->getPostParams();
 *         // Créer l'utilisateur...
 *         return new Response('Utilisateur créé', 201);
 *     }
 *
 *     // Route protégée par des middlewares
 *     #[Route('/users/{id}', methods: ['PUT'], name: 'user_update', middlewares: [
 *         SessionMiddleware::class,
 *         AuthMiddleware::class,
 *     ])]
 *     public function update(Request $request, string $id): Response
 *     {
 *         // Seuls les utilisateurs connectés arrivent ici
 *         return new Response("Utilisateur #$id mis à jour");
 *     }
 *
 *     // Route avec plusieurs méthodes
 *     #[Route('/contact', methods: ['GET', 'POST'], name: 'contact')]
 *     public function contact(Request $request): Response
 *     {
 *         if ($request->getMethod() === 'POST') {
 *             // Traiter le formulaire
 *         }
 *         // Afficher le formulaire
 *     }
 * }
 * ```
 *
 * =============================================================================
 * COMMENT LE ROUTEUR UTILISE CET ATTRIBUT ?
 * =============================================================================
 *
 * ```
 * FLUX DE DÉCOUVERTE DES ROUTES
 *
 *    1. Le Router scanne les contrôleurs
 *           │
 *           ▼
 *    2. Pour chaque méthode, il cherche l'attribut #[Route]
 *           │
 *           ▼
 *    3. Il extrait : path, methods, name, middlewares
 *           │
 *           ▼
 *    4. Il enregistre la route dans sa table
 *           │
 *           ▼
 *    5. Quand une requête arrive, il cherche la route correspondante
 *           │
 *           ▼
 *    6. Il exécute les middlewares puis le contrôleur
 * ```
 *
 * @package Lunar\Attribute
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class Route
{
    /**
     * Le chemin URL de la route.
     *
     * Peut contenir des paramètres dynamiques entre accolades :
     * - '/users' : chemin fixe
     * - '/users/{id}' : avec un paramètre
     * - '/articles/{year}/{month}' : plusieurs paramètres
     *
     * @var string
     */
    public string $path;

    /**
     * Les méthodes HTTP autorisées pour cette route.
     *
     * Valeurs courantes :
     * - 'GET' : lecture de données
     * - 'POST' : création de données
     * - 'PUT' : mise à jour complète
     * - 'PATCH' : mise à jour partielle
     * - 'DELETE' : suppression
     *
     * Par défaut : ['GET']
     *
     * @var array<string>
     */
    public array $methods;

    /**
     * Nom unique de la route (optionnel).
     *
     * Utilisé pour :
     * - Générer des URLs : route('user_show', ['id' => 42])
     * - Référencer la route dans les templates
     * - Faciliter les redirections
     *
     * Convention : snake_case, préfixé par le domaine (user_, article_, etc.)
     *
     * @var string|null
     */
    public ?string $name;

    /**
     * Liste des middlewares à appliquer à cette route.
     *
     * Les middlewares sont exécutés AVANT le contrôleur, dans l'ordre.
     *
     * Exemples courants :
     * - SessionMiddleware : démarrer la session
     * - AuthMiddleware : vérifier l'authentification
     * - CsrfMiddleware : vérifier le token CSRF
     *
     * ```php
     * #[Route('/admin', middlewares: [
     *     SessionMiddleware::class,  // 1. Démarre la session
     *     AuthMiddleware::class,     // 2. Vérifie la connexion
     * ])]
     * ```
     *
     * @var array<class-string>
     */
    public array $middlewares;

    /**
     * Crée un nouvel attribut de route.
     *
     * =========================================================================
     * PARAMÈTRES
     * =========================================================================
     *
     * @param string $path Le chemin URL de la route.
     *                     Ex: '/users', '/articles/{slug}'
     *
     * @param array<string> $methods Les méthodes HTTP autorisées.
     *                               Défaut: ['GET']
     *                               Ex: ['POST'], ['GET', 'POST']
     *
     * @param string|null $name Nom unique de la route (optionnel).
     *                          Ex: 'user_list', 'article_show'
     *
     * @param array<class-string> $middlewares Classes de middleware à appliquer.
     *                                         Ex: [SessionMiddleware::class]
     *
     * =========================================================================
     * EXEMPLES
     * =========================================================================
     *
     * ```php
     * // Route GET simple
     * #[Route('/')]
     *
     * // Route GET avec nom
     * #[Route('/about', name: 'about')]
     *
     * // Route POST
     * #[Route('/login', methods: ['POST'], name: 'login_submit')]
     *
     * // Route avec paramètre et middlewares
     * #[Route('/users/{id}/edit', methods: ['GET', 'POST'], name: 'user_edit', middlewares: [
     *     SessionMiddleware::class,
     *     AuthMiddleware::class,
     * ])]
     * ```
     */
    public function __construct(
        string $path,
        array $methods = ['GET'],
        ?string $name = null,
        array $middlewares = []
    ) {
        $this->path = $path;
        $this->methods = $methods;
        $this->name = $name;
        $this->middlewares = $middlewares;
    }
}
