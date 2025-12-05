<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Parseur Markdown sans dépendance externe.
 *
 * Supporte la syntaxe Markdown courante :
 * - Titres (# à ######)
 * - Emphase (*italique*, **gras**, ***les deux***)
 * - Liens [texte](url) et images ![alt](src)
 * - Listes (- ou * ou 1.)
 * - Code (`inline` et ```blocs```)
 * - Citations (>)
 * - Lignes horizontales (---, ***)
 *
 * @example
 * ```php
 * $parser = new MarkdownParser();
 * $html = $parser->parse('# Titre\n\nParagraphe avec **gras**.');
 *
 * $title = $parser->extractTitle($markdown);
 * $excerpt = $parser->extractExcerpt($markdown, 150);
 * ```
 */
final class MarkdownParser
{
    /**
     * Parse le Markdown et retourne du HTML.
     */
    public function parse(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        // Normaliser les fins de ligne
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);

        // Échapper le HTML
        $markdown = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');

        // Traiter les caractères échappés par backslash
        $markdown = $this->processEscapes($markdown);

        // Traiter les blocs de code en premier (pour protéger leur contenu)
        $markdown = $this->parseCodeBlocks($markdown);

        // Traiter les blocs
        $markdown = $this->parseHorizontalRules($markdown);
        $markdown = $this->parseBlockquotes($markdown);
        $markdown = $this->parseHeadings($markdown);
        $markdown = $this->parseLists($markdown);
        $markdown = $this->parseIndentedCodeBlocks($markdown);

        // Traiter les éléments inline
        $markdown = $this->parseInlineCode($markdown);
        $markdown = $this->parseImages($markdown);
        $markdown = $this->parseLinks($markdown);
        $markdown = $this->parseAutoLinks($markdown);
        $markdown = $this->parseEmphasis($markdown);

        // Traiter les paragraphes
        $markdown = $this->parseParagraphs($markdown);

        // Restaurer les caractères échappés
        $markdown = $this->restoreEscapes($markdown);

        return trim($markdown);
    }

    /**
     * Extrait le titre (premier h1) du Markdown.
     */
    public function extractTitle(string $markdown): ?string
    {
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extrait un extrait du contenu sans le formatage Markdown.
     */
    public function extractExcerpt(string $markdown, int $maxLength = 150): string
    {
        // Supprimer le titre
        $text = preg_replace('/^#.*$/m', '', $markdown);

        // Supprimer le formatage Markdown
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);
        $text = preg_replace('/__(.+?)__/', '$1', $text);
        $text = preg_replace('/_(.+?)_/', '$1', $text);
        $text = preg_replace('/`(.+?)`/', '$1', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '', $text);

        // Supprimer les blocs de code
        $text = preg_replace('/```[\s\S]*?```/', '', $text);

        // Normaliser les espaces
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        // Tronquer à la limite du mot
        $excerpt = mb_substr($text, 0, $maxLength);
        $lastSpace = mb_strrpos($excerpt, ' ');

        if ($lastSpace !== false) {
            $excerpt = mb_substr($excerpt, 0, $lastSpace);
        }

        return $excerpt . '...';
    }

    /**
     * Traite les caractères échappés par backslash.
     */
    private function processEscapes(string $text): string
    {
        // Remplacer les séquences \* \_ \` etc. par des placeholders
        $escapes = ['\\*', '\\_', '\\`', '\\[', '\\]'];
        $placeholders = ["\x00STAR\x00", "\x00UNDER\x00", "\x00TICK\x00", "\x00LBRACK\x00", "\x00RBRACK\x00"];

        return str_replace($escapes, $placeholders, $text);
    }

    /**
     * Restaure les caractères échappés.
     */
    private function restoreEscapes(string $text): string
    {
        $placeholders = ["\x00STAR\x00", "\x00UNDER\x00", "\x00TICK\x00", "\x00LBRACK\x00", "\x00RBRACK\x00"];
        $chars = ['*', '_', '`', '[', ']'];

        return str_replace($placeholders, $chars, $text);
    }

    /**
     * Parse les titres (# à ######).
     */
    private function parseHeadings(string $text): string
    {
        return preg_replace_callback(
            '/^(#{1,6})\s+(.+?)\s*#*$/m',
            function ($matches) {
                $level = strlen($matches[1]);
                $content = trim($matches[2]);
                return "<h{$level}>{$content}</h{$level}>";
            },
            $text
        );
    }

    /**
     * Parse les blocs de code (```).
     */
    private function parseCodeBlocks(string $text): string
    {
        return preg_replace_callback(
            '/```(\w*)\n([\s\S]*?)```/',
            function ($matches) {
                $language = $matches[1];
                $code = rtrim($matches[2]);

                if ($language) {
                    return "<pre><code class=\"language-{$language}\">{$code}</code></pre>";
                }

                return "<pre><code>{$code}</code></pre>";
            },
            $text
        );
    }

    /**
     * Parse les blocs de code indentés (4 espaces).
     */
    private function parseIndentedCodeBlocks(string $text): string
    {
        return preg_replace_callback(
            '/(?:^    .+$\n?)+/m',
            function ($matches) {
                $code = preg_replace('/^    /m', '', $matches[0]);
                $code = rtrim($code);
                return "<pre><code>{$code}</code></pre>\n";
            },
            $text
        );
    }

    /**
     * Parse le code inline (`code`).
     */
    private function parseInlineCode(string $text): string
    {
        return preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    }

    /**
     * Parse les citations (>).
     */
    private function parseBlockquotes(string $text): string
    {
        return preg_replace_callback(
            '/(?:^&gt;\s?.+$\n?)+/m',
            function ($matches) {
                $content = preg_replace('/^&gt;\s?/m', '', $matches[0]);
                $content = trim($content);
                return "<blockquote><p>{$content}</p></blockquote>\n";
            },
            $text
        );
    }

    /**
     * Parse les listes.
     */
    private function parseLists(string $text): string
    {
        // Listes non-ordonnées
        $text = preg_replace_callback(
            '/(?:^[-*]\s+.+$\n?)+/m',
            function ($matches) {
                $items = preg_split('/^[-*]\s+/m', $matches[0], -1, PREG_SPLIT_NO_EMPTY);
                $listItems = array_map(
                    fn($item) => '<li>' . trim($item) . '</li>',
                    $items
                );
                return '<ul>' . implode('', $listItems) . '</ul>';
            },
            $text
        );

        // Listes ordonnées
        $text = preg_replace_callback(
            '/(?:^\d+\.\s+.+$\n?)+/m',
            function ($matches) {
                $items = preg_split('/^\d+\.\s+/m', $matches[0], -1, PREG_SPLIT_NO_EMPTY);
                $listItems = array_map(
                    fn($item) => '<li>' . trim($item) . '</li>',
                    $items
                );
                return '<ol>' . implode('', $listItems) . '</ol>';
            },
            $text
        );

        return $text;
    }

    /**
     * Parse les images.
     */
    private function parseImages(string $text): string
    {
        // ![alt](src "title")
        $text = preg_replace(
            '/!\[([^\]]*)\]\(([^)\s]+)(?:\s+&quot;([^&]+)&quot;)?\)/',
            '<img src="$2" alt="$1" title="$3">',
            $text
        );

        // Nettoyer les attributs vides
        return preg_replace('/\s+title=""/', '', $text);
    }

    /**
     * Parse les liens.
     */
    private function parseLinks(string $text): string
    {
        // [texte](url "title")
        $text = preg_replace(
            '/\[([^\]]+)\]\(([^)\s]+)(?:\s+&quot;([^&]+)&quot;)?\)/',
            '<a href="$2" title="$3">$1</a>',
            $text
        );

        // Nettoyer les attributs vides
        return preg_replace('/\s+title=""/', '', $text);
    }

    /**
     * Parse les liens automatiques (<url>).
     */
    private function parseAutoLinks(string $text): string
    {
        return preg_replace(
            '/&lt;(https?:\/\/[^&]+)&gt;/',
            '<a href="$1">$1</a>',
            $text
        );
    }

    /**
     * Parse l'emphase (gras, italique).
     */
    private function parseEmphasis(string $text): string
    {
        // Gras et italique (*** ou ___)
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/___(.+?)___/', '<strong><em>$1</em></strong>', $text);

        // Gras (** ou __)
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);

        // Italique (* ou _)
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);

        return $text;
    }

    /**
     * Parse les lignes horizontales.
     */
    private function parseHorizontalRules(string $text): string
    {
        return preg_replace('/^[-*]{3,}$/m', '<hr>', $text);
    }

    /**
     * Enveloppe les lignes restantes dans des paragraphes.
     */
    private function parseParagraphs(string $text): string
    {
        // Séparer en blocs
        $blocks = preg_split('/\n{2,}/', $text);

        $result = [];
        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            // Ne pas envelopper les éléments de bloc existants
            if (preg_match('/^<(h[1-6]|ul|ol|li|blockquote|pre|hr|p)/', $block)) {
                $result[] = $block;
                continue;
            }

            // Envelopper dans un paragraphe
            $result[] = '<p>' . str_replace("\n", '<br>', $block) . '</p>';
        }

        return implode("\n", $result);
    }
}
