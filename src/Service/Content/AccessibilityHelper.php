<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Helper pour l'accessibilité (a11y).
 *
 * Ajoute et améliore les attributs d'accessibilité dans le HTML.
 *
 * @example
 * ```php
 * $a11y = new AccessibilityHelper();
 *
 * // Ajouter aria-label à un lien
 * $html = $a11y->enhanceLink($link, 'Lire l\'article complet');
 *
 * // Traiter tout le contenu
 * $html = $a11y->processContent($htmlContent);
 * ```
 */
final class AccessibilityHelper
{
    private string $skipLinkTarget = '#main-content';
    private string $skipLinkText = 'Aller au contenu principal';

    /**
     * Définit la cible du lien skip.
     */
    public function setSkipLinkTarget(string $target): self
    {
        $this->skipLinkTarget = $target;
        return $this;
    }

    /**
     * Définit le texte du lien skip.
     */
    public function setSkipLinkText(string $text): self
    {
        $this->skipLinkText = $text;
        return $this;
    }

    /**
     * Génère un lien skip-to-content.
     */
    public function generateSkipLink(): string
    {
        $target = htmlspecialchars($this->skipLinkTarget);
        $text = htmlspecialchars($this->skipLinkText);

        return <<<HTML
<a href="{$target}" class="skip-link">{$text}</a>
HTML;
    }

    /**
     * Améliore un lien avec des attributs d'accessibilité.
     */
    public function enhanceLink(string $link, ?string $ariaLabel = null, bool $isExternal = false): string
    {
        $result = $link;

        // Ajouter aria-label si fourni
        if ($ariaLabel !== null && !str_contains($link, 'aria-label')) {
            $result = preg_replace('/<a\s/', '<a aria-label="' . htmlspecialchars($ariaLabel) . '" ', $result);
        }

        // Gérer les liens externes
        if ($isExternal) {
            // Ajouter target et rel si pas déjà présents
            if (!str_contains($result, 'target=')) {
                $result = preg_replace('/<a\s/', '<a target="_blank" ', $result);
            }
            if (!str_contains($result, 'rel=')) {
                $result = preg_replace('/<a\s/', '<a rel="noopener noreferrer" ', $result);
            }

            // Ajouter indicateur visuel
            if (!str_contains($result, 'external-link-icon')) {
                $result = preg_replace('/<\/a>/', ' <span class="external-link-icon" aria-hidden="true">↗</span></a>', $result);
            }
        }

        return $result;
    }

    /**
     * Améliore une image avec des attributs d'accessibilité.
     */
    public function enhanceImage(string $img, ?string $alt = null, bool $isDecorative = false): string
    {
        $result = $img;

        if ($isDecorative) {
            // Image décorative
            if (!str_contains($result, 'alt=')) {
                $result = preg_replace('/<img\s/', '<img alt="" ', $result);
            }
            if (!str_contains($result, 'role=')) {
                $result = preg_replace('/<img\s/', '<img role="presentation" ', $result);
            }
        } elseif ($alt !== null && !str_contains($result, 'alt=')) {
            // Image avec alt
            $result = preg_replace('/<img\s/', '<img alt="' . htmlspecialchars($alt) . '" ', $result);
        }

        return $result;
    }

    /**
     * Améliore un bouton avec des attributs d'accessibilité.
     */
    public function enhanceButton(string $button, ?string $ariaLabel = null, ?string $ariaExpanded = null): string
    {
        $result = $button;

        if ($ariaLabel !== null && !str_contains($button, 'aria-label')) {
            $result = preg_replace('/<button\s/', '<button aria-label="' . htmlspecialchars($ariaLabel) . '" ', $result);
        }

        if ($ariaExpanded !== null && !str_contains($button, 'aria-expanded')) {
            $result = preg_replace('/<button\s/', '<button aria-expanded="' . $ariaExpanded . '" ', $result);
        }

        return $result;
    }

    /**
     * Ajoute les attributs ARIA à un formulaire.
     */
    public function enhanceForm(string $form, ?string $ariaDescribedBy = null): string
    {
        $result = $form;

        if ($ariaDescribedBy !== null && !str_contains($form, 'aria-describedby')) {
            $result = preg_replace('/<form\s/', '<form aria-describedby="' . htmlspecialchars($ariaDescribedBy) . '" ', $result);
        }

        return $result;
    }

    /**
     * Ajoute des attributs aux tables.
     */
    public function enhanceTable(string $table, ?string $caption = null, ?string $summary = null): string
    {
        $result = $table;

        // Ajouter role="table" si pas déjà présent
        if (!str_contains($result, 'role=')) {
            $result = preg_replace('/<table\s*/', '<table role="table" ', $result);
            $result = preg_replace('/<table>/', '<table role="table">', $result);
        }

        // Ajouter caption si fourni
        if ($caption !== null && !str_contains($result, '<caption')) {
            $captionHtml = '<caption>' . htmlspecialchars($caption) . '</caption>';
            $result = preg_replace('/<table([^>]*)>/', '<table$1>' . $captionHtml, $result);
        }

        // Ajouter aria-label pour le résumé
        if ($summary !== null && !str_contains($result, 'aria-label')) {
            $result = preg_replace('/<table\s/', '<table aria-label="' . htmlspecialchars($summary) . '" ', $result);
        }

        return $result;
    }

    /**
     * Traite tout le contenu HTML.
     */
    public function processContent(string $html): string
    {
        // Améliorer les images sans alt
        $html = preg_replace_callback(
            '/<img(?![^>]*alt=)[^>]*>/i',
            function ($matches) {
                return $this->enhanceImage($matches[0], 'Image', false);
            },
            $html
        );

        // Améliorer les liens externes
        $html = preg_replace_callback(
            '/<a[^>]*href=["\']https?:\/\/[^"\']+["\'][^>]*>.*?<\/a>/i',
            function ($matches) {
                // Vérifier si c'est un lien externe (pas le même domaine)
                return $this->enhanceLink($matches[0], null, true);
            },
            $html
        );

        // Ajouter scope aux en-têtes de tableau
        $html = preg_replace_callback(
            '/<th(?![^>]*scope=)[^>]*>/i',
            function ($matches) {
                return preg_replace('/<th/', '<th scope="col"', $matches[0]);
            },
            $html
        );

        return $html;
    }

    /**
     * Génère le CSS pour l'accessibilité.
     */
    public function generateCss(): string
    {
        return <<<CSS
/* Skip link */
.skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    background: #000;
    color: #fff;
    padding: 8px 16px;
    z-index: 10000;
    text-decoration: none;
    transition: top 0.2s ease;
}

.skip-link:focus {
    top: 0;
    outline: 2px solid #fff;
    outline-offset: 2px;
}

/* Focus visible */
:focus-visible {
    outline: 2px solid var(--accent-color, #3b82f6);
    outline-offset: 2px;
}

/* Réduire les animations pour ceux qui le préfèrent */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

/* Contraste élevé */
@media (prefers-contrast: more) {
    :root {
        --text-primary: #000;
        --bg-primary: #fff;
        --border-color: #000;
    }
}

/* Indicateur de lien externe */
.external-link-icon {
    font-size: 0.75em;
    vertical-align: super;
    margin-left: 0.25em;
}

/* Screen reader only */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Masquer visuellement mais accessible */
.visually-hidden:not(:focus):not(:active) {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
CSS;
    }

    /**
     * Génère un texte pour les lecteurs d'écran uniquement.
     */
    public function screenReaderText(string $text): string
    {
        return '<span class="sr-only">' . htmlspecialchars($text) . '</span>';
    }

    /**
     * Génère un message de chargement accessible.
     */
    public function loadingMessage(string $message = 'Chargement en cours...'): string
    {
        return '<div role="status" aria-live="polite" aria-busy="true">' . htmlspecialchars($message) . '</div>';
    }

    /**
     * Génère un message d'alerte accessible.
     */
    public function alertMessage(string $message, string $type = 'info'): string
    {
        $role = $type === 'error' ? 'alert' : 'status';
        return '<div role="' . $role . '" aria-live="assertive" class="alert alert-' . $type . '">'
             . htmlspecialchars($message)
             . '</div>';
    }

    /**
     * Vérifie si le HTML a un alt sur toutes les images.
     */
    public function hasAllImageAlts(string $html): bool
    {
        // Compter les images sans alt
        $imagesWithoutAlt = preg_match_all('/<img(?![^>]*alt=)[^>]*>/i', $html);
        return $imagesWithoutAlt === 0;
    }

    /**
     * Vérifie les problèmes d'accessibilité courants.
     *
     * @return array<string, array<string>>
     */
    public function checkIssues(string $html): array
    {
        $issues = [];

        // Images sans alt
        if (preg_match_all('/<img(?![^>]*alt=)[^>]*>/i', $html, $matches)) {
            $issues['missing_alt'] = $matches[0];
        }

        // Liens sans texte
        if (preg_match_all('/<a[^>]*>\s*<\/a>/i', $html, $matches)) {
            $issues['empty_links'] = $matches[0];
        }

        // Boutons sans texte accessible
        if (preg_match_all('/<button[^>]*>\s*<\/button>/i', $html, $matches)) {
            $issues['empty_buttons'] = $matches[0];
        }

        // Tables sans caption
        if (preg_match_all('/<table(?![^>]*aria-label)[^>]*>(?!.*<caption)/i', $html, $matches)) {
            $issues['tables_without_caption'] = $matches[0];
        }

        return $issues;
    }
}
