<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;

/**
 * Commande CLI pour restaurer une sauvegarde du blog.
 *
 * Restaure les données à partir d'une archive créée par blog:backup.
 */
#[Command(name: 'blog:restore', description: 'Restaure les données du blog depuis une sauvegarde.')]
class BlogRestoreCommand implements CommandInterface
{
    private string $basePath;
    private bool $verbose = false;

    public function execute(array $args): int
    {
        $this->basePath = dirname(__DIR__, 2);
        $this->verbose = in_array('-v', $args, true) || in_array('--verbose', $args, true);
        $force = in_array('--force', $args, true);
        $dryRun = in_array('--dry-run', $args, true);

        // Obtenir le chemin de l'archive
        $archivePath = $this->getArchivePath($args);

        if ($archivePath === null) {
            echo "Usage: blog:restore <archive> [options]\n";
            echo "Utilisez --help pour plus d'informations.\n";
            return 1;
        }

        if (!file_exists($archivePath)) {
            echo "✗ Erreur : Archive non trouvée : {$archivePath}\n";
            return 1;
        }

        $this->printHeader();

        try {
            echo "Archive : {$archivePath}\n";
            echo "Mode : " . ($dryRun ? 'Simulation' : 'Restauration') . "\n";
            echo "\n";

            // Extraire l'archive dans un répertoire temporaire
            $tempDir = sys_get_temp_dir() . '/blog-restore-' . uniqid();
            mkdir($tempDir, 0755, true);

            echo "→ Extraction de l'archive...\n";
            $this->extractArchive($archivePath, $tempDir);

            // Lire le manifest
            $manifestPath = $tempDir . '/manifest.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                echo "  Date de création : " . ($manifest['created_at'] ?? 'Inconnue') . "\n";
                echo "  Articles : " . ($manifest['stats']['posts'] ?? '?') . "\n";
                echo "  Catégories : " . ($manifest['stats']['categories'] ?? '?') . "\n";
                echo "  Tags : " . ($manifest['stats']['tags'] ?? '?') . "\n";
                echo "\n";
            }

            // Vérifier les conflits
            if (!$force && !$dryRun) {
                $conflicts = $this->checkConflicts($tempDir);
                if (!empty($conflicts)) {
                    echo "⚠ Conflits détectés :\n";
                    foreach (array_slice($conflicts, 0, 5) as $conflict) {
                        echo "  - {$conflict}\n";
                    }
                    if (count($conflicts) > 5) {
                        echo "  ... et " . (count($conflicts) - 5) . " autres\n";
                    }
                    echo "\nUtilisez --force pour écraser les fichiers existants.\n";
                    $this->deleteDirectory($tempDir);
                    return 1;
                }
            }

            if ($dryRun) {
                echo "→ Simulation de restauration...\n\n";
                $stats = $this->simulateRestore($tempDir);
            } else {
                // Sauvegarder les données actuelles
                echo "→ Sauvegarde des données actuelles...\n";
                $backupDir = $this->basePath . '/var/restore-backup-' . date('YmdHis');
                $this->createBackup($backupDir);

                // Restaurer les données
                echo "→ Restauration des données...\n";
                $stats = $this->restoreData($tempDir, $force);
            }

            // Nettoyer
            $this->deleteDirectory($tempDir);

            $this->printResults($stats, $dryRun);

            return 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur : " . $e->getMessage() . "\n";
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            return 1;
        }
    }

    /**
     * Obtient le chemin de l'archive depuis les arguments.
     */
    private function getArchivePath(array $args): ?string
    {
        foreach ($args as $arg) {
            if (!str_starts_with($arg, '-')) {
                return $arg;
            }
        }
        return null;
    }

    /**
     * Extrait une archive.
     */
    private function extractArchive(string $archivePath, string $destDir): void
    {
        $extension = strtolower(pathinfo($archivePath, PATHINFO_EXTENSION));

        if ($extension === 'zip') {
            $zip = new \ZipArchive();
            if ($zip->open($archivePath) !== true) {
                throw new \RuntimeException("Impossible d'ouvrir l'archive ZIP");
            }
            $zip->extractTo($destDir);
            $zip->close();

        } elseif ($extension === 'gz') {
            // tar.gz
            $phar = new \PharData($archivePath);
            $phar->decompress();

            $tarPath = substr($archivePath, 0, -3); // Enlever .gz
            $pharTar = new \PharData($tarPath);
            $pharTar->extractTo($destDir);

            unlink($tarPath);

        } elseif ($extension === 'tar') {
            $phar = new \PharData($archivePath);
            $phar->extractTo($destDir);

        } else {
            throw new \RuntimeException("Format d'archive non supporté : {$extension}");
        }
    }

    /**
     * Vérifie les conflits avec les fichiers existants.
     */
    private function checkConflicts(string $sourceDir): array
    {
        $conflicts = [];
        $dirs = ['data/blog/posts', 'data/blog/categories', 'data/blog/tags'];

        foreach ($dirs as $dir) {
            $sourcePath = $sourceDir . '/' . $dir;
            $destPath = $this->basePath . '/' . $dir;

            if (!is_dir($sourcePath)) {
                continue;
            }

            $files = glob($sourcePath . '/*.json');
            foreach ($files as $file) {
                $destFile = $destPath . '/' . basename($file);
                if (file_exists($destFile)) {
                    $conflicts[] = $dir . '/' . basename($file);
                }
            }
        }

        return $conflicts;
    }

    /**
     * Simule la restauration.
     */
    private function simulateRestore(string $sourceDir): array
    {
        $stats = [
            'posts' => 0,
            'categories' => 0,
            'tags' => 0,
            'media' => 0,
            'skipped' => 0,
        ];

        $dirs = [
            'data/blog/posts' => 'posts',
            'data/blog/categories' => 'categories',
            'data/blog/tags' => 'tags',
            'public/uploads' => 'media',
        ];

        foreach ($dirs as $dir => $key) {
            $sourcePath = $sourceDir . '/' . $dir;
            if (!is_dir($sourcePath)) {
                continue;
            }

            $files = $this->countFiles($sourcePath);
            $stats[$key] = $files;

            if ($this->verbose) {
                echo "  {$key}: {$files} fichiers à restaurer\n";
            }
        }

        return $stats;
    }

    /**
     * Crée une sauvegarde des données actuelles.
     */
    private function createBackup(string $backupDir): void
    {
        mkdir($backupDir, 0755, true);

        $dirs = ['data/blog/posts', 'data/blog/categories', 'data/blog/tags'];
        foreach ($dirs as $dir) {
            $sourcePath = $this->basePath . '/' . $dir;
            if (is_dir($sourcePath)) {
                $destPath = $backupDir . '/' . $dir;
                $this->copyDirectory($sourcePath, $destPath);
            }
        }

        if ($this->verbose) {
            echo "  Sauvegarde créée dans : {$backupDir}\n";
        }
    }

    /**
     * Restaure les données.
     */
    private function restoreData(string $sourceDir, bool $force): array
    {
        $stats = [
            'posts' => 0,
            'categories' => 0,
            'tags' => 0,
            'media' => 0,
            'skipped' => 0,
        ];

        $mappings = [
            'data/blog/posts' => 'posts',
            'data/blog/categories' => 'categories',
            'data/blog/tags' => 'tags',
            'public/uploads' => 'media',
        ];

        foreach ($mappings as $dir => $key) {
            $sourcePath = $sourceDir . '/' . $dir;
            $destPath = $this->basePath . '/' . $dir;

            if (!is_dir($sourcePath)) {
                continue;
            }

            // Créer le répertoire de destination si nécessaire
            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
            }

            // Copier les fichiers
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $subPath = $iterator->getSubPathname();
                $destFile = $destPath . '/' . $subPath;

                if ($item->isDir()) {
                    if (!is_dir($destFile)) {
                        mkdir($destFile, 0755, true);
                    }
                } else {
                    if (file_exists($destFile) && !$force) {
                        $stats['skipped']++;
                        continue;
                    }

                    copy($item->getPathname(), $destFile);
                    $stats[$key]++;

                    if ($this->verbose) {
                        echo "  ✓ {$dir}/{$subPath}\n";
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Copie un répertoire récursivement.
     */
    private function copyDirectory(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $destPath = $dest . '/' . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $destPath);
            }
        }
    }

    /**
     * Compte les fichiers dans un répertoire.
     */
    private function countFiles(string $dir): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Supprime un répertoire récursivement.
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Restauration                       ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    private function printResults(array $stats, bool $dryRun): void
    {
        echo "\n";
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│              " . ($dryRun ? 'SIMULATION' : 'RESTAURATION') . " TERMINÉE                        │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-25s %35s │\n", "Articles", $stats['posts']);
        printf("│  %-25s %35s │\n", "Catégories", $stats['categories']);
        printf("│  %-25s %35s │\n", "Tags", $stats['tags']);
        printf("│  %-25s %35s │\n", "Médias", $stats['media']);
        if ($stats['skipped'] > 0) {
            printf("│  %-25s %35s │\n", "Fichiers ignorés", $stats['skipped']);
        }
        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";

        if (!$dryRun) {
            echo "✓ Restauration terminée !\n";
            echo "Note : Une sauvegarde des données précédentes a été créée.\n";
        }
        echo "\n";
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:restore <archive> [options]

Restaure les données du blog depuis une sauvegarde.

Arguments :
  <archive>           Chemin vers l'archive à restaurer

Options :
  -v, --verbose       Affiche les fichiers restaurés
  --dry-run           Simule la restauration sans modifier les fichiers
  --force             Écrase les fichiers existants en cas de conflit

Exemples :
  blog:restore var/backups/blog-backup-2024-01-01.zip
  blog:restore backup.tar.gz --dry-run
  blog:restore backup.zip --force --verbose

Formats supportés :
  - ZIP (.zip)
  - TAR (.tar)
  - TAR.GZ (.tar.gz)

Sécurité :
  - Une sauvegarde des données actuelles est créée avant restauration
  - Les conflits sont détectés et signalés
  - Utilisez --dry-run pour prévisualiser les changements
HELP;
    }
}
