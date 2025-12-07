<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Entity\Post;
use Lunar\Entity\PostStatus;
use Lunar\Service\Content\RelatedPostsService;
use PHPUnit\Framework\TestCase;

final class RelatedPostsServiceTest extends TestCase
{
    private RelatedPostsService $service;

    protected function setUp(): void
    {
        $this->service = new RelatedPostsService();
    }

    private function createPost(string $id, string $title, array $tags = [], ?string $categoryId = null): Post
    {
        $post = new Post($title, 'Content for ' . $title);
        $reflection = new \ReflectionClass($post);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($post, $id);

        $post->setStatus(PostStatus::PUBLISHED);

        foreach ($tags as $tag) {
            $post->addTag($tag);
        }

        if ($categoryId) {
            $post->setCategoryId($categoryId);
        }

        return $post;
    }

    public function testFindRelatedReturnsEmptyForNoMatches(): void
    {
        $post = $this->createPost('1', 'Post 1', ['php']);
        $candidates = [
            $this->createPost('2', 'Post 2', ['javascript']),
        ];

        $this->service->setMinScore(0.5);
        $results = $this->service->findRelated($post, $candidates);

        $this->assertEmpty($results);
    }

    public function testFindRelatedReturnsMatchingTags(): void
    {
        $post = $this->createPost('1', 'PHP Tutorial', ['php', 'tutorial']);
        $candidates = [
            $this->createPost('2', 'Advanced PHP', ['php', 'advanced']),
            $this->createPost('3', 'JavaScript Basics', ['javascript']),
        ];

        $results = $this->service->findRelated($post, $candidates);

        $this->assertCount(1, $results);
        $this->assertSame('2', $results[0]['post']->getId());
    }

    public function testFindRelatedExcludesSamePost(): void
    {
        $post = $this->createPost('1', 'My Post', ['php']);
        $candidates = [$post];

        $results = $this->service->findRelated($post, $candidates);

        $this->assertEmpty($results);
    }

    public function testFindRelatedExcludesUnpublished(): void
    {
        $post = $this->createPost('1', 'Published', ['php']);
        $draft = $this->createPost('2', 'Draft', ['php']);
        $draft->setStatus(PostStatus::DRAFT);

        $results = $this->service->findRelated($post, [$draft]);

        $this->assertEmpty($results);
    }

    public function testSetMaxResultsLimitsResults(): void
    {
        $post = $this->createPost('1', 'Main Post', ['php']);
        $candidates = [
            $this->createPost('2', 'Post 2', ['php']),
            $this->createPost('3', 'Post 3', ['php']),
            $this->createPost('4', 'Post 4', ['php']),
        ];

        $this->service->setMaxResults(2);
        $results = $this->service->findRelated($post, $candidates);

        $this->assertCount(2, $results);
    }

    public function testCalculateSimilarityWithMatchingTags(): void
    {
        $post1 = $this->createPost('1', 'Post 1', ['php', 'mysql']);
        $post2 = $this->createPost('2', 'Post 2', ['php', 'mysql']);

        $similarity = $this->service->calculateSimilarity($post1, $post2);

        $this->assertGreaterThan(0.5, $similarity);
    }

    public function testCalculateSimilarityWithMatchingCategory(): void
    {
        $post1 = $this->createPost('1', 'Post 1', [], 'cat-1');
        $post2 = $this->createPost('2', 'Post 2', [], 'cat-1');

        $this->service->setCategoryWeight(1.0)->setTagWeight(0);
        $similarity = $this->service->calculateSimilarity($post1, $post2);

        $this->assertSame(1.0, $similarity);
    }

    public function testCalculateSimilarityWithMatchingTitle(): void
    {
        $post1 = $this->createPost('1', 'Introduction to PHP', []);
        $post2 = $this->createPost('2', 'Advanced PHP Tutorial', []);

        $this->service->setTitleWeight(1.0)->setTagWeight(0)->setCategoryWeight(0);
        $similarity = $this->service->calculateSimilarity($post1, $post2);

        $this->assertGreaterThan(0, $similarity);
    }

    public function testSetMinScoreFiltersLowScores(): void
    {
        $post = $this->createPost('1', 'PHP', ['php', 'mysql', 'web']);
        $candidates = [
            $this->createPost('2', 'Also PHP', ['php']), // Faible similarité
        ];

        $this->service->setMinScore(0.8);
        $results = $this->service->findRelated($post, $candidates);

        $this->assertEmpty($results);
    }

    public function testResultsSortedByScore(): void
    {
        $post = $this->createPost('1', 'Main', ['php', 'mysql', 'web']);
        $candidates = [
            $this->createPost('2', 'Low Match', ['php']),
            $this->createPost('3', 'High Match', ['php', 'mysql', 'web']),
            $this->createPost('4', 'Medium Match', ['php', 'mysql']),
        ];

        $results = $this->service->findRelated($post, $candidates);

        $this->assertSame('3', $results[0]['post']->getId());
    }

    public function testGenerateHtmlReturnsEmptyForNoResults(): void
    {
        $html = $this->service->generateHtml([]);

        $this->assertEmpty($html);
    }

    public function testGenerateHtmlContainsTitle(): void
    {
        $post = $this->createPost('1', 'Related Post', ['php']);
        $related = [['post' => $post, 'score' => 0.8]];

        $html = $this->service->generateHtml($related, 'Articles similaires');

        $this->assertStringContainsString('Articles similaires', $html);
        $this->assertStringContainsString('Related Post', $html);
    }

    public function testGenerateCssReturnsValidCss(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('.la-related-posts', $css);
        $this->assertStringContainsString('.la-related-card', $css);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setMaxResults(10)
            ->setTagWeight(1.5)
            ->setCategoryWeight(0.5)
            ->setTitleWeight(0.3)
            ->setMinScore(0.2);

        $this->assertSame($this->service, $result);
    }

    public function testZeroWeightsReturnsZeroSimilarity(): void
    {
        $post1 = $this->createPost('1', 'Post 1', ['php']);
        $post2 = $this->createPost('2', 'Post 2', ['php']);

        $this->service
            ->setTagWeight(0)
            ->setCategoryWeight(0)
            ->setTitleWeight(0);

        $similarity = $this->service->calculateSimilarity($post1, $post2);

        $this->assertSame(0.0, $similarity);
    }
}
