<?php

declare(strict_types=1);

namespace Tests\Service\StaticSite;

use Lunar\Service\Blog\PostService;
use Lunar\Service\StaticSite\SitemapGenerator;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour SitemapGenerator.
 */
final class SitemapGeneratorTest extends TestCase
{
    private string $storagePath;
    private PostService $postService;
    private SitemapGenerator $generator;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_sitemap_test_' . uniqid();
        mkdir($this->storagePath, 0755, true);

        $this->postService = new PostService(
            new FileStorage($this->storagePath)
        );

        $this->generator = new SitemapGenerator(
            $this->postService,
            'https://example.com'
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
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
    }

    public function testGenerateContainsBlogIndex(): void
    {
        $xml = $this->generator->generate();

        $this->assertStringContainsString('<loc>https://example.com/blog/</loc>', $xml);
        $this->assertStringContainsString('<priority>1.0</priority>', $xml);
    }

    public function testGenerateListsPublishedPosts(): void
    {
        $post = $this->postService->create('Article Test', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString($post->getUrl(), $xml);
    }

    public function testGenerateExcludesDrafts(): void
    {
        $this->postService->create('Brouillon', 'Contenu'); // Non publié

        $xml = $this->generator->generate();

        $this->assertStringNotContainsString('brouillon', $xml);
    }

    public function testGenerateIncludesLastmod(): void
    {
        $post = $this->postService->create('Test', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('<lastmod>', $xml);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $xml);
    }

    public function testGenerateIncludesChangefreq(): void
    {
        $post = $this->postService->create('Test', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('<changefreq>monthly</changefreq>', $xml);
    }

    public function testGenerateIncludesPriority(): void
    {
        $post = $this->postService->create('Test', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('<priority>0.8</priority>', $xml);
    }

    public function testXmlIsWellFormed(): void
    {
        $post = $this->postService->create('Test', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $doc = new \DOMDocument();
        $result = @$doc->loadXML($xml);

        $this->assertTrue($result, 'Le XML doit être bien formé');
    }

    public function testGenerateEscapesUrls(): void
    {
        $post = $this->postService->create('Test & Essai', 'Contenu');
        $this->postService->publish($post->getId());

        $xml = $this->generator->generate();

        $this->assertStringContainsString('test-essai', $xml);
        // L'URL ne devrait pas contenir de caractères non échappés
        $doc = new \DOMDocument();
        $this->assertTrue(@$doc->loadXML($xml));
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
