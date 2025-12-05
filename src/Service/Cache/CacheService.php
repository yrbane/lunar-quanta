<?php
/**
 * Lunar Quanta Framework - Service de Gestion du Cache.
 *
 * =============================================================================
 * QU'EST-CE QUE LE CACHE ?
 * =============================================================================
 *
 * Le CACHE est un mécanisme de stockage temporaire qui accélère l'application
 * en évitant de recalculer ou recharger des données fréquemment utilisées.
 *
 * ```
 * SANS CACHE                          AVEC CACHE
 *
 *    Requête                            Requête
 *       │                                  │
 *       ▼                                  ▼
 *    Scanner tous les             Lire le fichier cache
 *    contrôleurs                  (router.php)
 *       │                                  │
 *       │ (lent: 50ms)                     │ (rapide: 1ms)
 *       ▼                                  ▼
 *    Analyser les                    Données prêtes !
 *    attributs #[Route]
 *       │
 *       ▼
 *    Construire la
 *    table des routes
 * ```
 *
 * =============================================================================
 * TYPES DE DONNÉES MISES EN CACHE
 * =============================================================================
 *
 * ```
 * CACHE
 * └── cache/
 *     ├── router.php       → Table des routes compilées
 *     ├── templates/       → Templates HTML compilés
 *     │   ├── home_abc.php
 *     │   └── user_def.php
 *     └── config.php       → Configuration fusionnée
 * ```
 *
 * =============================================================================
 * QUAND VIDER LE CACHE ?
 * =============================================================================
 *
 * 1. DÉVELOPPEMENT : Après chaque modification de code
 * 2. DÉPLOIEMENT : Avant de mettre en production
 * 3. PROBLÈMES : Quand l'app se comporte bizarrement
 * 4. NOUVELLE ROUTE : Si une nouvelle route n'est pas reconnue
 *
 * @package    Lunar\Service\Cache
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see CacheClearCommand Commande CLI pour vider le cache
 * @see Config Classe de configuration
 */
declare(strict_types=1);

namespace Lunar\Service\Cache;

use Lunar\Config\Config;

/**
 * Service de gestion du cache de l'application.
 *
 * Cette classe fournit des méthodes pour manipuler le cache :
 * - Vidage complet du répertoire de cache
 * - Rapport détaillé des opérations effectuées
 *
 * ==========================================================================
 * EXEMPLE D'UTILISATION
 * ==========================================================================
 *
 * ```php
 * $cache = new CacheService();
 * $results = $cache->clear();
 *
 * foreach ($results as $result) {
 *     if ($result['status'] === 'success') {
 *         echo "✓ " . $result['message'] . "\n";
 *     } else {
 *         echo "✗ " . $result['message'] . "\n";
 *     }
 * }
 * ```
 *
 * ==========================================================================
 * ITÉRATEURS RÉCURSIFS
 * ==========================================================================
 *
 * Cette classe utilise RecursiveDirectoryIterator pour parcourir les fichiers.
 *
 * ```
 * BOUCLE CLASSIQUE                    ITÉRATEUR RÉCURSIF
 *
 *    function parcourir($dir) {       $iterator = new RecursiveIterator(
 *        foreach (scandir($dir)) {        new RecursiveDirectoryIterator($dir)
 *            if (is_dir($file)) {     );
 *                parcourir($file);    foreach ($iterator as $file) {
 *            }                            // Tous les fichiers, même imbriqués
 *        }                            }
 *    }
 *
 * AVANTAGE : Plus propre, gère automatiquement la récursion
 * ```
 *
 * @package Lunar\Service\Cache
 */
class CacheService
{
    /**
     * @return array<array{status: string, message: string}>
     */
    public function clear(): array
    {
        $cacheDirConfig = Config::get('cache', 'cache.dir', 'cache');
        $cacheDir = Config::resolvePath(
            is_string($cacheDirConfig) ? $cacheDirConfig : 'cache'
        );

        /** @var array<array{status: string, message: string}> $results */
        $results = [];

        if (!is_dir($cacheDir)) {
            $results[] = ['status' => 'error', 'message' => "Le dossier cache '{$cacheDir}' n'existe pas."];

            return $results;
        }

        /** @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $files */
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if (!$fileinfo instanceof \SplFileInfo) {
                continue;
            }
            $realPath = $fileinfo->getRealPath();
            if (false === $realPath) {
                continue;
            }
            if ($fileinfo->isDir()) {
                if (rmdir($realPath)) {
                    $results[] = ['status' => 'success', 'message' => "Dossier supprimé : {$realPath}"];
                } else {
                    $results[] = ['status' => 'error', 'message' => "Impossible de supprimer le dossier : {$realPath}"];
                }
            } else {
                if (unlink($realPath)) {
                    $results[] = ['status' => 'success', 'message' => "Fichier supprimé : {$realPath}"];
                } else {
                    $results[] = ['status' => 'error', 'message' => "Impossible de supprimer le fichier : {$realPath}"];
                }
            }
        }

        if (rmdir($cacheDir)) {
            $results[] = ['status' => 'success', 'message' => "Dossier cache principal supprimé : {$cacheDir}"];

            // Recreate the cache directory after clearing
            if (!mkdir($cacheDir, 0777, true)) {
                $results[] = ['status' => 'error', 'message' => "Impossible de recréer le dossier cache : {$cacheDir}"];
            }
        }

        return $results;
    }
}
