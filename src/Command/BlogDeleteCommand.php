<?php
/**
 * Lunar Quanta Framework - Commande de suppression d'un article.
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
 * Commande CLI pour supprimer un article.
 */
#[Command(name: 'blog:delete', description: 'Supprime un article.')]
class BlogDeleteCommand extends AbstractBlogCommand
{
    public function execute(array $args): int
    {
        // Parse arguments
        $identifier = null;
        $force = false;

        foreach ($args as $arg) {
            if ($arg === '--force' || $arg === '-f') {
                $force = true;
            } elseif (!str_starts_with($arg, '-') && $identifier === null) {
                $identifier = $arg;
            }
        }

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

            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║                    SUPPRESSION D'ARTICLE                     ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";
            echo "\n";

            // Show article info
            echo "  Article à supprimer :\n";
            echo "  ┌──────────────────────────────────────────────────────────┐\n";
            printf("  │  %-10s : %-44s │\n", "ID", $post->getId());
            printf("  │  %-10s : %-44s │\n", "Titre", mb_substr($post->getTitle(), 0, 44));
            printf("  │  %-10s : %-44s │\n", "Slug", $post->getSlug());
            printf("  │  %-10s : %-44s │\n", "Statut", $post->isPublished() ? 'Publié' : ($post->isDraft() ? 'Brouillon' : 'Archivé'));
            printf("  │  %-10s : %-44s │\n", "Créé le", $post->getCreatedAt()->format('d/m/Y H:i'));
            echo "  └──────────────────────────────────────────────────────────┘\n";
            echo "\n";

            // Require confirmation unless --force
            if (!$force) {
                echo "  ⚠️  Cette action est IRRÉVERSIBLE !\n\n";
                echo "  Pour confirmer la suppression, relancez avec --force :\n";
                echo "  ./bin/console blog:delete {$post->getId()} --force\n\n";
                return 0;
            }

            // Delete the post
            $postService->delete($post->getId());

            echo "  ✓ Article supprimé avec succès !\n\n";

            if ($post->isPublished()) {
                echo "  💡 L'article était publié. N'oubliez pas de régénérer le blog :\n";
                echo "     ./bin/console blog:regenerate\n\n";
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
Commande : blog:delete
Supprime un article.

Utilisation :
  ./bin/console blog:delete <id|slug> [options]

Arguments :
    <id|slug>               ID ou slug de l'article à supprimer

Options :
    -f, --force             Confirmer la suppression (obligatoire)
    --help                  Affiche cette aide

⚠️  ATTENTION : La suppression est définitive et irréversible !

Exemples :
    ./bin/console blog:delete abc123           # Affiche les détails
    ./bin/console blog:delete abc123 --force   # Supprime réellement
    ./bin/console blog:delete mon-slug -f      # Supprime par slug

HELP;
    }
}
