<?php

declare(strict_types=1);

namespace Lunar\Controller\Admin;

use Lunar\Attribute\Route;
use Lunar\Entity\PostStatus;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Blog\TagService;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Storage\FileStorage;

/**
 * Contrôleur du tableau de bord d'administration.
 *
 * Affiche les statistiques globales du blog :
 * - Nombre d'articles par statut
 * - Articles récents
 * - Tags populaires
 * - Statistiques de lecture
 */
#[Route('/admin')]
class DashboardController extends BaseController
{
    private PostService $postService;
    private CategoryService $categoryService;
    private TagService $tagService;

    public function __construct()
    {
        parent::__construct();

        $basePath = dirname(__DIR__, 3);

        $this->postService = new PostService(
            new FileStorage($basePath . '/data/blog/posts')
        );

        $this->categoryService = new CategoryService(
            new FileStorage($basePath . '/data/blog/categories')
        );

        $this->tagService = new TagService(
            new FileStorage($basePath . '/data/blog/tags')
        );
    }

    /**
     * Tableau de bord principal.
     */
    #[Route('', methods: ['GET'], name: 'admin.dashboard')]
    #[Route('/dashboard', methods: ['GET'], name: 'admin.dashboard.alt')]
    public function index(Request $request): Response
    {
        $posts = $this->postService->all();
        $categories = $this->categoryService->all();
        $tags = $this->tagService->all();

        // Statistiques par statut
        $statusStats = [
            'total' => count($posts),
            'published' => 0,
            'draft' => 0,
            'archived' => 0,
            'scheduled' => 0,
            'featured' => 0,
        ];

        foreach ($posts as $post) {
            match ($post->getStatus()) {
                PostStatus::PUBLISHED => $statusStats['published']++,
                PostStatus::DRAFT => $statusStats['draft']++,
                PostStatus::ARCHIVED => $statusStats['archived']++,
            };
            if ($post->isScheduled()) {
                $statusStats['scheduled']++;
            }
            if ($post->isFeatured()) {
                $statusStats['featured']++;
            }
        }

        // Articles récents
        $recentPosts = $this->postService->findRecent(5);

        // Articles programmés
        $scheduledPosts = $this->postService->findScheduled();

        // Posts featured
        $featuredPosts = $this->postService->findFeatured();

        // Tags populaires (avec comptage)
        $tagCounts = [];
        foreach ($posts as $post) {
            foreach ($post->getTags() as $tag) {
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }
        arsort($tagCounts);
        $popularTags = array_slice($tagCounts, 0, 10, true);

        // Catégories avec comptage
        $categoryCounts = [];
        foreach ($posts as $post) {
            $catId = $post->getCategoryId();
            if ($catId !== null) {
                $categoryCounts[$catId] = ($categoryCounts[$catId] ?? 0) + 1;
            }
        }

        // Statistiques de lecture
        $readingStats = [
            'totalWords' => 0,
            'totalReadingTime' => 0,
            'avgWordsPerPost' => 0,
            'avgReadingTime' => 0,
        ];
        $publishedPosts = array_filter($posts, fn($p) => $p->isPublished());
        foreach ($publishedPosts as $post) {
            $readingStats['totalWords'] += $post->getWordCount();
            $readingStats['totalReadingTime'] += $post->getReadingTime();
        }
        if (count($publishedPosts) > 0) {
            $readingStats['avgWordsPerPost'] = (int) ($readingStats['totalWords'] / count($publishedPosts));
            $readingStats['avgReadingTime'] = (int) ($readingStats['totalReadingTime'] / count($publishedPosts));
        }

        // Statistiques des notes
        $ratingStats = [
            'totalRated' => 0,
            'avgRating' => 0,
        ];
        $ratedPosts = array_filter($posts, fn($p) => $p->isRated());
        $ratingStats['totalRated'] = count($ratedPosts);
        if (count($ratedPosts) > 0) {
            $totalRating = array_sum(array_map(fn($p) => $p->getAverageRating(), $ratedPosts));
            $ratingStats['avgRating'] = round($totalRating / count($ratedPosts), 1);
        }

        // Activité récente (7 derniers jours)
        $weekAgo = new \DateTimeImmutable('-7 days');
        $recentActivity = [
            'created' => count(array_filter($posts, fn($p) => $p->getCreatedAt() >= $weekAgo)),
            'published' => count(array_filter($posts, fn($p) => $p->getPublishedAt() && $p->getPublishedAt() >= $weekAgo)),
            'updated' => count(array_filter($posts, fn($p) => $p->getUpdatedAt() >= $weekAgo)),
        ];

        return $this->renderAdmin('admin/dashboard/index', [
            'title' => 'Tableau de bord',
            'statusStats' => $statusStats,
            'recentPosts' => $recentPosts,
            'scheduledPosts' => $scheduledPosts,
            'featuredPosts' => $featuredPosts,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'tags' => $tags,
            'popularTags' => $popularTags,
            'readingStats' => $readingStats,
            'ratingStats' => $ratingStats,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * API: Statistiques en JSON (pour widgets AJAX).
     */
    #[Route('/api/stats', methods: ['GET'], name: 'admin.api.stats')]
    public function stats(Request $request): Response
    {
        $posts = $this->postService->all();

        $stats = [
            'total' => count($posts),
            'published' => count(array_filter($posts, fn($p) => $p->isPublished())),
            'draft' => count(array_filter($posts, fn($p) => $p->isDraft())),
            'archived' => count(array_filter($posts, fn($p) => $p->isArchived())),
            'scheduled' => count(array_filter($posts, fn($p) => $p->isScheduled())),
            'featured' => count(array_filter($posts, fn($p) => $p->isFeatured())),
            'categories' => count($this->categoryService->all()),
            'tags' => count($this->tagService->all()),
        ];

        return $this->json($stats);
    }

    /**
     * API: Articles récents.
     */
    #[Route('/api/recent-posts', methods: ['GET'], name: 'admin.api.recent')]
    public function recentPosts(Request $request): Response
    {
        $limit = (int) ($request->getQueryParams()['limit'] ?? 10);
        $posts = $this->postService->findRecent($limit);

        $data = array_map(fn($post) => [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'slug' => $post->getSlug(),
            'status' => $post->getStatus()->value,
            'author' => $post->getAuthor(),
            'publishedAt' => $post->getPublishedAt()?->format('c'),
            'url' => $post->getUrl(),
        ], $posts);

        return $this->json($data);
    }

    /**
     * Réponse JSON.
     */
    private function json(array $data, int $status = 200): Response
    {
        return new Response(
            json_encode($data, JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}
