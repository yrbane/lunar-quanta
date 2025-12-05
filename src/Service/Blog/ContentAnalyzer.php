<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

/**
 * Analyseur de contenu utilisant TF-IDF.
 *
 * TF-IDF (Term Frequency - Inverse Document Frequency) permet
 * d'identifier les mots-clés importants dans un texte.
 *
 * @example
 * ```php
 * $analyzer = new ContentAnalyzer();
 * $keywords = $analyzer->extractKeywords($content, 10);
 * // ['php', 'framework', 'routing', ...]
 * ```
 */
final class ContentAnalyzer
{
    /** @var string[] */
    private array $stopwords;

    public function __construct(?array $customStopwords = null)
    {
        $this->stopwords = $customStopwords ?? $this->getDefaultStopwords();
    }

    /**
     * Extrait les mots-clés d'un texte.
     *
     * @param int $limit Nombre max de mots-clés
     * @return string[]
     */
    public function extractKeywords(string $text, int $limit = 10): array
    {
        $words = $this->tokenize($text);
        $termFrequency = $this->calculateTermFrequency($words);

        // Trier par fréquence décroissante
        arsort($termFrequency);

        return array_slice(array_keys($termFrequency), 0, $limit);
    }

    /**
     * Calcule les scores TF-IDF par rapport à un corpus.
     *
     * @param string[] $corpus Collection de documents
     * @return array<string, float>
     */
    public function calculateTfIdf(string $text, array $corpus): array
    {
        $words = $this->tokenize($text);
        $tf = $this->calculateTermFrequency($words);

        $scores = [];
        foreach ($tf as $term => $freq) {
            $idf = $this->calculateIdf($term, $corpus);
            $scores[$term] = $freq * $idf;
        }

        arsort($scores);
        return $scores;
    }

    /**
     * Suggère des tags basés sur le contenu.
     *
     * @param string[] $existingTags Tags existants dans le système
     * @return string[]
     */
    public function suggestTags(string $text, array $existingTags, int $limit = 5): array
    {
        $keywords = $this->extractKeywords($text, 20);
        $suggestions = [];

        // Chercher les tags existants qui matchent
        foreach ($existingTags as $tag) {
            $tagLower = mb_strtolower($tag);
            foreach ($keywords as $keyword) {
                if ($this->isSimilar($keyword, $tagLower)) {
                    $suggestions[$tag] = true;
                }
            }
        }

        // Ajouter les mots-clés les plus pertinents comme nouveaux tags potentiels
        foreach ($keywords as $keyword) {
            if (count($suggestions) >= $limit) {
                break;
            }
            if (!isset($suggestions[$keyword]) && mb_strlen($keyword) >= 3) {
                $suggestions[$keyword] = true;
            }
        }

        return array_slice(array_keys($suggestions), 0, $limit);
    }

    /**
     * Tokenise le texte en mots.
     *
     * @return string[]
     */
    private function tokenize(string $text): array
    {
        // Supprimer le HTML
        $text = strip_tags($text);

        // Supprimer le Markdown
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/`[^`]+`/', '', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        $text = preg_replace('/[#*_~]/', '', $text);

        // Convertir en minuscules
        $text = mb_strtolower($text);

        // Extraire les mots (lettres et chiffres)
        preg_match_all('/\b[\p{L}\p{N}]+\b/u', $text, $matches);

        $words = $matches[0];

        // Filtrer les stopwords et les mots trop courts
        return array_filter($words, function (string $word) {
            return mb_strlen($word) >= 3 && !in_array($word, $this->stopwords, true);
        });
    }

    /**
     * Calcule la fréquence des termes.
     *
     * @param string[] $words
     * @return array<string, float>
     */
    private function calculateTermFrequency(array $words): array
    {
        if (empty($words)) {
            return [];
        }

        $counts = array_count_values($words);
        $max = max($counts);

        // Normaliser par rapport au terme le plus fréquent
        $tf = [];
        foreach ($counts as $term => $count) {
            $tf[$term] = $count / $max;
        }

        return $tf;
    }

    /**
     * Calcule l'IDF d'un terme dans un corpus.
     *
     * @param string[] $corpus
     */
    private function calculateIdf(string $term, array $corpus): float
    {
        if (empty($corpus)) {
            return 1.0;
        }

        $documentsWithTerm = 0;
        foreach ($corpus as $document) {
            if (stripos($document, $term) !== false) {
                $documentsWithTerm++;
            }
        }

        if ($documentsWithTerm === 0) {
            return 0.0;
        }

        return log(count($corpus) / $documentsWithTerm) + 1;
    }

    /**
     * Vérifie si deux mots sont similaires.
     */
    private function isSimilar(string $word1, string $word2): bool
    {
        // Exactement égaux
        if ($word1 === $word2) {
            return true;
        }

        // L'un contient l'autre (ex: "php" et "phpunit")
        if (str_contains($word1, $word2) || str_contains($word2, $word1)) {
            return true;
        }

        // Distance de Levenshtein faible
        $distance = levenshtein($word1, $word2);
        $maxLen = max(mb_strlen($word1), mb_strlen($word2));

        return $distance <= ($maxLen / 4);
    }

    /**
     * Retourne les stopwords par défaut (français + anglais).
     *
     * @return string[]
     */
    private function getDefaultStopwords(): array
    {
        return [
            // Français
            'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'et', 'ou', 'mais',
            'donc', 'car', 'ni', 'que', 'qui', 'quoi', 'dont', 'où', 'ce', 'cette',
            'ces', 'mon', 'ton', 'son', 'notre', 'votre', 'leur', 'mes', 'tes', 'ses',
            'nos', 'vos', 'leurs', 'je', 'tu', 'il', 'elle', 'on', 'nous', 'vous',
            'ils', 'elles', 'me', 'te', 'se', 'lui', 'eux', 'moi', 'toi', 'soi',
            'être', 'avoir', 'faire', 'dire', 'aller', 'voir', 'venir', 'prendre',
            'est', 'sont', 'était', 'été', 'sera', 'fait', 'dit', 'peut', 'doit',
            'très', 'plus', 'moins', 'bien', 'aussi', 'encore', 'toujours', 'jamais',
            'tout', 'tous', 'toute', 'toutes', 'autre', 'autres', 'même', 'mêmes',
            'dans', 'sur', 'sous', 'avec', 'sans', 'pour', 'par', 'entre', 'vers',
            'chez', 'avant', 'après', 'pendant', 'depuis', 'jusque', 'comme',
            'quand', 'comment', 'pourquoi', 'combien', 'ainsi', 'alors', 'puis',
            'cela', 'ceci', 'cet', 'cette', 'celui', 'celle', 'ceux', 'celles',

            // Anglais
            'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had',
            'her', 'was', 'one', 'our', 'out', 'has', 'have', 'been', 'were', 'way',
            'who', 'did', 'get', 'its', 'let', 'put', 'say', 'she', 'too', 'use',
            'will', 'with', 'this', 'that', 'from', 'they', 'your', 'what', 'when',
            'would', 'there', 'their', 'which', 'could', 'other', 'than', 'then',
            'them', 'these', 'some', 'just', 'only', 'come', 'made', 'find', 'here',
            'many', 'said', 'each', 'tell', 'does', 'into', 'year', 'good', 'give',
            'most', 'make', 'want', 'able', 'also', 'very', 'after', 'before',
        ];
    }
}
