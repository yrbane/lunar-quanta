<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Générateur d'extraits intelligents.
 *
 * Crée des extraits à partir de contenu HTML ou Markdown.
 *
 * @example
 * ```php
 * $generator = new ExcerptGenerator();
 *
 * // Extrait simple
 * echo $generator->generate($content, 200);
 *
 * // Extrait à partir du premier paragraphe
 * echo $generator->fromFirstParagraph($htmlContent);
 *
 * // Extrait sans couper les mots
 * echo $generator->generate($content, 200, true);
 * ```
 */
final class ExcerptGenerator
{
    /**
     * Génère un extrait à partir de contenu.
     *
     * @param string $content Contenu source (HTML ou texte)
     * @param int $length Longueur maximale
     * @param bool $preserveWords Ne pas couper au milieu d'un mot
     * @param string $suffix Suffixe à ajouter si tronqué
     */
    public function generate(
        string $content,
        int $length = 160,
        bool $preserveWords = true,
        string $suffix = '...'
    ): string {
        // Nettoyer le HTML
        $text = $this->stripHtml($content);

        // Normaliser les espaces
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        if ($preserveWords) {
            return $this->truncateAtWord($text, $length, $suffix);
        }

        return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    /**
     * Extrait le premier paragraphe.
     */
    public function fromFirstParagraph(string $htmlContent, int $maxLength = 300): string
    {
        // Chercher le premier paragraphe
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $htmlContent, $matches)) {
            $paragraph = $this->stripHtml($matches[1]);
        } else {
            // Pas de paragraphe HTML, prendre le premier bloc de texte
            $paragraph = $this->stripHtml($htmlContent);
        }

        $paragraph = trim($paragraph);

        if (mb_strlen($paragraph) <= $maxLength) {
            return $paragraph;
        }

        return $this->truncateAtWord($paragraph, $maxLength, '...');
    }

    /**
     * Génère un extrait optimisé pour le SEO.
     *
     * Extrait les 155-160 caractères optimaux pour les meta descriptions.
     */
    public function forSeo(string $content): string
    {
        $excerpt = $this->generate($content, 160, true, '');

        // S'assurer que l'extrait se termine par une phrase complète si possible
        $lastPeriod = mb_strrpos($excerpt, '.');
        $lastQuestion = mb_strrpos($excerpt, '?');
        $lastExclamation = mb_strrpos($excerpt, '!');

        $lastPunctuation = max($lastPeriod ?: 0, $lastQuestion ?: 0, $lastExclamation ?: 0);

        if ($lastPunctuation > 100) {
            return mb_substr($excerpt, 0, $lastPunctuation + 1);
        }

        if (mb_strlen($excerpt) < 160) {
            return $excerpt;
        }

        return $excerpt . '...';
    }

    /**
     * Extrait les premiers mots.
     */
    public function firstWords(string $content, int $wordCount = 30): string
    {
        $text = $this->stripHtml($content);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) <= $wordCount) {
            return implode(' ', $words);
        }

        return implode(' ', array_slice($words, 0, $wordCount)) . '...';
    }

    /**
     * Génère un extrait à partir de Markdown.
     */
    public function fromMarkdown(string $markdown, int $length = 160): string
    {
        // Supprimer les titres
        $text = preg_replace('/^#{1,6}\s+.+$/m', '', $markdown);

        // Supprimer les images
        $text = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $text);

        // Supprimer les liens en gardant le texte
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);

        // Supprimer le formatage Markdown
        $text = preg_replace('/[*_~`]+/', '', $text);

        // Supprimer les blocs de code
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/`[^`]+`/', '', $text);

        // Supprimer les listes
        $text = preg_replace('/^\s*[-*+]\s+/m', '', $text);
        $text = preg_replace('/^\s*\d+\.\s+/m', '', $text);

        return $this->generate($text, $length);
    }

    /**
     * Supprime les balises HTML.
     */
    private function stripHtml(string $html): string
    {
        // Supprimer les scripts et styles
        $text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $text);

        // Supprimer les balises
        $text = strip_tags($text);

        // Décoder les entités HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $text;
    }

    /**
     * Tronque le texte à la limite de mots.
     */
    private function truncateAtWord(string $text, int $length, string $suffix): string
    {
        $suffixLength = mb_strlen($suffix);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        // Trouver le dernier espace avant la limite
        $truncated = mb_substr($text, 0, $length - $suffixLength);
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > $length * 0.5) {
            return mb_substr($truncated, 0, $lastSpace) . $suffix;
        }

        // Pas d'espace trouvé à une position raisonnable
        return $truncated . $suffix;
    }
}
