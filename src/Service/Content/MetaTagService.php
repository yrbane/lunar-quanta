<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service de génération de méta-tags pour le SEO.
 *
 * Génère les balises meta, Open Graph et Twitter Cards.
 *
 * @example
 * ```php
 * $meta = new MetaTagService();
 * $meta->setSiteName('Mon Site');
 *
 * echo $meta->generateAll([
 *     'title' => 'Article',
 *     'description' => 'Description',
 *     'image' => '/img/cover.jpg',
 * ]);
 * ```
 */
final class MetaTagService
{
    private string $siteName = '';
    private string $siteUrl = '';
    private string $defaultImage = '';
    private string $twitterSite = '';
    private string $locale = 'fr_FR';

    /**
     * Définit le nom du site.
     */
    public function setSiteName(string $name): self
    {
        $this->siteName = $name;
        return $this;
    }

    /**
     * Définit l'URL du site.
     */
    public function setSiteUrl(string $url): self
    {
        $this->siteUrl = rtrim($url, '/');
        return $this;
    }

    /**
     * Définit l'image par défaut.
     */
    public function setDefaultImage(string $url): self
    {
        $this->defaultImage = $url;
        return $this;
    }

    /**
     * Définit le compte Twitter du site.
     */
    public function setTwitterSite(string $handle): self
    {
        $this->twitterSite = $handle;
        return $this;
    }

    /**
     * Définit la locale.
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Génère toutes les méta-tags.
     *
     * @param array{
     *     title?: string,
     *     description?: string,
     *     author?: string,
     *     image?: string,
     *     url?: string,
     *     type?: string,
     *     datePublished?: string,
     *     dateModified?: string,
     *     keywords?: string[],
     * } $data
     */
    public function generateAll(array $data): string
    {
        $parts = [];

        // Basic meta tags
        $parts[] = $this->generateBasic($data);

        // Open Graph
        $parts[] = $this->generateOpenGraph($data);

        // Twitter Cards
        $parts[] = $this->generateTwitterCards($data);

        // Article specific
        if (($data['type'] ?? '') === 'article') {
            $parts[] = $this->generateArticleMeta($data);
        }

        return implode("\n", array_filter($parts));
    }

    /**
     * Génère les méta-tags de base.
     *
     * @param array<string, mixed> $data
     */
    public function generateBasic(array $data): string
    {
        $tags = [];

        if (!empty($data['description'])) {
            $tags[] = sprintf(
                '<meta name="description" content="%s">',
                $this->escape($this->truncate($data['description'], 160))
            );
        }

        if (!empty($data['author'])) {
            $tags[] = sprintf('<meta name="author" content="%s">', $this->escape($data['author']));
        }

        if (!empty($data['keywords']) && is_array($data['keywords'])) {
            $tags[] = sprintf(
                '<meta name="keywords" content="%s">',
                $this->escape(implode(', ', $data['keywords']))
            );
        }

        // Canonical URL
        if (!empty($data['url'])) {
            $tags[] = sprintf('<link rel="canonical" href="%s">', $this->escape($data['url']));
        }

        return implode("\n", $tags);
    }

    /**
     * Génère les méta-tags Open Graph.
     *
     * @param array<string, mixed> $data
     */
    public function generateOpenGraph(array $data): string
    {
        $tags = [];

        // Required OG tags
        if (!empty($data['title'])) {
            $title = $this->siteName
                ? $data['title'] . ' - ' . $this->siteName
                : $data['title'];
            $tags[] = sprintf('<meta property="og:title" content="%s">', $this->escape($title));
        }

        if (!empty($data['description'])) {
            $tags[] = sprintf(
                '<meta property="og:description" content="%s">',
                $this->escape($this->truncate($data['description'], 300))
            );
        }

        $type = $data['type'] ?? 'website';
        $tags[] = sprintf('<meta property="og:type" content="%s">', $this->escape($type));

        if (!empty($data['url'])) {
            $tags[] = sprintf('<meta property="og:url" content="%s">', $this->escape($data['url']));
        }

        // Image
        $image = $data['image'] ?? $this->defaultImage;
        if (!empty($image)) {
            $imageUrl = $this->makeAbsoluteUrl($image);
            $tags[] = sprintf('<meta property="og:image" content="%s">', $this->escape($imageUrl));
        }

        // Site name
        if (!empty($this->siteName)) {
            $tags[] = sprintf('<meta property="og:site_name" content="%s">', $this->escape($this->siteName));
        }

        // Locale
        $tags[] = sprintf('<meta property="og:locale" content="%s">', $this->escape($this->locale));

        return implode("\n", $tags);
    }

    /**
     * Génère les méta-tags Twitter Cards.
     *
     * @param array<string, mixed> $data
     */
    public function generateTwitterCards(array $data): string
    {
        $tags = [];

        // Card type
        $image = $data['image'] ?? $this->defaultImage;
        $cardType = !empty($image) ? 'summary_large_image' : 'summary';
        $tags[] = sprintf('<meta name="twitter:card" content="%s">', $cardType);

        // Site
        if (!empty($this->twitterSite)) {
            $tags[] = sprintf('<meta name="twitter:site" content="%s">', $this->escape($this->twitterSite));
        }

        // Title
        if (!empty($data['title'])) {
            $tags[] = sprintf('<meta name="twitter:title" content="%s">', $this->escape($data['title']));
        }

        // Description
        if (!empty($data['description'])) {
            $tags[] = sprintf(
                '<meta name="twitter:description" content="%s">',
                $this->escape($this->truncate($data['description'], 200))
            );
        }

        // Image
        if (!empty($image)) {
            $imageUrl = $this->makeAbsoluteUrl($image);
            $tags[] = sprintf('<meta name="twitter:image" content="%s">', $this->escape($imageUrl));
        }

        return implode("\n", $tags);
    }

    /**
     * Génère les méta-tags spécifiques aux articles.
     *
     * @param array<string, mixed> $data
     */
    public function generateArticleMeta(array $data): string
    {
        $tags = [];

        if (!empty($data['datePublished'])) {
            $tags[] = sprintf(
                '<meta property="article:published_time" content="%s">',
                $this->escape($data['datePublished'])
            );
        }

        if (!empty($data['dateModified'])) {
            $tags[] = sprintf(
                '<meta property="article:modified_time" content="%s">',
                $this->escape($data['dateModified'])
            );
        }

        if (!empty($data['author'])) {
            $tags[] = sprintf(
                '<meta property="article:author" content="%s">',
                $this->escape($data['author'])
            );
        }

        if (!empty($data['keywords']) && is_array($data['keywords'])) {
            foreach ($data['keywords'] as $tag) {
                $tags[] = sprintf(
                    '<meta property="article:tag" content="%s">',
                    $this->escape((string) $tag)
                );
            }
        }

        return implode("\n", $tags);
    }

    /**
     * Génère une balise meta simple.
     */
    public function meta(string $name, string $content): string
    {
        return sprintf('<meta name="%s" content="%s">', $this->escape($name), $this->escape($content));
    }

    /**
     * Génère une balise meta property.
     */
    public function property(string $property, string $content): string
    {
        return sprintf('<meta property="%s" content="%s">', $this->escape($property), $this->escape($content));
    }

    /**
     * Échappe une chaîne pour l'HTML.
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Tronque une chaîne à une longueur donnée.
     */
    private function truncate(string $text, int $maxLength): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * Convertit une URL relative en absolue.
     */
    private function makeAbsoluteUrl(string $url): string
    {
        if (preg_match('#^https?://#', $url)) {
            return $url;
        }

        if (empty($this->siteUrl)) {
            return $url;
        }

        return $this->siteUrl . '/' . ltrim($url, '/');
    }
}
