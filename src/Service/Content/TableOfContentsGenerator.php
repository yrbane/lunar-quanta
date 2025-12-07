<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Générateur de table des matières à partir de contenu HTML ou Markdown.
 *
 * Extrait les titres et génère une navigation hiérarchique.
 *
 * @example
 * ```php
 * $generator = new TableOfContentsGenerator();
 * $toc = $generator->generate($htmlContent);
 *
 * echo $toc->render();
 * echo $toc->renderFlat();
 * ```
 */
final class TableOfContentsGenerator
{
    private int $minLevel = 2;
    private int $maxLevel = 4;
    private bool $addAnchors = true;
    private string $anchorPrefix = 'heading-';

    /**
     * Définit le niveau minimum de titre à inclure.
     */
    public function setMinLevel(int $level): self
    {
        $this->minLevel = max(1, min(6, $level));
        return $this;
    }

    /**
     * Définit le niveau maximum de titre à inclure.
     */
    public function setMaxLevel(int $level): self
    {
        $this->maxLevel = max(1, min(6, $level));
        return $this;
    }

    /**
     * Active/désactive l'ajout d'ancres aux titres.
     */
    public function setAddAnchors(bool $add): self
    {
        $this->addAnchors = $add;
        return $this;
    }

    /**
     * Définit le préfixe des ancres.
     */
    public function setAnchorPrefix(string $prefix): self
    {
        $this->anchorPrefix = $prefix;
        return $this;
    }

    /**
     * Génère la table des matières à partir du contenu HTML.
     *
     * @return array{toc: TableOfContents, content: string}
     */
    public function generate(string $htmlContent): array
    {
        $toc = new TableOfContents();
        $headings = $this->extractHeadings($htmlContent);

        if (empty($headings)) {
            return ['toc' => $toc, 'content' => $htmlContent];
        }

        $modifiedContent = $htmlContent;

        foreach ($headings as $heading) {
            if ($heading['level'] < $this->minLevel || $heading['level'] > $this->maxLevel) {
                continue;
            }

            $anchor = $this->generateAnchor($heading['text']);

            // Ajouter au TOC
            $toc->add($heading['text'], $anchor, $heading['level']);

            // Modifier le contenu pour ajouter les ancres
            if ($this->addAnchors) {
                $modifiedContent = $this->addAnchorToHeading(
                    $modifiedContent,
                    $heading['original'],
                    $anchor
                );
            }
        }

        return ['toc' => $toc, 'content' => $modifiedContent];
    }

    /**
     * Génère la table des matières à partir du contenu Markdown.
     */
    public function generateFromMarkdown(string $markdown): TableOfContents
    {
        $toc = new TableOfContents();

        // Pattern pour les titres Markdown
        preg_match_all('/^(#{1,6})\s+(.+)$/m', $markdown, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $level = strlen($match[1]);

            if ($level < $this->minLevel || $level > $this->maxLevel) {
                continue;
            }

            $text = trim($match[2]);
            $anchor = $this->generateAnchor($text);

            $toc->add($text, $anchor, $level);
        }

        return $toc;
    }

    /**
     * Extrait les titres du HTML.
     */
    private function extractHeadings(string $html): array
    {
        $headings = [];

        $pattern = '/<h([1-6])([^>]*)>(.+?)<\/h\1>/is';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $level = (int) $match[1];
            $attributes = $match[2];
            $content = $match[3];

            // Nettoyer le contenu (supprimer les balises imbriquées)
            $text = strip_tags($content);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim($text);

            if (!empty($text)) {
                $headings[] = [
                    'level' => $level,
                    'text' => $text,
                    'original' => $match[0],
                    'attributes' => $attributes,
                ];
            }
        }

        return $headings;
    }

    /**
     * Génère un identifiant d'ancre à partir du texte.
     */
    private function generateAnchor(string $text): string
    {
        // Translittération
        $anchor = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);

        if ($anchor === false) {
            $anchor = mb_strtolower($text);
        }

        // Remplacer les caractères non alphanumériques par des tirets
        $anchor = preg_replace('/[^a-z0-9]+/', '-', $anchor);

        // Supprimer les tirets en début et fin
        $anchor = trim($anchor, '-');

        return $this->anchorPrefix . $anchor;
    }

    /**
     * Ajoute une ancre à un titre dans le HTML.
     */
    private function addAnchorToHeading(string $html, string $heading, string $anchor): string
    {
        // Vérifier si le titre a déjà un id
        if (preg_match('/<h([1-6])\s+[^>]*id\s*=/', $heading)) {
            return $html;
        }

        // Ajouter l'id au titre
        $replacement = preg_replace(
            '/<h([1-6])/',
            '<h$1 id="' . htmlspecialchars($anchor, ENT_QUOTES) . '"',
            $heading,
            1
        );

        return str_replace($heading, $replacement, $html);
    }
}

/**
 * Représente une table des matières.
 */
final class TableOfContents
{
    /** @var array<array{text: string, anchor: string, level: int, children: array}> */
    private array $items = [];

    /**
     * Ajoute un élément à la table des matières.
     */
    public function add(string $text, string $anchor, int $level): self
    {
        $this->items[] = [
            'text' => $text,
            'anchor' => $anchor,
            'level' => $level,
        ];
        return $this;
    }

    /**
     * Retourne tous les éléments.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Vérifie si la table des matières est vide.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Retourne le nombre d'éléments.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Génère le HTML de la table des matières (liste hiérarchique).
     */
    public function render(string $class = 'toc'): string
    {
        if (empty($this->items)) {
            return '';
        }

        $html = '<nav class="' . $this->escape($class) . '" aria-label="Table des matières">';
        $html .= '<ul>';

        $prevLevel = $this->items[0]['level'];
        $openLists = 1;

        foreach ($this->items as $item) {
            $level = $item['level'];

            // Gérer la hiérarchie
            if ($level > $prevLevel) {
                // Ouvrir des sous-listes
                for ($i = $prevLevel; $i < $level; $i++) {
                    $html .= '<ul>';
                    $openLists++;
                }
            } elseif ($level < $prevLevel) {
                // Fermer des sous-listes
                for ($i = $level; $i < $prevLevel; $i++) {
                    $html .= '</li></ul>';
                    $openLists--;
                }
                $html .= '</li>';
            } else {
                if ($item !== $this->items[0]) {
                    $html .= '</li>';
                }
            }

            $html .= '<li>';
            $html .= '<a href="#' . $this->escape($item['anchor']) . '">';
            $html .= $this->escape($item['text']);
            $html .= '</a>';

            $prevLevel = $level;
        }

        // Fermer toutes les listes ouvertes
        for ($i = 0; $i < $openLists; $i++) {
            $html .= '</li></ul>';
        }

        $html .= '</nav>';

        return $html;
    }

    /**
     * Génère une liste plate (non hiérarchique).
     */
    public function renderFlat(string $class = 'toc-flat'): string
    {
        if (empty($this->items)) {
            return '';
        }

        $html = '<nav class="' . $this->escape($class) . '" aria-label="Table des matières">';
        $html .= '<ul>';

        foreach ($this->items as $item) {
            $indent = str_repeat('&nbsp;&nbsp;', $item['level'] - 2);
            $html .= '<li class="toc-level-' . $item['level'] . '">';
            $html .= $indent;
            $html .= '<a href="#' . $this->escape($item['anchor']) . '">';
            $html .= $this->escape($item['text']);
            $html .= '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Retourne la table des matières sous forme de tableau.
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Échappe le contenu HTML.
     */
    private function escape(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
