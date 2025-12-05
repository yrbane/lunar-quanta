<?php
/**
 * Lunar Quanta Framework - Exception de Routage.
 *
 * =============================================================================
 * QUAND CETTE EXCEPTION EST-ELLE LANCÉE ?
 * =============================================================================
 *
 * RouterException est lancée pour toutes les erreurs liées au ROUTAGE,
 * c'est-à-dire le processus qui associe une URL à un contrôleur.
 *
 * CAS D'UTILISATION :
 *
 * 1. ROUTE NON TROUVÉE (404)
 *    L'URL demandée ne correspond à aucune route configurée.
 *
 * 2. MÉTHODE NON AUTORISÉE (405)
 *    La route existe mais pas pour cette méthode HTTP.
 *    Ex: POST sur une route qui n'accepte que GET.
 *
 * 3. CONTRÔLEUR INTROUVABLE
 *    La classe de contrôleur référencée n'existe pas.
 *
 * 4. MÉTHODE INTROUVABLE
 *    La méthode du contrôleur n'existe pas.
 *
 * ```
 * EXEMPLES DE SITUATIONS
 *
 *    Requête : GET /utilisateurs/42
 *
 *    Routes configurées :
 *    ├── GET  /users      → 404 (chemin différent)
 *    ├── POST /users/{id} → 405 (méthode différente)
 *    └── GET  /users/{id} → OK ! Route trouvée
 * ```
 *
 * =============================================================================
 * COMMENT GÉRER CETTE EXCEPTION ?
 * =============================================================================
 *
 * ```php
 * // Dans le FrontController ou index.php
 * try {
 *     $response = $router->dispatch($request);
 * } catch (RouterException $e) {
 *     // Erreur de routage → page d'erreur appropriée
 *     $code = $e->getCode() ?: 404;
 *
 *     if ($code === 404) {
 *         $response = new Response('Page non trouvée', 404);
 *     } elseif ($code === 405) {
 *         $response = new Response('Méthode non autorisée', 405);
 *     } else {
 *         $response = new Response('Erreur serveur', 500);
 *     }
 * }
 * ```
 *
 * @package    Lunar\Exception
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see \Lunar\Service\Core\Router Le routeur qui lance cette exception
 * @see LunarException Classe parente
 */
declare(strict_types=1);

namespace Lunar\Exception;

/**
 * Exception lancée pour les erreurs liées au routage.
 *
 * Cette exception signale des problèmes lors de la résolution des routes :
 * - Route non trouvée (404)
 * - Méthode HTTP non autorisée (405)
 * - Contrôleur ou méthode manquant
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // Dans le Router
 * public function dispatch(Request $request): Response
 * {
 *     $route = $this->match($request->getUri(), $request->getMethod());
 *
 *     if ($route === null) {
 *         throw new RouterException(
 *             "Aucune route ne correspond à " . $request->getUri(),
 *             404
 *         );
 *     }
 *
 *     // ...
 * }
 *
 * // Gestion dans l'application
 * try {
 *     $response = $router->dispatch($request);
 * } catch (RouterException $e) {
 *     return $errorController->notFound();
 * }
 * ```
 *
 * @package Lunar\Exception
 */
class RouterException extends LunarException
{
}
