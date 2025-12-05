<?php
/**
 * Lunar Quanta Framework - Exception de Sécurité.
 *
 * =============================================================================
 * QUAND CETTE EXCEPTION EST-ELLE LANCÉE ?
 * =============================================================================
 *
 * SecurityException est lancée pour toutes les erreurs liées à la
 * SÉCURITÉ : chiffrement, déchiffrement, tokens, etc.
 *
 * CAS D'UTILISATION :
 *
 * 1. ERREUR DE CHIFFREMENT
 *    Le chiffrement des données a échoué (clé invalide, données trop longues...).
 *
 * 2. ERREUR DE DÉCHIFFREMENT
 *    Les données ne peuvent pas être déchiffrées (altérées, clé différente...).
 *
 * 3. CLÉ DE CHIFFREMENT INVALIDE
 *    La clé fournie n'est pas au bon format ou trop courte.
 *
 * 4. DONNÉES ALTÉRÉES
 *    L'intégrité des données chiffrées n'a pas pu être vérifiée.
 *
 * ```
 * EXEMPLES DE SITUATIONS
 *
 *    Chiffrement :
 *    ┌─────────────────┐       ┌─────────────────┐
 *    │ Données claires │──────▶│ Données chiffrées│
 *    │ "Mon secret"    │  clé  │ "x8f2k9p..."    │
 *    └─────────────────┘       └─────────────────┘
 *
 *    Déchiffrement ÉCHOUÉ :
 *    ┌─────────────────┐       ┌─────────────────┐
 *    │ Données altérées│──────▶│ ERREUR !        │
 *    │ "x8f2k9p...XXX" │  clé  │ SecurityException│
 *    └─────────────────┘       └─────────────────┘
 *
 *    → Les données ont été modifiées, impossible de déchiffrer.
 * ```
 *
 * =============================================================================
 * COMMENT GÉRER CETTE EXCEPTION ?
 * =============================================================================
 *
 * ```php
 * // Déchiffrement de données sensibles
 * try {
 *     $donnees = $encryption->decrypt($donneesCryptees);
 * } catch (SecurityException $e) {
 *     // Les données sont corrompues ou la clé est mauvaise
 *     error_log("Erreur sécurité : " . $e->getMessage());
 *
 *     // NE PAS afficher le message à l'utilisateur (risque de fuite)
 *     throw new \RuntimeException("Impossible de lire les données");
 * }
 * ```
 *
 * =============================================================================
 * BONNES PRATIQUES DE SÉCURITÉ
 * =============================================================================
 *
 * ```php
 * // ❌ MAUVAIS : Afficher le message d'erreur à l'utilisateur
 * catch (SecurityException $e) {
 *     echo $e->getMessage();  // Peut révéler des informations sensibles !
 * }
 *
 * // ✅ BON : Logger et afficher un message générique
 * catch (SecurityException $e) {
 *     error_log($e->getMessage());  // Pour le debug
 *     echo "Une erreur est survenue.";  // Message neutre
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
 * @see \Lunar\Service\Security\EncryptionService Le service de chiffrement
 * @see LunarException Classe parente
 */
declare(strict_types=1);

namespace Lunar\Exception;

/**
 * Exception lancée pour les erreurs liées à la sécurité.
 *
 * Cette exception signale des problèmes lors des opérations cryptographiques :
 * - Échec du chiffrement
 * - Échec du déchiffrement
 * - Clé invalide
 * - Données altérées ou corrompues
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // Dans le service de chiffrement
 * public function decrypt(string $encrypted): string
 * {
 *     $decoded = base64_decode($encrypted, true);
 *     if ($decoded === false) {
 *         throw new SecurityException(
 *             "Les données ne sont pas au format base64 valide."
 *         );
 *     }
 *
 *     $decrypted = openssl_decrypt($decoded, $this->cipher, $this->key, ...);
 *     if ($decrypted === false) {
 *         throw new SecurityException(
 *             "Impossible de déchiffrer les données. " .
 *             "La clé est peut-être incorrecte ou les données altérées."
 *         );
 *     }
 *
 *     return $decrypted;
 * }
 *
 * // Gestion dans l'application
 * try {
 *     $secret = $encryption->decrypt($data);
 * } catch (SecurityException $e) {
 *     // Données compromises ou erreur de configuration
 *     $this->logger->critical('Erreur de sécurité', ['error' => $e->getMessage()]);
 *     throw new \RuntimeException('Erreur de sécurité');
 * }
 * ```
 *
 * @package Lunar\Exception
 */
class SecurityException extends LunarException
{
}
