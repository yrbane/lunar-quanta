<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

use Lunar\Entity\Post;

/**
 * Service de génération d'index de recherche pour le blog.
 *
 * Génère un index JSON pour la recherche côté client
 * compatible avec des bibliothèques comme Lunr.js ou FlexSearch.
 *
 * @example
 * ```php
 * $search = new SearchIndexService();
 * $index = $search->buildIndex($posts);
 * $search->saveIndex($index, '/path/to/search-index.json');
 * ```
 */
final class SearchIndexService
{
    private bool $includeContent = true;
    private int $excerptLength = 200;
    private bool $includeMetadata = true;
    private array $stopWords = [];

    public function __construct()
    {
        $this->stopWords = [
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'au', 'aux',
            'ce', 'cette', 'ces', 'mon', 'ma', 'mes', 'ton', 'ta', 'tes',
            'son', 'sa', 'ses', 'notre', 'nos', 'votre', 'vos', 'leur', 'leurs',
            'qui', 'que', 'quoi', 'dont', 'où', 'et', 'ou', 'mais', 'donc',
            'car', 'ni', 'ne', 'pas', 'plus', 'moins', 'très', 'peu', 'trop',
            'est', 'sont', 'était', 'été', 'être', 'avoir', 'a', 'ont', 'avait',
            'pour', 'par', 'avec', 'sans', 'sous', 'sur', 'dans', 'en', 'vers',
            'chez', 'entre', 'comme', 'aussi', 'bien', 'même', 'tout', 'tous',
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been',
        ];
    }

    /**
     * Active/désactive l'inclusion du contenu dans l'index.
     */
    public function setIncludeContent(bool $include): self
    {
        $this->includeContent = $include;
        return $this;
    }

    /**
     * Définit la longueur de l'extrait dans l'index.
     */
    public function setExcerptLength(int $length): self
    {
        $this->excerptLength = max(50, $length);
        return $this;
    }

    /**
     * Active/désactive l'inclusion des métadonnées.
     */
    public function setIncludeMetadata(bool $include): self
    {
        $this->includeMetadata = $include;
        return $this;
    }

    /**
     * Définit les mots vides à exclure.
     *
     * @param string[] $words
     */
    public function setStopWords(array $words): self
    {
        $this->stopWords = $words;
        return $this;
    }

    /**
     * Construit l'index de recherche.
     *
     * @param Post[] $posts
     * @return array{documents: array, metadata: array}
     */
    public function buildIndex(array $posts): array
    {
        $documents = [];
        $wordFrequency = [];

        foreach ($posts as $post) {
            if ($post->getStatus()->value !== 'published') {
                continue;
            }

            $doc = $this->indexPost($post);
            $documents[] = $doc;

            // Calculer la fréquence des mots
            foreach ($doc['keywords'] as $word) {
                $wordFrequency[$word] = ($wordFrequency[$word] ?? 0) + 1;
            }
        }

        // Trier les mots par fréquence
        arsort($wordFrequency);

        return [
            'documents' => $documents,
            'metadata' => [
                'total' => count($documents),
                'generated_at' => (new \DateTimeImmutable())->format('c'),
                'top_keywords' => array_slice(array_keys($wordFrequency), 0, 50),
            ],
        ];
    }

    /**
     * Indexe un article.
     */
    private function indexPost(Post $post): array
    {
        $content = $post->getContent();
        $plainText = $this->stripMarkdown($content);
        $keywords = $this->extractKeywords($post->getTitle() . ' ' . $plainText);

        $doc = [
            'id' => $post->getId(),
            'slug' => $post->getSlug(),
            'title' => $post->getTitle(),
            'url' => '/blog/posts/' . $post->getSlug() . '.html',
            'keywords' => $keywords,
        ];

        if ($this->includeContent) {
            $doc['excerpt'] = mb_substr($plainText, 0, $this->excerptLength);
        }

        if ($this->includeMetadata) {
            $doc['author'] = $post->getAuthor();
            $doc['tags'] = $post->getTags();
            $doc['category'] = $post->getCategoryId();
            $doc['date'] = $post->getPublishedAt()?->format('Y-m-d');
            $doc['reading_time'] = $post->getReadingTime();
        }

        return $doc;
    }

    /**
     * Extrait les mots-clés d'un texte.
     *
     * @return string[]
     */
    public function extractKeywords(string $text): array
    {
        // Nettoyer le texte
        $text = mb_strtolower($text);
        $text = $this->removeAccents($text);

        // Extraire les mots
        preg_match_all('/[a-z]{3,}/', $text, $matches);
        $words = $matches[0];

        // Filtrer les mots vides
        $words = array_diff($words, $this->stopWords);

        // Compter les occurrences
        $counts = array_count_values($words);
        arsort($counts);

        // Retourner les mots uniques triés par fréquence
        return array_keys($counts);
    }

    /**
     * Supprime le formatage Markdown.
     */
    private function stripMarkdown(string $text): string
    {
        // Supprimer les blocs de code
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/`[^`]+`/', '', $text);

        // Supprimer les titres
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);

        // Supprimer les liens
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);

        // Supprimer les images
        $text = preg_replace('/!\[[^\]]*\]\([^\)]+\)/', '', $text);

        // Supprimer le formatage
        $text = preg_replace('/[*_~]{1,3}([^*_~]+)[*_~]{1,3}/', '$1', $text);

        // Supprimer les listes
        $text = preg_replace('/^[\s]*[-*+]\s+/m', '', $text);
        $text = preg_replace('/^[\s]*\d+\.\s+/m', '', $text);

        // Supprimer les citations
        $text = preg_replace('/^>\s*/m', '', $text);

        // Normaliser les espaces
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Supprime les accents.
     */
    private function removeAccents(string $text): string
    {
        $search = ['à', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'ï', 'î', 'ô', 'ö', 'ù', 'û', 'ü', 'ÿ', 'ç', 'œ', 'æ'];
        $replace = ['a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'y', 'c', 'oe', 'ae'];

        return str_replace($search, $replace, $text);
    }

    /**
     * Sauvegarde l'index dans un fichier JSON.
     */
    public function saveIndex(array $index, string $path): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return file_put_contents($path, $json) !== false;
    }

    /**
     * Charge un index depuis un fichier JSON.
     */
    public function loadIndex(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    /**
     * Génère le JavaScript pour la recherche côté client.
     */
    public function generateSearchScript(string $indexPath = '/blog/search-index.json'): string
    {
        return <<<JS
class BlogSearch {
    constructor(indexPath = '{$indexPath}') {
        this.indexPath = indexPath;
        this.index = null;
        this.loaded = false;
    }

    async load() {
        if (this.loaded) return;
        const response = await fetch(this.indexPath);
        this.index = await response.json();
        this.loaded = true;
    }

    search(query, limit = 10) {
        if (!this.loaded || !query.trim()) return [];

        const terms = query.toLowerCase().split(/\s+/).filter(t => t.length >= 2);
        const results = [];

        for (const doc of this.index.documents) {
            let score = 0;

            for (const term of terms) {
                // Recherche dans le titre (poids élevé)
                if (doc.title.toLowerCase().includes(term)) {
                    score += 10;
                }

                // Recherche dans les mots-clés
                const keywordMatch = doc.keywords.filter(k => k.includes(term)).length;
                score += keywordMatch * 2;

                // Recherche dans les tags
                if (doc.tags && doc.tags.some(t => t.toLowerCase().includes(term))) {
                    score += 5;
                }

                // Recherche dans l'extrait
                if (doc.excerpt && doc.excerpt.toLowerCase().includes(term)) {
                    score += 1;
                }
            }

            if (score > 0) {
                results.push({ ...doc, score });
            }
        }

        return results
            .sort((a, b) => b.score - a.score)
            .slice(0, limit);
    }

    highlight(text, query) {
        const terms = query.toLowerCase().split(/\s+/).filter(t => t.length >= 2);
        let result = text;

        for (const term of terms) {
            const regex = new RegExp(`(${term})`, 'gi');
            result = result.replace(regex, '<mark>\$1</mark>');
        }

        return result;
    }
}

// Usage: const search = new BlogSearch(); await search.load(); search.search('php');
JS;
    }

    /**
     * Génère le CSS pour les résultats de recherche.
     */
    public function generateSearchCss(): string
    {
        return <<<'CSS'
.search-results {
    max-height: 400px;
    overflow-y: auto;
}

.search-result {
    padding: 1rem;
    border-bottom: 1px solid var(--la-border, #e5e7eb);
}

.search-result:last-child {
    border-bottom: none;
}

.search-result a {
    text-decoration: none;
    color: inherit;
}

.search-result-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.search-result-excerpt {
    font-size: 0.875rem;
    color: var(--la-text-muted, #6b7280);
}

.search-result mark {
    background: var(--la-warning, #fbbf24);
    color: inherit;
    padding: 0 0.125rem;
    border-radius: 0.125rem;
}

.search-no-results {
    padding: 2rem;
    text-align: center;
    color: var(--la-text-muted, #6b7280);
}
CSS;
    }
}
