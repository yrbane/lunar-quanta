<?php
/**
 * Lunar Quanta Framework - Commande de Vidage du Cache.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE COMMANDE CLI ?
 * =============================================================================
 *
 * Une commande CLI (Command Line Interface) est un programme exécutable depuis
 * le TERMINAL (la ligne de commande). Contrairement à une page web, elle ne
 * génère pas de HTML mais affiche du texte dans le terminal.
 *
 * ```
 * REQUÊTE WEB                           COMMANDE CLI
 *
 *    Navigateur                           Terminal
 *        │                                    │
 *        │ GET /page                          │ ./bin/console cache:clear
 *        ▼                                    ▼
 *    ┌─────────┐                        ┌─────────┐
 *    │ Router  │                        │   CLI   │
 *    │ →       │                        │ Kernel  │
 *    │Controller                        │   →     │
 *    └─────────┘                        │ Command │
 *        │                              └─────────┘
 *        ▼                                    │
 *    Page HTML                           Texte (stdout)
 *    (navigateur)                        (terminal)
 * ```
 *
 * =============================================================================
 * POURQUOI VIDER LE CACHE ?
 * =============================================================================
 *
 * Le CACHE stocke des données pré-calculées pour accélérer l'application :
 * - Routes compilées (évite de scanner les contrôleurs)
 * - Templates compilés (évite de parser le template à chaque requête)
 * - Données diverses (résultats de calculs coûteux)
 *
 * QUAND VIDER LE CACHE ?
 * - Après une mise à jour du code
 * - Quand les routes ne correspondent plus
 * - En cas de comportement bizarre
 * - En développement (pour voir les changements)
 *
 * =============================================================================
 * ANATOMIE D'UNE COMMANDE
 * =============================================================================
 *
 * ```php
 * #[Command(name: 'cache:clear', description: '...')]  // Attribut de déclaration
 * class CacheClearCommand implements CommandInterface  // Interface obligatoire
 * {
 *     public function execute(array $args): int  // Point d'entrée
 *     {
 *         // ... logique de la commande ...
 *         return 0;  // Code de sortie (0 = succès, autre = erreur)
 *     }
 *
 *     public function getHelp(): string  // Documentation
 *     {
 *         return "Aide de la commande...";
 *     }
 * }
 * ```
 *
 * =============================================================================
 * CODE DE SORTIE (EXIT CODE)
 * =============================================================================
 *
 * Le code de retour indique au système si la commande a réussi :
 *
 * ```
 * RETOUR    SIGNIFICATION
 *   0       Succès total
 *   1       Erreur générale (répertoire inexistant, etc.)
 *   2       Erreur de syntaxe ou argument invalide
 *   >0      Différents types d'erreurs
 *
 * Utilisation en shell :
 *
 *   $ ./bin/console cache:clear && echo "OK" || echo "ERREUR"
 *                                 │             │
 *                                 │             └─ Exécuté si code ≠ 0
 *                                 └─ Exécuté si code = 0
 * ```
 *
 * @package    Lunar\Command
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see CommandInterface Interface que toute commande doit implémenter
 * @see Config Classe de configuration pour résoudre les chemins
 */
declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Config\Config;

/**
 * Commande CLI pour vider le cache de l'application.
 *
 * Cette commande supprime récursivement tous les fichiers du répertoire
 * de cache, permettant de régénérer les données en cache lors des
 * prochaines requêtes.
 *
 * ==========================================================================
 * UTILISATION
 * ==========================================================================
 *
 * ```bash
 * # Vider tout le cache
 * ./bin/console cache:clear
 *
 * # Afficher l'aide
 * ./bin/console cache:clear --help
 * ```
 *
 * ==========================================================================
 * COMPORTEMENT
 * ==========================================================================
 *
 * ```
 * AVANT                              APRÈS
 *
 * cache/                             cache/
 * ├── router.php (3KB)               └── (vide)
 * ├── templates/
 * │   ├── home.php (1KB)
 * │   └── user.php (2KB)
 * └── data/
 *     └── config.php (500B)
 *
 * Total: 6.5KB                       Total: 0KB
 * ```
 *
 * @package Lunar\Command
 */
#[Command(name: 'cache:clear', description: 'Supprime les fichiers du cache.')]
class CacheClearCommand implements CommandInterface
{
    private bool $verbose = false;
    private bool $dryRun = false;
    private int $filesDeleted = 0;
    private int $bytesFreed = 0;

    /**
     * Exécute la commande de vidage du cache.
     *
     * ======================================================================
     * ALGORITHME
     * ======================================================================
     *
     * 1. Récupère le chemin du répertoire de cache depuis la config
     * 2. Vérifie que le répertoire existe
     * 3. Supprime récursivement tout son contenu
     * 4. Affiche un message de confirmation
     *
     * ======================================================================
     * CODES DE RETOUR
     * ======================================================================
     *
     * ```
     * 0 → Cache vidé avec succès
     * 1 → Le répertoire de cache n'existe pas
     * ```
     *
     * @param array<string> $args Arguments passés à la commande (non utilisés)
     *
     * @return int Code de sortie (0 = succès, 1 = erreur)
     */
    public function execute(array $args): int
    {
        $this->verbose = in_array('-v', $args, true) || in_array('--verbose', $args, true);
        $this->dryRun = in_array('--dry-run', $args, true);
        $showStats = in_array('--stats', $args, true);

        // Déterminer le type de cache à vider
        $cacheType = $this->parseType($args);

        $cacheDir = Config::resolvePath(
            (string) Config::get('cache', 'cache.dir', 'cache')
        );

        if (!is_dir($cacheDir)) {
            echo "Le répertoire de cache n'existe pas.\n";
            return 1;
        }

        // Afficher les stats avant
        if ($showStats) {
            $this->displayStats($cacheDir, 'AVANT');
        }

        // Réinitialiser les compteurs
        $this->filesDeleted = 0;
        $this->bytesFreed = 0;

        if ($this->dryRun) {
            echo "Mode dry-run : aucune modification ne sera effectuée.\n\n";
        }

        // Vider le cache selon le type
        if ($cacheType === 'all') {
            $this->deleteDirContent($cacheDir);
        } else {
            $targetDir = $cacheDir . '/' . $cacheType;
            if (!is_dir($targetDir)) {
                echo "Le type de cache '{$cacheType}' n'existe pas.\n";
                return 1;
            }
            $this->deleteDirContent($targetDir);
            if (!$this->dryRun) {
                @rmdir($targetDir);
            }
        }

        // Afficher le résumé
        echo "\n";
        if ($this->dryRun) {
            echo "Simulation terminée :\n";
        } else {
            echo "Cache vidé avec succès !\n";
        }

        echo "  Fichiers supprimés : {$this->filesDeleted}\n";
        echo "  Espace libéré : " . $this->formatBytes($this->bytesFreed) . "\n";

        // Afficher les stats après
        if ($showStats && !$this->dryRun) {
            $this->displayStats($cacheDir, 'APRÈS');
        }

        return 0;
    }

    /**
     * Parse le type de cache depuis les arguments.
     */
    private function parseType(array $args): string
    {
        foreach ($args as $i => $arg) {
            if ($arg === '--type' && isset($args[$i + 1])) {
                return $args[$i + 1];
            }
            if (str_starts_with($arg, '--type=')) {
                return substr($arg, 7);
            }
        }
        return 'all';
    }

    /**
     * Affiche les statistiques du cache.
     */
    private function displayStats(string $cacheDir, string $label): void
    {
        $stats = $this->getCacheStats($cacheDir);

        echo "\n┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                    STATISTIQUES CACHE ({$label})                │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-30s %29s │\n", "Fichiers totaux", $stats['files']);
        printf("│  %-30s %29s │\n", "Répertoires", $stats['directories']);
        printf("│  %-30s %29s │\n", "Taille totale", $this->formatBytes($stats['size']));
        echo "├──────────────────────────────────────────────────────────────┤\n";

        if (!empty($stats['by_type'])) {
            echo "│  Par type :                                                  │\n";
            foreach ($stats['by_type'] as $type => $info) {
                $label = mb_substr($type, 0, 20);
                printf("│    %-28s %19s (%4d) │\n",
                    $label,
                    $this->formatBytes($info['size']),
                    $info['count']
                );
            }
        }
        echo "└──────────────────────────────────────────────────────────────┘\n";
    }

    /**
     * Calcule les statistiques du cache.
     */
    private function getCacheStats(string $dir): array
    {
        $stats = [
            'files' => 0,
            'directories' => 0,
            'size' => 0,
            'by_type' => [],
        ];

        if (!is_dir($dir)) {
            return $stats;
        }

        // Compter les sous-répertoires de premier niveau comme types
        $items = glob($dir . '/*');
        if ($items === false) {
            return $stats;
        }

        foreach ($items as $item) {
            if (is_dir($item)) {
                $typeName = basename($item);
                $typeStats = $this->countDirStats($item);
                $stats['by_type'][$typeName] = [
                    'count' => $typeStats['files'],
                    'size' => $typeStats['size'],
                ];
                $stats['files'] += $typeStats['files'];
                $stats['directories'] += $typeStats['directories'] + 1;
                $stats['size'] += $typeStats['size'];
            } else {
                $stats['files']++;
                $stats['size'] += filesize($item);
            }
        }

        return $stats;
    }

    /**
     * Compte les fichiers et la taille d'un répertoire récursivement.
     */
    private function countDirStats(string $dir): array
    {
        $stats = ['files' => 0, 'directories' => 0, 'size' => 0];

        $items = glob($dir . '/*');
        if ($items === false) {
            return $stats;
        }

        foreach ($items as $item) {
            if (is_dir($item)) {
                $stats['directories']++;
                $subStats = $this->countDirStats($item);
                $stats['files'] += $subStats['files'];
                $stats['directories'] += $subStats['directories'];
                $stats['size'] += $subStats['size'];
            } else {
                $stats['files']++;
                $stats['size'] += filesize($item);
            }
        }

        return $stats;
    }

    /**
     * Formate une taille en bytes en format lisible.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Supprime récursivement le contenu d'un répertoire.
     *
     * ======================================================================
     * QU'EST-CE QUE LA RÉCURSIVITÉ ?
     * ======================================================================
     *
     * La RÉCURSIVITÉ est une technique où une fonction s'appelle elle-même
     * pour traiter des structures imbriquées (comme des dossiers).
     *
     * ```
     * EXEMPLE D'ARBORESCENCE :
     *
     *    cache/
     *    ├── file1.txt           ← unlink() direct
     *    └── subdir/
     *        ├── file2.txt       ← unlink() après récursion
     *        └── deep/
     *            └── file3.txt   ← unlink() après récursion
     *
     * ORDRE DE SUPPRESSION :
     *
     *    1. deleteDirContent("cache/")
     *       └─ Trouve "file1.txt" → supprime
     *       └─ Trouve "subdir/" → RÉCURSION
     *
     *    2. deleteDirContent("cache/subdir/")
     *       └─ Trouve "file2.txt" → supprime
     *       └─ Trouve "deep/" → RÉCURSION
     *
     *    3. deleteDirContent("cache/subdir/deep/")
     *       └─ Trouve "file3.txt" → supprime
     *       └─ Retour (plus rien)
     *
     *    4. rmdir("cache/subdir/deep/")   ← vide, peut être supprimé
     *    5. rmdir("cache/subdir/")        ← vide, peut être supprimé
     * ```
     *
     * POURQUOI CETTE APPROCHE ?
     * - On ne peut supprimer un dossier que s'il est VIDE
     * - Il faut donc d'abord supprimer son contenu (fichiers + sous-dossiers)
     * - D'où la récursion : on descend au plus profond d'abord
     *
     * @param string $dir Chemin absolu du répertoire à vider
     */
    public function deleteDirContent(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*');
        if (false === $files) {
            return;
        }

        // Inclure aussi les fichiers cachés (commençant par .)
        $hidden = glob($dir . '/.*');
        if ($hidden !== false) {
            foreach ($hidden as $h) {
                if (basename($h) !== '.' && basename($h) !== '..') {
                    $files[] = $h;
                }
            }
        }

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDirContent($file);
                if (!$this->dryRun) {
                    @rmdir($file);
                }
            } else {
                $size = filesize($file);
                $this->filesDeleted++;
                $this->bytesFreed += $size;

                if ($this->verbose) {
                    echo "  " . ($this->dryRun ? "[dry-run] " : "") . basename($file) . " (" . $this->formatBytes($size) . ")\n";
                }

                if (!$this->dryRun) {
                    unlink($file);
                }
            }
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : cache:clear
Supprime les fichiers du cache.

Utilisation :
  ./bin/console cache:clear [options]

Options :
  -v, --verbose       Affiche chaque fichier supprimé
  --dry-run           Simule la suppression sans modifier les fichiers
  --stats             Affiche les statistiques avant et après
  --type=<type>       Vide seulement un type de cache spécifique
  --help              Affiche cette aide

Description :
  Cette commande supprime tous les fichiers du répertoire de cache.
  Elle est utile pour libérer de l'espace disque ou résoudre des problèmes liés au cache.

Exemples :
  cache:clear                     # Vide tout le cache
  cache:clear --verbose           # Vide avec détails
  cache:clear --dry-run           # Prévisualisation
  cache:clear --stats             # Affiche les stats avant/après
  cache:clear --type=templates    # Vide seulement le cache templates

Remarque :
  Assurez-vous d'avoir les permissions nécessaires pour supprimer les fichiers du cache.

HELP;
    }
}
