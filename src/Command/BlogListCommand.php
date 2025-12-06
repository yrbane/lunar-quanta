<?php
/**
 * Lunar Quanta Framework - Commande de listage des articles.
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
 * Commande CLI pour lister les articles du blog.
 */
#[Command(name: 'blog:list', description: 'Liste tous les articles du blog.')]
class BlogListCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $basePath = dirname(__DIR__, 2);

        // Parse options
        $status = null;
        $limit = 20;
        $page = 1;
        $search = null;
        $category = null;

        for ($i = 0; $i < count($args); $i++) {
            switch ($args[$i]) {
                case '--status':
                case '-s':
                    $status = $args[++$i] ?? null;
                    break;
                case '--limit':
                case '-l':
                    $limit = (int) ($args[++$i] ?? 20);
                    break;
                case '--page':
                case '-p':
                    $page = (int) ($args[++$i] ?? 1);
                    break;
                case '--search':
                case '-q':
                    $search = $args[++$i] ?? null;
                    break;
                case '--category':
                case '-c':
                    $category = $args[++$i] ?? null;
                    break;
                case '--all':
                case '-a':
                    $limit = 0;
                    break;
            }
        }

        try {
            $postStorage = new FileStorage($basePath . '/data/blog/posts');
            $categoryStorage = new FileStorage($basePath . '/data/blog/categories');
            $postService = new PostService($postStorage);
            $categoryService = new CategoryService($categoryStorage);

            // Get posts based on status filter
            $posts = match ($status) {
                'published' => $postService->findPublished(),
                'draft' => $postService->findDrafts(),
                default => $postService->all()
            };

            // Filter by category
            if ($category !== null) {
                $posts = array_filter($posts, fn($p) => $p->getCategoryId() === $category);
            }

            // Filter by search term
            if ($search !== null) {
                $searchLower = strtolower($search);
                $posts = array_filter($posts, function($p) use ($searchLower) {
                    return str_contains(strtolower($p->getTitle()), $searchLower)
                        || str_contains(strtolower($p->getExcerpt()), $searchLower);
                });
            }

            // Sort by creation date (newest first)
            usort($posts, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

            $total = count($posts);

            // Pagination
            if ($limit > 0) {
                $offset = ($page - 1) * $limit;
                $posts = array_slice($posts, $offset, $limit);
            }

            // Display header
            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
            echo "║                           ARTICLES DU BLOG                                   ║\n";
            echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
            echo "\n";

            if (empty($posts)) {
                echo "  Aucun article trouvé.\n\n";
                return 0;
            }

            // Stats
            $allPosts = $postService->all();
            $published = count(array_filter($allPosts, fn($p) => $p->isPublished()));
            $drafts = count(array_filter($allPosts, fn($p) => $p->isDraft()));
            $archived = count(array_filter($allPosts, fn($p) => $p->isArchived()));

            echo "  📊 Total: {$total} | ✓ Publiés: {$published} | ✎ Brouillons: {$drafts} | 📦 Archivés: {$archived}\n";
            echo "\n";

            // Table header
            echo "┌──────────────────┬─────────────────────────────────────────┬────────────┬────────────┐\n";
            echo "│ ID               │ Titre                                   │ Statut     │ Date       │\n";
            echo "├──────────────────┼─────────────────────────────────────────┼────────────┼────────────┤\n";

            foreach ($posts as $post) {
                $id = substr($post->getId(), 0, 16);
                $title = mb_substr($post->getTitle(), 0, 39);
                $title = str_pad($title, 39);

                $statusIcon = match ($post->getStatus()) {
                    PostStatus::PUBLISHED => '✓ Publié  ',
                    PostStatus::DRAFT => '✎ Brouillon',
                    PostStatus::ARCHIVED => '📦 Archivé',
                };

                $date = $post->getCreatedAt()->format('d/m/Y');

                printf("│ %-16s │ %-39s │ %-10s │ %s │\n", $id, $title, $statusIcon, $date);
            }

            echo "└──────────────────┴─────────────────────────────────────────┴────────────┴────────────┘\n";

            // Pagination info
            if ($limit > 0 && $total > $limit) {
                $totalPages = (int) ceil($total / $limit);
                echo "\n  Page {$page}/{$totalPages} ({$total} articles)\n";
            }

            echo "\n";
            echo "  💡 Utilisez 'blog:show <id>' pour voir les détails d'un article.\n";
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
Commande : blog:list
Liste tous les articles du blog.

Utilisation :
  ./bin/console blog:list [options]

Options :
    -s, --status <status>   Filtrer par statut (published, draft, archived)
    -l, --limit <n>         Nombre d'articles par page (défaut: 20)
    -p, --page <n>          Numéro de page (défaut: 1)
    -q, --search <terme>    Rechercher dans le titre et l'extrait
    -c, --category <id>     Filtrer par catégorie
    -a, --all               Afficher tous les articles sans pagination
    --help                  Affiche cette aide

Exemples :
    ./bin/console blog:list
    ./bin/console blog:list --status published
    ./bin/console blog:list -s draft -l 10
    ./bin/console blog:list --search "quantum"
    ./bin/console blog:list --all

HELP;
    }
}
