<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\StaticSite\StaticGenerator;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour publier automatiquement les articles programmés.
 *
 * Cette commande doit être exécutée régulièrement (cron job) pour
 * publier les articles dont la date de publication programmée est passée.
 *
 * Exemple cron (toutes les minutes):
 * * * * * * cd /path/to/project && php bin/console blog:publish-scheduled
 */
#[Command(name: 'blog:publish-scheduled', description: 'Publie les articles dont la date programmée est passée.')]
class BlogPublishScheduledCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $verbose = in_array('-v', $args, true) || in_array('--verbose', $args, true);
        $dryRun = in_array('--dry-run', $args, true);
        $regenerate = !in_array('--no-regenerate', $args, true);

        try {
            $basePath = dirname(__DIR__, 2);
            $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));

            // Trouver les articles à publier
            $readyToPublish = $postService->findReadyToPublish();

            if (empty($readyToPublish)) {
                if ($verbose) {
                    echo "Aucun article à publier.\n";
                }
                return 0;
            }

            if ($verbose || $dryRun) {
                echo "╔══════════════════════════════════════════════════════════════╗\n";
                echo "║         LUNAR BLOG - Publication Programmée                  ║\n";
                echo "╚══════════════════════════════════════════════════════════════╝\n\n";

                echo "Articles à publier : " . count($readyToPublish) . "\n\n";

                foreach ($readyToPublish as $post) {
                    echo "  → " . $post->getTitle() . "\n";
                    echo "    Programmé : " . $post->getScheduledPublishAt()->format('d/m/Y H:i') . "\n";
                }
                echo "\n";
            }

            if ($dryRun) {
                echo "Mode dry-run : aucune modification effectuée.\n";
                return 0;
            }

            // Publier les articles
            $count = $postService->publishScheduled();

            if ($verbose) {
                echo "✓ $count article(s) publié(s).\n";
            }

            // Régénérer le site statique si demandé
            if ($regenerate && $count > 0) {
                if ($verbose) {
                    echo "\n→ Régénération du site statique...\n";
                }

                $categoryService = new CategoryService(
                    new FileStorage($basePath . '/data/blog/categories')
                );

                $generator = new StaticGenerator(
                    $postService,
                    new MarkdownParser(),
                    $basePath . '/public/blog',
                    $basePath . '/template/blog',
                    'https://example.com'
                );
                $generator->setCategoryService($categoryService);

                $result = $generator->regenerate();

                if ($verbose) {
                    echo "✓ Site régénéré ({$result['posts']} articles).\n";
                }
            }

            return 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:publish-scheduled [options]

Publie automatiquement les articles dont la date de publication
programmée est passée.

Options :
  -v, --verbose      Affiche les détails de la publication
  --dry-run          Affiche ce qui serait publié sans modifier
  --no-regenerate    Ne pas régénérer le site statique après publication

Exemples :
  blog:publish-scheduled                  # Publie silencieusement
  blog:publish-scheduled -v               # Publie avec détails
  blog:publish-scheduled --dry-run        # Prévisualisation

Cron job recommandé (toutes les minutes) :
  * * * * * cd /path/to/project && php bin/console blog:publish-scheduled
HELP;
    }
}
