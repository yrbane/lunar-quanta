<?php

declare(strict_types=1);

namespace Tests\Service\Blog;

use Lunar\Entity\Post;
use Lunar\Entity\PostStatus;
use Lunar\Service\Blog\BlogException;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour PostService.
 */
final class PostServiceTest extends TestCase
{
    private string $storagePath;
    private PostService $service;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_post_test_' . uniqid();
        mkdir($this->storagePath, 0755, true);

        $this->service = new PostService(
            new FileStorage($this->storagePath)
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testCreatePost(): void
    {
        $post = $this->service->create('Mon Article', '# Contenu');

        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame('Mon Article', $post->getTitle());
    }

    public function testCreatePostIsPersisted(): void
    {
        $post = $this->service->create('Mon Article', 'Contenu');
        $found = $this->service->find($post->getId());

        $this->assertNotNull($found);
        $this->assertSame($post->getId(), $found->getId());
    }

    public function testFindReturnsNullForNonExistent(): void
    {
        $found = $this->service->find('non-existent-id');

        $this->assertNull($found);
    }

    public function testFindBySlug(): void
    {
        $post = $this->service->create('Mon Article', 'Contenu');
        $found = $this->service->findBySlug('mon-article');

        $this->assertNotNull($found);
        $this->assertSame($post->getId(), $found->getId());
    }

    public function testUpdate(): void
    {
        $post = $this->service->create('Original', 'Content');
        $post->setTitle('Updated');
        $this->service->update($post);

        $found = $this->service->find($post->getId());
        $this->assertSame('Updated', $found->getTitle());
    }

    public function testDelete(): void
    {
        $post = $this->service->create('To Delete', 'Content');
        $this->service->delete($post->getId());

        $this->assertNull($this->service->find($post->getId()));
    }

    public function testAllReturnsAllPosts(): void
    {
        $this->service->create('Post 1', 'Content');
        $this->service->create('Post 2', 'Content');
        $this->service->create('Post 3', 'Content');

        $all = $this->service->all();

        $this->assertCount(3, $all);
    }

    public function testPublishPost(): void
    {
        $post = $this->service->create('To Publish', 'Content');
        $published = $this->service->publish($post->getId());

        $this->assertTrue($published->isPublished());
    }

    public function testPublishNonExistentThrows(): void
    {
        $this->expectException(BlogException::class);

        $this->service->publish('non-existent');
    }

    public function testUnpublishPost(): void
    {
        $post = $this->service->create('Test', 'Content');
        $this->service->publish($post->getId());
        $unpublished = $this->service->unpublish($post->getId());

        $this->assertTrue($unpublished->isDraft());
    }

    public function testArchivePost(): void
    {
        $post = $this->service->create('Test', 'Content');
        $archived = $this->service->archive($post->getId());

        $this->assertTrue($archived->isArchived());
    }

    public function testFindPublished(): void
    {
        $post1 = $this->service->create('Published', 'Content');
        $this->service->publish($post1->getId());

        $this->service->create('Draft', 'Content'); // reste en draft

        $published = $this->service->findPublished();

        $this->assertCount(1, $published);
        $this->assertSame('Published', $published[0]->getTitle());
    }

    public function testFindDrafts(): void
    {
        $this->service->create('Draft 1', 'Content');
        $this->service->create('Draft 2', 'Content');

        $post3 = $this->service->create('Published', 'Content');
        $this->service->publish($post3->getId());

        $drafts = $this->service->findDrafts();

        $this->assertCount(2, $drafts);
    }

    public function testFindByTag(): void
    {
        $post1 = $this->service->create('Post 1', 'Content');
        $post1->addTag('php');
        $this->service->update($post1);

        $post2 = $this->service->create('Post 2', 'Content');
        $post2->addTag('php');
        $post2->addTag('mysql');
        $this->service->update($post2);

        $this->service->create('Post 3', 'Content'); // pas de tag

        $phpPosts = $this->service->findByTag('php');

        $this->assertCount(2, $phpPosts);
    }

    public function testFindByCategory(): void
    {
        $post1 = $this->service->create('Post 1', 'Content');
        $post1->setCategoryId('cat-1');
        $this->service->update($post1);

        $post2 = $this->service->create('Post 2', 'Content');
        $post2->setCategoryId('cat-1');
        $this->service->update($post2);

        $this->service->create('Post 3', 'Content'); // pas de catégorie

        $catPosts = $this->service->findByCategory('cat-1');

        $this->assertCount(2, $catPosts);
    }

    public function testFindRecent(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $post = $this->service->create("Post $i", 'Content');
            $this->service->publish($post->getId());
        }

        $recent = $this->service->findRecent(3);

        $this->assertCount(3, $recent);
    }

    public function testFindRecentReturnsOnlyPublished(): void
    {
        $post1 = $this->service->create('Published', 'Content');
        $this->service->publish($post1->getId());

        $this->service->create('Draft', 'Content');

        $recent = $this->service->findRecent(10);

        $this->assertCount(1, $recent);
    }

    public function testSlugUniqueness(): void
    {
        $post1 = $this->service->create('Mon Article', 'Content 1');
        $post2 = $this->service->create('Mon Article', 'Content 2');

        $this->assertNotSame($post1->getSlug(), $post2->getSlug());
        $this->assertSame('mon-article-1', $post2->getSlug());
    }

    public function testCount(): void
    {
        $this->service->create('Post 1', 'Content');
        $this->service->create('Post 2', 'Content');

        $this->assertSame(2, $this->service->count());
    }

    public function testCountByStatus(): void
    {
        $post1 = $this->service->create('Published', 'Content');
        $this->service->publish($post1->getId());

        $this->service->create('Draft', 'Content');
        $this->service->create('Draft 2', 'Content');

        $this->assertSame(1, $this->service->countByStatus(PostStatus::PUBLISHED));
        $this->assertSame(2, $this->service->countByStatus(PostStatus::DRAFT));
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
