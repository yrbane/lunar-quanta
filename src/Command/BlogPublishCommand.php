<?php
/**
 * Lunar Quanta Framework - Commande de publication d'un article.
 *
 * @package    Lunar\Command
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.0.0
 */
declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour publier un article.
 */
#[Command(name: 'blog:publish', description: 'Publie un article.')]
class BlogPublishCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $basePath = dirname(__DIR__, 2);

        $identifier = $args[0] ?? null;

        if ($identifier === null || $identifier === '--help') {
            echo $this->getHelp();
            return $identifier === '--help' ? 0 : 1;
        }

        try {
            $postStorage = new FileStorage($basePath . '/data/blog/posts');
            $postService = new PostService($postStorage);

            // Find post
            $post = $postService->find($identifier);
            if ($post === null) {
                $post = $postService->findBySlug($identifier);
            }

            if ($post === null) {
                echo "\n✗ Article non trouvé : {$identifier}\n\n";
                return 1;
            }

            if ($post->isPublished()) {
                echo "\n⚠ L'article est déjà publié.\n";
                echo "  ID    : {$post->getId()}\n";
                echo "  Titre : {$post->getTitle()}\n\n";
                return 0;
            }

            // Publish
            $post->publish();
            $postService->update($post);

            echo "\n";
            echo "✓ Article publié avec succès !\n\n";
            echo "  ID        : {$post->getId()}\n";
            echo "  Titre     : {$post->getTitle()}\n";
            echo "  URL       : {$post->getUrl()}\n";
            echo "  Publié le : {$post->getPublishedAt()->format('d/m/Y H:i')}\n\n";
            echo "💡 N'oubliez pas de régénérer le blog : ./bin/console blog:regenerate\n\n";

            return 0;

        } catch (\Throwable $e) {
            echo "\n✗ Erreur : " . $e->getMessage() . "\n\n";
            return 1;
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : blog:publish
Publie un article (passage de brouillon à publié).

Utilisation :
  ./bin/console blog:publish <id|slug>

Arguments :
    <id|slug>               ID ou slug de l'article

Exemples :
    ./bin/console blog:publish abc123
    ./bin/console blog:publish mon-article-slug

HELP;
    }
}
