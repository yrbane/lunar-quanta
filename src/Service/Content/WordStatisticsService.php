<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service de statistiques textuelles.
 *
 * Analyse le contenu pour fournir des statistiques détaillées.
 *
 * @example
 * ```php
 * $stats = new WordStatisticsService();
 *
 * $result = $stats->analyze($content);
 * echo $result['words'];        // Nombre de mots
 * echo $result['reading_time']; // Temps de lecture en minutes
 * echo $result['difficulty'];   // Niveau de difficulté
 * ```
 */
final class WordStatisticsService
{
    private int $wordsPerMinute = 200;

    /**
     * Définit la vitesse de lecture (mots par minute).
     */
    public function setWordsPerMinute(int $wpm): self
    {
        $this->wordsPerMinute = max(50, min(500, $wpm));
        return $this;
    }

    /**
     * Analyse le contenu et retourne des statistiques complètes.
     */
    public function analyze(string $content): array
    {
        $text = $this->stripHtml($content);

        $words = $this->countWords($text);
        $characters = mb_strlen($text);
        $charactersNoSpaces = mb_strlen(preg_replace('/\s+/', '', $text));
        $sentences = $this->countSentences($text);
        $paragraphs = $this->countParagraphs($text);
        $uniqueWords = $this->countUniqueWords($text);
        $avgWordLength = $words > 0 ? round($charactersNoSpaces / $words, 1) : 0;
        $avgSentenceLength = $sentences > 0 ? round($words / $sentences, 1) : 0;

        $readingTime = $this->calculateReadingTime($words);
        $speakingTime = $this->calculateSpeakingTime($words);

        $difficulty = $this->calculateDifficulty($text, $words, $sentences);
        $fleschScore = $this->calculateFleschScore($text, $words, $sentences);

        return [
            'words' => $words,
            'characters' => $characters,
            'characters_no_spaces' => $charactersNoSpaces,
            'sentences' => $sentences,
            'paragraphs' => $paragraphs,
            'unique_words' => $uniqueWords,
            'avg_word_length' => $avgWordLength,
            'avg_sentence_length' => $avgSentenceLength,
            'reading_time' => $readingTime,
            'speaking_time' => $speakingTime,
            'difficulty' => $difficulty,
            'flesch_score' => $fleschScore,
            'vocabulary_richness' => $words > 0 ? round($uniqueWords / $words * 100, 1) : 0,
        ];
    }

    /**
     * Compte les mots dans le texte.
     */
    public function countWords(string $text): int
    {
        $text = $this->stripHtml($text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    /**
     * Calcule le temps de lecture en minutes.
     */
    public function calculateReadingTime(int $words): int
    {
        return (int) ceil($words / $this->wordsPerMinute);
    }

    /**
     * Calcule le temps de lecture en format lisible.
     */
    public function formatReadingTime(int $words): string
    {
        $minutes = $this->calculateReadingTime($words);

        if ($minutes < 1) {
            return '< 1 min';
        }
        if ($minutes === 1) {
            return '1 min';
        }
        return "{$minutes} min";
    }

    /**
     * Calcule le temps d'élocution (parole).
     */
    public function calculateSpeakingTime(int $words): int
    {
        // Environ 150 mots par minute à l'oral
        return (int) ceil($words / 150);
    }

    /**
     * Compte les phrases.
     */
    private function countSentences(string $text): int
    {
        // Compter les séparateurs de phrases
        $count = preg_match_all('/[.!?]+/', $text);
        return max(1, $count ?: 1);
    }

    /**
     * Compte les paragraphes.
     */
    private function countParagraphs(string $text): int
    {
        // Compter les doubles sauts de ligne
        $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return count($paragraphs);
    }

    /**
     * Compte les mots uniques.
     */
    private function countUniqueWords(string $text): int
    {
        $text = mb_strtolower($text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Nettoyer la ponctuation
        $words = array_map(function ($word) {
            return preg_replace('/[^\p{L}\p{N}]/u', '', $word);
        }, $words);

        $words = array_filter($words);

        return count(array_unique($words));
    }

    /**
     * Calcule le niveau de difficulté.
     */
    private function calculateDifficulty(string $text, int $words, int $sentences): string
    {
        if ($words < 10) {
            return 'N/A';
        }

        $avgSentenceLength = $words / max(1, $sentences);
        $avgWordLength = mb_strlen(preg_replace('/\s+/', '', $text)) / max(1, $words);

        // Score basé sur la longueur des mots et des phrases
        $score = ($avgSentenceLength * 0.5) + ($avgWordLength * 2);

        if ($score < 15) {
            return 'Facile';
        }
        if ($score < 25) {
            return 'Modéré';
        }
        if ($score < 35) {
            return 'Difficile';
        }

        return 'Expert';
    }

    /**
     * Calcule le score Flesch-Kincaid (adapté au français).
     */
    private function calculateFleschScore(string $text, int $words, int $sentences): float
    {
        if ($words < 10 || $sentences < 1) {
            return 0;
        }

        $syllables = $this->countSyllables($text);

        // Formule Flesch adaptée au français
        $score = 206.835 - (1.015 * ($words / $sentences)) - (84.6 * ($syllables / $words));

        return round(max(0, min(100, $score)), 1);
    }

    /**
     * Compte approximativement les syllabes.
     */
    private function countSyllables(string $text): int
    {
        $text = mb_strtolower($text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $totalSyllables = 0;

        foreach ($words as $word) {
            // Nettoyer le mot
            $word = preg_replace('/[^\p{L}]/u', '', $word);
            if (empty($word)) {
                continue;
            }

            // Compter les voyelles consécutives comme une syllabe
            $vowels = preg_match_all('/[aeiouyàâäéèêëïîôùûüœæ]+/u', $word);
            $totalSyllables += max(1, $vowels);
        }

        return $totalSyllables;
    }

    /**
     * Trouve les mots les plus fréquents.
     *
     * @return array<string, int>
     */
    public function getMostFrequentWords(string $content, int $limit = 10): array
    {
        $text = $this->stripHtml($content);
        $text = mb_strtolower($text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Nettoyer et compter
        $frequency = [];
        $stopWords = $this->getStopWords();

        foreach ($words as $word) {
            $word = preg_replace('/[^\p{L}\p{N}]/u', '', $word);
            if (empty($word) || mb_strlen($word) < 3 || in_array($word, $stopWords)) {
                continue;
            }

            $frequency[$word] = ($frequency[$word] ?? 0) + 1;
        }

        arsort($frequency);

        return array_slice($frequency, 0, $limit, true);
    }

    /**
     * Retourne les mots vides (stop words) à ignorer.
     */
    private function getStopWords(): array
    {
        return [
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'en', 'et', 'ou',
            'à', 'au', 'aux', 'ce', 'ces', 'cette', 'qui', 'que', 'quoi', 'dont',
            'pour', 'par', 'sur', 'avec', 'sans', 'dans', 'est', 'sont', 'être',
            'avoir', 'fait', 'faire', 'plus', 'moins', 'très', 'tout', 'tous',
            'the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
            'should', 'may', 'might', 'must', 'shall', 'can', 'need', 'dare',
            'of', 'to', 'in', 'for', 'on', 'with', 'at', 'by', 'from', 'as',
            'and', 'or', 'but', 'if', 'then', 'else', 'when', 'where', 'why',
            'how', 'all', 'each', 'every', 'both', 'few', 'more', 'most', 'other',
            'some', 'such', 'not', 'only', 'own', 'same', 'than',
            'pas', 'ne', 'non', 'oui', 'bien', 'aussi', 'comme', 'mais',
            'donc', 'car', 'même', 'encore', 'déjà', 'toujours', 'jamais',
        ];
    }

    /**
     * Supprime le HTML du contenu.
     */
    private function stripHtml(string $html): string
    {
        $text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $text);
        $text = strip_tags($text);
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
