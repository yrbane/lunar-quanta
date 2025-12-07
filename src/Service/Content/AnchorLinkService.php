<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service pour les liens d'ancrage sur les titres.
 *
 * Ajoute des liens cliquables aux titres (h1-h6) pour la navigation.
 *
 * @example
 * ```php
 * $anchor = new AnchorLinkService();
 *
 * // Transformer un titre
 * $html = $anchor->addAnchor('<h2>Mon Titre</h2>');
 *
 * // Transformer tout le contenu
 * $html = $anchor->processContent($htmlWithHeadings);
 * ```
 */
final class AnchorLinkService
{
    private string $linkPosition = 'before';
    private string $linkClass = 'anchor-link';
    private string $linkSymbol = '#';
    private bool $addId = true;
    /** @var int[] */
    private array $levels = [1, 2, 3, 4, 5, 6];
    private bool $visibleOnHover = true;

    /**
     * Définit la position du lien.
     */
    public function setLinkPosition(string $position): self
    {
        $this->linkPosition = in_array($position, ['before', 'after', 'wrap']) ? $position : 'before';
        return $this;
    }

    /**
     * Définit la classe CSS du lien.
     */
    public function setLinkClass(string $class): self
    {
        $this->linkClass = $class;
        return $this;
    }

    /**
     * Définit le symbole du lien.
     */
    public function setLinkSymbol(string $symbol): self
    {
        $this->linkSymbol = $symbol;
        return $this;
    }

    /**
     * Active/désactive l'ajout d'id.
     */
    public function setAddId(bool $add): self
    {
        $this->addId = $add;
        return $this;
    }

    /**
     * Définit les niveaux de titre à traiter.
     *
     * @param int[] $levels
     */
    public function setLevels(array $levels): self
    {
        $this->levels = array_filter($levels, fn ($l) => $l >= 1 && $l <= 6);
        return $this;
    }

    /**
     * Active/désactive l'affichage au survol uniquement.
     */
    public function setVisibleOnHover(bool $visible): self
    {
        $this->visibleOnHover = $visible;
        return $this;
    }

    /**
     * Génère un slug à partir du texte.
     */
    public function generateSlug(string $text): string
    {
        // Supprimer le HTML
        $text = strip_tags($text);

        // Convertir en minuscules
        $text = mb_strtolower($text);

        // Translitérer les caractères accentués
        $text = $this->transliterate($text);

        // Remplacer les espaces et caractères spéciaux par des tirets
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        // Supprimer les tirets en début et fin
        $text = trim($text, '-');

        return $text ?: 'section';
    }

    /**
     * Ajoute un lien d'ancrage à un titre.
     */
    public function addAnchor(string $heading): string
    {
        // Extraire le niveau et le contenu
        if (!preg_match('/<h([1-6])([^>]*)>(.*?)<\/h\1>/is', $heading, $matches)) {
            return $heading;
        }

        $level = (int) $matches[1];
        $attributes = $matches[2];
        $content = $matches[3];

        // Vérifier si ce niveau doit être traité
        if (!in_array($level, $this->levels)) {
            return $heading;
        }

        // Générer l'id
        $slug = $this->generateSlug($content);

        // Extraire l'id existant ou en créer un nouveau
        $id = $slug;
        if (preg_match('/id=["\']([^"\']+)["\']/', $attributes, $idMatch)) {
            $id = $idMatch[1];
            $hasId = true;
        } else {
            $hasId = false;
        }

        // Créer le lien
        $linkHtml = $this->createLink($id);

        // Construire le nouveau titre
        $newContent = match ($this->linkPosition) {
            'before' => $linkHtml . $content,
            'after' => $content . $linkHtml,
            'wrap' => '<a href="#' . htmlspecialchars($id) . '" class="' . $this->linkClass . '-wrap">' . $content . '</a>',
            default => $linkHtml . $content,
        };

        // Ajouter l'id si nécessaire
        $newAttributes = $attributes;
        if ($this->addId && !$hasId) {
            $newAttributes .= ' id="' . htmlspecialchars($slug) . '"';
        }

        return "<h{$level}{$newAttributes}>{$newContent}</h{$level}>";
    }

    /**
     * Traite tout le contenu HTML.
     */
    public function processContent(string $html): string
    {
        $levelsPattern = implode('|', $this->levels);

        return preg_replace_callback(
            '/<h([' . $levelsPattern . '])([^>]*)>(.*?)<\/h\1>/is',
            fn ($matches) => $this->addAnchor($matches[0]),
            $html
        );
    }

    /**
     * Crée le lien HTML.
     */
    private function createLink(string $id): string
    {
        $escapedId = htmlspecialchars($id);
        $escapedSymbol = htmlspecialchars($this->linkSymbol);

        return '<a href="#' . $escapedId . '" class="' . $this->linkClass . '" aria-label="Lien vers cette section">'
             . $escapedSymbol
             . '</a>';
    }

    /**
     * Génère le CSS pour les liens d'ancrage.
     */
    public function generateCss(): string
    {
        $hoverRule = $this->visibleOnHover
            ? "opacity: 0; &:hover { opacity: 1; }"
            : "";

        return <<<CSS
.{$this->linkClass} {
    text-decoration: none;
    color: var(--la-primary, #3b82f6);
    margin-right: 0.5em;
    font-weight: normal;
    {$hoverRule}
    transition: opacity 0.2s ease;
}

h1, h2, h3, h4, h5, h6 {
    position: relative;
}

h1:hover .{$this->linkClass},
h2:hover .{$this->linkClass},
h3:hover .{$this->linkClass},
h4:hover .{$this->linkClass},
h5:hover .{$this->linkClass},
h6:hover .{$this->linkClass} {
    opacity: 1;
}

.{$this->linkClass}-wrap {
    text-decoration: none;
    color: inherit;
}

.{$this->linkClass}-wrap:hover {
    text-decoration: underline;
}

/* Offset pour le scroll avec header fixe */
h1[id], h2[id], h3[id], h4[id], h5[id], h6[id] {
    scroll-margin-top: 80px;
}
CSS;
    }

    /**
     * Génère le JavaScript pour le smooth scroll.
     */
    public function generateJs(): string
    {
        return <<<JS
(function() {
    'use strict';

    // Smooth scroll pour les liens d'ancrage
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').slice(1);
            const target = document.getElementById(targetId);

            if (target) {
                e.preventDefault();

                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Mettre à jour l'URL
                history.pushState(null, '', '#' + targetId);
            }
        });
    });

    // Copier le lien au clic (optionnel)
    document.querySelectorAll('.{$this->linkClass}').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                const url = window.location.href.split('#')[0] + this.getAttribute('href');
                navigator.clipboard.writeText(url).then(function() {
                    // Feedback visuel
                    link.classList.add('copied');
                    setTimeout(function() {
                        link.classList.remove('copied');
                    }, 1000);
                });
            }
        });
    });
})();
JS;
    }

    /**
     * Translitère les caractères accentués.
     */
    private function transliterate(string $text): string
    {
        $replacements = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
            'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'é' => 'e',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ñ' => 'n',
            'ç' => 'c',
            'œ' => 'oe', 'æ' => 'ae',
        ];

        return strtr($text, $replacements);
    }
}
