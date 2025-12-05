<?php
/**
 * Lunar Quanta Framework - Exception de Stockage.
 *
 * =============================================================================
 * QUAND CETTE EXCEPTION EST-ELLE LANCÉE ?
 * =============================================================================
 *
 * StorageException est lancée pour toutes les erreurs liées au
 * STOCKAGE DE DONNÉES (fichiers, cache, base de données simple).
 *
 * CAS D'UTILISATION :
 *
 * 1. FICHIER NON TROUVÉ
 *    Le fichier de données n'existe pas.
 *
 * 2. PERMISSION REFUSÉE
 *    Impossible d'écrire dans le dossier ou le fichier.
 *
 * 3. FORMAT INVALIDE
 *    Les données lues ne sont pas au format attendu (JSON invalide, etc.).
 *
 * 4. ESPACE DISQUE INSUFFISANT
 *    Plus d'espace pour écrire les données.
 *
 * ```
 * EXEMPLES DE SITUATIONS
 *
 *    Écriture de fichier :
 *
 *    Tentative : écrire dans /var/data/cache.json
 *
 *    ├── Dossier /var/data n'existe pas
 *    │   → StorageException : "Directory does not exist"
 *    │
 *    ├── Fichier en lecture seule
 *    │   → StorageException : "Permission denied"
 *    │
 *    └── Disque plein
 *        → StorageException : "Not enough space"
 * ```
 *
 * =============================================================================
 * COMMENT GÉRER CETTE EXCEPTION ?
 * =============================================================================
 *
 * ```php
 * // Lecture d'un fichier de configuration
 * try {
 *     $config = $storage->read('config.json');
 * } catch (StorageException $e) {
 *     // Fichier manquant ou illisible
 *     error_log("Erreur stockage : " . $e->getMessage());
 *
 *     // Utiliser une configuration par défaut
 *     $config = ['default' => true];
 * }
 *
 * // Écriture de données
 * try {
 *     $storage->write('data.json', $data);
 * } catch (StorageException $e) {
 *     // Impossible d'écrire
 *     throw new \RuntimeException("Sauvegarde impossible : " . $e->getMessage());
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
 * @see \Lunar\Service\Storage\JsonStorage Le service de stockage JSON
 * @see LunarException Classe parente
 */
declare(strict_types=1);

namespace Lunar\Exception;

/**
 * Exception lancée pour les erreurs de stockage.
 *
 * Cette exception signale des problèmes lors des opérations de stockage :
 * - Fichier introuvable
 * - Permission refusée
 * - Format de données invalide
 * - Espace disque insuffisant
 *
 * =============================================================================
 * EXEMPLES D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // Dans le service de stockage JSON
 * public function read(string $filename): array
 * {
 *     $path = $this->directory . '/' . $filename;
 *
 *     if (!file_exists($path)) {
 *         throw new StorageException(
 *             "Le fichier '$filename' n'existe pas dans '$this->directory'"
 *         );
 *     }
 *
 *     $content = file_get_contents($path);
 *     if ($content === false) {
 *         throw new StorageException(
 *             "Impossible de lire le fichier '$filename'"
 *         );
 *     }
 *
 *     $data = json_decode($content, true);
 *     if (json_last_error() !== JSON_ERROR_NONE) {
 *         throw new StorageException(
 *             "Le fichier '$filename' ne contient pas de JSON valide : " .
 *             json_last_error_msg()
 *         );
 *     }
 *
 *     return $data;
 * }
 *
 * // Gestion dans l'application
 * try {
 *     $users = $storage->read('users.json');
 * } catch (StorageException $e) {
 *     // Fichier manquant ou corrompu
 *     $this->logger->warning('Fichier utilisateurs illisible', ['error' => $e->getMessage()]);
 *     $users = [];  // Fallback
 * }
 * ```
 *
 * @package Lunar\Exception
 */
class StorageException extends LunarException
{
}
