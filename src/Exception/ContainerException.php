<?php
/**
 * Lunar Quanta Framework - Exception du Conteneur de Dépendances.
 *
 * =============================================================================
 * QUAND CETTE EXCEPTION EST-ELLE LANCÉE ?
 * =============================================================================
 *
 * ContainerException est lancée pour toutes les erreurs liées au
 * CONTENEUR D'INJECTION DE DÉPENDANCES.
 *
 * CAS D'UTILISATION :
 *
 * 1. SERVICE NON TROUVÉ
 *    On demande un service qui n'est pas enregistré dans le conteneur.
 *
 * 2. DÉPENDANCE CIRCULAIRE
 *    Le service A dépend de B, qui dépend de C, qui dépend de A.
 *
 * 3. AUTO-WIRING IMPOSSIBLE
 *    Le conteneur ne peut pas déterminer comment créer une dépendance.
 *
 * 4. ERREUR DE CONFIGURATION
 *    La factory enregistrée lance une erreur.
 *
 * ```
 * EXEMPLES DE SITUATIONS
 *
 *    Dépendance circulaire :
 *
 *    ServiceA                  ServiceB                  ServiceC
 *    ┌───────┐                ┌───────┐                ┌───────┐
 *    │       │───dépend de───▶│       │───dépend de───▶│       │
 *    │   A   │                │   B   │                │   C   │
 *    │       │◀───dépend de───│       │◀───────────────│       │
 *    └───────┘                └───────┘                └───────┘
 *        ▲                                                  │
 *        └──────────────── dépend de ───────────────────────┘
 *
 *    → ERREUR ! Impossible de résoudre.
 * ```
 *
 * =============================================================================
 * COMMENT GÉRER CETTE EXCEPTION ?
 * =============================================================================
 *
 * ```php
 * // Récupération d'un service
 * try {
 *     $service = $container->get(MonService::class);
 * } catch (ContainerException $e) {
 *     // Le service n'existe pas ou ne peut pas être créé
 *     error_log("Erreur DI : " . $e->getMessage());
 *
 *     // Utiliser un fallback ou afficher une erreur
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
 * @see \Lunar\Service\Core\Container Le conteneur qui lance cette exception
 * @see LunarException Classe parente
 */
declare(strict_types=1);

namespace Lunar\Exception;

/**
 * Exception lancée pour les erreurs d'injection de dépendances.
 *
 * Cette exception signale des problèmes lors de la résolution des services :
 * - Service non enregistré
 * - Dépendance circulaire détectée
 * - Impossible d'auto-wirer une dépendance
 * - Erreur lors de la création d'un service
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // Dans le Container
 * public function get(string $id): mixed
 * {
 *     if (!$this->has($id)) {
 *         throw new ContainerException(
 *             "Le service '$id' n'est pas enregistré dans le conteneur."
 *         );
 *     }
 *
 *     // Détection de dépendance circulaire
 *     if (in_array($id, $this->resolving, true)) {
 *         throw new ContainerException(
 *             "Dépendance circulaire détectée pour '$id'. " .
 *             "Chaîne : " . implode(' -> ', $this->resolving) . " -> $id"
 *         );
 *     }
 *
 *     // ...
 * }
 *
 * // Gestion dans l'application
 * try {
 *     $userService = $container->get(UserService::class);
 * } catch (ContainerException $e) {
 *     die("Configuration invalide : " . $e->getMessage());
 * }
 * ```
 *
 * @package Lunar\Exception
 */
class ContainerException extends LunarException
{
}
