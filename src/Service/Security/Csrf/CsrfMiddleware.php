<?php
/**
 * Lunar Quanta Framework - Middleware de Protection CSRF.
 *
 * =============================================================================
 * QUE FAIT CE MIDDLEWARE ?
 * =============================================================================
 *
 * Ce middleware protège automatiquement votre application contre les
 * attaques CSRF (Cross-Site Request Forgery) en :
 *
 * 1. LAISSANT PASSER les requêtes "sûres" (GET, HEAD, OPTIONS)
 * 2. VÉRIFIANT le token CSRF sur les requêtes "dangereuses" (POST, PUT, DELETE)
 * 3. REJETANT les requêtes sans token valide (403 Forbidden)
 *
 * =============================================================================
 * MÉTHODES HTTP : SÛRES vs DANGEREUSES
 * =============================================================================
 *
 * Les méthodes HTTP sont classées en deux catégories :
 *
 * MÉTHODES SÛRES (idempotentes, sans effet de bord)
 * ─────────────────────────────────────────────────
 * - GET : Récupère des données (ne modifie rien)
 * - HEAD : Comme GET mais sans corps de réponse
 * - OPTIONS : Demande les options de communication
 * - TRACE : Diagnostic (rarement utilisé)
 *
 * → Ces méthodes NE DEVRAIENT PAS modifier des données
 * → Pas besoin de protection CSRF
 *
 * MÉTHODES DANGEREUSES (peuvent modifier des données)
 * ────────────────────────────────────────────────────
 * - POST : Crée une ressource
 * - PUT : Remplace une ressource
 * - PATCH : Modifie partiellement une ressource
 * - DELETE : Supprime une ressource
 *
 * → Ces méthodes MODIFIENT des données
 * → Nécessitent une protection CSRF
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
 *  │   CsrfMiddleware                │
 *  │                                 │
 *  │   Méthode = GET ?               │
 *  │   ├── OUI → Passe directement   │─────────┐
 *  │   │                             │         │
 *  │   └── NON → Vérifie le token    │         │
 *  │       │                         │         │
 *  │       ├── Token valide ?        │         │
 *  │       │   ├── OUI → Continue    │─────────┤
 *  │       │   │                     │         │
 *  │       │   └── NON → 403 Forbidden        │
 *  │       │         │               │         │
 *  │       │         ▼               │         │
 *  │       │   return Response(403)  │         │
 *  │       │                         │         │
 *  └───────│─────────────────────────┘         │
 *          │                                   │
 *          └───────────────────────────────────┘
 *                        │
 *                        ▼
 *                  Contrôleur
 *                        │
 *                        ▼
 *                  Réponse HTTP
 * ```
 *
 * =============================================================================
 * OÙ CHERCHE-T-IL LE TOKEN ?
 * =============================================================================
 *
 * Le middleware cherche le token dans cet ordre :
 *
 * 1. HEADER HTTP : X-CSRF-Token
 *    Utile pour les requêtes AJAX/fetch
 *
 * 2. CORPS DE LA REQUÊTE : _csrf_token
 *    Utile pour les formulaires HTML classiques
 *
 * ```html
 * <!-- Formulaire HTML classique -->
 * <form method="POST">
 *     <input type="hidden" name="_csrf_token" value="abc123...">
 *     <button type="submit">Envoyer</button>
 * </form>
 * ```
 *
 * ```javascript
 * // Requête AJAX avec header
 * fetch('/api/resource', {
 *     method: 'POST',
 *     headers: {
 *         'X-CSRF-Token': 'abc123...',
 *         'Content-Type': 'application/json'
 *     },
 *     body: JSON.stringify(data)
 * });
 * ```
 *
 * @package    Lunar\Service\Security\Csrf
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see CsrfTokenManagerInterface Gestionnaire de tokens
 * @see CsrfTokenManager Implémentation du gestionnaire
 * @see SessionMiddleware Doit être exécuté AVANT ce middleware
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Csrf;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;
use Lunar\Service\Session\SessionInterface;

/**
 * Middleware qui valide les tokens CSRF sur les requêtes modifiantes.
 *
 * Ce middleware protège les requêtes POST, PUT, PATCH et DELETE en exigeant
 * un token CSRF valide dans le corps de la requête ou les headers.
 *
 * =============================================================================
 * UTILISATION AVEC LES ROUTES
 * =============================================================================
 *
 * ```php
 * // Protection CSRF sur une route
 * #[Route('/contact', name: 'contact_submit', methods: ['POST'], middlewares: [
 *     SessionMiddleware::class,  // Session AVANT CSRF !
 *     CsrfMiddleware::class,     // Protection CSRF
 * ])]
 * public function submitContact(Request $request): Response
 * {
 *     // Si on arrive ici, le token CSRF est valide
 *     $data = $request->getPostParams();
 *     // ... traiter le formulaire
 * }
 * ```
 *
 * =============================================================================
 * GÉNÉRATION DU TOKEN DANS LE CONTRÔLEUR
 * =============================================================================
 *
 * ```php
 * // Affichage du formulaire (GET)
 * #[Route('/contact', name: 'contact_form', methods: ['GET'], middlewares: [
 *     SessionMiddleware::class,
 * ])]
 * public function showContactForm(Request $request): Response
 * {
 *     // Récupère le gestionnaire CSRF attaché par le middleware
 *     $csrf = $request->getAttribute('csrf');
 *
 *     // Génère un token pour ce formulaire
 *     $token = $csrf->generate('csrf');  // 'csrf' = identifiant par défaut
 *
 *     // Inclut le token dans le HTML
 *     $html = '<form method="POST">
 *         <input type="hidden" name="_csrf_token" value="' . $token . '">
 *         <textarea name="message"></textarea>
 *         <button>Envoyer</button>
 *     </form>';
 *
 *     return new Response($html);
 * }
 * ```
 *
 * @package Lunar\Service\Security\Csrf
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Nom du champ de formulaire contenant le token.
     *
     * ```html
     * <input type="hidden" name="_csrf_token" value="abc123...">
     * ```
     *
     * @var string
     */
    public const TOKEN_FIELD = '_csrf_token';

    /**
     * Nom du header HTTP contenant le token.
     *
     * ```
     * X-CSRF-Token: abc123...
     * ```
     *
     * @var string
     */
    public const TOKEN_HEADER = 'X-CSRF-Token';

    /**
     * Identifiant par défaut pour les tokens.
     *
     * Si vous utilisez un seul token par session (cas le plus courant),
     * vous pouvez utiliser cet identifiant par défaut.
     *
     * @var string
     */
    public const TOKEN_ID = 'csrf';

    /**
     * Gestionnaire de tokens CSRF.
     *
     * @var CsrfTokenManagerInterface
     */
    private CsrfTokenManagerInterface $tokenManager;

    /**
     * Méthodes HTTP qui ne nécessitent pas de vérification CSRF.
     *
     * Ces méthodes sont considérées comme "sûres" car elles ne devraient
     * pas modifier des données côté serveur.
     *
     * @var array<string>
     */
    private array $safeMethods = ['GET', 'HEAD', 'OPTIONS', 'TRACE'];

    /**
     * Crée une nouvelle instance du middleware CSRF.
     *
     * @param CsrfTokenManagerInterface|null $tokenManager Le gestionnaire de tokens.
     *                                                     Si null, crée un gestionnaire
     *                                                     par défaut qui nécessite une session.
     *
     * @example Avec injection manuelle
     * ```php
     * $session = $request->getAttribute('session');
     * $middleware = new CsrfMiddleware(new CsrfTokenManager($session));
     * ```
     *
     * @example Avec la factory statique
     * ```php
     * $middleware = CsrfMiddleware::withSession($session);
     * ```
     */
    public function __construct(?CsrfTokenManagerInterface $tokenManager = null)
    {
        $this->tokenManager = $tokenManager ?? $this->createDefaultTokenManager();
    }

    /**
     * {@inheritdoc}
     *
     * Vérifie le token CSRF pour les requêtes modifiantes.
     */
    public function process(Request $request, callable $next): Response
    {
        // Les méthodes "sûres" n'ont pas besoin de vérification CSRF
        // car elles ne devraient pas modifier de données
        if (in_array($request->getMethod(), $this->safeMethods, true)) {
            // Attache quand même le gestionnaire pour générer des tokens
            $this->attachTokenManager($request);
            return $next($request);
        }

        // Pour les méthodes "dangereuses" (POST, PUT, PATCH, DELETE),
        // on exige un token CSRF valide
        $token = $this->extractToken($request);
        if (!$this->tokenManager->isValid(self::TOKEN_ID, $token)) {
            // Token invalide ou absent → requête rejetée !
            return new Response('CSRF token mismatch', 403);
        }

        // Token valide → continue vers le contrôleur
        $this->attachTokenManager($request);
        return $next($request);
    }

    /**
     * Extrait le token CSRF de la requête.
     *
     * Cherche le token dans :
     * 1. Le header X-CSRF-Token
     * 2. Le corps de la requête (champ _csrf_token)
     *
     * @param Request $request La requête HTTP.
     *
     * @return string Le token trouvé, ou chaîne vide si absent.
     */
    private function extractToken(Request $request): string
    {
        // 1. Vérifie d'abord les headers
        $headers = $request->getHeaders();
        foreach ($headers as $key => $value) {
            // Header au format clé => valeur
            if (is_string($key) && strtolower($key) === strtolower(self::TOKEN_HEADER)) {
                return is_string($value) ? $value : '';
            }
            // Header au format "X-CSRF-Token: value" dans la valeur
            if (is_string($value) && str_starts_with(strtolower($value), strtolower(self::TOKEN_HEADER . ':'))) {
                return trim(substr($value, strlen(self::TOKEN_HEADER) + 1));
            }
        }

        // 2. Vérifie $_SERVER pour le header (variante HTTP_X_CSRF_TOKEN)
        $serverParams = $request->getServerParams();
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', self::TOKEN_HEADER));
        if (isset($serverParams[$headerKey]) && is_string($serverParams[$headerKey])) {
            return $serverParams[$headerKey];
        }

        // 3. Vérifie le corps de la requête (formulaire POST)
        $postParams = $request->getPostParams();
        $token = $postParams[self::TOKEN_FIELD] ?? '';
        return is_string($token) ? $token : '';
    }

    /**
     * Attache le gestionnaire de tokens à la requête.
     *
     * Permet aux contrôleurs de générer des tokens via :
     * $csrf = $request->getAttribute('csrf');
     *
     * @param Request $request La requête HTTP.
     *
     * @return void
     */
    private function attachTokenManager(Request $request): void
    {
        $request->setAttribute('csrf', $this->tokenManager);
    }

    /**
     * Crée un gestionnaire de tokens par défaut.
     *
     * Ce gestionnaire lance une exception si utilisé sans session.
     * Il sert de "fail-safe" pour rappeler qu'il faut configurer
     * correctement le middleware.
     *
     * @return CsrfTokenManagerInterface Un gestionnaire qui échoue.
     */
    private function createDefaultTokenManager(): CsrfTokenManagerInterface
    {
        // Classe anonyme qui lance des erreurs explicatives
        return new class implements CsrfTokenManagerInterface {
            public function generate(string $tokenId): string
            {
                throw new \RuntimeException(
                    'CSRF token manager requires a session. ' .
                    'Either inject CsrfTokenManager with a session or ensure SessionMiddleware runs first.'
                );
            }

            public function isValid(string $tokenId, string $token): bool
            {
                throw new \RuntimeException(
                    'CSRF token manager requires a session. ' .
                    'Either inject CsrfTokenManager with a session or ensure SessionMiddleware runs first.'
                );
            }

            public function remove(string $tokenId): void
            {
                throw new \RuntimeException(
                    'CSRF token manager requires a session. ' .
                    'Either inject CsrfTokenManager with a session or ensure SessionMiddleware runs first.'
                );
            }
        };
    }

    /**
     * Crée un middleware CSRF avec un gestionnaire basé sur la session.
     *
     * Factory method qui simplifie la création du middleware
     * avec une session déjà disponible.
     *
     * @param SessionInterface $session Le service de session.
     *
     * @return self Une nouvelle instance configurée.
     *
     * @example
     * ```php
     * // Dans la configuration des middlewares
     * $session = new SessionService();
     * $session->start();
     *
     * $csrfMiddleware = CsrfMiddleware::withSession($session);
     * ```
     */
    public static function withSession(SessionInterface $session): self
    {
        return new self(new CsrfTokenManager($session));
    }
}
