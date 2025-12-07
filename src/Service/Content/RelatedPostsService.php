<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

use Lunar\Entity\Post;

/**
 * Service pour trouver les articles connexes.
 *
 * Utilise différents algorithmes pour calculer la similarité
 * entre articles basée sur les tags, catégories et contenu.
 *
 * @example
 * ```php
 * $related = new RelatedPostsService();
 * $related->setMaxResults(5);
 *
 * $similarPosts = $related->findRelated($currentPost, $allPosts);
 * ```
 */
final class RelatedPostsService
{
    private int $maxResults = 5;
    private float $tagWeight = 1.0;
    private float $categoryWeight = 0.5;
    private float $titleWeight = 0.3;
    private float $minScore = 0.1;

    /**
     * Définit le nombre maximum de résultats.
     */
    public function setMaxResults(int $max): self
    {
        $this->maxResults = max(1, $max);
        return $this;
    }

    /**
     * Définit le poids des tags dans le calcul.
     */
    public function setTagWeight(float $weight): self
    {
        $this->tagWeight = max(0, $weight);
        return $this;
    }

    /**
     * Définit le poids de la catégorie dans le calcul.
     */
    public function setCategoryWeight(float $weight): self
    {
        $this->categoryWeight = max(0, $weight);
        return $this;
    }

    /**
     * Définit le poids du titre dans le calcul.
     */
    public function setTitleWeight(float $weight): self
    {
        $this->titleWeight = max(0, $weight);
        return $this;
    }

    /**
     * Définit le score minimum pour être considéré comme connexe.
     */
    public function setMinScore(float $score): self
    {
        $this->minScore = max(0, min(1, $score));
        return $this;
    }

    /**
     * Trouve les articles connexes.
     *
     * @param Post $post L'article de référence
     * @param Post[] $candidates Les articles candidats
     * @return array<array{post: Post, score: float}>
     */
    public function findRelated(Post $post, array $candidates): array
    {
        $results = [];

        foreach ($candidates as $candidate) {
            // Ignorer l'article lui-même
            if ($candidate->getId() === $post->getId()) {
                continue;
            }

            // Ignorer les articles non publiés
            if ($candidate->getStatus()->value !== 'published') {
                continue;
            }

            $score = $this->calculateSimilarity($post, $candidate);

            if ($score >= $this->minScore) {
                $results[] = [
                    'post' => $candidate,
                    'score' => $score,
                ];
            }
        }

        // Trier par score décroissant
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        // Limiter les résultats
        return array_slice($results, 0, $this->maxResults);
    }

    /**
     * Calcule la similarité entre deux articles.
     */
    public function calculateSimilarity(Post $post1, Post $post2): float
    {
        $totalWeight = $this->tagWeight + $this->categoryWeight + $this->titleWeight;
        if ($totalWeight === 0.0) {
            return 0.0;
        }

        $score = 0.0;

        // Score basé sur les tags
        if ($this->tagWeight > 0) {
            $score += $this->calculateTagSimilarity($post1, $post2) * $this->tagWeight;
        }

        // Score basé sur la catégorie
        if ($this->categoryWeight > 0) {
            $score += $this->calculateCategorySimilarity($post1, $post2) * $this->categoryWeight;
        }

        // Score basé sur le titre
        if ($this->titleWeight > 0) {
            $score += $this->calculateTitleSimilarity($post1, $post2) * $this->titleWeight;
        }

        return $score / $totalWeight;
    }

    /**
     * Calcule la similarité des tags (Jaccard index).
     */
    private function calculateTagSimilarity(Post $post1, Post $post2): float
    {
        $tags1 = array_map('strtolower', $post1->getTags());
        $tags2 = array_map('strtolower', $post2->getTags());

        if (empty($tags1) && empty($tags2)) {
            return 0.0;
        }

        $intersection = count(array_intersect($tags1, $tags2));
        $union = count(array_unique(array_merge($tags1, $tags2)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Calcule la similarité des catégories.
     */
    private function calculateCategorySimilarity(Post $post1, Post $post2): float
    {
        $cat1 = $post1->getCategoryId();
        $cat2 = $post2->getCategoryId();

        if ($cat1 === null || $cat2 === null) {
            return 0.0;
        }

        return $cat1 === $cat2 ? 1.0 : 0.0;
    }

    /**
     * Calcule la similarité des titres (mots communs).
     */
    private function calculateTitleSimilarity(Post $post1, Post $post2): float
    {
        $words1 = $this->extractWords($post1->getTitle());
        $words2 = $this->extractWords($post2->getTitle());

        if (empty($words1) || empty($words2)) {
            return 0.0;
        }

        $intersection = count(array_intersect($words1, $words2));
        $minCount = min(count($words1), count($words2));

        return $minCount > 0 ? $intersection / $minCount : 0.0;
    }

    /**
     * Extrait les mots significatifs d'un texte.
     */
    private function extractWords(string $text): array
    {
        // Convertir en minuscules et supprimer les accents
        $text = mb_strtolower($text);
        $text = $this->removeAccents($text);

        // Extraire les mots
        preg_match_all('/[a-z]{3,}/', $text, $matches);

        // Filtrer les mots vides
        $stopWords = ['les', 'des', 'une', 'pour', 'avec', 'dans', 'sur', 'par', 'qui', 'que', 'est', 'sont', 'ont', 'été'];

        return array_diff($matches[0], $stopWords);
    }

    /**
     * Supprime les accents d'une chaîne.
     */
    private function removeAccents(string $text): string
    {
        $search = ['à', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'ï', 'î', 'ô', 'ö', 'ù', 'û', 'ü', 'ÿ', 'ç'];
        $replace = ['a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'y', 'c'];

        return str_replace($search, $replace, $text);
    }

    /**
     * Génère le HTML pour afficher les articles connexes.
     *
     * @param array<array{post: Post, score: float}> $related
     */
    public function generateHtml(array $related, string $title = 'Articles connexes'): string
    {
        if (empty($related)) {
            return '';
        }

        $html = '<aside class="la-related-posts">';
        $html .= '<h3 class="la-related-title">' . htmlspecialchars($title) . '</h3>';
        $html .= '<div class="la-related-grid">';

        foreach ($related as $item) {
            $post = $item['post'];
            $html .= $this->generatePostCard($post);
        }

        $html .= '</div>';
        $html .= '</aside>';

        return $html;
    }

    /**
     * Génère une carte pour un article connexe.
     */
    private function generatePostCard(Post $post): string
    {
        $title = htmlspecialchars($post->getTitle());
        $slug = htmlspecialchars($post->getSlug());
        $excerpt = htmlspecialchars(mb_substr($post->getExcerpt() ?? '', 0, 100));
        $readingTime = $post->getReadingTime();

        $html = '<article class="la-related-card">';
        $html .= '<a href="/blog/posts/' . $slug . '.html">';
        $html .= '<h4 class="la-related-card-title">' . $title . '</h4>';

        if (!empty($excerpt)) {
            $html .= '<p class="la-related-card-excerpt">' . $excerpt . '</p>';
        }

        $html .= '<span class="la-related-card-meta">';
        $html .= '<span class="la-icon xs">schedule</span> ' . $readingTime . ' min';
        $html .= '</span>';
        $html .= '</a>';
        $html .= '</article>';

        return $html;
    }

    /**
     * Génère le CSS pour les articles connexes.
     */
    public function generateCss(): string
    {
        return <<<'CSS'
.la-related-posts {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--la-border, #e5e7eb);
}

.la-related-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.la-related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.la-related-card {
    padding: 1rem;
    border-radius: 0.5rem;
    background: var(--la-surface, #f9fafb);
    transition: transform 0.2s, box-shadow 0.2s;
}

.la-related-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.la-related-card a {
    text-decoration: none;
    color: inherit;
}

.la-related-card-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--la-text, #111827);
}

.la-related-card-excerpt {
    font-size: 0.875rem;
    color: var(--la-text-muted, #6b7280);
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.la-related-card-meta {
    font-size: 0.75rem;
    color: var(--la-text-muted, #6b7280);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
CSS;
    }
}
