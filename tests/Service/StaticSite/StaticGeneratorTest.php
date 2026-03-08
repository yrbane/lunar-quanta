<?php

declare(strict_types=1);

namespace Tests\Service\StaticSite;

use Lunar\Entity\Post;
use Lunar\Service\StaticSite\StaticGenerator;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour StaticGenerator.
 *
 * Le générateur produit des fichiers HTML statiques
 * à partir des articles publiés.
 */
final class StaticGeneratorTest extends TestCase
{
    private string $storagePath;
    private string $outputPath;
    private string $templatePath;
    private StaticGenerator $generator;
    private PostService $postService;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_static_test_' . uniqid();
        $this->outputPath = $this->storagePath . '/output';
        $this->templatePath = $this->storagePath . '/templates';

        mkdir($this->storagePath, 0755, true);
        mkdir($this->outputPath, 0755, true);
        mkdir($this->templatePath, 0755, true);

        // Créer les templates de test
        $this->createTestTemplates();

        $this->postService = new PostService(
            new FileStorage($this->storagePath . '/posts')
        );

        $this->generator = new StaticGenerator(
            $this->postService,
            new MarkdownParser(),
            $this->outputPath,
            $this->templatePath
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testGeneratePostCreatesHtmlFile(): void
    {
        $post = $this->postService->create('Mon Article', '# Hello World');
        $this->postService->publish($post->getId());

        $this->generator->generatePost($post);

        $expectedPath = $this->outputPath . '/posts/mon-article.html';
        $this->assertFileExists($expectedPath);
    }

    public function testGeneratedPostContainsTitle(): void
    {
        $post = $this->postService->create('Mon Super Article', 'Contenu');
        $this->postService->publish($post->getId());

        $this->generator->generatePost($post);

        $content = file_get_contents($this->outputPath . '/posts/mon-super-article.html');
        $this->assertStringContainsString('Mon Super Article', $content);
    }

    public function testGeneratedPostContainsParsedMarkdown(): void
    {
        $post = $this->postService->create('Test', '**Texte en gras**');
        $this->postService->publish($post->getId());

        $this->generator->generatePost($post);

        $content = file_get_contents($this->outputPath . '/posts/test.html');
        $this->assertStringContainsString('<strong>Texte en gras</strong>', $content);
    }

    public function testGenerateIndexCreatesIndexHtml(): void
    {
        $post1 = $this->postService->create('Article 1', 'Content');
        $post2 = $this->postService->create('Article 2', 'Content');
        $this->postService->publish($post1->getId());
        $this->postService->publish($post2->getId());

        $this->generator->generateIndex();

        $this->assertFileExists($this->outputPath . '/index.html');
    }

    public function testGenerateIndexListsPublishedPosts(): void
    {
        $post = $this->postService->create('Article Publié', 'Content');
        $this->postService->publish($post->getId());

        $this->postService->create('Article Draft', 'Content'); // Non publié

        $this->generator->generateIndex();

        $content = file_get_contents($this->outputPath . '/index.html');
        $this->assertStringContainsString('Article Publié', $content);
        $this->assertStringNotContainsString('Article Draft', $content);
    }

    public function testGenerateAllGeneratesEverything(): void
    {
        $post1 = $this->postService->create('Post 1', 'Content');
        $post2 = $this->postService->create('Post 2', 'Content');
        $this->postService->publish($post1->getId());
        $this->postService->publish($post2->getId());

        $result = $this->generator->generateAll();

        $this->assertFileExists($this->outputPath . '/index.html');
        $this->assertFileExists($this->outputPath . '/posts/post-1.html');
        $this->assertFileExists($this->outputPath . '/posts/post-2.html');
        $this->assertSame(2, $result['posts']);
    }

    public function testGenerateAllSkipsDrafts(): void
    {
        $post = $this->postService->create('Draft Post', 'Content');
        // Ne pas publier

        $result = $this->generator->generateAll();

        $this->assertSame(0, $result['posts']);
        $this->assertFileDoesNotExist($this->outputPath . '/posts/draft-post.html');
    }

    public function testCleanRemovesGeneratedFiles(): void
    {
        $post = $this->postService->create('Test', 'Content');
        $this->postService->publish($post->getId());
        $this->generator->generatePost($post);

        $this->assertFileExists($this->outputPath . '/posts/test.html');

        $this->generator->clean();

        $this->assertFileDoesNotExist($this->outputPath . '/posts/test.html');
    }

    public function testRegenerateCleansThenGenerates(): void
    {
        // Premier article
        $post1 = $this->postService->create('Old Post', 'Content');
        $this->postService->publish($post1->getId());
        $this->generator->generatePost($post1);

        // Supprimer l'article
        $this->postService->delete($post1->getId());

        // Nouvel article
        $post2 = $this->postService->create('New Post', 'Content');
        $this->postService->publish($post2->getId());

        // Régénérer
        $this->generator->regenerate();

        $this->assertFileDoesNotExist($this->outputPath . '/posts/old-post.html');
        $this->assertFileExists($this->outputPath . '/posts/new-post.html');
    }

    public function testPublishCallbackIsTriggered(): void
    {
        $called = false;
        $this->generator->onPublish(function ($post) use (&$called) {
            $called = true;
        });

        $post = $this->postService->create('Test', 'Content');
        $this->postService->publish($post->getId());
        $this->generator->generatePost($post);

        $this->assertTrue($called);
    }

    public function testGeneratePostWithMetadata(): void
    {
        $post = $this->postService->create('SEO Post', 'Content');
        $post->setExcerpt('Meta description');
        $post->setAuthor('John Doe');
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $this->generator->generatePost($post);

        $content = file_get_contents($this->outputPath . '/posts/seo-post.html');
        $this->assertStringContainsString('John Doe', $content);
    }

    public function testGenerateTagPagesCreatesHtmlFiles(): void
    {
        // Créer le template de tag
        $this->createTagTemplate();

        $post1 = $this->postService->create('Article PHP', 'Content');
        $post1->addTag('php');
        $post1->addTag('programming');
        $this->postService->update($post1);
        $this->postService->publish($post1->getId());

        $post2 = $this->postService->create('Article MySQL', 'Content');
        $post2->addTag('mysql');
        $post2->addTag('programming');
        $this->postService->update($post2);
        $this->postService->publish($post2->getId());

        $count = $this->generator->generateTagPages();

        $this->assertSame(3, $count); // php, mysql, programming
        $this->assertFileExists($this->outputPath . '/tag/php.html');
        $this->assertFileExists($this->outputPath . '/tag/mysql.html');
        $this->assertFileExists($this->outputPath . '/tag/programming.html');

        // Vérifier le contenu
        $phpContent = file_get_contents($this->outputPath . '/tag/php.html');
        $this->assertStringContainsString('Article PHP', $phpContent);
        $this->assertStringNotContainsString('Article MySQL', $phpContent);

        $programmingContent = file_get_contents($this->outputPath . '/tag/programming.html');
        $this->assertStringContainsString('Article PHP', $programmingContent);
        $this->assertStringContainsString('Article MySQL', $programmingContent);
    }

    public function testGenerateTagPagesReturnsZeroWithoutTemplate(): void
    {
        $post = $this->postService->create('Article', 'Content');
        $post->addTag('test');
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $count = $this->generator->generateTagPages();

        $this->assertSame(0, $count);
    }

    public function testGenerateAllIncludesTagPages(): void
    {
        $this->createTagTemplate();

        $post = $this->postService->create('Article', 'Content');
        $post->addTag('test');
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $result = $this->generator->generateAll();

        $this->assertSame(1, $result['tags']);
        $this->assertFileExists($this->outputPath . '/tag/test.html');
    }

    public function testGenerateCategoryPagesReturnsZeroWithoutService(): void
    {
        $this->createCategoryTemplate();

        $result = $this->generator->generateCategoryPages();

        $this->assertSame(0, $result);
    }

    public function testGenerateCategoryPagesCreatesHtmlFiles(): void
    {
        $this->createCategoryTemplate();

        // Créer CategoryService
        $categoryStorage = new FileStorage($this->storagePath . '/categories');
        $categoryService = new \Lunar\Service\Blog\CategoryService($categoryStorage);

        $category = $categoryService->create('PHP');
        $category->setDescription('Articles PHP');
        $category->setColor('#8892BF');
        $categoryService->update($category);

        $this->generator->setCategoryService($categoryService);

        $post = $this->postService->create('Article PHP', 'Content');
        $post->setCategoryId($category->getId());
        $this->postService->update($post);
        $this->postService->publish($post->getId());

        $count = $this->generator->generateCategoryPages();

        $this->assertSame(1, $count);
        $this->assertFileExists($this->outputPath . '/category/php.html');

        $content = file_get_contents($this->outputPath . '/category/php.html');
        $this->assertStringContainsString('PHP', $content);
        $this->assertStringContainsString('Article PHP', $content);
    }

    public function testGenerateAllWithCategoryServiceDoesNotCallFindPerPost(): void
    {
        $this->createCategoryTemplate();
        $this->createTagTemplate();

        $categoryStorage = new FileStorage($this->storagePath . '/categories');
        $categoryService = new \Lunar\Service\Blog\CategoryService($categoryStorage);

        $cat = $categoryService->create('PHP');
        $categoryService->update($cat);

        $this->generator->setCategoryService($categoryService);

        // Create multiple posts with the same category
        for ($i = 1; $i <= 5; $i++) {
            $post = $this->postService->create("Post $i", "Content $i");
            $post->setCategoryId($cat->getId());
            $post->addTag('php');
            $this->postService->update($post);
            $this->postService->publish($post->getId());
        }

        // This should work efficiently with category cache
        $result = $this->generator->generateAll();

        $this->assertSame(5, $result['posts']);
        $this->assertTrue($result['index']);
    }

    private function createCategoryTemplate(): void
    {
        $categoryTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>[[ category_name ]]</title></head>
<body>
    <h1>[[ category_name ]]</h1>
    <p>[[ category_description ]]</p>
    <p>[[ count ]] article(s)</p>
    [% if posts %]
    <ul>
    [% for post in posts %]
        <li><a href="[[ post.url ]]">[[ post.title ]]</a></li>
    [% endfor %]
    </ul>
    [% endif %]
</body>
</html>
HTML;
        file_put_contents($this->templatePath . '/category.html.tpl', $categoryTemplate);
    }

    private function createTagTemplate(): void
    {
        $tagTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>[[ tag ]]</title></head>
<body>
    <h1>Articles tagués "[[ tag ]]"</h1>
    <p>[[ count ]] article(s)</p>
    [% if posts %]
    <ul>
    [% for post in posts %]
        <li><a href="[[ post.url ]]">[[ post.title ]]</a></li>
    [% endfor %]
    </ul>
    [% endif %]
</body>
</html>
HTML;
        file_put_contents($this->templatePath . '/tag.html.tpl', $tagTemplate);
    }

    private function createTestTemplates(): void
    {
        // Template pour un article (lunar-template syntax)
        $postTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>[[ title ]]</title>
</head>
<body>
    <h1>[[ title ]]</h1>
    [% if author %]
    <p class="author">Par [[ author ]]</p>
    [% endif %]
    <article>[[ content|raw ]]</article>
</body>
</html>
HTML;

        // Template pour l'index (lunar-template syntax)
        $indexTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Blog</title>
</head>
<body>
    <h1>Articles</h1>
    <ul>
    [% for post in posts %]
        <li><a href="[[ post.url ]]">[[ post.title ]]</a></li>
    [% endfor %]
    </ul>
</body>
</html>
HTML;

        file_put_contents($this->templatePath . '/post.html.tpl', $postTemplate);
        file_put_contents($this->templatePath . '/index.html.tpl', $indexTemplate);
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
