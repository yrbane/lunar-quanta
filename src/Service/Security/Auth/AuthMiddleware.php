<?php
/**
 * Lunar Quanta Framework - Middleware d'Authentification.
 *
 * =============================================================================
 * QUE FAIT CE MIDDLEWARE ?
 * =============================================================================
 *
 * Ce middleware PROTÈGE les routes qui nécessitent une connexion.
 * Il vérifie si l'utilisateur est connecté et bloque l'accès sinon.
 *
 * ANALOGIE : Le vigile à l'entrée d'une boîte de nuit
 *
 * Le vigile (middleware) vérifie si vous êtes sur la liste (connecté).
 * - Si OUI : vous entrez (la requête continue vers le contrôleur)
 * - Si NON : vous êtes refoulé (redirection vers login ou erreur 401)
 *
 * ```
 * FLUX DE PROTECTION
 *
 *    Requête vers /dashboard
 *           │
 *           ▼
 *    ┌─────────────────────────────────────┐
 *    │       AuthMiddleware                │
 *    │                                     │
 *    │    L'utilisateur est connecté ?     │
 *    │                                     │
 *    │    ├── OUI → Continue               │───────────────────┐
 *    │    │                                │                   │
 *    │    └── NON → Bloque                 │                   │
 *    │              │                      │                   │
 *    │              ├── URL de redirection │                   │
 *    │              │   définie ?          │                   │
 *    │              │   ├── OUI → 302      │                   │
 *    │              │   │   vers /login    │                   │
 *    │              │   │                  │                   │
 *    │              │   └── NON → 401      │                   │
 *    │              │       Unauthorized   │                   │
 *    └──────────────┼──────────────────────┘                   │
 *                   │                                          │
 *                   │                              ┌───────────┘
 *                   │                              │
 *                   │                              ▼
 *                   │                    ┌─────────────────────┐
 *                   │                    │    Contrôleur       │
 *                   │                    │    dashboard()      │
 *                   │                    └─────────────────────┘
 *                   │                              │
 *                   │                              ▼
 *                   │                         Réponse OK
 *                   │
 *                   ▼
 *          Réponse de blocage
 *          (401 ou redirection)
 * ```
 *
 * =============================================================================
 * DEUX MODES DE BLOCAGE
 * =============================================================================
 *
 * Ce middleware peut réagir de deux façons quand l'utilisateur n'est pas
 * connecté :
 *
 * ┌─────────────────────┬────────────────────────────────────────────────────┐
 * │  Mode               │  Comportement                                      │
 * ├─────────────────────┼────────────────────────────────────────────────────┤
 * │  Redirection        │  Redirige vers une URL (ex: /login)                │
 * │  (redirectUrl)      │  Idéal pour les applications web avec formulaire   │
 * │                     │  → L'utilisateur voit la page de connexion         │
 * ├─────────────────────┼────────────────────────────────────────────────────┤
 * │  Erreur 401         │  Retourne une erreur HTTP 401 Unauthorized         │
 * │  (pas de redirect)  │  Idéal pour les API                                │
 * │                     │  → Le client sait qu'il doit s'authentifier        │
 * └─────────────────────┴────────────────────────────────────────────────────┘
 *
 * =============================================================================
 * CODES HTTP EXPLIQUÉS
 * =============================================================================
 *
 * 302 (Found / Redirect)
 * ----------------------
 * "La ressource demandée a été temporairement déplacée."
 * Le navigateur SUIT automatiquement le header "Location" et charge la
 * nouvelle URL (la page de login).
 *
 * 401 (Unauthorized)
 * ------------------
 * "Authentification requise."
 * Le client doit s'authentifier pour obtenir la réponse demandée.
 * Pour les API, le client sait qu'il doit fournir des credentials.
 *
 * ```
 * QUAND UTILISER QUOI ?
 *
 *    Application Web (navigateur)
 *    ────────────────────────────
 *    → Utiliser redirectUrl vers /login
 *    → L'utilisateur voit le formulaire de connexion
 *
 *    API REST (fetch, axios, mobile)
 *    ──────────────────────────────
 *    → Utiliser 401 sans redirection
 *    → Le client gère l'erreur et demande les credentials
 * ```
 *
 * @package    Lunar\Service\Security\Auth
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see Authenticator Service d'authentification utilisé
 * @see MiddlewareInterface Interface des middlewares
 * @see SessionMiddleware Doit être exécuté AVANT ce middleware
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;

/**
 * Middleware qui exige une authentification pour accéder aux routes protégées.
 *
 * Ce middleware bloque les utilisateurs non connectés avec soit :
 * - Une redirection vers une page de login (applications web)
 * - Une réponse 401 Unauthorized (APIs)
 *
 * =============================================================================
 * UTILISATION DANS LES ROUTES
 * =============================================================================
 *
 * ```php
 * // Protection d'une route unique
 * #[Route('/dashboard', name: 'dashboard', middlewares: [
 *     SessionMiddleware::class,  // Session AVANT Auth !
 *     AuthMiddleware::class,     // Protection authentification
 * ])]
 * public function dashboard(Request $request): Response
 * {
 *     // Si on arrive ici, l'utilisateur est connecté
 *     $user = $request->getAttribute('user');
 *     return new Response("Bienvenue " . $user->getIdentifier());
 * }
 * ```
 *
 * =============================================================================
 * ACCÈS À L'UTILISATEUR DANS LE CONTRÔLEUR
 * =============================================================================
 *
 * Le middleware attache automatiquement l'utilisateur et l'authenticator
 * à la requête :
 *
 * ```php
 * public function profile(Request $request): Response
 * {
 *     // L'utilisateur connecté (objet UserInterface)
 *     $user = $request->getAttribute('user');
 *
 *     // Le service d'authentification complet
 *     $auth = $request->getAttribute('auth');
 *
 *     // Utilisation
 *     echo $user->getIdentifier();       // Email
 *     echo $user->getId();               // ID
 *     print_r($user->getRoles());        // Rôles
 *
 *     // Vérification des rôles
 *     if (in_array('ROLE_ADMIN', $user->getRoles())) {
 *         // Afficher les options admin
 *     }
 * }
 * ```
 *
 * =============================================================================
 * ORDRE DES MIDDLEWARES
 * =============================================================================
 *
 * L'ordre est IMPORTANT ! AuthMiddleware a besoin de la session pour
 * vérifier si l'utilisateur est connecté.
 *
 * ```php
 * // ✅ BON ORDRE
 * middlewares: [
 *     SessionMiddleware::class,  // 1. Démarre la session
 *     AuthMiddleware::class,     // 2. Vérifie la connexion (utilise la session)
 * ]
 *
 * // ❌ MAUVAIS ORDRE
 * middlewares: [
 *     AuthMiddleware::class,     // Erreur ! Pas de session disponible
 *     SessionMiddleware::class,
 * ]
 * ```
 *
 * @package Lunar\Service\Security\Auth
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Service d'authentification.
     *
     * Utilisé pour vérifier si un utilisateur est connecté
     * et récupérer ses informations.
     *
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * URL de redirection pour les utilisateurs non connectés.
     *
     * Si définie, les visiteurs sont redirigés vers cette URL.
     * Si null, ils reçoivent une erreur 401 Unauthorized.
     *
     * @var string|null
     */
    private ?string $redirectUrl;

    /**
     * Crée un nouveau middleware d'authentification.
     *
     * =========================================================================
     * PARAMÈTRES
     * =========================================================================
     *
     * @param Authenticator $authenticator Le service d'authentification.
     *                                     Doit être configuré avec un UserProvider,
     *                                     un PasswordHasher et une Session.
     *
     * @param string|null $redirectUrl URL de redirection si non connecté.
     *                                 - Définie : redirige vers cette URL (302)
     *                                 - null : retourne une erreur 401
     *
     * =========================================================================
     * EXEMPLES DE CONFIGURATION
     * =========================================================================
     *
     * ```php
     * // Pour une application web (redirection vers login)
     * $authMiddleware = new AuthMiddleware($authenticator, '/login');
     *
     * // Pour une API (erreur 401)
     * $authMiddleware = new AuthMiddleware($authenticator);  // Pas de redirectUrl
     *
     * // Avec message d'erreur personnalisé, utilisez une URL avec paramètre
     * $authMiddleware = new AuthMiddleware(
     *     $authenticator,
     *     '/login?error=auth_required'
     * );
     * ```
     *
     * =========================================================================
     * INJECTION VIA LE CONTAINER
     * =========================================================================
     *
     * ```php
     * // Configuration dans le conteneur de services
     * $container->register(AuthMiddleware::class, function($c) {
     *     return new AuthMiddleware(
     *         $c->get(Authenticator::class),
     *         '/login'  // URL de redirection
     *     );
     * });
     * ```
     */
    public function __construct(Authenticator $authenticator, ?string $redirectUrl = null)
    {
        $this->authenticator = $authenticator;
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * Traite la requête en vérifiant l'authentification.
     *
     * =========================================================================
     * QUE FAIT CETTE MÉTHODE ?
     * =========================================================================
     *
     * 1. Vérifie si l'utilisateur est un "guest" (non connecté)
     * 2. Si guest : bloque (redirection ou 401)
     * 3. Si connecté : attache l'utilisateur à la requête et continue
     *
     * ```
     * FLUX D'EXÉCUTION
     *
     *    process($request, $next)
     *              │
     *              ▼
     *    authenticator->guest() ?
     *              │
     *    ┌─────────┴─────────┐
     *    │                   │
     *   OUI                 NON
     * (pas connecté)    (connecté)
     *    │                   │
     *    ▼                   ▼
     * redirectUrl          Attache user à $request
     * définie ?            Attache auth à $request
     *    │                   │
     *  ┌─┴───┐               ▼
     * OUI   NON         return $next($request)
     *  │     │          (continue vers le contrôleur)
     *  │     │
     *  ▼     ▼
     * 302   401
     * vers  Unauthorized
     * login
     * ```
     *
     * =========================================================================
     * ATTRIBUTS ATTACHÉS À LA REQUÊTE
     * =========================================================================
     *
     * Quand l'utilisateur est connecté, le middleware attache :
     *
     * ```php
     * // L'utilisateur (UserInterface)
     * $request->setAttribute('user', $user);
     *
     * // Le service d'authentification
     * $request->setAttribute('auth', $this->authenticator);
     * ```
     *
     * Ces attributs sont ensuite accessibles dans le contrôleur :
     *
     * ```php
     * public function dashboard(Request $request): Response
     * {
     *     $user = $request->getAttribute('user');
     *     $auth = $request->getAttribute('auth');
     *     // ...
     * }
     * ```
     *
     * @param Request $request La requête HTTP entrante.
     *
     * @param callable(Request): Response $next Le handler suivant
     *                                          (autre middleware ou contrôleur).
     *
     * @return Response La réponse HTTP :
     *                  - Redirection (302) si non connecté et redirectUrl définie
     *                  - Erreur (401) si non connecté et pas de redirectUrl
     *                  - Réponse du contrôleur si connecté
     */
    public function process(Request $request, callable $next): Response
    {
        // 1. Vérifie si l'utilisateur est un visiteur (non connecté)
        if ($this->authenticator->guest()) {
            // Non connecté → bloquer l'accès

            // Si une URL de redirection est définie
            if (null !== $this->redirectUrl) {
                // Redirection HTTP 302 vers la page de login
                // Le navigateur suivra automatiquement ce header
                return new Response('', 302, ['Location: ' . $this->redirectUrl]);
            }

            // Pas de redirection → erreur 401 (pour les APIs)
            return new Response('Unauthorized', 401);
        }

        // 2. L'utilisateur est connecté → attacher ses infos à la requête
        $user = $this->authenticator->user();

        // Attache l'utilisateur pour un accès facile dans le contrôleur
        // Le contrôleur peut faire : $request->getAttribute('user')
        $request->setAttribute('user', $user);

        // Attache aussi l'authenticator (utile pour vérifier les rôles, etc.)
        // Le contrôleur peut faire : $request->getAttribute('auth')
        $request->setAttribute('auth', $this->authenticator);

        // 3. Continue vers le middleware suivant ou le contrôleur
        return $next($request);
    }
}
