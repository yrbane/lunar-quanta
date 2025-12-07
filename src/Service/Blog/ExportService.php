<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

use Lunar\Entity\Post;

/**
 * Service d'export des articles.
 *
 * Permet d'exporter les articles en différents formats (JSON, CSV, XML).
 *
 * @example
 * ```php
 * $exportService = new ExportService($postService);
 *
 * // Export JSON
 * $json = $exportService->toJson();
 *
 * // Export CSV
 * $csv = $exportService->toCsv();
 *
 * // Export sélectif
 * $json = $exportService->toJson(['status' => 'published']);
 * ```
 */
final class ExportService
{
    public function __construct(
        private readonly PostService $postService
    ) {
    }

    /**
     * Exporte les articles au format JSON.
     *
     * @param array<string, mixed> $filters Filtres optionnels (status, category, tag)
     * @param bool $pretty Formater le JSON pour la lisibilité
     * @return string Contenu JSON
     */
    public function toJson(array $filters = [], bool $pretty = true): string
    {
        $posts = $this->getFilteredPosts($filters);
        $data = array_map(fn(Post $post) => $this->formatPost($post), $posts);

        $flags = JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode([
            'exported_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'count' => count($data),
            'posts' => $data,
        ], $flags);
    }

    /**
     * Exporte les articles au format CSV.
     *
     * @param array<string, mixed> $filters Filtres optionnels
     * @param string $delimiter Délimiteur de colonnes
     * @return string Contenu CSV
     */
    public function toCsv(array $filters = [], string $delimiter = ','): string
    {
        $posts = $this->getFilteredPosts($filters);

        if (empty($posts)) {
            return '';
        }

        $headers = [
            'id', 'title', 'slug', 'author', 'status', 'category_id',
            'tags', 'excerpt', 'featured', 'created_at', 'published_at', 'url'
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers, $delimiter);

        foreach ($posts as $post) {
            fputcsv($output, [
                $post->getId(),
                $post->getTitle(),
                $post->getSlug(),
                $post->getAuthor(),
                $post->getStatus()->value,
                $post->getCategoryId() ?? '',
                implode(';', $post->getTags()),
                $post->getExcerpt(),
                $post->isFeatured() ? '1' : '0',
                $post->getCreatedAt()->format('Y-m-d H:i:s'),
                $post->getPublishedAt()?->format('Y-m-d H:i:s') ?? '',
                $post->getUrl(),
            ], $delimiter);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Exporte les articles au format XML.
     *
     * @param array<string, mixed> $filters Filtres optionnels
     * @return string Contenu XML
     */
    public function toXml(array $filters = []): string
    {
        $posts = $this->getFilteredPosts($filters);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><export/>');
        $xml->addAttribute('exported_at', (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));
        $xml->addChild('count', (string) count($posts));

        $postsNode = $xml->addChild('posts');

        foreach ($posts as $post) {
            $postNode = $postsNode->addChild('post');
            $postNode->addChild('id', $post->getId());
            $postNode->addChild('title', htmlspecialchars($post->getTitle()));
            $postNode->addChild('slug', $post->getSlug());
            $postNode->addChild('author', htmlspecialchars($post->getAuthor()));
            $postNode->addChild('status', $post->getStatus()->value);
            $postNode->addChild('category_id', $post->getCategoryId() ?? '');
            $postNode->addChild('excerpt', htmlspecialchars($post->getExcerpt()));
            $postNode->addChild('featured', $post->isFeatured() ? 'true' : 'false');
            $postNode->addChild('created_at', $post->getCreatedAt()->format(\DateTimeInterface::ATOM));
            $postNode->addChild('published_at', $post->getPublishedAt()?->format(\DateTimeInterface::ATOM) ?? '');
            $postNode->addChild('url', $post->getUrl());

            $tagsNode = $postNode->addChild('tags');
            foreach ($post->getTags() as $tag) {
                $tagsNode->addChild('tag', htmlspecialchars($tag));
            }

            // Contenu (CDATA pour préserver le formatage)
            $contentNode = $postNode->addChild('content');
            $dom = dom_import_simplexml($contentNode);
            $dom->appendChild($dom->ownerDocument->createCDATASection($post->getContent()));
        }

        // Formater le XML
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    /**
     * Exporte les articles au format Markdown (un fichier par article).
     *
     * @param array<string, mixed> $filters Filtres optionnels
     * @return array<string, string> [filename => content]
     */
    public function toMarkdown(array $filters = []): array
    {
        $posts = $this->getFilteredPosts($filters);
        $files = [];

        foreach ($posts as $post) {
            $filename = $post->getSlug() . '.md';

            $frontmatter = [
                'title' => $post->getTitle(),
                'author' => $post->getAuthor(),
                'date' => $post->getCreatedAt()->format('Y-m-d'),
                'status' => $post->getStatus()->value,
                'tags' => $post->getTags(),
                'excerpt' => $post->getExcerpt(),
            ];

            if ($post->getCategoryId()) {
                $frontmatter['category'] = $post->getCategoryId();
            }

            if ($post->getFeaturedImage()) {
                $frontmatter['image'] = $post->getFeaturedImage();
            }

            $content = "---\n";
            foreach ($frontmatter as $key => $value) {
                if (is_array($value)) {
                    $content .= "$key:\n";
                    foreach ($value as $item) {
                        $content .= "  - $item\n";
                    }
                } else {
                    $content .= "$key: \"$value\"\n";
                }
            }
            $content .= "---\n\n";
            $content .= $post->getContent();

            $files[$filename] = $content;
        }

        return $files;
    }

    /**
     * Crée une archive ZIP des exports Markdown.
     *
     * @param array<string, mixed> $filters Filtres optionnels
     * @return string Chemin vers le fichier ZIP temporaire
     */
    public function toMarkdownZip(array $filters = []): string
    {
        $files = $this->toMarkdown($filters);

        $zipPath = sys_get_temp_dir() . '/blog-export-' . date('Ymd-His') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Cannot create ZIP archive');
        }

        foreach ($files as $filename => $content) {
            $zip->addFromString($filename, $content);
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Récupère les articles filtrés.
     *
     * @param array<string, mixed> $filters
     * @return Post[]
     */
    private function getFilteredPosts(array $filters): array
    {
        $posts = $this->postService->all();

        // Filtre par statut
        if (isset($filters['status'])) {
            $status = $filters['status'];
            $posts = array_filter($posts, fn(Post $p) => $p->getStatus()->value === $status);
        }

        // Filtre par catégorie
        if (isset($filters['category'])) {
            $categoryId = $filters['category'];
            $posts = array_filter($posts, fn(Post $p) => $p->getCategoryId() === $categoryId);
        }

        // Filtre par tag
        if (isset($filters['tag'])) {
            $tag = $filters['tag'];
            $posts = array_filter($posts, fn(Post $p) => in_array($tag, $p->getTags(), true));
        }

        // Filtre par date (depuis)
        if (isset($filters['from'])) {
            $from = new \DateTimeImmutable($filters['from']);
            $posts = array_filter($posts, fn(Post $p) => $p->getCreatedAt() >= $from);
        }

        // Filtre par date (jusqu'à)
        if (isset($filters['to'])) {
            $to = new \DateTimeImmutable($filters['to']);
            $posts = array_filter($posts, fn(Post $p) => $p->getCreatedAt() <= $to);
        }

        // Trier par date décroissante
        usort($posts, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        return array_values($posts);
    }

    /**
     * Formate un article pour l'export.
     *
     * @return array<string, mixed>
     */
    private function formatPost(Post $post): array
    {
        return [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'slug' => $post->getSlug(),
            'author' => $post->getAuthor(),
            'excerpt' => $post->getExcerpt(),
            'content' => $post->getContent(),
            'status' => $post->getStatus()->value,
            'category_id' => $post->getCategoryId(),
            'tags' => $post->getTags(),
            'featured' => $post->isFeatured(),
            'featured_image' => $post->getFeaturedImage(),
            'ratings' => $post->getRatings(),
            'average_rating' => $post->getAverageRating(),
            'word_count' => $post->getWordCount(),
            'reading_time' => $post->getReadingTime(),
            'url' => $post->getUrl(),
            'created_at' => $post->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $post->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'published_at' => $post->getPublishedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
