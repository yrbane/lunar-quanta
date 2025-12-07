<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Entity\PostStatus;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Blog\TagService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour afficher les statistiques du blog.
 */
#[Command(name: 'blog:stats', description: 'Affiche les statistiques détaillées du blog.')]
class BlogStatsCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $format = 'table';
        if (in_array('--json', $args, true)) {
            $format = 'json';
        }

        try {
            $basePath = dirname(__DIR__, 2);

            $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));
            $categoryService = new CategoryService(new FileStorage($basePath . '/data/blog/categories'));
            $tagService = new TagService(new FileStorage($basePath . '/data/blog/tags'));

            $posts = $postService->all();
            $categories = $categoryService->all();
            $tags = $tagService->all();

            // Calculer les statistiques
            $stats = $this->calculateStats($posts, $categories, $tags);

            if ($format === 'json') {
                echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                $this->displayTable($stats);
            }

            return 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Calcule toutes les statistiques.
     */
    private function calculateStats(array $posts, array $categories, array $tags): array
    {
        $now = new \DateTimeImmutable();
        $weekAgo = $now->modify('-7 days');
        $monthAgo = $now->modify('-30 days');

        // Stats par statut
        $byStatus = [
            'total' => count($posts),
            'published' => 0,
            'draft' => 0,
            'archived' => 0,
            'scheduled' => 0,
            'featured' => 0,
            'locked' => 0,
        ];

        // Stats de contenu
        $totalWords = 0;
        $totalReadingTime = 0;
        $ratedCount = 0;
        $totalRating = 0;

        // Stats temporelles
        $createdThisWeek = 0;
        $createdThisMonth = 0;
        $publishedThisWeek = 0;
        $publishedThisMonth = 0;

        // Stats par catégorie
        $byCategory = [];

        // Stats par tag
        $byTag = [];

        // Stats par auteur
        $byAuthor = [];

        foreach ($posts as $post) {
            // Par statut
            match ($post->getStatus()) {
                PostStatus::PUBLISHED => $byStatus['published']++,
                PostStatus::DRAFT => $byStatus['draft']++,
                PostStatus::ARCHIVED => $byStatus['archived']++,
            };

            if ($post->isScheduled()) {
                $byStatus['scheduled']++;
            }
            if ($post->isFeatured()) {
                $byStatus['featured']++;
            }
            if ($post->isLocked()) {
                $byStatus['locked']++;
            }

            // Contenu
            $totalWords += $post->getWordCount();
            $totalReadingTime += $post->getReadingTime();

            if ($post->isRated()) {
                $ratedCount++;
                $totalRating += $post->getAverageRating();
            }

            // Temporel
            if ($post->getCreatedAt() >= $weekAgo) {
                $createdThisWeek++;
            }
            if ($post->getCreatedAt() >= $monthAgo) {
                $createdThisMonth++;
            }
            if ($post->getPublishedAt() && $post->getPublishedAt() >= $weekAgo) {
                $publishedThisWeek++;
            }
            if ($post->getPublishedAt() && $post->getPublishedAt() >= $monthAgo) {
                $publishedThisMonth++;
            }

            // Par catégorie
            $catId = $post->getCategoryId() ?? 'uncategorized';
            $byCategory[$catId] = ($byCategory[$catId] ?? 0) + 1;

            // Par tag
            foreach ($post->getTags() as $tag) {
                $byTag[$tag] = ($byTag[$tag] ?? 0) + 1;
            }

            // Par auteur
            $author = $post->getAuthor() ?: 'Anonyme';
            $byAuthor[$author] = ($byAuthor[$author] ?? 0) + 1;
        }

        // Trier les tags par popularité
        arsort($byTag);

        // Trier les auteurs par nombre d'articles
        arsort($byAuthor);

        return [
            'general' => [
                'total_posts' => $byStatus['total'],
                'total_categories' => count($categories),
                'total_tags' => count($tags),
                'total_words' => $totalWords,
                'total_reading_time_minutes' => $totalReadingTime,
            ],
            'by_status' => $byStatus,
            'content' => [
                'average_words_per_post' => $byStatus['total'] > 0 ? (int) ($totalWords / $byStatus['total']) : 0,
                'average_reading_time' => $byStatus['total'] > 0 ? round($totalReadingTime / $byStatus['total'], 1) : 0,
                'rated_posts' => $ratedCount,
                'average_rating' => $ratedCount > 0 ? round($totalRating / $ratedCount, 2) : 0,
            ],
            'activity' => [
                'created_this_week' => $createdThisWeek,
                'created_this_month' => $createdThisMonth,
                'published_this_week' => $publishedThisWeek,
                'published_this_month' => $publishedThisMonth,
            ],
            'top_tags' => array_slice($byTag, 0, 10, true),
            'top_authors' => array_slice($byAuthor, 0, 5, true),
            'by_category' => $byCategory,
        ];
    }

    /**
     * Affiche les statistiques en tableau formaté.
     */
    private function displayTable(array $stats): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Statistiques                       ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Statistiques générales
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                    STATISTIQUES GÉNÉRALES                    │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-25s %35s │\n", "Articles totaux", $stats['general']['total_posts']);
        printf("│  %-25s %35s │\n", "Catégories", $stats['general']['total_categories']);
        printf("│  %-25s %35s │\n", "Tags", $stats['general']['total_tags']);
        printf("│  %-25s %35s │\n", "Mots écrits", number_format($stats['general']['total_words']));
        printf("│  %-25s %32s min │\n", "Temps de lecture total", $stats['general']['total_reading_time_minutes']);
        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";

        // Par statut
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                       PAR STATUT                             │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-25s %35s │\n", "Publiés", $stats['by_status']['published']);
        printf("│  %-25s %35s │\n", "Brouillons", $stats['by_status']['draft']);
        printf("│  %-25s %35s │\n", "Archivés", $stats['by_status']['archived']);
        printf("│  %-25s %35s │\n", "Programmés", $stats['by_status']['scheduled']);
        printf("│  %-25s %35s │\n", "À la une", $stats['by_status']['featured']);
        printf("│  %-25s %35s │\n", "Verrouillés (CC)", $stats['by_status']['locked']);
        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";

        // Contenu
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                        CONTENU                               │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-25s %35s │\n", "Mots par article (moy.)", $stats['content']['average_words_per_post']);
        printf("│  %-25s %32s min │\n", "Temps lecture (moy.)", $stats['content']['average_reading_time']);
        printf("│  %-25s %35s │\n", "Articles notés", $stats['content']['rated_posts']);
        printf("│  %-25s %33s/5 │\n", "Note moyenne", $stats['content']['average_rating']);
        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";

        // Activité
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                       ACTIVITÉ                               │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";
        printf("│  %-25s %35s │\n", "Créés cette semaine", $stats['activity']['created_this_week']);
        printf("│  %-25s %35s │\n", "Créés ce mois", $stats['activity']['created_this_month']);
        printf("│  %-25s %35s │\n", "Publiés cette semaine", $stats['activity']['published_this_week']);
        printf("│  %-25s %35s │\n", "Publiés ce mois", $stats['activity']['published_this_month']);
        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";

        // Top tags
        if (!empty($stats['top_tags'])) {
            echo "┌──────────────────────────────────────────────────────────────┐\n";
            echo "│                    TOP 10 TAGS                               │\n";
            echo "├──────────────────────────────────────────────────────────────┤\n";
            foreach ($stats['top_tags'] as $tag => $count) {
                printf("│  %-40s %19s │\n", $tag, $count . " articles");
            }
            echo "└──────────────────────────────────────────────────────────────┘\n";
            echo "\n";
        }

        // Top auteurs
        if (!empty($stats['top_authors'])) {
            echo "┌──────────────────────────────────────────────────────────────┐\n";
            echo "│                    TOP AUTEURS                               │\n";
            echo "├──────────────────────────────────────────────────────────────┤\n";
            foreach ($stats['top_authors'] as $author => $count) {
                printf("│  %-40s %19s │\n", mb_substr($author, 0, 40), $count . " articles");
            }
            echo "└──────────────────────────────────────────────────────────────┘\n";
            echo "\n";
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:stats [options]

Affiche les statistiques détaillées du blog.

Options :
  --json    Affiche les statistiques au format JSON

Statistiques affichées :
  - Nombre total d'articles, catégories, tags
  - Répartition par statut (publié, brouillon, archivé)
  - Statistiques de contenu (mots, temps de lecture)
  - Activité récente (7 jours, 30 jours)
  - Tags les plus utilisés
  - Auteurs les plus prolifiques
HELP;
    }
}
