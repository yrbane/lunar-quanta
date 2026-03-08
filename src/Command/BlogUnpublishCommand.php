<?php
/**
 * Lunar Quanta Framework - Commande de dépublication d'un article.
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
 * Commande CLI pour dépublier un article.
 */
#[Command(name: 'blog:unpublish', description: 'Dépublie un article (retour en brouillon).')]
class BlogUnpublishCommand extends AbstractBlogCommand
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

            if ($post->isDraft()) {
                echo "\n⚠ L'article est déjà en brouillon.\n";
                echo "  ID    : {$post->getId()}\n";
                echo "  Titre : {$post->getTitle()}\n\n";
                return 0;
            }

            // Unpublish
            $post->unpublish();
            $postService->update($post);

            echo "\n";
            echo "✓ Article dépublié avec succès !\n\n";
            echo "  ID     : {$post->getId()}\n";
            echo "  Titre  : {$post->getTitle()}\n";
            echo "  Statut : Brouillon\n\n";
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
Commande : blog:unpublish
Dépublie un article (retour en brouillon).

Utilisation :
  ./bin/console blog:unpublish <id|slug>

Arguments :
    <id|slug>               ID ou slug de l'article

Exemples :
    ./bin/console blog:unpublish abc123
    ./bin/console blog:unpublish mon-article-slug

HELP;
    }
}
