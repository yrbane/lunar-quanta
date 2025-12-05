<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

/**
 * Service de suggestion de tags basé sur l'analyse de contenu.
 *
 * Utilise ContentAnalyzer pour suggérer des tags pertinents
 * basés sur le contenu d'un article.
 *
 * @example
 * ```php
 * $suggester = new TagSuggester($postService, $analyzer);
 * $tags = $suggester->suggest($content);
 * // ['php', 'framework', 'web']
 * ```
 */
final class TagSuggester
{
    public function __construct(
        private readonly PostService $postService,
        private readonly ContentAnalyzer $analyzer
    ) {
    }

    /**
     * Suggère des tags pour un contenu donné.
     *
     * @param int $limit Nombre max de suggestions
     * @return string[]
     */
    public function suggest(string $content, int $limit = 5): array
    {
        // Collecter tous les tags existants
        $existingTags = $this->collectExistingTags();

        return $this->analyzer->suggestTags($content, $existingTags, $limit);
    }

    /**
     * Suggère des tags avec scores de pertinence.
     *
     * @return array<string, float>
     */
    public function suggestWithScores(string $content, int $limit = 5): array
    {
        $existingTags = $this->collectExistingTags();
        $corpus = $this->buildCorpus();

        $tfIdf = $this->analyzer->calculateTfIdf($content, $corpus);
        $suggestions = [];

        // Matcher avec les tags existants
        foreach ($existingTags as $tag) {
            $tagLower = mb_strtolower($tag);
            if (isset($tfIdf[$tagLower])) {
                $suggestions[$tag] = $tfIdf[$tagLower];
            }
        }

        // Ajouter les mots-clés de haut score comme nouveaux tags
        foreach ($tfIdf as $term => $score) {
            if (count($suggestions) >= $limit) {
                break;
            }
            if (!isset($suggestions[$term]) && mb_strlen($term) >= 3) {
                $suggestions[$term] = $score;
            }
        }

        arsort($suggestions);
        return array_slice($suggestions, 0, $limit, true);
    }

    /**
     * Collecte tous les tags utilisés dans les articles.
     *
     * @return string[]
     */
    private function collectExistingTags(): array
    {
        $posts = $this->postService->all();
        $tags = [];

        foreach ($posts as $post) {
            foreach ($post->getTags() as $tag) {
                $tags[$tag] = true;
            }
        }

        return array_keys($tags);
    }

    /**
     * Construit le corpus de documents pour TF-IDF.
     *
     * @return string[]
     */
    private function buildCorpus(): array
    {
        $posts = $this->postService->all();
        $corpus = [];

        foreach ($posts as $post) {
            $corpus[] = $post->getTitle() . ' ' . $post->getContent();
        }

        return $corpus;
    }
}
