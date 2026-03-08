<?php
/**
 * Lunar Quanta Framework - Commande d'archivage d'un article.
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

/**
 * Commande CLI pour archiver un article.
 */
#[Command(name: 'blog:archive', description: 'Archive un article.')]
class BlogArchiveCommand extends AbstractBlogCommand
{
    public function execute(array $args): int
    {
        $identifier = $args[0] ?? null;

        if ($identifier === null || $identifier === '--help') {
            echo $this->getHelp();
            return $identifier === '--help' ? 0 : 1;
        }

        try {
            $postService = $this->createPostService();
            $post = $this->findPostOrFail($postService, $identifier);

            if ($post === null) {
                echo "\n✗ Article non trouvé : {$identifier}\n\n";
                return 1;
            }

            if ($post->isArchived()) {
                echo "\n⚠ L'article est déjà archivé.\n";
                echo "  ID    : {$post->getId()}\n";
                echo "  Titre : {$post->getTitle()}\n\n";
                return 0;
            }

            $wasPublished = $post->isPublished();

            // Archive
            $post->archive();
            $postService->update($post);

            echo "\n";
            echo "✓ Article archivé avec succès !\n\n";
            echo "  ID     : {$post->getId()}\n";
            echo "  Titre  : {$post->getTitle()}\n";
            echo "  Statut : Archivé\n\n";

            if ($wasPublished) {
                echo "💡 L'article était publié. N'oubliez pas de régénérer le blog :\n";
                echo "   ./bin/console blog:regenerate\n\n";
            }

            return 0;

        } catch (\Throwable $e) {
            echo "\n✗ Erreur : " . $e->getMessage() . "\n\n";
            return 1;
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : blog:archive
Archive un article (le retire du blog sans le supprimer).

Utilisation :
  ./bin/console blog:archive <id|slug>

Arguments :
    <id|slug>               ID ou slug de l'article

Note :
    Un article archivé est conservé mais ne sera plus affiché.
    Pour le réactiver, utilisez blog:publish.

Exemples :
    ./bin/console blog:archive abc123
    ./bin/console blog:archive mon-article-slug

HELP;
    }
}
