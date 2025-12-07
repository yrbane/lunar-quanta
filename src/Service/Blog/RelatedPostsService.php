<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

use Lunar\Entity\Post;

/**
 * Service de recommandation d'articles similaires.
 *
 * Trouve les articles les plus pertinents basés sur les tags,
 * la catégorie et les mots-clés du contenu.
 *
 * @example
 * ```php
 * $service = new RelatedPostsService($postService);
 * $related = $service->findRelated($post, 5);
 * ```
 */
final class RelatedPostsService
{
    private PostService $postService;

    /** Poids pour la correspondance de catégorie */
    private int $categoryWeight = 50;

    /** Poids pour chaque tag correspondant */
    private int $tagWeight = 30;

    /** Poids pour la correspondance d'auteur */
    private int $authorWeight = 10;

    /** Poids pour les mots-clés du titre */
    private int $titleKeywordWeight = 20;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Configure les poids de scoring.
     */
    public function setWeights(int $category, int $tag, int $author, int $titleKeyword): self
    {
        $this->categoryWeight = $category;
        $this->tagWeight = $tag;
        $this->authorWeight = $author;
        $this->titleKeywordWeight = $titleKeyword;
        return $this;
    }

    /**
     * Trouve les articles liés à un article donné.
     *
     * @param Post $post L'article de référence
     * @param int $limit Nombre maximum d'articles à retourner
     * @param bool $publishedOnly Ne retourner que les articles publiés
     * @return array<array{post: Post, score: int, reasons: array<string>}>
     */
    public function findRelated(Post $post, int $limit = 5, bool $publishedOnly = true): array
    {
        $allPosts = $publishedOnly
            ? $this->postService->findPublished()
            : $this->postService->all();

        $postId = $post->getId();
        $postTags = array_map('strtolower', $post->getTags());
        $postCategory = $post->getCategoryId();
        $postAuthor = $post->getAuthor();
        $titleKeywords = $this->extractKeywords($post->getTitle());

        $scored = [];

        foreach ($allPosts as $candidate) {
            // Ignorer l'article lui-même
            if ($candidate->getId() === $postId) {
                continue;
            }

            $score = 0;
            $reasons = [];

            // Score par catégorie
            if ($postCategory !== null && $candidate->getCategoryId() === $postCategory) {
                $score += $this->categoryWeight;
                $reasons[] = 'Même catégorie';
            }

            // Score par tags
            $candidateTags = array_map('strtolower', $candidate->getTags());
            $commonTags = array_intersect($postTags, $candidateTags);
            if (!empty($commonTags)) {
                $tagScore = count($commonTags) * $this->tagWeight;
                $score += $tagScore;
                $reasons[] = count($commonTags) . ' tag(s) en commun';
            }

            // Score par auteur
            if ($postAuthor !== null && $candidate->getAuthor() === $postAuthor) {
                $score += $this->authorWeight;
                $reasons[] = 'Même auteur';
            }

            // Score par mots-clés du titre
            $candidateKeywords = $this->extractKeywords($candidate->getTitle());
            $commonKeywords = array_intersect($titleKeywords, $candidateKeywords);
            if (!empty($commonKeywords)) {
                $keywordScore = count($commonKeywords) * $this->titleKeywordWeight;
                $score += $keywordScore;
                $reasons[] = count($commonKeywords) . ' mot(s)-clé(s) en commun';
            }

            // Bonus pour les articles récents (articles de moins de 30 jours)
            $publishedAt = $candidate->getPublishedAt();
            if ($publishedAt !== null) {
                $daysSincePublished = (new \DateTimeImmutable())->diff($publishedAt)->days;
                if ($daysSincePublished < 30) {
                    $recencyBonus = (int) ((30 - $daysSincePublished) / 3);
                    $score += $recencyBonus;
                }
            }

            if ($score > 0) {
                $scored[] = [
                    'post' => $candidate,
                    'score' => $score,
                    'reasons' => $reasons,
                ];
            }
        }

        // Trier par score décroissant
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Trouve les articles populaires dans la même catégorie.
     */
    public function findInSameCategory(Post $post, int $limit = 5): array
    {
        $categoryId = $post->getCategoryId();
        if ($categoryId === null) {
            return [];
        }

        $posts = $this->postService->findPublished();
        $postId = $post->getId();

        $inCategory = array_filter($posts, function (Post $p) use ($postId, $categoryId) {
            return $p->getId() !== $postId && $p->getCategoryId() === $categoryId;
        });

        // Trier par date de publication décroissante
        usort($inCategory, function (Post $a, Post $b) {
            $dateA = $a->getPublishedAt() ?? $a->getCreatedAt();
            $dateB = $b->getPublishedAt() ?? $b->getCreatedAt();
            return $dateB <=> $dateA;
        });

        return array_slice($inCategory, 0, $limit);
    }

    /**
     * Trouve les articles avec les mêmes tags.
     */
    public function findWithSameTags(Post $post, int $limit = 5): array
    {
        $tags = $post->getTags();
        if (empty($tags)) {
            return [];
        }

        $posts = $this->postService->findPublished();
        $postId = $post->getId();
        $postTags = array_map('strtolower', $tags);

        $withTags = [];

        foreach ($posts as $p) {
            if ($p->getId() === $postId) {
                continue;
            }

            $pTags = array_map('strtolower', $p->getTags());
            $common = array_intersect($postTags, $pTags);

            if (!empty($common)) {
                $withTags[] = [
                    'post' => $p,
                    'common' => count($common),
                ];
            }
        }

        // Trier par nombre de tags en commun
        usort($withTags, fn($a, $b) => $b['common'] <=> $a['common']);

        return array_map(fn($item) => $item['post'], array_slice($withTags, 0, $limit));
    }

    /**
     * Trouve les articles du même auteur.
     */
    public function findByAuthor(Post $post, int $limit = 5): array
    {
        $author = $post->getAuthor();
        if ($author === null) {
            return [];
        }

        $posts = $this->postService->findPublished();
        $postId = $post->getId();

        $byAuthor = array_filter($posts, function (Post $p) use ($postId, $author) {
            return $p->getId() !== $postId && $p->getAuthor() === $author;
        });

        // Trier par date de publication décroissante
        usort($byAuthor, function (Post $a, Post $b) {
            $dateA = $a->getPublishedAt() ?? $a->getCreatedAt();
            $dateB = $b->getPublishedAt() ?? $b->getCreatedAt();
            return $dateB <=> $dateA;
        });

        return array_slice($byAuthor, 0, $limit);
    }

    /**
     * Extrait les mots-clés significatifs d'un texte.
     */
    private function extractKeywords(string $text): array
    {
        // Liste de mots vides à ignorer
        $stopWords = [
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'en', 'et', 'ou',
            'à', 'au', 'aux', 'ce', 'ces', 'cette', 'qui', 'que', 'quoi', 'dont',
            'pour', 'par', 'sur', 'avec', 'sans', 'dans', 'est', 'sont', 'être',
            'avoir', 'fait', 'faire', 'plus', 'moins', 'très', 'tout', 'tous',
            'the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
            'should', 'may', 'might', 'must', 'shall', 'can', 'need', 'dare',
            'of', 'to', 'in', 'for', 'on', 'with', 'at', 'by', 'from', 'as',
            'into', 'through', 'during', 'before', 'after', 'above', 'below',
            'and', 'or', 'but', 'if', 'then', 'else', 'when', 'where', 'why',
            'how', 'all', 'each', 'every', 'both', 'few', 'more', 'most', 'other',
            'some', 'such', 'no', 'not', 'only', 'own', 'same', 'so', 'than',
        ];

        // Normaliser et extraire les mots
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Filtrer les mots vides et courts
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return mb_strlen($word) >= 3 && !in_array($word, $stopWords);
        });

        return array_values(array_unique($keywords));
    }
}
