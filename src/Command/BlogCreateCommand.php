<?php
/**
 * Lunar Quanta Framework - Commande de création d'un article.
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
 * Commande CLI pour créer un nouvel article.
 */
#[Command(name: 'blog:create', description: 'Crée un nouvel article.')]
class BlogCreateCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $basePath = dirname(__DIR__, 2);

        // Parse options
        $title = null;
        $content = null;
        $excerpt = null;
        $author = null;
        $category = null;
        $tags = [];
        $image = null;
        $publish = false;
        $fromFile = null;

        for ($i = 0; $i < count($args); $i++) {
            switch ($args[$i]) {
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
                case '--tags':
                    $tagsStr = $args[++$i] ?? '';
                    $tags = array_map('trim', explode(',', $tagsStr));
                    $tags = array_filter($tags);
                    break;
                case '--image':
                case '-i':
                    $image = $args[++$i] ?? null;
                    break;
                case '--publish':
                case '-p':
                    $publish = true;
                    break;
                case '--file':
                case '-f':
                    $fromFile = $args[++$i] ?? null;
                    break;
            }
        }

        // Read content from file if specified
        if ($fromFile !== null) {
            if (!file_exists($fromFile)) {
                echo "\n✗ Fichier non trouvé : {$fromFile}\n\n";
                return 1;
            }
            $content = file_get_contents($fromFile);
        }

        // Validate required fields
        if ($title === null || $content === null) {
            echo "\n✗ Les options --title et --content (ou --file) sont requises.\n";
            echo $this->getHelp();
            return 1;
        }

        try {
            $postStorage = new FileStorage($basePath . '/data/blog/posts');
            $categoryStorage = new FileStorage($basePath . '/data/blog/categories');
            $postService = new PostService($postStorage);
            $categoryService = new CategoryService($categoryStorage);

            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║              CRÉATION D'UN NOUVEL ARTICLE                    ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";
            echo "\n";

            // Create post
            echo "→ Création de l'article...\n";
            $post = $postService->create($title, $content);

            // Set optional fields
            if ($excerpt !== null) {
                $post->setExcerpt($excerpt);
            }
            if ($author !== null) {
                $post->setAuthor($author);
            }
            if ($category !== null) {
                // Verify category exists
                $cat = $categoryService->find($category);
                if ($cat === null) {
                    echo "  ⚠ Catégorie '{$category}' non trouvée, ignorée.\n";
                } else {
                    $post->setCategoryId($category);
                }
            }
            foreach ($tags as $tag) {
                $post->addTag($tag);
            }
            if ($image !== null) {
                $post->setFeaturedImage($image);
            }

            // Publish if requested
            if ($publish) {
                $post->publish();
                echo "→ Article publié automatiquement.\n";
            }

            // Save changes
            $postService->update($post);

            echo "\n";
            echo "┌──────────────────────────────────────────────────────────────┐\n";
            echo "│                      ✓ ARTICLE CRÉÉ                          │\n";
            echo "├──────────────────────────────────────────────────────────────┤\n";
            printf("│  %-12s : %-44s │\n", "ID", $post->getId());
            printf("│  %-12s : %-44s │\n", "Titre", mb_substr($post->getTitle(), 0, 44));
            printf("│  %-12s : %-44s │\n", "Slug", $post->getSlug());
            printf("│  %-12s : %-44s │\n", "Statut", $post->isPublished() ? 'Publié' : 'Brouillon');
            printf("│  %-12s : %-44s │\n", "URL", $post->getUrl());
            echo "└──────────────────────────────────────────────────────────────┘\n";
            echo "\n";

            if (!$post->isPublished()) {
                echo "  💡 Pour publier : ./bin/console blog:publish {$post->getId()}\n";
            }
            echo "  💡 Pour modifier : ./bin/console blog:edit {$post->getId()}\n";
            echo "  💡 Pour voir     : ./bin/console blog:show {$post->getId()}\n";
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
Commande : blog:create
Crée un nouvel article.

Utilisation :
  ./bin/console blog:create --title <titre> --content <contenu> [options]
  ./bin/console blog:create --title <titre> --file <fichier.md> [options]

Options requises :
    -t, --title <titre>     Titre de l'article
    -c, --content <text>    Contenu Markdown de l'article
    -f, --file <fichier>    Lire le contenu depuis un fichier

Options optionnelles :
    -e, --excerpt <text>    Extrait/résumé de l'article
    -a, --author <nom>      Nom de l'auteur
    --category <id>         ID de la catégorie
    --tags <tag1,tag2>      Tags séparés par des virgules
    -i, --image <url>       URL de l'image mise en avant
    -p, --publish           Publier immédiatement l'article
    --help                  Affiche cette aide

Exemples :
    ./bin/console blog:create -t "Mon Article" -c "# Introduction\n\nContenu..."
    ./bin/console blog:create --title "Guide PHP" --file article.md --publish
    ./bin/console blog:create -t "News" -c "Contenu" --tags "php,web" -a "John"

HELP;
    }
}
