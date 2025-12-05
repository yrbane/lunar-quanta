<?php

declare(strict_types=1);

namespace Lunar\Controller\Admin;

use Lunar\Attribute\Route;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\CategorySuggester;
use Lunar\Service\Blog\ContentAnalyzer;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Blog\TagSuggester;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Storage\FileStorage;

/**
 * Contrôleur pour les suggestions de contenu en AJAX.
 *
 * Fournit des endpoints pour suggérer des tags et catégories
 * basés sur le contenu d'un article.
 */
#[Route('/admin/suggestions')]
class SuggestionController extends BaseController
{
    private TagSuggester $tagSuggester;
    private CategorySuggester $categorySuggester;
    private CategoryService $categoryService;

    public function __construct()
    {
        parent::__construct();

        $basePath = dirname(__DIR__, 3);
        $analyzer = new ContentAnalyzer();

        $postService = new PostService(
            new FileStorage($basePath . '/data/blog/posts')
        );

        $this->categoryService = new CategoryService(
            new FileStorage($basePath . '/data/blog/categories')
        );

        $this->tagSuggester = new TagSuggester($postService, $analyzer);
        $this->categorySuggester = new CategorySuggester(
            $this->categoryService,
            $postService,
            $analyzer
        );
    }

    /**
     * Suggère des tags basés sur le contenu.
     */
    #[Route('/tags', methods: ['POST'], name: 'admin.suggestions.tags')]
    public function tags(Request $request): Response
    {
        $body = $request->getParsedBody();
        $content = $body['content'] ?? '';
        $title = $body['title'] ?? '';
        $limit = (int) ($body['limit'] ?? 5);

        if ($limit < 1) {
            $limit = 5;
        }
        if ($limit > 10) {
            $limit = 10;
        }

        $fullContent = $title . ' ' . $content;
        $suggestions = $this->tagSuggester->suggestWithScores($fullContent, $limit);

        return $this->json([
            'success' => true,
            'suggestions' => array_map(
                fn($tag, $score) => ['tag' => $tag, 'score' => round($score, 3)],
                array_keys($suggestions),
                array_values($suggestions)
            ),
        ]);
    }

    /**
     * Suggère une catégorie basée sur le contenu.
     */
    #[Route('/category', methods: ['POST'], name: 'admin.suggestions.category')]
    public function category(Request $request): Response
    {
        $body = $request->getParsedBody();
        $content = $body['content'] ?? '';
        $title = $body['title'] ?? '';
        $limit = (int) ($body['limit'] ?? 3);

        if ($limit < 1) {
            $limit = 3;
        }
        if ($limit > 5) {
            $limit = 5;
        }

        $fullContent = $title . ' ' . $content;
        $scores = $this->categorySuggester->suggestWithScores($fullContent, $limit);

        $suggestions = [];
        foreach ($scores as $categoryId => $score) {
            $category = $this->categoryService->find($categoryId);
            if ($category !== null) {
                $suggestions[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'color' => $category->getColor(),
                    'score' => round($score, 3),
                ];
            }
        }

        return $this->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Retourne une réponse JSON.
     *
     * @param array<string, mixed> $data
     */
    private function json(array $data): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        return new Response($json, 200, ['Content-Type' => 'application/json']);
    }
}
