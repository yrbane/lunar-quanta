<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\StaticSite\EnhancedStaticGenerator;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour surveiller les changements et régénérer automatiquement.
 */
#[Command(name: 'blog:watch', description: 'Surveille les changements et régénère automatiquement.')]
class BlogWatchCommand implements CommandInterface
{
    private array $lastModified = [];

    public function execute(array $args): int
    {
        $interval = 2; // secondes

        // Parser l'intervalle si fourni
        foreach ($args as $i => $arg) {
            if ($arg === '--interval' && isset($args[$i + 1])) {
                $interval = max(1, (int) $args[$i + 1]);
            }
        }

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Mode Watch                         ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "  Surveillance des changements (Ctrl+C pour arrêter)\n";
        echo "  Intervalle : {$interval}s\n\n";

        try {
            $basePath = dirname(__DIR__, 2);

            $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));
            $generator = new EnhancedStaticGenerator(
                $postService,
                new MarkdownParser(),
                $basePath . '/public/blog',
                $basePath . '/template/blog',
                'https://example.com'
            );

            $watchPaths = [
                $basePath . '/data/blog/posts' => 'posts',
                $basePath . '/template/blog' => 'templates',
                $basePath . '/data/blog/categories' => 'categories',
            ];

            // Initialiser les timestamps
            $this->initializeTimestamps($watchPaths);

            $regenerationCount = 0;
            $lastCheck = time();

            while (true) {
                $changes = $this->detectChanges($watchPaths);

                if (!empty($changes)) {
                    $regenerationCount++;
                    echo "\n  [" . date('H:i:s') . "] Changements détectés :\n";

                    foreach ($changes as $change) {
                        echo "    • {$change['type']}: {$change['file']}\n";
                    }

                    echo "\n  → Régénération en cours...\n";
                    $start = microtime(true);

                    $result = $generator->generateAll();

                    $duration = round((microtime(true) - $start) * 1000);
                    echo "  ✓ Terminé en {$duration}ms\n";
                    echo "    Posts: {$result['posts']}, Index: {$result['index']}, ";
                    echo "Tags: {$result['tags']}, Categories: {$result['categories']}\n";
                }

                // Afficher un point toutes les 10 secondes pour montrer que c'est actif
                if (time() - $lastCheck >= 10) {
                    echo ".";
                    $lastCheck = time();
                }

                sleep($interval);
            }

        } catch (\Throwable $e) {
            echo "  ✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }

        return 0;
    }

    /**
     * Initialise les timestamps des fichiers.
     */
    private function initializeTimestamps(array $watchPaths): void
    {
        foreach ($watchPaths as $path => $type) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*.{json,html,md}', GLOB_BRACE) ?: [];
            foreach ($files as $file) {
                $this->lastModified[$file] = filemtime($file);
            }
        }
    }

    /**
     * Détecte les changements dans les fichiers surveillés.
     */
    private function detectChanges(array $watchPaths): array
    {
        $changes = [];

        foreach ($watchPaths as $path => $type) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*.{json,html,md}', GLOB_BRACE) ?: [];

            // Vérifier les fichiers modifiés ou nouveaux
            foreach ($files as $file) {
                $mtime = filemtime($file);

                if (!isset($this->lastModified[$file])) {
                    // Nouveau fichier
                    $changes[] = [
                        'type' => 'added',
                        'file' => basename($file),
                        'category' => $type,
                    ];
                    $this->lastModified[$file] = $mtime;
                } elseif ($this->lastModified[$file] < $mtime) {
                    // Fichier modifié
                    $changes[] = [
                        'type' => 'modified',
                        'file' => basename($file),
                        'category' => $type,
                    ];
                    $this->lastModified[$file] = $mtime;
                }
            }

            // Vérifier les fichiers supprimés
            $currentFiles = array_flip($files);
            foreach ($this->lastModified as $file => $mtime) {
                if (str_starts_with($file, $path) && !isset($currentFiles[$file])) {
                    $changes[] = [
                        'type' => 'deleted',
                        'file' => basename($file),
                        'category' => $type,
                    ];
                    unset($this->lastModified[$file]);
                }
            }
        }

        return $changes;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:watch [options]

Surveille les changements dans les articles, templates et catégories,
et régénère automatiquement le blog statique.

Options :
  --interval <seconds>  Intervalle de vérification (défaut: 2s)

Dossiers surveillés :
  - data/blog/posts      Articles (fichiers JSON)
  - data/blog/categories Catégories (fichiers JSON)
  - template/blog        Templates (fichiers HTML)

Actions détectées :
  - Ajout de fichiers
  - Modification de fichiers
  - Suppression de fichiers

Exemple :
  php bin/console blog:watch
  php bin/console blog:watch --interval 5

Note : Utilisez Ctrl+C pour arrêter la surveillance.
HELP;
    }
}
