<?php

declare(strict_types=1);

namespace Tests\Service\Blog;

use Lunar\Entity\Category;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\CategorySuggester;
use Lunar\Service\Blog\ContentAnalyzer;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour CategorySuggester.
 */
final class CategorySuggesterTest extends TestCase
{
    private string $storagePath;
    private CategoryService $categoryService;
    private PostService $postService;
    private CategorySuggester $suggester;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_category_suggester_test_' . uniqid();
        mkdir($this->storagePath . '/categories', 0755, true);
        mkdir($this->storagePath . '/posts', 0755, true);

        $this->categoryService = new CategoryService(
            new FileStorage($this->storagePath . '/categories')
        );

        $this->postService = new PostService(
            new FileStorage($this->storagePath . '/posts')
        );

        $this->suggester = new CategorySuggester(
            $this->categoryService,
            $this->postService,
            new ContentAnalyzer()
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testSuggestReturnsCategoryOrNull(): void
    {
        $result = $this->suggester->suggest('Contenu quelconque');

        $this->assertTrue($result === null || $result instanceof Category);
    }

    public function testSuggestFindsRelevantCategory(): void
    {
        // Créer des catégories
        $phpCategory = $this->categoryService->create('Développement PHP');
        $phpCategory->setDescription('Articles sur PHP et les frameworks PHP');
        $this->categoryService->update($phpCategory);

        $jsCategory = $this->categoryService->create('JavaScript');
        $jsCategory->setDescription('Articles sur JavaScript et Node.js');
        $this->categoryService->update($jsCategory);

        // Suggérer pour un contenu PHP
        $content = 'Introduction à PHP et au framework Laravel. Ce guide couvre les bases de PHP.';
        $suggestion = $this->suggester->suggest($content);

        $this->assertNotNull($suggestion);
        $this->assertSame($phpCategory->getId(), $suggestion->getId());
    }

    public function testSuggestWithScoresReturnsScores(): void
    {
        $this->categoryService->create('PHP');
        $this->categoryService->create('JavaScript');

        $scores = $this->suggester->suggestWithScores('PHP programmation', 5);

        $this->assertIsArray($scores);
        foreach ($scores as $categoryId => $score) {
            $this->assertIsString($categoryId);
            $this->assertIsFloat($score);
        }
    }

    public function testSuggestWithScoresRespectsLimit(): void
    {
        $this->categoryService->create('Cat 1');
        $this->categoryService->create('Cat 2');
        $this->categoryService->create('Cat 3');
        $this->categoryService->create('Cat 4');
        $this->categoryService->create('Cat 5');

        $scores = $this->suggester->suggestWithScores('Contenu', 2);

        $this->assertLessThanOrEqual(2, count($scores));
    }

    public function testSuggestReturnsNullWithNoCategories(): void
    {
        $suggestion = $this->suggester->suggest('PHP est un langage');

        $this->assertNull($suggestion);
    }

    public function testSuggestUsesExistingPostsForScoring(): void
    {
        // Créer des catégories
        $phpCategory = $this->categoryService->create('PHP');
        $jsCategory = $this->categoryService->create('JavaScript');

        // Créer des articles PHP dans la catégorie PHP
        $post1 = $this->postService->create('Laravel Framework', 'Laravel est un framework PHP');
        $post1->setCategoryId($phpCategory->getId());
        $this->postService->update($post1);

        $post2 = $this->postService->create('Symfony Framework', 'Symfony est un framework PHP');
        $post2->setCategoryId($phpCategory->getId());
        $this->postService->update($post2);

        // Suggérer pour un contenu similaire
        $content = 'Comparaison entre Laravel et Symfony pour le développement PHP';
        $suggestion = $this->suggester->suggest($content);

        $this->assertNotNull($suggestion);
        $this->assertSame($phpCategory->getId(), $suggestion->getId());
    }

    public function testSuggestWithEmptyContent(): void
    {
        $this->categoryService->create('PHP');

        $suggestion = $this->suggester->suggest('');

        // Avec un contenu vide, pas de suggestion possible
        $this->assertNull($suggestion);
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
