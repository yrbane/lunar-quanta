<?php

declare(strict_types=1);

namespace Lunar\Service\Seo;

use Lunar\Entity\Post;

/**
 * Service de génération des meta tags SEO.
 *
 * Génère les meta tags pour l'optimisation du référencement,
 * incluant Open Graph et Twitter Cards.
 *
 * @example
 * ```php
 * $metaService = new MetaTagService('https://example.com', 'Mon Blog');
 * $meta = $metaService->forPost($post);
 * echo $meta->render();
 * ```
 */
final class MetaTagService
{
    private string $siteUrl;
    private string $siteName;
    private ?string $defaultImage = null;
    private ?string $twitterSite = null;
    private string $locale = 'fr_FR';

    public function __construct(string $siteUrl, string $siteName)
    {
        $this->siteUrl = rtrim($siteUrl, '/');
        $this->siteName = $siteName;
    }

    /**
     * Définit l'image par défaut pour les partages.
     */
    public function setDefaultImage(string $imageUrl): self
    {
        $this->defaultImage = $imageUrl;
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
     * Génère les meta tags pour un article.
     */
    public function forPost(Post $post): MetaTagCollection
    {
        $meta = new MetaTagCollection();

        // Meta tags de base
        $meta->set('title', $post->getTitle());
        $meta->set('description', $this->truncate($post->getExcerpt() ?? $post->getContent(), 160));

        // Canonical URL
        $meta->setCanonical($this->siteUrl . $post->getUrl());

        // Robots
        if ($post->getStatus()->value !== 'published') {
            $meta->set('robots', 'noindex, nofollow');
        } else {
            $meta->set('robots', 'index, follow');
        }

        // Author
        if ($post->getAuthor()) {
            $meta->set('author', $post->getAuthor());
        }

        // Published and modified time
        if ($post->getPublishedAt()) {
            $meta->set('article:published_time', $post->getPublishedAt()->format('c'));
        }
        if ($post->getUpdatedAt()) {
            $meta->set('article:modified_time', $post->getUpdatedAt()->format('c'));
        }

        // Keywords from tags
        $tags = $post->getTags();
        if (!empty($tags)) {
            $meta->set('keywords', implode(', ', $tags));
        }

        // Open Graph
        $this->addOpenGraph($meta, $post);

        // Twitter Cards
        $this->addTwitterCards($meta, $post);

        // JSON-LD structured data
        $meta->setJsonLd($this->generateArticleJsonLd($post));

        return $meta;
    }

    /**
     * Génère les meta tags pour une page de catégorie.
     */
    public function forCategory(string $name, string $slug, ?string $description = null): MetaTagCollection
    {
        $meta = new MetaTagCollection();

        $title = "Catégorie : {$name}";
        $meta->set('title', $title);
        $meta->set('description', $description ?? "Articles dans la catégorie {$name}");
        $meta->setCanonical($this->siteUrl . '/blog/category/' . $slug . '/');
        $meta->set('robots', 'index, follow');

        // Open Graph
        $meta->setOpenGraph('og:title', $title);
        $meta->setOpenGraph('og:description', $description ?? "Articles dans la catégorie {$name}");
        $meta->setOpenGraph('og:type', 'website');
        $meta->setOpenGraph('og:url', $this->siteUrl . '/blog/category/' . $slug . '/');
        $meta->setOpenGraph('og:site_name', $this->siteName);

        if ($this->defaultImage) {
            $meta->setOpenGraph('og:image', $this->absoluteUrl($this->defaultImage));
        }

        return $meta;
    }

    /**
     * Génère les meta tags pour une page de tag.
     */
    public function forTag(string $name, string $slug): MetaTagCollection
    {
        $meta = new MetaTagCollection();

        $title = "Tag : {$name}";
        $meta->set('title', $title);
        $meta->set('description', "Articles tagués avec {$name}");
        $meta->setCanonical($this->siteUrl . '/blog/tag/' . $slug . '/');
        $meta->set('robots', 'index, follow');

        // Open Graph
        $meta->setOpenGraph('og:title', $title);
        $meta->setOpenGraph('og:description', "Articles tagués avec {$name}");
        $meta->setOpenGraph('og:type', 'website');
        $meta->setOpenGraph('og:url', $this->siteUrl . '/blog/tag/' . $slug . '/');
        $meta->setOpenGraph('og:site_name', $this->siteName);

        return $meta;
    }

    /**
     * Génère les meta tags pour la page d'accueil du blog.
     */
    public function forBlogIndex(string $title = 'Blog', ?string $description = null): MetaTagCollection
    {
        $meta = new MetaTagCollection();

        $meta->set('title', $title);
        $meta->set('description', $description ?? 'Les derniers articles du blog');
        $meta->setCanonical($this->siteUrl . '/blog/');
        $meta->set('robots', 'index, follow');

        // Open Graph
        $meta->setOpenGraph('og:title', $title);
        $meta->setOpenGraph('og:description', $description ?? 'Les derniers articles du blog');
        $meta->setOpenGraph('og:type', 'website');
        $meta->setOpenGraph('og:url', $this->siteUrl . '/blog/');
        $meta->setOpenGraph('og:site_name', $this->siteName);

        if ($this->defaultImage) {
            $meta->setOpenGraph('og:image', $this->absoluteUrl($this->defaultImage));
        }

        return $meta;
    }

    /**
     * Ajoute les meta tags Open Graph.
     */
    private function addOpenGraph(MetaTagCollection $meta, Post $post): void
    {
        $meta->setOpenGraph('og:title', $post->getTitle());
        $meta->setOpenGraph('og:description', $this->truncate($post->getExcerpt() ?? $post->getContent(), 200));
        $meta->setOpenGraph('og:type', 'article');
        $meta->setOpenGraph('og:url', $this->siteUrl . $post->getUrl());
        $meta->setOpenGraph('og:site_name', $this->siteName);
        $meta->setOpenGraph('og:locale', $this->locale);

        // Image
        $image = $post->getFeaturedImage() ?? $this->defaultImage;
        if ($image) {
            $meta->setOpenGraph('og:image', $this->absoluteUrl($image));
            $meta->setOpenGraph('og:image:alt', $post->getTitle());
        }

        // Author
        if ($post->getAuthor()) {
            $meta->setOpenGraph('article:author', $post->getAuthor());
        }

        // Section/Category
        if ($post->getCategoryId()) {
            $meta->setOpenGraph('article:section', $post->getCategoryId());
        }

        // Tags
        foreach ($post->getTags() as $tag) {
            $meta->addOpenGraph('article:tag', $tag);
        }
    }

    /**
     * Ajoute les meta tags Twitter Cards.
     */
    private function addTwitterCards(MetaTagCollection $meta, Post $post): void
    {
        $image = $post->getFeaturedImage() ?? $this->defaultImage;

        // Utiliser summary_large_image si une image est disponible
        $cardType = $image ? 'summary_large_image' : 'summary';

        $meta->setTwitter('twitter:card', $cardType);
        $meta->setTwitter('twitter:title', $post->getTitle());
        $meta->setTwitter('twitter:description', $this->truncate($post->getExcerpt() ?? $post->getContent(), 200));

        if ($this->twitterSite) {
            $meta->setTwitter('twitter:site', $this->twitterSite);
        }

        if ($image) {
            $meta->setTwitter('twitter:image', $this->absoluteUrl($image));
            $meta->setTwitter('twitter:image:alt', $post->getTitle());
        }
    }

    /**
     * Génère les données JSON-LD pour un article.
     */
    private function generateArticleJsonLd(Post $post): array
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->getTitle(),
            'description' => $this->truncate($post->getExcerpt() ?? $post->getContent(), 200),
            'url' => $this->siteUrl . $post->getUrl(),
        ];

        // Dates
        if ($post->getPublishedAt()) {
            $jsonLd['datePublished'] = $post->getPublishedAt()->format('c');
        }
        if ($post->getUpdatedAt()) {
            $jsonLd['dateModified'] = $post->getUpdatedAt()->format('c');
        }

        // Image
        $image = $post->getFeaturedImage();
        if ($image) {
            $jsonLd['image'] = $this->absoluteUrl($image);
        }

        // Author
        if ($post->getAuthor()) {
            $jsonLd['author'] = [
                '@type' => 'Person',
                'name' => $post->getAuthor(),
            ];
        }

        // Publisher
        $jsonLd['publisher'] = [
            '@type' => 'Organization',
            'name' => $this->siteName,
            'url' => $this->siteUrl,
        ];

        // Keywords
        $tags = $post->getTags();
        if (!empty($tags)) {
            $jsonLd['keywords'] = implode(', ', $tags);
        }

        // Word count and reading time
        $jsonLd['wordCount'] = $post->getWordCount();

        return $jsonLd;
    }

    /**
     * Convertit une URL relative en URL absolue.
     */
    private function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        return $this->siteUrl . '/' . ltrim($url, '/');
    }

    /**
     * Tronque un texte à une longueur donnée.
     */
    private function truncate(string $text, int $length): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 3) . '...';
    }
}
