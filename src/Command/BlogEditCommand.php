<?php
/**
 * Lunar Quanta Framework - Commande d'édition d'un article.
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
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour éditer un article existant.
 */
#[Command(name: 'blog:edit', description: 'Modifie un article existant.')]
class BlogEditCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $basePath = dirname(__DIR__, 2);

        // Get article ID
        $identifier = null;
        $title = null;
        $content = null;
        $excerpt = null;
        $author = null;
        $category = null;
        $addTags = [];
        $removeTags = [];
        $setTags = null;
        $image = null;
        $fromFile = null;
        $slug = null;

        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];
            if (!str_starts_with($arg, '-') && $identifier === null) {
                $identifier = $arg;
                continue;
            }

            switch ($arg) {
                case '--title':
                case '-t':
                    $title = $args[++$i] ?? null;
                    break;
                case '--content':
                case '-c':
                    $content = $args[++$i] ?? null;
                    break;
                case '--excerpt':
                case '-e':
                    $excerpt = $args[++$i] ?? null;
                    break;
                case '--author':
                case '-a':
                    $author = $args[++$i] ?? null;
                    break;
                case '--category':
                    $category = $args[++$i] ?? null;
                    break;
                case '--add-tags':
                    $tagsStr = $args[++$i] ?? '';
                    $addTags = array_map('trim', explode(',', $tagsStr));
                    $addTags = array_filter($addTags);
                    break;
                case '--remove-tags':
                    $tagsStr = $args[++$i] ?? '';
                    $removeTags = array_map('trim', explode(',', $tagsStr));
                    $removeTags = array_filter($removeTags);
                    break;
                case '--set-tags':
                    $tagsStr = $args[++$i] ?? '';
                    $setTags = array_map('trim', explode(',', $tagsStr));
                    $setTags = array_filter($setTags);
                    break;
                case '--image':
                case '-i':
                    $image = $args[++$i] ?? null;
                    break;
                case '--file':
                case '-f':
                    $fromFile = $args[++$i] ?? null;
                    break;
                case '--slug':
                case '-s':
                    $slug = $args[++$i] ?? null;
                    break;
            }
        }

        if ($identifier === null || $identifier === '--help') {
            echo $this->getHelp();
            return $identifier === '--help' ? 0 : 1;
        }

        // Read content from file if specified
        if ($fromFile !== null) {
            if (!file_exists($fromFile)) {
                echo "\n✗ Fichier non trouvé : {$fromFile}\n\n";
                return 1;
            }
            $content = file_get_contents($fromFile);
        }

        try {
            $postStorage = new FileStorage($basePath . '/data/blog/posts');
            $categoryStorage = new FileStorage($basePath . '/data/blog/categories');
            $postService = new PostService($postStorage);
            $categoryService = new CategoryService($categoryStorage);

            // Find post
            $post = $postService->find($identifier);
            if ($post === null) {
                $post = $postService->findBySlug($identifier);
            }

            if ($post === null) {
                echo "\n✗ Article non trouvé : {$identifier}\n\n";
                return 1;
            }

            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║                    MODIFICATION D'ARTICLE                    ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";
            echo "\n";

            $changes = [];

            // Apply changes
            if ($title !== null) {
                $old = $post->getTitle();
                $post->setTitle($title);
                $changes[] = "Titre : '{$old}' → '{$title}'";
            }

            if ($content !== null) {
                $post->setContent($content);
                $changes[] = "Contenu mis à jour (" . strlen($content) . " caractères)";
            }

            if ($excerpt !== null) {
                $post->setExcerpt($excerpt);
                $changes[] = "Extrait mis à jour";
            }

            if ($author !== null) {
                $old = $post->getAuthor();
                $post->setAuthor($author);
                $changes[] = "Auteur : '{$old}' → '{$author}'";
            }

            if ($category !== null) {
                if ($category === 'null' || $category === '') {
                    $post->setCategoryId(null);
                    $changes[] = "Catégorie supprimée";
                } else {
                    $cat = $categoryService->find($category);
                    if ($cat === null) {
                        echo "  ⚠ Catégorie '{$category}' non trouvée, ignorée.\n";
                    } else {
                        $post->setCategoryId($category);
                        $changes[] = "Catégorie : '{$cat->getName()}'";
                    }
                }
            }

            if ($slug !== null) {
                $old = $post->getSlug();
                $post->setSlug($slug);
                $changes[] = "Slug : '{$old}' → '{$slug}'";
            }

            if ($image !== null) {
                if ($image === 'null' || $image === '') {
                    $post->setFeaturedImage(null);
                    $changes[] = "Image supprimée";
                } else {
                    $post->setFeaturedImage($image);
                    $changes[] = "Image mise à jour";
                }
            }

            // Handle tags
            if ($setTags !== null) {
                // Clear and set new tags
                foreach ($post->getTags() as $tag) {
                    $post->removeTag($tag);
                }
                foreach ($setTags as $tag) {
                    $post->addTag($tag);
                }
                $changes[] = "Tags remplacés : " . implode(', ', $setTags);
            } else {
                foreach ($addTags as $tag) {
                    $post->addTag($tag);
                    $changes[] = "Tag ajouté : {$tag}";
                }
                foreach ($removeTags as $tag) {
                    $post->removeTag($tag);
                    $changes[] = "Tag supprimé : {$tag}";
                }
            }

            if (empty($changes)) {
                echo "  ⚠ Aucune modification spécifiée.\n";
                echo "  Utilisez --help pour voir les options disponibles.\n\n";
                return 0;
            }

            // Save changes
            $postService->update($post);

            echo "  ✓ Article modifié : {$post->getId()}\n\n";
            echo "  Modifications appliquées :\n";
            foreach ($changes as $change) {
                echo "    • {$change}\n";
            }

            echo "\n";
            echo "┌──────────────────────────────────────────────────────────────┐\n";
            printf("│  %-12s : %-44s │\n", "ID", $post->getId());
            printf("│  %-12s : %-44s │\n", "Titre", mb_substr($post->getTitle(), 0, 44));
            printf("│  %-12s : %-44s │\n", "Slug", $post->getSlug());
            printf("│  %-12s : %-44s │\n", "Modifié", $post->getUpdatedAt()->format('d/m/Y H:i'));
            echo "└──────────────────────────────────────────────────────────────┘\n";
            echo "\n";

            if ($post->isPublished()) {
                echo "  💡 N'oubliez pas de régénérer le blog : ./bin/console blog:regenerate\n\n";
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
Commande : blog:edit
Modifie un article existant.

Utilisation :
  ./bin/console blog:edit <id|slug> [options]

Arguments :
    <id|slug>               ID ou slug de l'article à modifier

Options de contenu :
    -t, --title <titre>     Nouveau titre
    -c, --content <text>    Nouveau contenu Markdown
    -f, --file <fichier>    Lire le contenu depuis un fichier
    -e, --excerpt <text>    Nouvel extrait
    -a, --author <nom>      Nouvel auteur
    -s, --slug <slug>       Nouveau slug
    -i, --image <url>       Nouvelle image (ou 'null' pour supprimer)
    --category <id>         Nouvelle catégorie (ou 'null' pour supprimer)

Options de tags :
    --add-tags <t1,t2>      Ajouter des tags
    --remove-tags <t1,t2>   Supprimer des tags
    --set-tags <t1,t2>      Remplacer tous les tags

Autres :
    --help                  Affiche cette aide

Exemples :
    ./bin/console blog:edit abc123 --title "Nouveau Titre"
    ./bin/console blog:edit mon-slug --file nouveau-contenu.md
    ./bin/console blog:edit abc123 --add-tags "php,web" --author "John"
    ./bin/console blog:edit abc123 --set-tags "nouveau,tags"
    ./bin/console blog:edit abc123 --image null --category null

HELP;
    }
}
