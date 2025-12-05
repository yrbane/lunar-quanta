<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

use Lunar\Entity\Category;

/**
 * Service de suggestion de catégorie basé sur l'analyse de contenu.
 *
 * Utilise ContentAnalyzer pour suggérer la catégorie la plus pertinente
 * basée sur le contenu d'un article.
 *
 * @example
 * ```php
 * $suggester = new CategorySuggester($categoryService, $postService, $analyzer);
 * $category = $suggester->suggest($content);
 * // Category object or null
 * ```
 */
final class CategorySuggester
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly PostService $postService,
        private readonly ContentAnalyzer $analyzer
    ) {
    }

    /**
     * Suggère la catégorie la plus pertinente pour un contenu.
     */
    public function suggest(string $content): ?Category
    {
        $scores = $this->suggestWithScores($content);

        if (empty($scores)) {
            return null;
        }

        // Retourner la catégorie avec le score le plus élevé
        $categoryId = array_key_first($scores);
        return $this->categoryService->find($categoryId);
    }

    /**
     * Suggère des catégories avec scores de pertinence.
     *
     * @param int $limit Nombre max de suggestions
     * @return array<string, float> categoryId => score
     */
    public function suggestWithScores(string $content, int $limit = 3): array
    {
        $categories = $this->categoryService->all();

        if (empty($categories)) {
            return [];
        }

        $contentKeywords = $this->analyzer->extractKeywords($content, 20);

        if (empty($contentKeywords)) {
            return [];
        }

        $scores = [];

        foreach ($categories as $category) {
            $score = $this->calculateCategoryScore($category, $contentKeywords);
            if ($score > 0) {
                $scores[$category->getId()] = $score;
            }
        }

        // Trier par score décroissant
        arsort($scores);

        return array_slice($scores, 0, $limit, true);
    }

    /**
     * Calcule le score d'une catégorie par rapport aux mots-clés.
     *
     * @param string[] $contentKeywords
     */
    private function calculateCategoryScore(Category $category, array $contentKeywords): float
    {
        $score = 0.0;

        // Score basé sur le nom de la catégorie
        $categoryName = mb_strtolower($category->getName());
        $categoryWords = preg_split('/\s+/', $categoryName);

        foreach ($categoryWords as $word) {
            foreach ($contentKeywords as $index => $keyword) {
                if ($this->isSimilar($word, $keyword)) {
                    // Plus le mot-clé est en haut de la liste, plus il est important
                    $weight = 1 - ($index / count($contentKeywords));
                    $score += 2.0 * $weight;
                }
            }
        }

        // Score basé sur la description
        $descKeywords = $this->analyzer->extractKeywords($category->getDescription(), 10);
        foreach ($descKeywords as $descWord) {
            foreach ($contentKeywords as $index => $keyword) {
                if ($this->isSimilar($descWord, $keyword)) {
                    $weight = 1 - ($index / count($contentKeywords));
                    $score += 1.0 * $weight;
                }
            }
        }

        // Score basé sur les articles existants dans cette catégorie
        $categoryPosts = $this->getPostsInCategory($category->getId());
        foreach ($categoryPosts as $post) {
            $postKeywords = $this->analyzer->extractKeywords(
                $post->getTitle() . ' ' . $post->getContent(),
                10
            );
            foreach ($postKeywords as $postWord) {
                foreach ($contentKeywords as $keyword) {
                    if ($this->isSimilar($postWord, $keyword)) {
                        $score += 0.5;
                    }
                }
            }
        }

        return $score;
    }

    /**
     * Récupère les articles d'une catégorie.
     *
     * @return \Lunar\Entity\Post[]
     */
    private function getPostsInCategory(string $categoryId): array
    {
        return array_filter(
            $this->postService->all(),
            fn($post) => $post->getCategoryId() === $categoryId
        );
    }

    /**
     * Vérifie si deux mots sont similaires.
     */
    private function isSimilar(string $word1, string $word2): bool
    {
        if ($word1 === $word2) {
            return true;
        }

        if (str_contains($word1, $word2) || str_contains($word2, $word1)) {
            return true;
        }

        $distance = levenshtein($word1, $word2);
        $maxLen = max(mb_strlen($word1), mb_strlen($word2));

        return $distance <= ($maxLen / 4);
    }
}
