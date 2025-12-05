<?php

declare(strict_types=1);

namespace Tests\Service\StaticSite;

use Lunar\Service\Blog\PostService;
use Lunar\Service\StaticSite\RssGenerator;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour RssGenerator.
 */
final class RssGeneratorTest extends TestCase
{
    private string $storagePath;
    private PostService $postService;
    private RssGenerator $generator;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_rss_test_' . uniqid();
        mkdir($this->storagePath, 0755, true);

        $this->postService = new PostService(
            new FileStorage($this->storagePath)
        );

        $this->generator = new RssGenerator(
            $this->postService,
            'https://example.com',
            'Mon Blog',
            'Un super blog'
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testGenerateReturnsValidXml(): void
    {
        $xml = $this->generator->generate();

        $this->assertStringStartsWith('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('<rss version="2.0"', $xml);
    }

    public function testGenerateContainsChannelInfo(): void
    {
        $xml = $this->generator->generate();

        $this->assertStringContainsString('<title>Mon Blog</title>', $xml);
        $this->assertStringContainsString('<description>Un super blog</description>', $xml);
        $this->assertStringContainsString('<link>https://example.com/blog/</link>', $xml);
    }

    public function testGenerateContainsAtomLink(): void
    {
        $xml = $this->generator->generate();

        $this->assertStringContainsString('atom:link', $xml);
        $this->assertStringContainsString('rel="self"', $xml);
    }

    public function testGenerateListsPublishedPosts(): void
    {
        $post = $this->postService->create('Article Test', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('<item>', $xml);
        $this->assertStringContainsString('<title>Article Test</title>', $xml);
    }

    public function testGenerateExcludesDrafts(): void
    {
        $this->postService->create('Brouillon', 'Contenu'); // Non publié

        $xml = $this->generator->generate();

        $this->assertStringNotContainsString('Brouillon', $xml);
    }

    public function testGenerateIncludesPostUrl(): void
    {
        $post = $this->postService->create('Mon Article', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('https://example.com/blog/posts/mon-article.html', $xml);
    }

    public function testGenerateIncludesExcerpt(): void
    {
        $post = $this->postService->create('Test', 'Contenu');
        $post->setExcerpt('Résumé de l\'article');
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('<description>Résumé de l&apos;article</description>', $xml);
    }

    public function testGenerateIncludesAuthor(): void
    {
        $post = $this->postService->create('Test', 'Contenu');
        $post->setAuthor('John Doe');
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('<author>John Doe</author>', $xml);
    }

    public function testGenerateIncludesPubDate(): void
    {
        $post = $this->postService->create('Test', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('<pubDate>', $xml);
    }

    public function testGenerateIncludesGuid(): void
    {
        $post = $this->postService->create('Test', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('<guid>', $xml);
    }

    public function testGenerateEscapesSpecialCharacters(): void
    {
        $post = $this->postService->create('Test <script>', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('&lt;script&gt;', $xml);
        $this->assertStringNotContainsString('<script>', $xml);
    }

    public function testGenerateLimitsItems(): void
    {
        // Créer 25 articles
        for ($i = 1; $i <= 25; $i++) {
            $post = $this->postService->create("Article $i", 'Contenu');
            $this->postService->publish($post->getId());
        }

        $xml = $this->generator->generate();

        // Max 20 items
        $this->assertSame(20, substr_count($xml, '<item>'));
    }

    public function testXmlIsWellFormed(): void
    {
        $post = $this->postService->create('Test', 'Contenu avec <balise> & caractères spéciaux');
        $post->setExcerpt('Extrait "avec" guillemets');
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        // Doit être parsable
        $doc = new \DOMDocument();
        $result = @$doc->loadXML($xml);

        $this->assertTrue($result, 'Le XML doit être bien formé');
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
