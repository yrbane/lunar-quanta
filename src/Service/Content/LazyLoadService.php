<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service pour le lazy loading des images.
 *
 * Transforme les images en lazy-loaded avec placeholder et animation.
 *
 * @example
 * ```php
 * $lazyLoad = new LazyLoadService();
 *
 * // Transformer une image
 * $html = $lazyLoad->transformImage($imgTag);
 *
 * // Transformer tout le contenu HTML
 * $html = $lazyLoad->processContent($htmlWithImages);
 *
 * // Générer le CSS et JS nécessaires
 * $css = $lazyLoad->generateCss();
 * $js = $lazyLoad->generateJs();
 * ```
 */
final class LazyLoadService
{
    private string $placeholderColor = '#f3f4f6';
    private string $loadingAnimation = 'fade';
    private int $threshold = 100;
    private bool $useNativeLazy = true;
    private bool $generateBlurHash = false;

    /**
     * Définit la couleur du placeholder.
     */
    public function setPlaceholderColor(string $color): self
    {
        $this->placeholderColor = $color;
        return $this;
    }

    /**
     * Définit l'animation de chargement.
     */
    public function setLoadingAnimation(string $animation): self
    {
        $this->loadingAnimation = in_array($animation, ['fade', 'blur', 'slide', 'none'])
            ? $animation
            : 'fade';
        return $this;
    }

    /**
     * Définit le seuil de préchargement en pixels.
     */
    public function setThreshold(int $threshold): self
    {
        $this->threshold = max(0, $threshold);
        return $this;
    }

    /**
     * Active/désactive le lazy loading natif.
     */
    public function setUseNativeLazy(bool $use): self
    {
        $this->useNativeLazy = $use;
        return $this;
    }

    /**
     * Transforme une balise image pour le lazy loading.
     */
    public function transformImage(string $imgTag, ?int $width = null, ?int $height = null): string
    {
        // Extraire l'attribut src
        if (!preg_match('/src=["\']([^"\']+)["\']/', $imgTag, $srcMatch)) {
            return $imgTag;
        }

        $originalSrc = $srcMatch[1];

        // Extraire les dimensions si présentes
        $imgWidth = $width;
        $imgHeight = $height;

        if (!$imgWidth && preg_match('/width=["\']?(\d+)/', $imgTag, $match)) {
            $imgWidth = (int) $match[1];
        }
        if (!$imgHeight && preg_match('/height=["\']?(\d+)/', $imgTag, $match)) {
            $imgHeight = (int) $match[1];
        }

        // Créer le placeholder SVG
        $placeholder = $this->generatePlaceholder($imgWidth, $imgHeight);

        // Construire la nouvelle balise
        $newTag = $imgTag;

        // Remplacer src par data-src
        $newTag = preg_replace(
            '/src=["\']([^"\']+)["\']/',
            'src="' . $placeholder . '" data-src="$1"',
            $newTag
        );

        // Ajouter loading="lazy" si natif activé
        if ($this->useNativeLazy && !str_contains($newTag, 'loading=')) {
            $newTag = preg_replace('/<img/', '<img loading="lazy"', $newTag);
        }

        // Ajouter la classe lazy
        if (preg_match('/class=["\']([^"\']*)["\']/', $newTag, $classMatch)) {
            $newTag = str_replace(
                $classMatch[0],
                'class="' . $classMatch[1] . ' lazy lazy-' . $this->loadingAnimation . '"',
                $newTag
            );
        } else {
            $newTag = preg_replace('/<img/', '<img class="lazy lazy-' . $this->loadingAnimation . '"', $newTag);
        }

        return $newTag;
    }

    /**
     * Traite tout le contenu HTML pour lazy loader les images.
     */
    public function processContent(string $html, bool $skipFirstImage = true): string
    {
        $imageCount = 0;

        return preg_replace_callback(
            '/<img[^>]+>/i',
            function ($matches) use ($skipFirstImage, &$imageCount) {
                $imageCount++;

                // Sauter la première image (LCP - Largest Contentful Paint)
                if ($skipFirstImage && $imageCount === 1) {
                    return $matches[0];
                }

                // Ne pas traiter si déjà lazy
                if (str_contains($matches[0], 'data-src')) {
                    return $matches[0];
                }

                return $this->transformImage($matches[0]);
            },
            $html
        );
    }

    /**
     * Génère un placeholder SVG.
     */
    public function generatePlaceholder(?int $width = null, ?int $height = null): string
    {
        $w = $width ?? 1;
        $h = $height ?? 1;
        $color = urlencode($this->placeholderColor);

        $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='{$w}' height='{$h}'>"
             . "<rect width='100%' height='100%' fill='{$this->placeholderColor}'/>"
             . "</svg>";

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Génère le CSS pour le lazy loading.
     */
    public function generateCss(): string
    {
        return <<<CSS
.lazy {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.lazy.loaded {
    opacity: 1;
}

.lazy-fade {
    opacity: 0;
}

.lazy-fade.loaded {
    opacity: 1;
}

.lazy-blur {
    filter: blur(20px);
    opacity: 0.5;
}

.lazy-blur.loaded {
    filter: blur(0);
    opacity: 1;
}

.lazy-slide {
    opacity: 0;
    transform: translateY(20px);
}

.lazy-slide.loaded {
    opacity: 1;
    transform: translateY(0);
}

.lazy-none.loaded {
    opacity: 1;
}

/* Conteneur pour maintenir le ratio */
.lazy-container {
    position: relative;
    overflow: hidden;
    background: {$this->placeholderColor};
}

.lazy-container img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
CSS;
    }

    /**
     * Génère le JavaScript pour le lazy loading.
     */
    public function generateJs(): string
    {
        $threshold = $this->threshold;

        return <<<JS
(function() {
    'use strict';

    // Support pour IntersectionObserver
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img.lazy[data-src]');

        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    loadImage(img);
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '{$threshold}px 0px'
        });

        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    } else {
        // Fallback pour les navigateurs sans IntersectionObserver
        const lazyImages = document.querySelectorAll('img.lazy[data-src]');
        lazyImages.forEach(loadImage);
    }

    function loadImage(img) {
        const src = img.getAttribute('data-src');
        if (!src) return;

        // Précharger l'image
        const tempImage = new Image();
        tempImage.onload = function() {
            img.src = src;
            img.removeAttribute('data-src');
            img.classList.add('loaded');
        };
        tempImage.onerror = function() {
            img.classList.add('error');
        };
        tempImage.src = src;
    }

    // Gérer les images ajoutées dynamiquement
    if ('MutationObserver' in window) {
        const mutationObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        const imgs = node.querySelectorAll ?
                            node.querySelectorAll('img.lazy[data-src]') : [];
                        imgs.forEach(function(img) {
                            loadImage(img);
                        });
                    }
                });
            });
        });

        mutationObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
})();
JS;
    }

    /**
     * Génère tout le code nécessaire.
     */
    public function generateAll(): array
    {
        return [
            'css' => $this->generateCss(),
            'js' => $this->generateJs(),
        ];
    }

    /**
     * Génère un snippet complet.
     */
    public function generateSnippet(): string
    {
        $css = $this->generateCss();
        $js = $this->generateJs();

        return <<<HTML
<!-- Lazy Load Images -->
<style>{$css}</style>
<script>{$js}</script>
<!-- /Lazy Load Images -->
HTML;
    }

    /**
     * Génère le wrapper responsive pour une image.
     */
    public function wrapImage(string $imgTag, int $width, int $height): string
    {
        $ratio = ($height / $width) * 100;

        return <<<HTML
<div class="lazy-container" style="padding-bottom: {$ratio}%;">
    {$imgTag}
</div>
HTML;
    }
}
