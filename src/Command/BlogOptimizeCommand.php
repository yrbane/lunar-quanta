<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MinificationService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour optimiser les assets et le contenu du blog.
 */
#[Command(name: 'blog:optimize', description: 'Optimise les assets et le contenu du blog.')]
class BlogOptimizeCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $dryRun = in_array('--dry-run', $args, true);
        $verbose = in_array('-v', $args, true) || in_array('--verbose', $args, true);

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Optimisation                       ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        if ($dryRun) {
            echo "  [Mode simulation - aucune modification]\n\n";
        }

        try {
            $basePath = dirname(__DIR__, 2);
            $publicPath = $basePath . '/public/blog';

            $totalSaved = 0;
            $filesOptimized = 0;

            // 1. Optimiser les fichiers CSS
            echo "  → Optimisation des fichiers CSS...\n";
            $cssResult = $this->optimizeFiles($publicPath, '*.css', 'css', $dryRun, $verbose);
            $totalSaved += $cssResult['saved'];
            $filesOptimized += $cssResult['count'];

            // 2. Optimiser les fichiers JS
            echo "  → Optimisation des fichiers JavaScript...\n";
            $jsResult = $this->optimizeFiles($publicPath, '*.js', 'js', $dryRun, $verbose);
            $totalSaved += $jsResult['saved'];
            $filesOptimized += $jsResult['count'];

            // 3. Optimiser les fichiers HTML
            echo "  → Optimisation des fichiers HTML...\n";
            $htmlResult = $this->optimizeFiles($publicPath, '*.html', 'html', $dryRun, $verbose);
            $totalSaved += $htmlResult['saved'];
            $filesOptimized += $htmlResult['count'];

            // 4. Analyser les images
            echo "  → Analyse des images...\n";
            $imageStats = $this->analyzeImages($publicPath, $verbose);

            // 5. Vérifier les liens cassés
            echo "  → Vérification des liens internes...\n";
            $brokenLinks = $this->checkBrokenLinks($publicPath, $verbose);

            // Résumé
            echo "\n";
            echo "┌──────────────────────────────────────────────────────────────┐\n";
            echo "│                        RÉSUMÉ                                │\n";
            echo "├──────────────────────────────────────────────────────────────┤\n";
            printf("│  %-35s %24s │\n", "Fichiers optimisés", $filesOptimized);
            printf("│  %-35s %21s Ko │\n", "Espace économisé", number_format($totalSaved / 1024, 2));
            printf("│  %-35s %24s │\n", "Images trouvées", $imageStats['count']);
            printf("│  %-35s %21s Ko │\n", "Taille totale images", number_format($imageStats['size'] / 1024, 2));
            printf("│  %-35s %24s │\n", "Liens cassés", count($brokenLinks));
            echo "└──────────────────────────────────────────────────────────────┘\n";

            if (!empty($brokenLinks)) {
                echo "\n  ⚠ Liens cassés détectés :\n";
                foreach (array_slice($brokenLinks, 0, 10) as $link) {
                    echo "    - {$link}\n";
                }
                if (count($brokenLinks) > 10) {
                    echo "    ... et " . (count($brokenLinks) - 10) . " autres\n";
                }
            }

            echo "\n  ✓ Optimisation terminée !\n\n";

            return 0;

        } catch (\Throwable $e) {
            echo "  ✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Optimise les fichiers d'un type donné.
     */
    private function optimizeFiles(string $path, string $pattern, string $type, bool $dryRun, bool $verbose): array
    {
        $minifier = new MinificationService();
        $files = glob($path . '/**/' . $pattern) ?: [];
        $files = array_merge($files, glob($path . '/' . $pattern) ?: []);

        $totalSaved = 0;
        $count = 0;

        foreach ($files as $file) {
            if (str_contains($file, '.min.')) {
                continue; // Ignorer les fichiers déjà minifiés
            }

            $original = file_get_contents($file);
            $minified = $minifier->file($original, $type);

            $originalSize = strlen($original);
            $minifiedSize = strlen($minified);
            $saved = $originalSize - $minifiedSize;

            if ($saved > 0) {
                $count++;
                $totalSaved += $saved;

                if ($verbose) {
                    $percent = round(($saved / $originalSize) * 100, 1);
                    echo "    ✓ " . basename($file) . " (-{$percent}%)\n";
                }

                if (!$dryRun) {
                    file_put_contents($file, $minified);
                }
            }
        }

        return ['count' => $count, 'saved' => $totalSaved];
    }

    /**
     * Analyse les images du blog.
     */
    private function analyzeImages(string $path, bool $verbose): array
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $totalSize = 0;
        $count = 0;
        $largeImages = [];

        foreach ($extensions as $ext) {
            $files = glob($path . '/**/*.' . $ext) ?: [];
            $files = array_merge($files, glob($path . '/*.' . $ext) ?: []);

            foreach ($files as $file) {
                $size = filesize($file);
                $totalSize += $size;
                $count++;

                if ($size > 500 * 1024) { // > 500 Ko
                    $largeImages[] = [
                        'file' => basename($file),
                        'size' => $size,
                    ];
                }
            }
        }

        if ($verbose && !empty($largeImages)) {
            echo "    ⚠ Images volumineuses (>500 Ko) :\n";
            foreach (array_slice($largeImages, 0, 5) as $img) {
                printf("      - %s (%.1f Ko)\n", $img['file'], $img['size'] / 1024);
            }
        }

        return ['count' => $count, 'size' => $totalSize, 'large' => $largeImages];
    }

    /**
     * Vérifie les liens internes cassés.
     */
    private function checkBrokenLinks(string $path, bool $verbose): array
    {
        $brokenLinks = [];
        $htmlFiles = glob($path . '/**/*.html') ?: [];
        $htmlFiles = array_merge($htmlFiles, glob($path . '/*.html') ?: []);

        foreach ($htmlFiles as $file) {
            $content = file_get_contents($file);

            // Trouver tous les liens internes
            preg_match_all('/href="(\/[^"]+)"/', $content, $matches);

            foreach ($matches[1] as $link) {
                // Ignorer les ancres et les liens externes
                if (str_starts_with($link, '#') || str_starts_with($link, 'http')) {
                    continue;
                }

                $targetPath = dirname($path) . $link;
                if (str_ends_with($link, '/')) {
                    $targetPath .= 'index.html';
                }

                if (!file_exists($targetPath) && !file_exists($targetPath . '.html')) {
                    $brokenLinks[] = $link . ' (dans ' . basename($file) . ')';
                }
            }
        }

        return array_unique($brokenLinks);
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:optimize [options]

Optimise les assets et le contenu du blog statique.

Options :
  --dry-run     Simule l'optimisation sans modifier les fichiers
  -v, --verbose Affiche les détails de chaque fichier optimisé

Actions effectuées :
  - Minification des fichiers CSS
  - Minification des fichiers JavaScript
  - Minification des fichiers HTML
  - Analyse des images volumineuses
  - Vérification des liens internes cassés

Exemple :
  php bin/console blog:optimize
  php bin/console blog:optimize --dry-run -v
HELP;
    }
}
