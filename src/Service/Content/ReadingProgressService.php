<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service pour la barre de progression de lecture.
 *
 * Génère le HTML/CSS/JS nécessaire pour afficher une barre de progression
 * qui indique la progression de lecture d'un article.
 *
 * @example
 * ```php
 * $progress = new ReadingProgressService();
 *
 * // Générer le CSS
 * $css = $progress->generateCss();
 *
 * // Générer le HTML de la barre
 * $html = $progress->generateHtml();
 *
 * // Générer le JavaScript
 * $js = $progress->generateJs();
 * ```
 */
final class ReadingProgressService
{
    private string $position = 'top';
    private int $height = 4;
    private string $color = '#3b82f6';
    private string $backgroundColor = 'transparent';
    private int $zIndex = 9999;
    private string $targetSelector = 'article';

    /**
     * Définit la position de la barre.
     */
    public function setPosition(string $position): self
    {
        $this->position = in_array($position, ['top', 'bottom']) ? $position : 'top';
        return $this;
    }

    /**
     * Définit la hauteur en pixels.
     */
    public function setHeight(int $height): self
    {
        $this->height = max(1, min(20, $height));
        return $this;
    }

    /**
     * Définit la couleur de la barre.
     */
    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Définit la couleur de fond.
     */
    public function setBackgroundColor(string $color): self
    {
        $this->backgroundColor = $color;
        return $this;
    }

    /**
     * Définit le z-index.
     */
    public function setZIndex(int $zIndex): self
    {
        $this->zIndex = $zIndex;
        return $this;
    }

    /**
     * Définit le sélecteur de l'élément cible.
     */
    public function setTargetSelector(string $selector): self
    {
        $this->targetSelector = $selector;
        return $this;
    }

    /**
     * Génère le CSS pour la barre de progression.
     */
    public function generateCss(): string
    {
        $positionStyle = $this->position === 'top' ? 'top: 0;' : 'bottom: 0;';

        return <<<CSS
.reading-progress-container {
    position: fixed;
    left: 0;
    right: 0;
    {$positionStyle}
    height: {$this->height}px;
    background: {$this->backgroundColor};
    z-index: {$this->zIndex};
    pointer-events: none;
}

.reading-progress-bar {
    height: 100%;
    width: 0%;
    background: {$this->color};
    transition: width 0.1s ease-out;
}

.reading-progress-bar.gradient {
    background: linear-gradient(90deg, {$this->color}, #8b5cf6);
}
CSS;
    }

    /**
     * Génère le HTML pour la barre de progression.
     */
    public function generateHtml(bool $useGradient = false): string
    {
        $gradientClass = $useGradient ? ' gradient' : '';

        return <<<HTML
<div class="reading-progress-container" aria-hidden="true">
    <div class="reading-progress-bar{$gradientClass}"></div>
</div>
HTML;
    }

    /**
     * Génère le JavaScript pour la barre de progression.
     */
    public function generateJs(): string
    {
        $selector = addslashes($this->targetSelector);

        return <<<JS
(function() {
    'use strict';

    const progressBar = document.querySelector('.reading-progress-bar');
    const targetElement = document.querySelector('{$selector}');

    if (!progressBar || !targetElement) return;

    function updateProgress() {
        const targetRect = targetElement.getBoundingClientRect();
        const targetTop = targetRect.top + window.pageYOffset;
        const targetHeight = targetRect.height;
        const windowHeight = window.innerHeight;
        const scrollY = window.pageYOffset;

        // Calcul de la progression
        const start = targetTop;
        const end = targetTop + targetHeight - windowHeight;
        const current = scrollY;

        let progress = 0;

        if (current >= start && current <= end) {
            progress = ((current - start) / (end - start)) * 100;
        } else if (current > end) {
            progress = 100;
        }

        progressBar.style.width = progress + '%';
    }

    // Throttle pour performance
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(function() {
                updateProgress();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    // Mise à jour initiale
    updateProgress();
})();
JS;
    }

    /**
     * Génère tout le code nécessaire (CSS + HTML + JS).
     */
    public function generateAll(bool $useGradient = false, bool $inline = true): array
    {
        $css = $this->generateCss();
        $html = $this->generateHtml($useGradient);
        $js = $this->generateJs();

        if ($inline) {
            return [
                'head' => "<style>{$css}</style>",
                'body_start' => $html,
                'body_end' => "<script>{$js}</script>",
            ];
        }

        return [
            'css' => $css,
            'html' => $html,
            'js' => $js,
        ];
    }

    /**
     * Génère un snippet complet à insérer.
     */
    public function generateSnippet(bool $useGradient = false): string
    {
        $all = $this->generateAll($useGradient, true);

        return <<<HTML
<!-- Reading Progress Bar -->
{$all['head']}
{$all['body_start']}
{$all['body_end']}
<!-- /Reading Progress Bar -->
HTML;
    }
}
