<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\StaticSite\StaticGenerator;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration du site statique complet.
 *
 * Vérifie que la génération statique fonctionne de bout en bout :
 * - Génération des articles
 * - Génération de l'index
 * - Génération des pages de tags
 * - Génération des pages de catégories
 * - RSS et Sitemap
 */
final class StaticSiteIntegrationTest extends TestCase
{
    private string $basePath;
    private string $postsPath;
    private string $categoriesPath;
    private string $outputPath;
    private string $templatePath;
    private PostService $postService;
    private CategoryService $categoryService;
    private StaticGenerator $generator;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/lunar_static_integration_' . uniqid();
        $this->postsPath = $this->basePath . '/data/posts';
        $this->categoriesPath = $this->basePath . '/data/categories';
        $this->outputPath = $this->basePath . '/public/blog';
        $this->templatePath = $this->basePath . '/templates';

        mkdir($this->postsPath, 0755, true);
        mkdir($this->categoriesPath, 0755, true);
        mkdir($this->outputPath, 0755, true);
        mkdir($this->templatePath, 0755, true);

        $this->createTemplates();

        $this->postService = new PostService(
            new FileStorage($this->postsPath)
        );

        $this->categoryService = new CategoryService(
            new FileStorage($this->categoriesPath)
        );

        $this->generator = new StaticGenerator(
            $this->postService,
            new MarkdownParser(),
            $this->outputPath,
            $this->templatePath,
            'https://example.com'
        );

        $this->generator->setCategoryService($this->categoryService);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    // =========================================================================
    // COMPLETE WORKFLOW
    // =========================================================================

    public function testCompleteStaticSiteGeneration(): void
    {
        // 1. Créer des catégories
        $phpCategory = $this->categoryService->create('PHP');
        $phpCategory->setDescription('Articles PHP');
        $this->categoryService->update($phpCategory);

        $jsCategory = $this->categoryService->create('JavaScript');
        $this->categoryService->update($jsCategory);

        // 2. Créer des articles
        $post1 = $this->postService->create('Introduction à PHP', '# PHP\n\nPHP est un langage.');
        $post1->setCategoryId($phpCategory->getId());
        $post1->addTag('php');
        $post1->addTag('web');
        $this->postService->update($post1);
        $this->postService->publish($post1->getId());

        $post2 = $this->postService->create('Laravel Framework', '# Laravel\n\nLaravel est un framework.');
        $post2->setCategoryId($phpCategory->getId());
        $post2->addTag('laravel');
        $post2->addTag('php');
        $this->postService->update($post2);
        $this->postService->publish($post2->getId());

        $post3 = $this->postService->create('JavaScript Basics', '# JS\n\nJavaScript est un langage.');
        $post3->setCategoryId($jsCategory->getId());
        $post3->addTag('javascript');
        $post3->addTag('web');
        $this->postService->update($post3);
        $this->postService->publish($post3->getId());

        // 3. Générer le site statique
        $result = $this->generator->generateAll();

        // 4. Vérifier les résultats
        $this->assertSame(3, $result['posts']);
        $this->assertTrue($result['index']);
        $this->assertTrue($result['rss']);
        $this->assertTrue($result['sitemap']);
        $this->assertSame(4, $result['tags']); // php, web, laravel, javascript
        $this->assertSame(2, $result['categories']); // PHP, JavaScript

        // 5. Vérifier les fichiers générés
        $this->assertFileExists($this->outputPath . '/index.html');
        $this->assertFileExists($this->outputPath . '/posts/introduction-a-php.html');
        $this->assertFileExists($this->outputPath . '/posts/laravel-framework.html');
        $this->assertFileExists($this->outputPath . '/posts/javascript-basics.html');
        $this->assertFileExists($this->outputPath . '/tags/php.html');
        $this->assertFileExists($this->outputPath . '/tags/web.html');
        $this->assertFileExists($this->outputPath . '/categories/php.html');
        $this->assertFileExists($this->outputPath . '/categories/javascript.html');
        $this->assertFileExists($this->outputPath . '/feed.xml');
        $this->assertFileExists($this->basePath . '/public/sitemap.xml');
    }

    public function testIndexContainsAllPublishedPosts(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $post = $this->postService->create("Article $i", "Contenu $i");
            $this->postService->publish($post->getId());
        }

        $this->generator->generateIndex();

        $html = file_get_contents($this->outputPath . '/index.html');

        for ($i = 1; $i <= 5; $i++) {
            $this->assertStringContainsString("Article $i", $html);
        }
    }

    public function testCategoryPageContainsOnlyCategoryPosts(): void
    {
        $cat1 = $this->categoryService->create('Cat1');
        $cat2 = $this->categoryService->create('Cat2');

        $post1 = $this->postService->create('Post in Cat1', 'Content');
        $post1->setCategoryId($cat1->getId());
        $this->postService->update($post1);
        $this->postService->publish($post1->getId());

        $post2 = $this->postService->create('Post in Cat2', 'Content');
        $post2->setCategoryId($cat2->getId());
        $this->postService->update($post2);
        $this->postService->publish($post2->getId());

        $this->generator->generateCategoryPages();

        $cat1Html = file_get_contents($this->outputPath . '/categories/cat1.html');
        $this->assertStringContainsString('Post in Cat1', $cat1Html);
        $this->assertStringNotContainsString('Post in Cat2', $cat1Html);

        $cat2Html = file_get_contents($this->outputPath . '/categories/cat2.html');
        $this->assertStringContainsString('Post in Cat2', $cat2Html);
        $this->assertStringNotContainsString('Post in Cat1', $cat2Html);
    }

    public function testTagPageContainsOnlyTaggedPosts(): void
    {
        $post1 = $this->postService->create('PHP Article', 'Content');
        $post1->addTag('php');
        $this->postService->update($post1);
        $this->postService->publish($post1->getId());

        $post2 = $this->postService->create('JS Article', 'Content');
        $post2->addTag('javascript');
        $this->postService->update($post2);
        $this->postService->publish($post2->getId());

        $this->generator->generateTagPages();

        $phpHtml = file_get_contents($this->outputPath . '/tags/php.html');
        $this->assertStringContainsString('PHP Article', $phpHtml);
        $this->assertStringNotContainsString('JS Article', $phpHtml);
    }

    public function testRssFeedIsValidXml(): void
    {
        $post = $this->postService->create('Test', 'Content');
        $this->postService->publish($post->getId());

        $this->generator->generateRss();

        $rss = file_get_contents($this->outputPath . '/feed.xml');

        $doc = new \DOMDocument();
        $this->assertTrue(@$doc->loadXML($rss), 'RSS should be valid XML');
        $this->assertStringContainsString('<rss', $rss);
    }

    public function testSitemapIsValidXml(): void
    {
        $post = $this->postService->create('Test', 'Content');
        $this->postService->publish($post->getId());

        $this->generator->generateSitemap();

        $sitemap = file_get_contents($this->basePath . '/public/sitemap.xml');

        $doc = new \DOMDocument();
        $this->assertTrue(@$doc->loadXML($sitemap), 'Sitemap should be valid XML');
        $this->assertStringContainsString('<urlset', $sitemap);
    }

    public function testRegenerateRemovesOldFiles(): void
    {
        // Créer un article et générer
        $post = $this->postService->create('Old Post', 'Content');
        $this->postService->publish($post->getId());
        $this->generator->generateAll();

        $this->assertFileExists($this->outputPath . '/posts/old-post.html');

        // Supprimer l'article et régénérer
        $this->postService->delete($post->getId());
        $this->generator->regenerate();

        // L'ancien fichier ne devrait plus exister
        $this->assertFileDoesNotExist($this->outputPath . '/posts/old-post.html');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function createTemplates(): void
    {
        $postTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>{{ title }}</title></head>
<body>
    <h1>{{ title }}</h1>
    {% if author %}<p>By {{ author }}</p>{% endif %}
    <article>{{ content }}</article>
</body>
</html>
HTML;

        $indexTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Blog</title></head>
<body>
    <h1>Articles</h1>
    {% if posts|length > 0 %}
    <ul>
    {% for post in posts %}
        <li><a href="{{ post.url }}">{{ post.title }}</a></li>
    {% endfor %}
    </ul>
    {% else %}
    <p>No posts</p>
    {% endif %}
</body>
</html>
HTML;

        $tagTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>{{ tag }}</title></head>
<body>
    <h1>{{ tag }}</h1>
    {% if posts|length > 0 %}
    <ul>
    {% for post in posts %}
        <li>{{ post.title }}</li>
    {% endfor %}
    </ul>
    {% endif %}
</body>
</html>
HTML;

        $categoryTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>{{ category_name }}</title></head>
<body>
    <h1>{{ category_name }}</h1>
    <p>{{ category_description }}</p>
    {% if posts|length > 0 %}
    <ul>
    {% for post in posts %}
        <li>{{ post.title }}</li>
    {% endfor %}
    </ul>
    {% endif %}
</body>
</html>
HTML;

        file_put_contents($this->templatePath . '/post.html', $postTemplate);
        file_put_contents($this->templatePath . '/index.html', $indexTemplate);
        file_put_contents($this->templatePath . '/tag.html', $tagTemplate);
        file_put_contents($this->templatePath . '/category.html', $categoryTemplate);
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
