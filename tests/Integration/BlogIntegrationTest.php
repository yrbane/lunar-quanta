<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lunar\Entity\Post;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\HtmlSanitizer;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\StaticSite\RssGenerator;
use Lunar\Service\StaticSite\SitemapGenerator;
use Lunar\Service\StaticSite\StaticGenerator;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration du système de blog complet.
 *
 * Vérifie que tous les composants fonctionnent ensemble :
 * - Création et gestion des articles
 * - Génération du site statique
 * - RSS et Sitemap
 */
final class BlogIntegrationTest extends TestCase
{
    private string $basePath;
    private string $storagePath;
    private string $outputPath;
    private string $templatePath;
    private PostService $postService;
    private StaticGenerator $staticGenerator;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/lunar_blog_integration_' . uniqid();
        $this->storagePath = $this->basePath . '/data';
        $this->outputPath = $this->basePath . '/public/blog';
        $this->templatePath = $this->basePath . '/templates';

        mkdir($this->storagePath, 0755, true);
        mkdir($this->outputPath, 0755, true);
        mkdir($this->templatePath, 0755, true);

        $this->createTemplates();

        $this->postService = new PostService(
            new FileStorage($this->storagePath)
        );

        $this->staticGenerator = new StaticGenerator(
            $this->postService,
            new MarkdownParser(),
            $this->outputPath,
            $this->templatePath,
            'https://example.com'
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    // =========================================================================
    // WORKFLOW COMPLET
    // =========================================================================

    public function testCompletePublishWorkflow(): void
    {
        // 1. Créer un article
        $post = $this->postService->create(
            'Mon Premier Article',
            "# Introduction\n\nCeci est le contenu de l'article."
        );
        $post->setExcerpt('Description courte');
        $post->setAuthor('John Doe');
        $this->postService->update($post);

        $this->assertTrue($post->isDraft());
        $this->assertNull($post->getPublishedAt());

        // 2. Publier
        $published = $this->postService->publish($post->getId());

        $this->assertTrue($published->isPublished());
        $this->assertNotNull($published->getPublishedAt());

        // 3. Générer le site statique
        $result = $this->staticGenerator->generateAll();

        $this->assertSame(1, $result['posts']);
        $this->assertTrue($result['index']);
        $this->assertTrue($result['rss']);
        $this->assertTrue($result['sitemap']);

        // 4. Vérifier les fichiers générés
        $this->assertFileExists($this->outputPath . '/posts/mon-premier-article.html');
        $this->assertFileExists($this->outputPath . '/index.html');
        $this->assertFileExists($this->outputPath . '/feed.xml');
        $this->assertFileExists($this->basePath . '/public/sitemap.xml');

        // 5. Vérifier le contenu
        $postHtml = file_get_contents($this->outputPath . '/posts/mon-premier-article.html');
        $this->assertStringContainsString('Mon Premier Article', $postHtml);
        $this->assertStringContainsString('John Doe', $postHtml);
        $this->assertStringContainsString('<h1>Introduction</h1>', $postHtml);
    }

    public function testUnpublishRemovesFromGeneration(): void
    {
        // Créer et publier
        $post = $this->postService->create('Article à dépublier', 'Contenu');
        $this->postService->publish($post->getId());
        $this->staticGenerator->generateAll();

        $this->assertFileExists($this->outputPath . '/posts/article-a-depublier.html');

        // Dépublier et régénérer
        $this->postService->unpublish($post->getId());
        $this->staticGenerator->regenerate();

        // Le fichier ne devrait plus contenir l'article dans l'index
        $indexHtml = file_get_contents($this->outputPath . '/index.html');
        $this->assertStringNotContainsString('Article à dépublier', $indexHtml);
    }

    // =========================================================================
    // MARKDOWN INTEGRATION
    // =========================================================================

    public function testMarkdownIsProperlyParsed(): void
    {
        $content = <<<'MD'
# Titre Principal

Paragraphe avec **gras** et *italique*.

## Sous-titre

- Item 1
- Item 2

```php
echo "Hello World";
```

> Citation importante
MD;

        $post = $this->postService->create('Test Markdown', $content);
        $this->postService->publish($post->getId());
        $this->staticGenerator->generatePost($post);

        $html = file_get_contents($this->outputPath . '/posts/test-markdown.html');

        $this->assertStringContainsString('<h1>Titre Principal</h1>', $html);
        $this->assertStringContainsString('<strong>gras</strong>', $html);
        $this->assertStringContainsString('<em>italique</em>', $html);
        $this->assertStringContainsString('<h2>Sous-titre</h2>', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<pre><code', $html);
        $this->assertStringContainsString('<blockquote>', $html);
    }

    // =========================================================================
    // RSS INTEGRATION
    // =========================================================================

    public function testRssFeedContainsAllPublishedPosts(): void
    {
        // Créer plusieurs articles
        for ($i = 1; $i <= 5; $i++) {
            $post = $this->postService->create("Article $i", "Contenu $i");
            $post->setExcerpt("Résumé $i");
            $this->postService->update($post);
            $this->postService->publish($post->getId());
        }

        $this->staticGenerator->generateRss();

        $rss = file_get_contents($this->outputPath . '/feed.xml');

        for ($i = 1; $i <= 5; $i++) {
            $this->assertStringContainsString("<title>Article $i</title>", $rss);
        }

        // Vérifier la validité XML
        $doc = new \DOMDocument();
        $this->assertTrue(@$doc->loadXML($rss));
    }

    // =========================================================================
    // SITEMAP INTEGRATION
    // =========================================================================

    public function testSitemapContainsAllPublishedPosts(): void
    {
        $post1 = $this->postService->create('Article Un', 'Contenu');
        $post2 = $this->postService->create('Article Deux', 'Contenu');
        $this->postService->publish($post1->getId());
        $this->postService->publish($post2->getId());

        $this->staticGenerator->generateSitemap();

        $sitemap = file_get_contents($this->basePath . '/public/sitemap.xml');

        $this->assertStringContainsString('article-un.html', $sitemap);
        $this->assertStringContainsString('article-deux.html', $sitemap);
        $this->assertStringContainsString('<priority>', $sitemap);

        // Vérifier la validité XML
        $doc = new \DOMDocument();
        $this->assertTrue(@$doc->loadXML($sitemap));
    }

    // =========================================================================
    // SLUG UNIQUENESS
    // =========================================================================

    public function testSlugUniquenessAcrossMultiplePosts(): void
    {
        $post1 = $this->postService->create('Mon Article', 'Contenu 1');
        $post2 = $this->postService->create('Mon Article', 'Contenu 2');
        $post3 = $this->postService->create('Mon Article', 'Contenu 3');

        $this->assertSame('mon-article', $post1->getSlug());
        $this->assertSame('mon-article-1', $post2->getSlug());
        $this->assertSame('mon-article-2', $post3->getSlug());

        // Publier tous et générer
        $this->postService->publish($post1->getId());
        $this->postService->publish($post2->getId());
        $this->postService->publish($post3->getId());
        $this->staticGenerator->generateAll();

        $this->assertFileExists($this->outputPath . '/posts/mon-article.html');
        $this->assertFileExists($this->outputPath . '/posts/mon-article-1.html');
        $this->assertFileExists($this->outputPath . '/posts/mon-article-2.html');
    }

    // =========================================================================
    // SANITIZATION
    // =========================================================================

    public function testHtmlSanitizationInContent(): void
    {
        $sanitizer = new HtmlSanitizer();

        $maliciousContent = '<script>alert("XSS")</script><p>Safe content</p>';
        $safe = $sanitizer->sanitize($maliciousContent);

        $this->assertStringNotContainsString('<script>', $safe);
        $this->assertStringContainsString('<p>Safe content</p>', $safe);
    }

    // =========================================================================
    // FILTERING
    // =========================================================================

    public function testPostFiltering(): void
    {
        // Créer des articles avec différents états
        $draft1 = $this->postService->create('Draft 1', 'Content');
        $draft2 = $this->postService->create('Draft 2', 'Content');

        $published1 = $this->postService->create('Published 1', 'Content');
        $this->postService->publish($published1->getId());

        $published2 = $this->postService->create('Published 2', 'Content');
        $this->postService->publish($published2->getId());

        $archived = $this->postService->create('Archived', 'Content');
        $this->postService->archive($archived->getId());

        // Vérifier les filtres
        $this->assertCount(5, $this->postService->all());
        $this->assertCount(2, $this->postService->findPublished());
        $this->assertCount(2, $this->postService->findDrafts());
    }

    // =========================================================================
    // TAGS
    // =========================================================================

    public function testPostsWithTags(): void
    {
        $post1 = $this->postService->create('PHP Article', 'Content');
        $post1->addTag('php');
        $post1->addTag('programming');
        $this->postService->update($post1);

        $post2 = $this->postService->create('MySQL Article', 'Content');
        $post2->addTag('mysql');
        $post2->addTag('programming');
        $this->postService->update($post2);

        $phpPosts = $this->postService->findByTag('php');
        $programmingPosts = $this->postService->findByTag('programming');

        $this->assertCount(1, $phpPosts);
        $this->assertCount(2, $programmingPosts);
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

        file_put_contents($this->templatePath . '/post.html', $postTemplate);
        file_put_contents($this->templatePath . '/index.html', $indexTemplate);
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
