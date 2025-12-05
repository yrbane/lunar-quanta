<?php
/**
 * Lunar Quanta Framework - Interface Middleware HTTP.
 *
 * =============================================================================
 * QU'EST-CE QU'UN MIDDLEWARE ?
 * =============================================================================
 *
 * Un MIDDLEWARE (littéralement "logiciel intermédiaire") est un composant
 * qui s'intercale entre la requête entrante et la réponse sortante.
 *
 * ANALOGIE : Pensez à une chaîne de contrôle à l'aéroport
 *
 * ```
 *  VOYAGEUR (Requête)
 *      │
 *      ▼
 *  ┌─────────────────────────────────┐
 *  │ Contrôle passeport              │ ← Middleware 1 (AuthMiddleware)
 *  │ "Avez-vous vos papiers ?"       │
 *  └─────────────────────────────────┘
 *      │ OK → Continue
 *      ▼
 *  ┌─────────────────────────────────┐
 *  │ Contrôle sécurité               │ ← Middleware 2 (CsrfMiddleware)
 *  │ "Pas d'objets interdits ?"      │
 *  └─────────────────────────────────┘
 *      │ OK → Continue
 *      ▼
 *  ┌─────────────────────────────────┐
 *  │ Embarquement                    │ ← Contrôleur (destination finale)
 *  │ "Bienvenue à bord !"            │
 *  └─────────────────────────────────┘
 *      │
 *      ▼
 *  AVION DÉCOLLE (Réponse)
 * ```
 *
 * Chaque contrôle peut soit :
 * - LAISSER PASSER vers le contrôle suivant
 * - REFUSER l'accès (court-circuiter la chaîne)
 *
 * =============================================================================
 * EXEMPLES DE MIDDLEWARES COURANTS
 * =============================================================================
 *
 * ┌─────────────────────────────────────────────────────────────────────────────┐
 * │ MIDDLEWARE               │ RÔLE                                            │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ SessionMiddleware        │ Démarre/restaure la session PHP                 │
 * │                          │ Injecte les données de session dans la requête  │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ AuthMiddleware           │ Vérifie que l'utilisateur est connecté          │
 * │                          │ Redirige vers /login si non authentifié         │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ CsrfMiddleware           │ Vérifie le jeton CSRF sur les requêtes POST     │
 * │                          │ Protège contre les attaques CSRF                │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ RateLimitMiddleware      │ Limite le nombre de requêtes par utilisateur    │
 * │                          │ Protège contre les abus et DDoS                 │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ LoggingMiddleware        │ Enregistre chaque requête dans les logs         │
 * │                          │ Utile pour le débogage et l'audit               │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ CorsMiddleware           │ Ajoute les headers CORS pour les API            │
 * │                          │ Permet les requêtes cross-origin                │
 * └─────────────────────────────────────────────────────────────────────────────┘
 *
 * =============================================================================
 * PATTERN "CHAIN OF RESPONSIBILITY"
 * =============================================================================
 *
 * Les middlewares implémentent le patron de conception "Chain of Responsibility"
 * (Chaîne de responsabilité).
 *
 * PRINCIPE :
 * - Chaque maillon de la chaîne peut traiter la requête
 * - Chaque maillon peut passer au maillon suivant
 * - Chaque maillon peut court-circuiter et retourner directement
 *
 * ```
 *  ┌─────────────────────────────────────────────────────────────────────────┐
 *  │                    FLUX D'UNE REQUÊTE                                   │
 *  │                                                                         │
 *  │  Requête ──► Middleware A ──► Middleware B ──► Contrôleur               │
 *  │                                                    │                    │
 *  │  Réponse ◄── Middleware A ◄── Middleware B ◄──────┘                    │
 *  │                                                                         │
 *  │  Les middlewares peuvent modifier la requête AVANT le contrôleur        │
 *  │  Les middlewares peuvent modifier la réponse APRÈS le contrôleur        │
 *  └─────────────────────────────────────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UNE INTERFACE ? (Concept POO)
 * =============================================================================
 *
 * Une INTERFACE est un CONTRAT. Elle définit QUELLES méthodes une classe
 * doit implémenter, mais PAS COMMENT les implémenter.
 *
 * ANALOGIE : Un formulaire à remplir
 * - Le formulaire définit les champs obligatoires (interface)
 * - Vous remplissez les champs avec vos informations (implémentation)
 * - Tous les formulaires remplis ont la même structure
 *
 * ```php
 * // Interface = Contrat
 * interface MiddlewareInterface
 * {
 *     public function process(Request $request, callable $next): Response;
 * }
 *
 * // Implémentation = Réalisation du contrat
 * class AuthMiddleware implements MiddlewareInterface
 * {
 *     public function process(Request $request, callable $next): Response
 *     {
 *         // Ma propre logique d'authentification
 *         if (!$this->isAuthenticated($request)) {
 *             return new Response('', 302, ['Location: /login']);
 *         }
 *         return $next($request);
 *     }
 * }
 * ```
 *
 * POURQUOI UTILISER UNE INTERFACE ?
 * - GARANTIE : Tous les middlewares ont la même signature
 * - INTERCHANGEABILITÉ : On peut remplacer un middleware par un autre
 * - TESTABILITÉ : On peut créer des mocks pour les tests
 *
 * @package    Lunar\Service\Core\Middleware
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see MiddlewareStack Classe qui gère l'exécution des middlewares
 * @see Request L'objet représentant la requête HTTP
 * @see Response L'objet représentant la réponse HTTP
 */
declare(strict_types=1);

namespace Lunar\Service\Core\Middleware;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;

/**
 * Interface pour les middlewares HTTP.
 *
 * Tous les middlewares du framework DOIVENT implémenter cette interface.
 * Elle garantit que chaque middleware a une méthode process() avec
 * la signature attendue.
 *
 * =============================================================================
 * COMMENT CRÉER UN MIDDLEWARE ?
 * =============================================================================
 *
 * ```php
 * class MonMiddleware implements MiddlewareInterface
 * {
 *     public function process(Request $request, callable $next): Response
 *     {
 *         // ═══════════════════════════════════════════════════════════════
 *         // AVANT le contrôleur (traitement de la requête)
 *         // ═══════════════════════════════════════════════════════════════
 *
 *         // Ici, vous pouvez :
 *         // - Vérifier quelque chose (authentification, permissions...)
 *         // - Modifier la requête (ajouter des attributs...)
 *         // - Court-circuiter (retourner une Response sans appeler $next)
 *
 *         // ═══════════════════════════════════════════════════════════════
 *         // Appel du middleware/contrôleur suivant
 *         // ═══════════════════════════════════════════════════════════════
 *
 *         $response = $next($request);  // Passe au suivant
 *
 *         // ═══════════════════════════════════════════════════════════════
 *         // APRÈS le contrôleur (traitement de la réponse)
 *         // ═══════════════════════════════════════════════════════════════
 *
 *         // Ici, vous pouvez :
 *         // - Modifier la réponse (ajouter des headers...)
 *         // - Logger des informations
 *         // - Mesurer le temps de réponse
 *
 *         return $response;
 *     }
 * }
 * ```
 *
 * =============================================================================
 * EXEMPLES CONCRETS
 * =============================================================================
 *
 * ```php
 * // Middleware d'authentification
 * class AuthMiddleware implements MiddlewareInterface
 * {
 *     public function process(Request $request, callable $next): Response
 *     {
 *         // Vérifie si l'utilisateur est connecté
 *         $session = $request->getAttribute('session');
 *         if (!$session || !$session->get('user_id')) {
 *             // Non connecté → redirection vers login
 *             return new Response('', 302, ['Location: /login']);
 *         }
 *
 *         // Connecté → continue vers le contrôleur
 *         return $next($request);
 *     }
 * }
 *
 * // Middleware de logging
 * class LoggingMiddleware implements MiddlewareInterface
 * {
 *     public function process(Request $request, callable $next): Response
 *     {
 *         $start = microtime(true);
 *
 *         // Exécute le reste de la chaîne
 *         $response = $next($request);
 *
 *         // Calcule le temps de réponse
 *         $duration = microtime(true) - $start;
 *         error_log(sprintf(
 *             '%s %s → %d (%.3fs)',
 *             $request->getMethod(),
 *             $request->getUri(),
 *             $response->getStatusCode(),
 *             $duration
 *         ));
 *
 *         return $response;
 *     }
 * }
 * ```
 *
 * @package Lunar\Service\Core\Middleware
 */
interface MiddlewareInterface
{
    /**
     * Traite la requête et retourne une réponse.
     *
     * =========================================================================
     * FONCTIONNEMENT DE process()
     * =========================================================================
     *
     * Cette méthode reçoit :
     * - La REQUÊTE ($request) : l'objet contenant les données HTTP entrantes
     * - Le SUIVANT ($next) : une fonction qui appelle le prochain middleware
     *
     * Et doit retourner :
     * - Une RÉPONSE : soit en appelant $next(), soit directement
     *
     * DEUX COMPORTEMENTS POSSIBLES :
     *
     * ```
     *  1. PASSER AU SUIVANT (comportement normal)
     *
     *     public function process(Request $request, callable $next): Response
     *     {
     *         // ... traitement optionnel ...
     *         return $next($request);  // ← Appelle le suivant
     *     }
     *
     *  2. COURT-CIRCUITER (bloquer la chaîne)
     *
     *     public function process(Request $request, callable $next): Response
     *     {
     *         if (!$this->isAuthorized()) {
     *             // Retourne directement sans appeler $next
     *             return new Response('Forbidden', 403);
     *         }
     *         return $next($request);
     *     }
     * ```
     *
     * =========================================================================
     * QU'EST-CE QU'UN CALLABLE ?
     * =========================================================================
     *
     * Un CALLABLE est tout ce qui peut être "appelé" comme une fonction :
     * - Une fonction normale : 'maFonction'
     * - Une méthode : [$objet, 'methode']
     * - Une closure : function($x) { return $x * 2; }
     * - Un objet avec __invoke : $objetCallable
     *
     * Ici, $next est une closure qui encapsule le middleware suivant.
     * Quand vous appelez $next($request), ça exécute le prochain middleware.
     *
     * @param Request $request La requête HTTP entrante.
     *                         Contient l'URL, la méthode, les headers, le body...
     *
     * @param callable(Request): Response $next Le handler suivant dans la chaîne.
     *                                          Peut être un autre middleware ou le contrôleur.
     *                                          Signature : reçoit Request, retourne Response.
     *
     * @return Response La réponse HTTP à renvoyer au client.
     *                  Soit générée par ce middleware, soit par $next().
     *
     * @example Middleware qui passe au suivant
     * ```php
     * public function process(Request $request, callable $next): Response
     * {
     *     // Pas de traitement spécial, on passe directement
     *     return $next($request);
     * }
     * ```
     *
     * @example Middleware qui court-circuite
     * ```php
     * public function process(Request $request, callable $next): Response
     * {
     *     if ($request->getMethod() === 'DELETE' && !$this->isAdmin()) {
     *         return new Response('Forbidden', 403);
     *     }
     *     return $next($request);
     * }
     * ```
     *
     * @example Middleware qui modifie la requête et la réponse
     * ```php
     * public function process(Request $request, callable $next): Response
     * {
     *     // AVANT : ajoute un attribut à la requête
     *     $request = $request->withAttribute('timestamp', time());
     *
     *     // Appelle le suivant
     *     $response = $next($request);
     *
     *     // APRÈS : pourrait modifier la réponse
     *     // (ex: ajouter un header, modifier le body...)
     *
     *     return $response;
     * }
     * ```
     */
    public function process(Request $request, callable $next): Response;
}
