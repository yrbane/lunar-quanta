<?php

declare(strict_types=1);

namespace Lunar\Service\StaticSite;

use Lunar\Entity\Post;
use Lunar\Service\Blog\PostService;

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
    public function __construct(
        private readonly PostService $postService,
        private readonly string $siteUrl
    ) {
    }

    /**
     * Génère le contenu XML du sitemap.
     */
    public function generate(): string
    {
        $posts = $this->postService->findPublished();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Page d'accueil du blog
        $xml .= $this->generateUrl(
            $this->siteUrl . '/blog/',
            new \DateTimeImmutable(),
            'daily',
            '1.0'
        );

        // Articles
        foreach ($posts as $post) {
            $xml .= $this->generatePostUrl($post);
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Génère une entrée URL pour un article.
     */
    private function generatePostUrl(Post $post): string
    {
        $lastmod = $post->getUpdatedAt() ?? $post->getPublishedAt() ?? $post->getCreatedAt();

        return $this->generateUrl(
            $this->siteUrl . $post->getUrl(),
            $lastmod,
            'monthly',
            '0.8'
        );
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
