<?php

declare(strict_types=1);

namespace Lunar\Service\StaticSite;

use Lunar\Entity\Post;
use Lunar\Service\Blog\PostService;

/**
 * Générateur de flux RSS pour le blog.
 *
 * Produit un fichier RSS 2.0 valide avec les derniers articles publiés.
 *
 * @example
 * ```php
 * $generator = new RssGenerator(
 *     $postService,
 *     'https://example.com',
 *     'Mon Blog',
 *     'Description du blog'
 * );
 *
 * $rss = $generator->generate();
 * file_put_contents('public/blog/feed.xml', $rss);
 * ```
 */
final class RssGenerator
{
    private const MAX_ITEMS = 20;

    public function __construct(
        private readonly PostService $postService,
        private readonly string $siteUrl,
        private readonly string $title,
        private readonly string $description,
        private readonly string $language = 'fr-FR'
    ) {
    }

    /**
     * Génère le contenu XML du flux RSS.
     */
    public function generate(): string
    {
        $posts = $this->postService->findRecent(self::MAX_ITEMS);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<channel>' . "\n";

        // Métadonnées du canal
        $xml .= $this->tag('title', $this->title);
        $xml .= $this->tag('link', $this->siteUrl . '/blog/');
        $xml .= $this->tag('description', $this->description);
        $xml .= $this->tag('language', $this->language);
        $xml .= $this->tag('lastBuildDate', $this->formatDate(new \DateTimeImmutable()));
        $xml .= '<atom:link href="' . $this->escape($this->siteUrl . '/blog/feed.xml') . '" rel="self" type="application/rss+xml"/>' . "\n";

        // Articles
        foreach ($posts as $post) {
            $xml .= $this->generateItem($post);
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Génère un item RSS pour un article.
     */
    private function generateItem(Post $post): string
    {
        $url = $this->siteUrl . $post->getUrl();
        $pubDate = $post->getPublishedAt() ?? $post->getCreatedAt();

        $item = '<item>' . "\n";
        $item .= $this->tag('title', $post->getTitle());
        $item .= $this->tag('link', $url);
        $item .= $this->tag('guid', $url);
        $item .= $this->tag('pubDate', $this->formatDate($pubDate));

        if ($post->getExcerpt()) {
            $item .= $this->tag('description', $post->getExcerpt());
        }

        if ($post->getAuthor()) {
            $item .= $this->tag('author', $post->getAuthor());
        }

        $item .= '</item>' . "\n";

        return $item;
    }

    /**
     * Crée une balise XML avec échappement.
     */
    private function tag(string $name, string $content): string
    {
        return '<' . $name . '>' . $this->escape($content) . '</' . $name . '>' . "\n";
    }

    /**
     * Échappe le contenu XML.
     */
    private function escape(string $content): string
    {
        return htmlspecialchars($content, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Formate une date au format RFC 2822 (requis par RSS).
     */
    private function formatDate(\DateTimeInterface $date): string
    {
        return $date->format(\DateTimeInterface::RFC2822);
    }
}
