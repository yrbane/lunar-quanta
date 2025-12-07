<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\ExportService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour exporter les articles du blog.
 */
#[Command(name: 'blog:export', description: 'Exporte les articles du blog en JSON, CSV ou XML.')]
class BlogExportCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $format = $args[0] ?? 'json';
        $outputPath = $args[1] ?? null;

        // Options
        $filters = [];
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--status=')) {
                $filters['status'] = substr($arg, 9);
            }
            if (str_starts_with($arg, '--category=')) {
                $filters['category'] = substr($arg, 11);
            }
            if (str_starts_with($arg, '--tag=')) {
                $filters['tag'] = substr($arg, 6);
            }
        }

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Export des Articles                ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        try {
            $basePath = dirname(__DIR__, 2);
            $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));
            $exportService = new ExportService($postService);

            echo "→ Format : " . strtoupper($format) . "\n";
            if (!empty($filters)) {
                echo "→ Filtres : " . json_encode($filters) . "\n";
            }
            echo "\n";

            $content = match (strtolower($format)) {
                'json' => $exportService->toJson($filters),
                'csv' => $exportService->toCsv($filters),
                'xml' => $exportService->toXml($filters),
                default => throw new \InvalidArgumentException("Format non supporté: $format (json, csv, xml)"),
            };

            if ($outputPath === null) {
                $extension = strtolower($format);
                $outputPath = $basePath . '/data/exports/blog-' . date('Ymd-His') . '.' . $extension;
            }

            // Créer le répertoire si nécessaire
            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($outputPath, $content);

            $count = substr_count($content, '"id"');
            $size = $this->formatBytes(strlen($content));

            echo "✓ Export terminé !\n";
            echo "  → Fichier : $outputPath\n";
            echo "  → Taille : $size\n";
            echo "\n";

            return 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur : " . $e->getMessage() . "\n\n";
            return 1;
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:export [format] [output] [options]

Formats disponibles :
  json    Export au format JSON (défaut)
  csv     Export au format CSV
  xml     Export au format XML

Options :
  --status=STATUS      Filtrer par statut (draft, published, archived)
  --category=ID        Filtrer par catégorie
  --tag=NAME           Filtrer par tag

Exemples :
  blog:export json                          # Export JSON dans data/exports/
  blog:export csv ./export.csv              # Export CSV vers fichier spécifié
  blog:export json --status=published       # Export des articles publiés uniquement
  blog:export csv --tag=php                 # Export des articles avec le tag PHP
HELP;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
