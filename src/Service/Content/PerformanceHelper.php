<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Helper pour l'optimisation des performances (Core Web Vitals).
 *
 * Génère les balises et optimisations pour améliorer les métriques LCP, FID, CLS.
 *
 * @example
 * ```php
 * $perf = new PerformanceHelper();
 *
 * // Précharger une ressource critique
 * $link = $perf->preload('/css/critical.css', 'style');
 *
 * // Générer les hints de ressources
 * $hints = $perf->generateResourceHints(['https://fonts.googleapis.com']);
 * ```
 */
final class PerformanceHelper
{
    /** @var string[] */
    private array $preconnectDomains = [];

    /** @var string[] */
    private array $dnsPrefetchDomains = [];

    /** @var array<array<string, string>> */
    private array $preloads = [];

    /** @var array<array<string, string>> */
    private array $prefetches = [];

    /**
     * Ajoute un domaine pour preconnect.
     */
    public function addPreconnect(string $domain, bool $crossorigin = false): self
    {
        $this->preconnectDomains[] = [
            'domain' => $domain,
            'crossorigin' => $crossorigin,
        ];
        return $this;
    }

    /**
     * Ajoute un domaine pour DNS prefetch.
     */
    public function addDnsPrefetch(string $domain): self
    {
        $this->dnsPrefetchDomains[] = $domain;
        return $this;
    }

    /**
     * Ajoute une ressource à précharger.
     */
    public function addPreload(string $url, string $as, ?string $type = null, bool $crossorigin = false): self
    {
        $this->preloads[] = [
            'url' => $url,
            'as' => $as,
            'type' => $type,
            'crossorigin' => $crossorigin,
        ];
        return $this;
    }

    /**
     * Ajoute une ressource à prefetch.
     */
    public function addPrefetch(string $url, string $as = ''): self
    {
        $this->prefetches[] = [
            'url' => $url,
            'as' => $as,
        ];
        return $this;
    }

    /**
     * Génère une balise preload.
     */
    public function preload(string $url, string $as, ?string $type = null, bool $crossorigin = false): string
    {
        $attrs = [
            'rel="preload"',
            'href="' . htmlspecialchars($url) . '"',
            'as="' . $as . '"',
        ];

        if ($type) {
            $attrs[] = 'type="' . $type . '"';
        }

        if ($crossorigin) {
            $attrs[] = 'crossorigin';
        }

        return '<link ' . implode(' ', $attrs) . '>';
    }

    /**
     * Génère une balise preconnect.
     */
    public function preconnect(string $domain, bool $crossorigin = false): string
    {
        $attrs = [
            'rel="preconnect"',
            'href="' . htmlspecialchars($domain) . '"',
        ];

        if ($crossorigin) {
            $attrs[] = 'crossorigin';
        }

        return '<link ' . implode(' ', $attrs) . '>';
    }

    /**
     * Génère une balise dns-prefetch.
     */
    public function dnsPrefetch(string $domain): string
    {
        return '<link rel="dns-prefetch" href="' . htmlspecialchars($domain) . '">';
    }

    /**
     * Génère une balise prefetch.
     */
    public function prefetch(string $url, string $as = ''): string
    {
        $attrs = [
            'rel="prefetch"',
            'href="' . htmlspecialchars($url) . '"',
        ];

        if ($as) {
            $attrs[] = 'as="' . $as . '"';
        }

        return '<link ' . implode(' ', $attrs) . '>';
    }

    /**
     * Génère toutes les balises de resource hints.
     */
    public function generateResourceHints(): string
    {
        $hints = [];

        // DNS Prefetch
        foreach ($this->dnsPrefetchDomains as $domain) {
            $hints[] = $this->dnsPrefetch($domain);
        }

        // Preconnect
        foreach ($this->preconnectDomains as $item) {
            $hints[] = $this->preconnect($item['domain'], $item['crossorigin']);
        }

        // Preloads
        foreach ($this->preloads as $item) {
            $hints[] = $this->preload($item['url'], $item['as'], $item['type'], $item['crossorigin']);
        }

        // Prefetches
        foreach ($this->prefetches as $item) {
            $hints[] = $this->prefetch($item['url'], $item['as']);
        }

        return implode("\n", $hints);
    }

    /**
     * Génère le CSS critique inline.
     */
    public function inlineCriticalCss(string $css): string
    {
        return '<style>' . $this->minifyCss($css) . '</style>';
    }

    /**
     * Génère le chargement async d'un CSS.
     */
    public function asyncCss(string $url): string
    {
        $escapedUrl = htmlspecialchars($url);

        return <<<HTML
<link rel="preload" href="{$escapedUrl}" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="{$escapedUrl}"></noscript>
HTML;
    }

    /**
     * Génère le chargement defer d'un script.
     */
    public function deferScript(string $url): string
    {
        return '<script src="' . htmlspecialchars($url) . '" defer></script>';
    }

    /**
     * Génère le chargement async d'un script.
     */
    public function asyncScript(string $url): string
    {
        return '<script src="' . htmlspecialchars($url) . '" async></script>';
    }

    /**
     * Génère l'attribut fetchpriority pour les images LCP.
     */
    public function lcpImage(string $imgTag): string
    {
        // Ajouter fetchpriority="high" si pas déjà présent
        if (!str_contains($imgTag, 'fetchpriority')) {
            $imgTag = preg_replace('/<img/', '<img fetchpriority="high"', $imgTag);
        }

        // Retirer loading="lazy" si présent (ne pas lazy load l'image LCP)
        $imgTag = preg_replace('/\s*loading=["\']lazy["\']/', '', $imgTag);

        return $imgTag;
    }

    /**
     * Génère les attributs pour une image avec dimensions explicites (CLS).
     */
    public function fixedDimensionsImage(string $imgTag, int $width, int $height): string
    {
        $result = $imgTag;

        // Ajouter width si pas présent
        if (!preg_match('/\bwidth=/', $result)) {
            $result = preg_replace('/<img/', '<img width="' . $width . '"', $result);
        }

        // Ajouter height si pas présent
        if (!preg_match('/\bheight=/', $result)) {
            $result = preg_replace('/<img/', '<img height="' . $height . '"', $result);
        }

        return $result;
    }

    /**
     * Génère le conteneur avec aspect ratio pour éviter CLS.
     */
    public function aspectRatioContainer(string $content, float $ratio): string
    {
        $paddingBottom = $ratio * 100;

        return <<<HTML
<div style="position:relative;padding-bottom:{$paddingBottom}%;height:0;overflow:hidden;">
    <div style="position:absolute;top:0;left:0;width:100%;height:100%;">
        {$content}
    </div>
</div>
HTML;
    }

    /**
     * Génère le JavaScript pour le monitoring des Core Web Vitals.
     */
    public function generateWebVitalsJs(): string
    {
        return <<<JS
(function() {
    'use strict';

    function sendMetric(name, value, id) {
        console.log('[Web Vitals]', name, value.toFixed(2));

        // Optionnel: envoyer à un endpoint analytics
        if (window.webVitalsEndpoint) {
            navigator.sendBeacon(window.webVitalsEndpoint, JSON.stringify({
                name: name,
                value: value,
                id: id,
                url: location.href,
                timestamp: Date.now()
            }));
        }
    }

    // LCP - Largest Contentful Paint
    new PerformanceObserver(function(list) {
        var entries = list.getEntries();
        var lastEntry = entries[entries.length - 1];
        sendMetric('LCP', lastEntry.startTime, lastEntry.id);
    }).observe({ type: 'largest-contentful-paint', buffered: true });

    // FID - First Input Delay
    new PerformanceObserver(function(list) {
        list.getEntries().forEach(function(entry) {
            sendMetric('FID', entry.processingStart - entry.startTime, entry.name);
        });
    }).observe({ type: 'first-input', buffered: true });

    // CLS - Cumulative Layout Shift
    var clsValue = 0;
    new PerformanceObserver(function(list) {
        list.getEntries().forEach(function(entry) {
            if (!entry.hadRecentInput) {
                clsValue += entry.value;
            }
        });
    }).observe({ type: 'layout-shift', buffered: true });

    // Envoyer CLS au déchargement de la page
    window.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            sendMetric('CLS', clsValue, 'cumulative');
        }
    });

    // INP - Interaction to Next Paint (nouvelle métrique)
    var interactions = [];
    new PerformanceObserver(function(list) {
        list.getEntries().forEach(function(entry) {
            if (entry.interactionId) {
                interactions.push(entry.duration);
            }
        });
    }).observe({ type: 'event', buffered: true, durationThreshold: 16 });

    window.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden' && interactions.length > 0) {
            interactions.sort(function(a, b) { return b - a; });
            var inp = interactions[Math.floor(interactions.length / 50)] || interactions[0];
            sendMetric('INP', inp, 'p98');
        }
    });
})();
JS;
    }

    /**
     * Génère les meta tags pour la performance.
     */
    public function generatePerformanceMeta(): string
    {
        return <<<HTML
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
HTML;
    }

    /**
     * Génère le script de font loading optimisé.
     */
    public function optimizedFontLoading(array $fonts): string
    {
        $fontLinks = [];
        $fontJs = [];

        foreach ($fonts as $font) {
            $url = htmlspecialchars($font['url']);
            $family = htmlspecialchars($font['family']);

            $fontLinks[] = '<link rel="preload" href="' . $url . '" as="font" type="font/woff2" crossorigin>';
            $fontJs[] = "{ family: '{$family}', url: '{$url}' }";
        }

        $linksHtml = implode("\n", $fontLinks);
        $fontsArray = implode(",\n        ", $fontJs);

        return <<<HTML
{$linksHtml}
<script>
(function() {
    var fonts = [
        {$fontsArray}
    ];

    if ('fonts' in document) {
        Promise.all(fonts.map(function(font) {
            return new FontFace(font.family, 'url(' + font.url + ')').load();
        })).then(function(loadedFonts) {
            loadedFonts.forEach(function(font) {
                document.fonts.add(font);
            });
            document.documentElement.classList.add('fonts-loaded');
        });
    }
})();
</script>
HTML;
    }

    /**
     * Minifie du CSS basique.
     */
    private function minifyCss(string $css): string
    {
        // Supprimer les commentaires
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);

        // Supprimer les espaces inutiles
        $css = preg_replace('/\s+/', ' ', $css);

        // Supprimer les espaces autour des caractères spéciaux
        $css = preg_replace('/\s*([{};:,>+~])\s*/', '$1', $css);

        // Supprimer les points-virgules avant les accolades fermantes
        $css = str_replace(';}', '}', $css);

        return trim($css);
    }
}
