<?php
/**
 * Lunar Quanta Framework - Middleware de Session.
 *
 * =============================================================================
 * QUE FAIT CE MIDDLEWARE ?
 * =============================================================================
 *
 * Ce middleware s'occupe automatiquement de :
 *
 * 1. DÉMARRER la session PHP au début de chaque requête
 * 2. ATTACHER le service de session à la requête (via les attributs)
 *
 * Grâce à ce middleware, les contrôleurs peuvent accéder à la session
 * sans avoir à la démarrer manuellement.
 *
 * =============================================================================
 * POURQUOI UN MIDDLEWARE POUR LA SESSION ?
 * =============================================================================
 *
 * Sans middleware, chaque contrôleur devrait :
 * ```php
 * // ❌ Répétitif et source d'erreurs
 * public function action(Request $request): Response
 * {
 *     $session = new SessionService();
 *     $session->start();
 *     $userId = $session->get('user_id');
 *     // ...
 * }
 * ```
 *
 * Avec le middleware :
 * ```php
 * // ✅ Simple et propre
 * public function action(Request $request): Response
 * {
 *     $session = $request->getAttribute('session');
 *     $userId = $session->get('user_id');
 *     // ...
 * }
 * ```
 *
 * =============================================================================
 * FLUX D'EXÉCUTION
 * =============================================================================
 *
 * ```
 *  Requête HTTP
 *      │
 *      ▼
 *  ┌─────────────────────────────────┐
 *  │      SessionMiddleware          │
 *  │  1. Démarre la session          │
 *  │  2. Attache à la requête        │
 *  └─────────────────────────────────┘
 *      │
 *      │ $request->setAttribute('session', $session)
 *      │
 *      ▼
 *  ┌─────────────────────────────────┐
 *  │   Autres middlewares            │
 *  │   (Auth, CSRF, etc.)            │
 *  │   → Peuvent utiliser            │
 *  │     $request->getAttribute      │
 *  │     ('session')                 │
 *  └─────────────────────────────────┘
 *      │
 *      ▼
 *  ┌─────────────────────────────────┐
 *  │      Contrôleur                 │
 *  │   $session = $request->         │
 *  │     getAttribute('session');    │
 *  │   $userId = $session->          │
 *  │     get('user_id');             │
 *  └─────────────────────────────────┘
 *      │
 *      ▼
 *  Réponse HTTP
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UN ATTRIBUT DE REQUÊTE ?
 * =============================================================================
 *
 * Les ATTRIBUTS sont des données attachées à l'objet Request pendant
 * son traitement. Ils ne viennent pas du client (comme GET, POST, headers)
 * mais sont ajoutés par le code serveur.
 *
 * C'est un moyen de PARTAGER des données entre les middlewares
 * et les contrôleurs sans utiliser de variables globales.
 *
 * ```php
 * // Le middleware ajoute un attribut
 * $request->setAttribute('session', $sessionService);
 *
 * // Le contrôleur récupère l'attribut
 * $session = $request->getAttribute('session');
 * ```
 *
 * @package    Lunar\Service\Session
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see SessionInterface Interface du service de session
 * @see SessionService Implémentation du service de session
 * @see MiddlewareInterface Interface que ce middleware implémente
 */
declare(strict_types=1);

namespace Lunar\Service\Session;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;

/**
 * Middleware qui démarre la session et la rend accessible via les attributs de requête.
 *
 * Ce middleware doit être ajouté en premier dans la pile de middlewares
 * car les autres middlewares (Auth, CSRF) dépendent de la session.
 *
 * =============================================================================
 * UTILISATION DANS LA DÉFINITION DES ROUTES
 * =============================================================================
 *
 * ```php
 * // Dans un contrôleur, avec l'attribut #[Route]
 *
 * #[Route('/dashboard', name: 'dashboard', middlewares: [
 *     SessionMiddleware::class,  // Session en premier !
 *     AuthMiddleware::class,     // Auth a besoin de la session
 * ])]
 * public function dashboard(Request $request): Response
 * {
 *     $session = $request->getAttribute('session');
 *     $user = $session->get('user');
 *
 *     return new Response('Bienvenue ' . $user['name']);
 * }
 * ```
 *
 * =============================================================================
 * AVEC INJECTION DE DÉPENDANCES
 * =============================================================================
 *
 * ```php
 * // On peut injecter un service de session personnalisé
 *
 * // Session en mémoire pour les tests
 * $sessionMiddleware = new SessionMiddleware(
 *     new SessionService(testMode: true)
 * );
 *
 * // Ou utiliser le service par défaut
 * $sessionMiddleware = new SessionMiddleware();  // SessionService par défaut
 * ```
 *
 * @package Lunar\Service\Session
 */
class SessionMiddleware implements MiddlewareInterface
{
    /**
     * Service de gestion des sessions.
     *
     * Ce service est utilisé pour démarrer la session et sera
     * attaché à la requête pour être utilisé par les autres middlewares
     * et contrôleurs.
     *
     * @var SessionInterface
     */
    private SessionInterface $session;

    /**
     * Crée une nouvelle instance du middleware de session.
     *
     * =========================================================================
     * INJECTION DE DÉPENDANCES OPTIONNELLE
     * =========================================================================
     *
     * Le constructeur accepte un service de session en paramètre.
     * Si aucun n'est fourni, il crée un SessionService par défaut.
     *
     * C'est le pattern "Injection de Dépendances avec Fallback" :
     * - En production : on laisse le défaut
     * - En test : on injecte un mock ou une implémentation de test
     *
     * @param SessionInterface|null $session Le service de session à utiliser.
     *                                       Si null, crée un SessionService.
     *
     * @example Production (service par défaut)
     * ```php
     * $middleware = new SessionMiddleware();
     * ```
     *
     * @example Tests (session en mémoire)
     * ```php
     * $middleware = new SessionMiddleware(
     *     new SessionService(testMode: true)
     * );
     * ```
     *
     * @example Custom (session Redis, par exemple)
     * ```php
     * $middleware = new SessionMiddleware(
     *     new RedisSessionService($redisClient)
     * );
     * ```
     */
    public function __construct(?SessionInterface $session = null)
    {
        // Utilise le service fourni ou crée un SessionService par défaut
        // L'opérateur ?? est le "null coalescing operator"
        // Si $session est null, utilise la valeur après ??
        $this->session = $session ?? new SessionService();
    }

    /**
     * Traite la requête en démarrant la session.
     *
     * =========================================================================
     * ACTIONS EFFECTUÉES
     * =========================================================================
     *
     * 1. Démarre la session (session_start() en interne)
     * 2. Attache le service de session à la requête
     * 3. Passe au middleware/contrôleur suivant
     *
     * Le service de session devient accessible via :
     * ```php
     * $session = $request->getAttribute('session');
     * ```
     *
     * =========================================================================
     * EXEMPLE D'UTILISATION DANS UN CONTRÔLEUR
     * =========================================================================
     *
     * ```php
     * public function dashboard(Request $request): Response
     * {
     *     // Récupère la session depuis les attributs de la requête
     *     /** @var SessionInterface $session * /
     *     $session = $request->getAttribute('session');
     *
     *     // Utilise la session
     *     $userId = $session->get('user_id');
     *     if (!$userId) {
     *         return new Response('', 302, ['Location: /login']);
     *     }
     *
     *     // Affiche un message flash s'il existe
     *     $message = $session->getFlash('success');
     *
     *     // ...
     * }
     * ```
     *
     * @param Request $request La requête HTTP entrante.
     *
     * @param callable(Request): Response $next Le handler suivant
     *                                          (autre middleware ou contrôleur).
     *
     * @return Response La réponse HTTP générée par la chaîne.
     */
    public function process(Request $request, callable $next): Response
    {
        // 1. Démarre la session PHP
        //    Ceci charge les données de session existantes ou crée une nouvelle session
        $this->session->start();

        // 2. Attache le service de session à la requête
        //    Les autres middlewares et contrôleurs pourront y accéder via getAttribute()
        $request->setAttribute('session', $this->session);

        // 3. Passe au middleware/contrôleur suivant
        //    La requête contient maintenant l'attribut 'session'
        return $next($request);
    }
}
