<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\SearchIndexService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour générer l'index de recherche.
 */
#[Command(name: 'blog:search-index', description: 'Génère l\'index de recherche pour le blog.')]
class BlogSearchIndexCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $verbose = in_array('-v', $args, true) || in_array('--verbose', $args, true);

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Index de Recherche                 ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        try {
            $basePath = dirname(__DIR__, 2);

            $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));
            $searchService = new SearchIndexService();

            echo "  → Chargement des articles...\n";
            $posts = $postService->findPublished();
            echo "    ✓ " . count($posts) . " articles trouvés\n\n";

            echo "  → Construction de l'index...\n";
            $index = $searchService->buildIndex($posts);
            echo "    ✓ " . count($index['documents']) . " documents indexés\n";
            echo "    ✓ " . count($index['metadata']['top_keywords']) . " mots-clés extraits\n\n";

            $outputPath = $basePath . '/public/blog/search-index.json';
            echo "  → Sauvegarde de l'index...\n";
            $searchService->saveIndex($index, $outputPath);
            echo "    ✓ Index sauvegardé : {$outputPath}\n\n";

            // Générer le script JS
            $jsPath = $basePath . '/public/blog/assets/search.js';
            if (!is_dir(dirname($jsPath))) {
                mkdir(dirname($jsPath), 0755, true);
            }
            file_put_contents($jsPath, $searchService->generateSearchScript('/blog/search-index.json'));
            echo "    ✓ Script généré : {$jsPath}\n";

            // Générer le CSS
            $cssPath = $basePath . '/public/blog/assets/search.css';
            file_put_contents($cssPath, $searchService->generateSearchCss());
            echo "    ✓ CSS généré : {$cssPath}\n\n";

            if ($verbose) {
                echo "┌──────────────────────────────────────────────────────────────┐\n";
                echo "│                    TOP MOTS-CLÉS                             │\n";
                echo "├──────────────────────────────────────────────────────────────┤\n";
                foreach (array_slice($index['metadata']['top_keywords'], 0, 15) as $keyword) {
                    printf("│  %-58s  │\n", $keyword);
                }
                echo "└──────────────────────────────────────────────────────────────┘\n";
                echo "\n";
            }

            $fileSize = filesize($outputPath);
            echo "  ✓ Index généré avec succès (" . number_format($fileSize / 1024, 2) . " Ko)\n\n";

            return 0;

        } catch (\Throwable $e) {
            echo "  ✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:search-index [options]

Génère un index de recherche JSON pour la recherche côté client.

Options :
  -v, --verbose  Affiche les mots-clés extraits

Fichiers générés :
  - public/blog/search-index.json  Index des articles
  - public/blog/assets/search.js   Script de recherche
  - public/blog/assets/search.css  Styles des résultats

L'index contient :
  - Titre, slug, URL de chaque article
  - Mots-clés extraits du contenu
  - Extrait pour l'affichage
  - Métadonnées (auteur, tags, date)

Exemple :
  php bin/console blog:search-index
  php bin/console blog:search-index -v
HELP;
    }
}
