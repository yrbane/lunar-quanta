<?php

declare(strict_types=1);

namespace Tests\Service\StaticSite;

use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\StaticSite\EnhancedStaticGenerator;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour EnhancedStaticGenerator.
 *
 * Le générateur amélioré intègre tous les services de contenu
 * pour une expérience blog optimale.
 */
final class EnhancedStaticGeneratorTest extends TestCase
{
    private string $storagePath;
    private string $outputPath;
    private string $templatePath;
    private EnhancedStaticGenerator $generator;
    private PostService $postService;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/enhanced-static-test-' . uniqid();
        $this->outputPath = $this->storagePath . '/output';
        $this->templatePath = dirname(__DIR__, 3) . '/template/blog';

        mkdir($this->storagePath, 0755, true);
        mkdir($this->outputPath, 0755, true);

        $this->postService = new PostService(
            new FileStorage($this->storagePath . '/posts')
        );

        $this->generator = new EnhancedStaticGenerator(
            $this->postService,
            new MarkdownParser(),
            $this->outputPath,
            $this->templatePath,
            'https://example.com'
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
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

    public function testGeneratePostCreatesHtmlFile(): void
    {
        $post = $this->postService->create('Test Article', '# Hello World');
        $this->postService->publish($post->getId());

        $this->generator->generatePost($post);

        $this->assertFileExists($this->outputPath . '/posts/test-article.html');
    }

    public function testGeneratedPostContainsTitle(): void
    {
        $post = $this->postService->create('Mon Super Article', 'Contenu');
        $this->postService->publish($post->getId());

        $this->generator->generatePost($post);

        $content = file_get_contents($this->outputPath . '/posts/mon-super-article.html');
        $this->assertStringContainsString('Mon Super Article', $content);
    }

    public function testGeneratedPostContainsSchemaOrg(): void
    {
        $post = $this->postService->create('SEO Article', 'Content with **bold**');
        $this->postService->publish($post->getId());

        $this->generator->generatePost($post);

        $content = file_get_contents($this->outputPath . '/posts/seo-article.html');
        $this->assertStringContainsString('application/ld+json', $content);
    }

    public function testGeneratedPostContainsMetaTags(): void
    {
        $post = $this->postService->create('Meta Article', 'Content');
        $post->setExcerpt('This is the description');
        $post->setAuthor('Test Author');
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $this->generator->generatePost($post);

        $content = file_get_contents($this->outputPath . '/posts/meta-article.html');
        $this->assertStringContainsString('This is the description', $content);
    }

    public function testSetEnableMinificationReturnsInstance(): void
    {
        $result = $this->generator->setEnableMinification(false);

        $this->assertSame($this->generator, $result);
    }

    public function testSetEnableLazyLoadingReturnsInstance(): void
    {
        $result = $this->generator->setEnableLazyLoading(false);

        $this->assertSame($this->generator, $result);
    }

    public function testSetEnableDarkModeReturnsInstance(): void
    {
        $result = $this->generator->setEnableDarkMode(false);

        $this->assertSame($this->generator, $result);
    }

    public function testGenerateAllCreatesAssetFiles(): void
    {
        $post = $this->postService->create('Asset Test', 'Content');
        $this->postService->publish($post->getId());

        $this->generator->generateAll();

        $this->assertFileExists($this->outputPath . '/assets/enhanced.css');
        $this->assertFileExists($this->outputPath . '/assets/enhanced.js');
        $this->assertFileExists($this->outputPath . '/assets/print.css');
    }

    public function testGenerateIndexCreatesFile(): void
    {
        $post = $this->postService->create('Index Test', 'Content');
        $this->postService->publish($post->getId());

        $this->generator->generateIndex();

        $this->assertFileExists($this->outputPath . '/index.html');
    }

    public function testCleanRemovesGeneratedFiles(): void
    {
        $post = $this->postService->create('Clean Test', 'Content');
        $this->postService->publish($post->getId());

        $this->generator->generatePost($post);
        $this->assertFileExists($this->outputPath . '/posts/clean-test.html');

        $this->generator->clean();
        $this->assertFileDoesNotExist($this->outputPath . '/posts/clean-test.html');
    }

    public function testPublishCallbackIsTriggered(): void
    {
        $called = false;
        $this->generator->onPublish(function ($p) use (&$called) {
            $called = true;
        });

        $post = $this->postService->create('Callback Test', 'Content');
        $this->postService->publish($post->getId());
        $this->generator->generatePost($post);

        $this->assertTrue($called);
    }

    public function testProgressCallbackIsTriggered(): void
    {
        $called = false;
        $this->generator->onProgress(function ($current, $total, $type, $item) use (&$called) {
            $called = true;
            $this->assertIsInt($current);
            $this->assertIsInt($total);
            $this->assertIsString($type);
        });

        $post = $this->postService->create('Progress Test', 'Content');
        $this->postService->publish($post->getId());
        $this->generator->generateAll();

        $this->assertTrue($called);
    }

    public function testRegenerateCleansThenGenerates(): void
    {
        $post1 = $this->postService->create('Old Post', 'Content');
        $this->postService->publish($post1->getId());
        $this->generator->generatePost($post1);

        $this->postService->delete($post1->getId());

        $post2 = $this->postService->create('New Post', 'Content');
        $this->postService->publish($post2->getId());

        $this->generator->regenerate();

        $this->assertFileDoesNotExist($this->outputPath . '/posts/old-post.html');
        $this->assertFileExists($this->outputPath . '/posts/new-post.html');
    }

    public function testGenerateRssCreatesFile(): void
    {
        $post = $this->postService->create('RSS Test', 'Content');
        $this->postService->publish($post->getId());

        $result = $this->generator->generateRss();

        $this->assertTrue($result);
        $this->assertFileExists($this->outputPath . '/feed.xml');
    }

    public function testWithMinificationDisabled(): void
    {
        $post = $this->postService->create('No Minify Test', 'Content');
        $this->postService->publish($post->getId());

        $this->generator->setEnableMinification(false);
        $this->generator->generatePost($post);

        $content = file_get_contents($this->outputPath . '/posts/no-minify-test.html');
        // With minification disabled, there should be more whitespace
        $this->assertStringContainsString("\n", $content);
    }

    public function testGenerateAllReturnsStats(): void
    {
        $post = $this->postService->create('Stats Test', 'Content');
        $post->addTag('test');
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $result = $this->generator->generateAll();

        $this->assertArrayHasKey('posts', $result);
        $this->assertArrayHasKey('index', $result);
        $this->assertArrayHasKey('rss', $result);
        $this->assertArrayHasKey('sitemap', $result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertArrayHasKey('categories', $result);
    }

    public function testCodeHighlightingIsApplied(): void
    {
        $content = <<<'MD'
# Code Example

```php
echo "Hello World";
```
MD;

        $post = $this->postService->create('Code Test', $content);
        $this->postService->publish($post->getId());
        $this->generator->generatePost($post);

        $html = file_get_contents($this->outputPath . '/posts/code-test.html');
        // Code should be wrapped in pre/code tags with highlighting
        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('<code', $html);
    }

    public function testSetCategoryServiceEnablesCategoryPages(): void
    {
        $categoryStorage = new FileStorage($this->storagePath . '/categories');
        $categoryService = new CategoryService($categoryStorage);

        $category = $categoryService->create('PHP');
        $category->setDescription('Articles PHP');
        $category->setColor('#8892BF');
        $categoryService->update($category);

        $this->generator->setCategoryService($categoryService);

        $post = $this->postService->create('Category Test', 'Content');
        $post->setCategoryId($category->getId());
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $result = $this->generator->generateAll();

        $this->assertGreaterThanOrEqual(1, $result['categories']);
        $this->assertFileExists($this->outputPath . '/categories/php.html');
    }

    public function testGeneratedIndexContainsSchemaOrg(): void
    {
        $post = $this->postService->create('Index Schema Test', 'Content');
        $this->postService->publish($post->getId());

        $this->generator->generateIndex();

        $content = file_get_contents($this->outputPath . '/index.html');
        $this->assertStringContainsString('application/ld+json', $content);
    }

    public function testGeneratedTagPageIncludesEnhancements(): void
    {
        $post = $this->postService->create('Tag Enhancement Test', 'Content');
        $post->addTag('enhanced');
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $this->generator->generateAll();

        $this->assertFileExists($this->outputPath . '/tags/enhanced.html');
        $content = file_get_contents($this->outputPath . '/tags/enhanced.html');
        $this->assertStringContainsString('Tag Enhancement Test', $content);
    }
}
