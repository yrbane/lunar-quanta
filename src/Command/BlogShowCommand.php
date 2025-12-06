<?php
/**
 * Lunar Quanta Framework - Commande d'affichage d'un article.
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
use Lunar\Entity\PostStatus;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour afficher les détails d'un article.
 */
#[Command(name: 'blog:show', description: 'Affiche les détails d\'un article.')]
class BlogShowCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $basePath = dirname(__DIR__, 2);

        // Get article ID or slug
        $identifier = $args[0] ?? null;
        $showContent = in_array('--content', $args) || in_array('-c', $args);
        $jsonOutput = in_array('--json', $args) || in_array('-j', $args);

        if ($identifier === null || $identifier === '--help') {
            echo $this->getHelp();
            return $identifier === '--help' ? 0 : 1;
        }

        try {
            $postStorage = new FileStorage($basePath . '/data/blog/posts');
            $categoryStorage = new FileStorage($basePath . '/data/blog/categories');
            $postService = new PostService($postStorage);
            $categoryService = new CategoryService($categoryStorage);

            // Find post by ID or slug
            $post = $postService->find($identifier);
            if ($post === null) {
                $post = $postService->findBySlug($identifier);
            }

            if ($post === null) {
                echo "\n✗ Article non trouvé : {$identifier}\n\n";
                return 1;
            }

            // JSON output
            if ($jsonOutput) {
                echo json_encode($post->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                return 0;
            }

            // Get category name
            $categoryName = 'Non catégorisé';
            if ($post->getCategoryId() !== null) {
                $category = $categoryService->find($post->getCategoryId());
                $categoryName = $category?->getName() ?? 'Inconnue';
            }

            $statusText = match ($post->getStatus()) {
                PostStatus::PUBLISHED => '✓ Publié',
                PostStatus::DRAFT => '✎ Brouillon',
                PostStatus::ARCHIVED => '📦 Archivé',
            };

            // Display
            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
            echo "║                           DÉTAILS DE L'ARTICLE                               ║\n";
            echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
            echo "\n";

            echo "  📝 Titre      : " . $post->getTitle() . "\n";
            echo "  🔗 Slug       : " . $post->getSlug() . "\n";
            echo "  🆔 ID         : " . $post->getId() . "\n";
            echo "  📊 Statut     : " . $statusText . "\n";
            echo "  📁 Catégorie  : " . $categoryName . "\n";
            echo "  👤 Auteur     : " . ($post->getAuthor() ?: 'Non défini') . "\n";
            echo "\n";

            echo "  📅 Créé le    : " . $post->getCreatedAt()->format('d/m/Y H:i') . "\n";
            echo "  ✏️  Modifié le : " . $post->getUpdatedAt()->format('d/m/Y H:i') . "\n";
            if ($post->getPublishedAt() !== null) {
                echo "  🚀 Publié le  : " . $post->getPublishedAt()->format('d/m/Y H:i') . "\n";
            }
            echo "\n";

            echo "  ⏱️  Lecture   : ~" . $post->getReadingTime() . " min (" . $post->getWordCount() . " mots)\n";
            echo "  🔗 URL       : " . $post->getUrl() . "\n";
            echo "\n";

            // Tags
            $tags = $post->getTags();
            if (!empty($tags)) {
                echo "  🏷️  Tags      : " . implode(', ', $tags) . "\n";
                echo "\n";
            }

            // Featured image
            if ($post->getFeaturedImage()) {
                echo "  🖼️  Image     : " . $post->getFeaturedImage() . "\n";
                echo "\n";
            }

            // Excerpt
            if ($post->getExcerpt()) {
                echo "  📋 Extrait :\n";
                echo "  ┌" . str_repeat('─', 74) . "┐\n";
                $excerptLines = wordwrap($post->getExcerpt(), 72, "\n", true);
                foreach (explode("\n", $excerptLines) as $line) {
                    echo "  │ " . str_pad($line, 72) . " │\n";
                }
                echo "  └" . str_repeat('─', 74) . "┘\n";
                echo "\n";
            }

            // Content
            if ($showContent) {
                echo "  📄 Contenu :\n";
                echo "  ┌" . str_repeat('─', 74) . "┐\n";
                $contentLines = explode("\n", $post->getContent());
                foreach ($contentLines as $line) {
                    $wrappedLines = wordwrap($line, 72, "\n", true);
                    foreach (explode("\n", $wrappedLines) as $wLine) {
                        echo "  │ " . str_pad(substr($wLine, 0, 72), 72) . " │\n";
                    }
                }
                echo "  └" . str_repeat('─', 74) . "┘\n";
                echo "\n";
            } else {
                echo "  💡 Utilisez --content ou -c pour afficher le contenu complet.\n";
                echo "\n";
            }

            echo "  ═══════════════════════════════════════════════════════════════════════════\n";
            echo "  Actions disponibles :\n";
            echo "    • blog:edit {$post->getId()}       Modifier l'article\n";
            echo "    • blog:delete {$post->getId()}     Supprimer l'article\n";
            if ($post->isDraft()) {
                echo "    • blog:publish {$post->getId()}    Publier l'article\n";
            } elseif ($post->isPublished()) {
                echo "    • blog:unpublish {$post->getId()}  Dépublier l'article\n";
            }
            echo "\n";

            return 0;

        } catch (\Throwable $e) {
            echo "\n✗ Erreur : " . $e->getMessage() . "\n\n";
            return 1;
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : blog:show
Affiche les détails d'un article.

Utilisation :
  ./bin/console blog:show <id|slug> [options]

Arguments :
    <id|slug>               ID ou slug de l'article

Options :
    -c, --content           Afficher le contenu complet
    -j, --json              Sortie au format JSON
    --help                  Affiche cette aide

Exemples :
    ./bin/console blog:show abc123-def456
    ./bin/console blog:show mon-article-slug
    ./bin/console blog:show abc123 --content
    ./bin/console blog:show abc123 --json

HELP;
    }
}
