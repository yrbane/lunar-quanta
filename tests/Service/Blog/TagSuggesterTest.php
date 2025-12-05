<?php

declare(strict_types=1);

namespace Tests\Service\Blog;

use Lunar\Service\Blog\ContentAnalyzer;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Blog\TagSuggester;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour TagSuggester.
 */
final class TagSuggesterTest extends TestCase
{
    private string $storagePath;
    private PostService $postService;
    private TagSuggester $suggester;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_tag_suggester_test_' . uniqid();
        mkdir($this->storagePath, 0755, true);

        $this->postService = new PostService(
            new FileStorage($this->storagePath)
        );

        $this->suggester = new TagSuggester(
            $this->postService,
            new ContentAnalyzer()
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testSuggestReturnsArray(): void
    {
        $suggestions = $this->suggester->suggest('PHP est un langage');

        $this->assertIsArray($suggestions);
    }

    public function testSuggestFindsExistingTags(): void
    {
        // Créer des articles avec tags
        $post1 = $this->postService->create('Article PHP', 'Contenu PHP');
        $post1->addTag('php');
        $post1->addTag('web');
        $this->postService->update($post1);

        $post2 = $this->postService->create('Article Laravel', 'Framework Laravel');
        $post2->addTag('laravel');
        $post2->addTag('php');
        $this->postService->update($post2);

        // Suggérer pour un nouveau contenu
        $suggestions = $this->suggester->suggest('Un article sur PHP et le web', 5);

        $this->assertContains('php', $suggestions);
    }

    public function testSuggestRespectsLimit(): void
    {
        $post = $this->postService->create('Article', 'Contenu');
        $post->addTag('tag1');
        $post->addTag('tag2');
        $post->addTag('tag3');
        $post->addTag('tag4');
        $post->addTag('tag5');
        $this->postService->update($post);

        $suggestions = $this->suggester->suggest('Contenu varié', 2);

        $this->assertLessThanOrEqual(2, count($suggestions));
    }

    public function testSuggestWithScoresReturnsScores(): void
    {
        $post = $this->postService->create('Article PHP', 'Développement PHP');
        $post->addTag('php');
        $this->postService->update($post);

        $scores = $this->suggester->suggestWithScores('PHP programmation', 5);

        $this->assertIsArray($scores);
        foreach ($scores as $tag => $score) {
            $this->assertIsString($tag);
            $this->assertIsFloat($score);
        }
    }

    public function testSuggestWithEmptyContent(): void
    {
        $suggestions = $this->suggester->suggest('', 5);

        $this->assertIsArray($suggestions);
    }

    public function testSuggestWithNoExistingTags(): void
    {
        // Pas de posts créés

        $suggestions = $this->suggester->suggest('PHP est un langage de programmation web', 5);

        // Devrait suggérer des mots-clés du texte
        $this->assertIsArray($suggestions);
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
