<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Entity\Post;
use Lunar\Entity\PostStatus;
use Lunar\Service\Content\SearchIndexService;
use PHPUnit\Framework\TestCase;

final class SearchIndexServiceTest extends TestCase
{
    private SearchIndexService $service;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->service = new SearchIndexService();
        $this->tempDir = sys_get_temp_dir() . '/search-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '/*') ?: []);
        @rmdir($this->tempDir);
    }

    private function createPost(string $id, string $title, string $content): Post
    {
        $post = new Post($title, $content);
        $reflection = new \ReflectionClass($post);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($post, $id);

        $post->publish();

        return $post;
    }

    public function testBuildIndexReturnsDocuments(): void
    {
        $posts = [
            $this->createPost('1', 'PHP Tutorial', 'Learn PHP programming'),
            $this->createPost('2', 'JavaScript Guide', 'Master JavaScript'),
        ];

        $index = $this->service->buildIndex($posts);

        $this->assertArrayHasKey('documents', $index);
        $this->assertArrayHasKey('metadata', $index);
        $this->assertCount(2, $index['documents']);
    }

    public function testBuildIndexExcludesUnpublished(): void
    {
        $published = $this->createPost('1', 'Published', 'Content');
        $draft = $this->createPost('2', 'Draft', 'Content');
        $draft->setStatus(PostStatus::DRAFT);

        $index = $this->service->buildIndex([$published, $draft]);

        $this->assertCount(1, $index['documents']);
    }

    public function testDocumentContainsRequiredFields(): void
    {
        $post = $this->createPost('1', 'Test Post', 'Test content');
        $post->addTag('php');

        $index = $this->service->buildIndex([$post]);
        $doc = $index['documents'][0];

        $this->assertArrayHasKey('id', $doc);
        $this->assertArrayHasKey('slug', $doc);
        $this->assertArrayHasKey('title', $doc);
        $this->assertArrayHasKey('url', $doc);
        $this->assertArrayHasKey('keywords', $doc);
    }

    public function testSetIncludeContentAddsExcerpt(): void
    {
        $post = $this->createPost('1', 'Test', 'This is the content');

        $this->service->setIncludeContent(true);
        $index = $this->service->buildIndex([$post]);

        $this->assertArrayHasKey('excerpt', $index['documents'][0]);
    }

    public function testSetIncludeContentFalseRemovesExcerpt(): void
    {
        $post = $this->createPost('1', 'Test', 'This is the content');

        $this->service->setIncludeContent(false);
        $index = $this->service->buildIndex([$post]);

        $this->assertArrayNotHasKey('excerpt', $index['documents'][0]);
    }

    public function testSetIncludeMetadataAddsFields(): void
    {
        $post = $this->createPost('1', 'Test', 'Content');
        $post->setAuthor('John Doe');

        $this->service->setIncludeMetadata(true);
        $index = $this->service->buildIndex([$post]);

        $this->assertArrayHasKey('author', $index['documents'][0]);
        $this->assertArrayHasKey('tags', $index['documents'][0]);
    }

    public function testExtractKeywordsReturnsWords(): void
    {
        $keywords = $this->service->extractKeywords('PHP programming tutorial for beginners');

        $this->assertContains('php', $keywords);
        $this->assertContains('programming', $keywords);
        $this->assertContains('tutorial', $keywords);
        $this->assertContains('beginners', $keywords);
    }

    public function testExtractKeywordsRemovesStopWords(): void
    {
        $keywords = $this->service->extractKeywords('The quick brown fox jumps over the lazy dog');

        $this->assertNotContains('the', $keywords);
        $this->assertContains('quick', $keywords);
        $this->assertContains('brown', $keywords);
    }

    public function testExtractKeywordsHandlesAccents(): void
    {
        $keywords = $this->service->extractKeywords('Développement PHP avancé');

        $this->assertContains('developpement', $keywords);
        $this->assertContains('avance', $keywords);
    }

    public function testSaveIndexCreatesFile(): void
    {
        $index = ['documents' => [], 'metadata' => []];
        $path = $this->tempDir . '/index.json';

        $result = $this->service->saveIndex($index, $path);

        $this->assertTrue($result);
        $this->assertFileExists($path);
    }

    public function testLoadIndexReturnsData(): void
    {
        $index = ['documents' => [['id' => '1']], 'metadata' => []];
        $path = $this->tempDir . '/index.json';
        file_put_contents($path, json_encode($index));

        $loaded = $this->service->loadIndex($path);

        $this->assertSame($index, $loaded);
    }

    public function testLoadIndexReturnsNullForMissingFile(): void
    {
        $loaded = $this->service->loadIndex('/nonexistent/file.json');

        $this->assertNull($loaded);
    }

    public function testMetadataContainsStats(): void
    {
        $posts = [
            $this->createPost('1', 'Post 1', 'Content'),
            $this->createPost('2', 'Post 2', 'Content'),
        ];

        $index = $this->service->buildIndex($posts);

        $this->assertArrayHasKey('total', $index['metadata']);
        $this->assertArrayHasKey('generated_at', $index['metadata']);
        $this->assertArrayHasKey('top_keywords', $index['metadata']);
        $this->assertSame(2, $index['metadata']['total']);
    }

    public function testGenerateSearchScriptReturnsJs(): void
    {
        $script = $this->service->generateSearchScript('/search.json');

        $this->assertStringContainsString('class BlogSearch', $script);
        $this->assertStringContainsString('search(query', $script);
        $this->assertStringContainsString('/search.json', $script);
    }

    public function testGenerateSearchCssReturnsCss(): void
    {
        $css = $this->service->generateSearchCss();

        $this->assertStringContainsString('.search-results', $css);
        $this->assertStringContainsString('.search-result', $css);
    }

    public function testSetExcerptLengthLimitsContent(): void
    {
        $longContent = str_repeat('word ', 100);
        $post = $this->createPost('1', 'Test', $longContent);

        $this->service->setExcerptLength(50);
        $index = $this->service->buildIndex([$post]);

        $this->assertLessThanOrEqual(50, strlen($index['documents'][0]['excerpt']));
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setIncludeContent(true)
            ->setExcerptLength(200)
            ->setIncludeMetadata(true)
            ->setStopWords(['test']);

        $this->assertSame($this->service, $result);
    }

    public function testStripMarkdownRemovesFormatting(): void
    {
        $post = $this->createPost('1', 'Test', '# Title

**Bold** and *italic*

```php
echo "code";
```

[Link](http://example.com)

![Image](image.jpg)');

        $index = $this->service->buildIndex([$post]);

        $excerpt = $index['documents'][0]['excerpt'];
        $this->assertStringNotContainsString('#', $excerpt);
        $this->assertStringNotContainsString('**', $excerpt);
        $this->assertStringNotContainsString('```', $excerpt);
        $this->assertStringNotContainsString('[Link]', $excerpt);
    }
}
