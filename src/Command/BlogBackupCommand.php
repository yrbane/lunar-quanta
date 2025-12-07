<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;

/**
 * Commande CLI pour sauvegarder les données du blog.
 *
 * Crée des archives compressées des articles, catégories, tags et médias.
 */
#[Command(name: 'blog:backup', description: 'Crée une sauvegarde complète des données du blog.')]
class BlogBackupCommand implements CommandInterface
{
    private string $basePath;
    private bool $verbose = false;

    public function execute(array $args): int
    {
        $this->basePath = dirname(__DIR__, 2);
        $this->verbose = in_array('-v', $args, true) || in_array('--verbose', $args, true);

        $outputDir = $this->parseOption($args, '--output') ?? $this->basePath . '/var/backups';
        $format = $this->parseOption($args, '--format') ?? 'zip';
        $includeMedia = !in_array('--no-media', $args, true);
        $includePublic = in_array('--include-public', $args, true);

        // Créer le répertoire de sortie si nécessaire
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->printHeader();

        try {
            $timestamp = date('Y-m-d_His');
            $backupName = "blog-backup-{$timestamp}";
            $tempDir = sys_get_temp_dir() . '/' . $backupName;

            echo "Configuration :\n";
            echo "  Format : {$format}\n";
            echo "  Destination : {$outputDir}\n";
            echo "  Médias : " . ($includeMedia ? 'Oui' : 'Non') . "\n";
            echo "  Fichiers publics : " . ($includePublic ? 'Oui' : 'Non') . "\n";
            echo "\n";

            // Créer le répertoire temporaire
            mkdir($tempDir, 0755, true);

            // Copier les données
            $stats = [
                'posts' => 0,
                'categories' => 0,
                'tags' => 0,
                'media' => 0,
                'public' => 0,
                'size' => 0,
            ];

            // Posts
            echo "→ Sauvegarde des articles...\n";
            $stats['posts'] = $this->copyDirectory(
                $this->basePath . '/data/blog/posts',
                $tempDir . '/data/blog/posts'
            );

            // Categories
            echo "→ Sauvegarde des catégories...\n";
            $stats['categories'] = $this->copyDirectory(
                $this->basePath . '/data/blog/categories',
                $tempDir . '/data/blog/categories'
            );

            // Tags
            echo "→ Sauvegarde des tags...\n";
            $stats['tags'] = $this->copyDirectory(
                $this->basePath . '/data/blog/tags',
                $tempDir . '/data/blog/tags'
            );

            // Media
            if ($includeMedia) {
                echo "→ Sauvegarde des médias...\n";
                $stats['media'] = $this->copyDirectory(
                    $this->basePath . '/public/uploads',
                    $tempDir . '/public/uploads'
                );
            }

            // Public blog files
            if ($includePublic) {
                echo "→ Sauvegarde des fichiers publics...\n";
                $stats['public'] = $this->copyDirectory(
                    $this->basePath . '/public/blog',
                    $tempDir . '/public/blog'
                );
            }

            // Créer le manifest
            $manifest = [
                'version' => '1.0',
                'created_at' => date('c'),
                'stats' => $stats,
                'options' => [
                    'include_media' => $includeMedia,
                    'include_public' => $includePublic,
                ],
            ];
            file_put_contents(
                $tempDir . '/manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            // Créer l'archive
            echo "\n→ Création de l'archive...\n";
            $archivePath = $this->createArchive($tempDir, $outputDir, $backupName, $format);

            // Nettoyer le répertoire temporaire
            $this->deleteDirectory($tempDir);

            // Calculer la taille
            $stats['size'] = filesize($archivePath);

            $this->printResults($archivePath, $stats);

            return 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Copie un répertoire récursivement.
     */
    private function copyDirectory(string $source, string $dest): int
    {
        if (!is_dir($source)) {
            return 0;
        }

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $count = 0;
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
                $count++;

                if ($this->verbose) {
                    echo "    " . $iterator->getSubPathname() . "\n";
                }
            }
        }

        return $count;
    }

    /**
     * Crée une archive à partir d'un répertoire.
     */
    private function createArchive(string $sourceDir, string $outputDir, string $name, string $format): string
    {
        $archivePath = $outputDir . '/' . $name;

        if ($format === 'zip') {
            $archivePath .= '.zip';

            $zip = new \ZipArchive();
            if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException("Impossible de créer l'archive ZIP");
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $relativePath = $iterator->getSubPathname();
                if ($item->isDir()) {
                    $zip->addEmptyDir($relativePath);
                } else {
                    $zip->addFile($item->getPathname(), $relativePath);
                }
            }

            $zip->close();

        } elseif ($format === 'tar' || $format === 'tar.gz') {
            $tarPath = $archivePath . '.tar';

            // Créer le tar
            $phar = new \PharData($tarPath);
            $phar->buildFromDirectory($sourceDir);

            if ($format === 'tar.gz') {
                // Compresser en gzip
                $phar->compress(\Phar::GZ);
                unlink($tarPath);
                $archivePath = $tarPath . '.gz';
            } else {
                $archivePath = $tarPath;
            }

        } else {
            throw new \RuntimeException("Format non supporté : {$format}");
        }

        return $archivePath;
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

    /**
     * Parse une option depuis les arguments.
     */
    private function parseOption(array $args, string $option): ?string
    {
        foreach ($args as $i => $arg) {
            if ($arg === $option && isset($args[$i + 1])) {
                return $args[$i + 1];
            }
            if (str_starts_with($arg, $option . '=')) {
                return substr($arg, strlen($option) + 1);
            }
        }
        return null;
    }

    /**
     * Formate une taille en bytes.
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

    private function printHeader(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Sauvegarde                         ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    private function printResults(string $archivePath, array $stats): void
    {
        echo "\n";
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                    SAUVEGARDE TERMINÉE                       │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-25s %35s │\n", "Articles", $stats['posts']);
        printf("│  %-25s %35s │\n", "Catégories", $stats['categories']);
        printf("│  %-25s %35s │\n", "Tags", $stats['tags']);
        printf("│  %-25s %35s │\n", "Médias", $stats['media']);
        printf("│  %-25s %35s │\n", "Fichiers publics", $stats['public']);
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-25s %35s │\n", "Taille archive", $this->formatBytes($stats['size']));
        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";
        echo "Archive créée : {$archivePath}\n";
        echo "\n";
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:backup [options]

Crée une sauvegarde complète des données du blog.

Options :
  -v, --verbose         Affiche les fichiers copiés
  --output=<path>       Répertoire de destination (défaut: var/backups)
  --format=<format>     Format de l'archive (zip, tar, tar.gz)
  --no-media            Exclure les fichiers médias
  --include-public      Inclure les fichiers publics générés

Exemples :
  blog:backup                               # Sauvegarde standard
  blog:backup --format=tar.gz               # Archive tar gzippée
  blog:backup --output=/backup              # Destination personnalisée
  blog:backup --no-media                    # Sans les médias
  blog:backup --include-public --verbose    # Complet avec détails

Contenu de la sauvegarde :
  - data/blog/posts/*.json      (Articles)
  - data/blog/categories/*.json (Catégories)
  - data/blog/tags/*.json       (Tags)
  - public/uploads/*            (Médias, si --no-media n'est pas spécifié)
  - public/blog/*               (Fichiers publics, si --include-public)
  - manifest.json               (Métadonnées de la sauvegarde)
HELP;
    }
}
