<?php

declare(strict_types=1);

namespace Lunar\Service\StaticSite;

use Lunar\Entity\Post;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Blog\TagService;

/**
 * Générateur de sitemap XML pour le blog.
 *
 * Produit un fichier sitemap.xml valide pour le référencement.
 *
 * @example
 * ```php
 * $generator = new SitemapGenerator(
 *     $postService,
 *     'https://example.com'
 * );
 *
 * $sitemap = $generator->generate();
 * file_put_contents('public/sitemap.xml', $sitemap);
 * ```
 */
final class SitemapGenerator
{
    private ?CategoryService $categoryService = null;
    private ?TagService $tagService = null;
    private bool $includeImages = true;

    public function __construct(
        private readonly PostService $postService,
        private readonly string $siteUrl
    ) {
    }

    /**
     * Définit le service de catégories pour inclure les pages de catégories.
     */
    public function setCategoryService(CategoryService $categoryService): self
    {
        $this->categoryService = $categoryService;
        return $this;
    }

    /**
     * Définit le service de tags pour inclure les pages de tags.
     */
    public function setTagService(TagService $tagService): self
    {
        $this->tagService = $tagService;
        return $this;
    }

    /**
     * Active/désactive l'inclusion des images dans le sitemap.
     */
    public function setIncludeImages(bool $include): self
    {
        $this->includeImages = $include;
        return $this;
    }

    /**
     * Génère le contenu XML du sitemap.
     */
    public function generate(): string
    {
        $posts = $this->postService->findPublished();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';

        if ($this->includeImages) {
            $xml .= "\n" . '         xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        }

        $xml .= '>' . "\n";

        // Page d'accueil du blog
        $xml .= $this->generateUrl(
            $this->siteUrl . '/blog/',
            new \DateTimeImmutable(),
            'daily',
            '1.0'
        );

        // Pages de catégories
        if ($this->categoryService !== null) {
            $categories = $this->categoryService->all();
            foreach ($categories as $category) {
                $xml .= $this->generateUrl(
                    $this->siteUrl . '/blog/category/' . $category->getSlug() . '/',
                    new \DateTimeImmutable(),
                    'weekly',
                    '0.7'
                );
            }
        }

        // Pages de tags
        if ($this->tagService !== null) {
            $tags = $this->tagService->all();
            foreach ($tags as $tag) {
                $xml .= $this->generateUrl(
                    $this->siteUrl . '/blog/tag/' . $tag->getSlug() . '/',
                    new \DateTimeImmutable(),
                    'weekly',
                    '0.6'
                );
            }
        }

        // Articles
        foreach ($posts as $post) {
            $xml .= $this->generatePostUrl($post);
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Génère un sitemap index pour les grands sites.
     */
    public function generateIndex(array $sitemapFiles): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemapFiles as $file) {
            $xml .= '<sitemap>' . "\n";
            $xml .= '  <loc>' . $this->escape($this->siteUrl . '/' . $file) . '</loc>' . "\n";
            $xml .= '  <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '</sitemap>' . "\n";
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * Génère une entrée URL pour un article.
     */
    private function generatePostUrl(Post $post): string
    {
        $lastmod = $post->getUpdatedAt() ?? $post->getPublishedAt() ?? $post->getCreatedAt();

        $url = '<url>' . "\n";
        $url .= '  <loc>' . $this->escape($this->siteUrl . $post->getUrl()) . '</loc>' . "\n";

        if ($lastmod !== null) {
            $url .= '  <lastmod>' . $lastmod->format('Y-m-d') . '</lastmod>' . "\n";
        }

        $url .= '  <changefreq>monthly</changefreq>' . "\n";

        // Priorité plus élevée pour les articles mis en avant
        $priority = $post->isFeatured() ? '0.9' : '0.8';
        $url .= '  <priority>' . $priority . '</priority>' . "\n";

        // Ajouter l'image mise en avant si disponible
        if ($this->includeImages && $post->getFeaturedImage() !== null) {
            $imageUrl = $post->getFeaturedImage();

            // Convertir en URL absolue si nécessaire
            if (!str_starts_with($imageUrl, 'http')) {
                $imageUrl = $this->siteUrl . $imageUrl;
            }

            $url .= '  <image:image>' . "\n";
            $url .= '    <image:loc>' . $this->escape($imageUrl) . '</image:loc>' . "\n";

            if ($post->getTitle()) {
                $url .= '    <image:title>' . $this->escape($post->getTitle()) . '</image:title>' . "\n";
            }

            if ($post->getExcerpt()) {
                $caption = mb_substr($post->getExcerpt(), 0, 200);
                $url .= '    <image:caption>' . $this->escape($caption) . '</image:caption>' . "\n";
            }

            $url .= '  </image:image>' . "\n";
        }

        $url .= '</url>' . "\n";

        return $url;
    }

    /**
     * Génère une entrée URL.
     */
    private function generateUrl(
        string $loc,
        \DateTimeInterface $lastmod,
        string $changefreq,
        string $priority
    ): string {
        $url = '<url>' . "\n";
        $url .= '  <loc>' . $this->escape($loc) . '</loc>' . "\n";
        $url .= '  <lastmod>' . $lastmod->format('Y-m-d') . '</lastmod>' . "\n";
        $url .= '  <changefreq>' . $changefreq . '</changefreq>' . "\n";
        $url .= '  <priority>' . $priority . '</priority>' . "\n";
        $url .= '</url>' . "\n";

        return $url;
    }

    /**
     * Échappe le contenu XML.
     */
    private function escape(string $content): string
    {
        return htmlspecialchars($content, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
