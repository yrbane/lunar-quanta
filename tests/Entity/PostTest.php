<?php

declare(strict_types=1);

namespace Tests\Entity;

use Lunar\Entity\Post;
use Lunar\Entity\PostStatus;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour l'entité Post.
 */
final class PostTest extends TestCase
{
    public function testConstructorGeneratesUniqueId(): void
    {
        $post1 = new Post('Title 1', 'Content 1');
        $post2 = new Post('Title 2', 'Content 2');

        $this->assertNotEmpty($post1->getId());
        $this->assertNotSame($post1->getId(), $post2->getId());
    }

    public function testConstructorSetsTitle(): void
    {
        $post = new Post('Mon Article', 'Contenu');

        $this->assertSame('Mon Article', $post->getTitle());
    }

    public function testConstructorSetsContent(): void
    {
        $post = new Post('Titre', 'Le contenu de mon article');

        $this->assertSame('Le contenu de mon article', $post->getContent());
    }

    public function testConstructorGeneratesSlugFromTitle(): void
    {
        $post = new Post('Mon Premier Article', 'Contenu');

        $this->assertSame('mon-premier-article', $post->getSlug());
    }

    public function testDefaultStatusIsDraft(): void
    {
        $post = new Post('Titre', 'Contenu');

        $this->assertSame(PostStatus::DRAFT, $post->getStatus());
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $post = new Post('Titre', 'Contenu');
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $post->getCreatedAt());
        $this->assertLessThanOrEqual($after, $post->getCreatedAt());
    }

    public function testConstructorSetsUpdatedAt(): void
    {
        $post = new Post('Titre', 'Contenu');

        $this->assertNotNull($post->getUpdatedAt());
    }

    public function testSetTitle(): void
    {
        $post = new Post('Old', 'Content');
        $post->setTitle('New');

        $this->assertSame('New', $post->getTitle());
    }

    public function testSetContent(): void
    {
        $post = new Post('Title', 'Old');
        $post->setContent('New content');

        $this->assertSame('New content', $post->getContent());
    }

    public function testSetSlug(): void
    {
        $post = new Post('Title', 'Content');
        $post->setSlug('custom-slug');

        $this->assertSame('custom-slug', $post->getSlug());
    }

    public function testSetExcerpt(): void
    {
        $post = new Post('Title', 'Content');
        $post->setExcerpt('Short description');

        $this->assertSame('Short description', $post->getExcerpt());
    }

    public function testSetAuthor(): void
    {
        $post = new Post('Title', 'Content');
        $post->setAuthor('John Doe');

        $this->assertSame('John Doe', $post->getAuthor());
    }

    public function testSetCategoryId(): void
    {
        $post = new Post('Title', 'Content');
        $post->setCategoryId('cat-123');

        $this->assertSame('cat-123', $post->getCategoryId());
    }

    public function testSetFeaturedImage(): void
    {
        $post = new Post('Title', 'Content');
        $post->setFeaturedImage('img-123');

        $this->assertSame('img-123', $post->getFeaturedImage());
    }

    public function testAddTag(): void
    {
        $post = new Post('Title', 'Content');
        $post->addTag('tag-1');
        $post->addTag('tag-2');

        $this->assertCount(2, $post->getTags());
        $this->assertContains('tag-1', $post->getTags());
    }

    public function testAddTagDoesNotDuplicate(): void
    {
        $post = new Post('Title', 'Content');
        $post->addTag('tag-1');
        $post->addTag('tag-1');

        $this->assertCount(1, $post->getTags());
    }

    public function testRemoveTag(): void
    {
        $post = new Post('Title', 'Content');
        $post->addTag('tag-1');
        $post->addTag('tag-2');
        $post->removeTag('tag-1');

        $this->assertCount(1, $post->getTags());
        $this->assertNotContains('tag-1', $post->getTags());
    }

    public function testHasTag(): void
    {
        $post = new Post('Title', 'Content');
        $post->addTag('tag-1');

        $this->assertTrue($post->hasTag('tag-1'));
        $this->assertFalse($post->hasTag('tag-2'));
    }

    public function testPublish(): void
    {
        $post = new Post('Title', 'Content');
        $post->publish();

        $this->assertSame(PostStatus::PUBLISHED, $post->getStatus());
        $this->assertNotNull($post->getPublishedAt());
    }

    public function testPublishSetsPublishedAtOnce(): void
    {
        $post = new Post('Title', 'Content');
        $post->publish();
        $firstPublished = $post->getPublishedAt();

        // Unpublish et republish
        $post->unpublish();
        $post->publish();

        // La date de première publication ne change pas
        $this->assertEquals($firstPublished, $post->getPublishedAt());
    }

    public function testUnpublish(): void
    {
        $post = new Post('Title', 'Content');
        $post->publish();
        $post->unpublish();

        $this->assertSame(PostStatus::DRAFT, $post->getStatus());
    }

    public function testArchive(): void
    {
        $post = new Post('Title', 'Content');
        $post->archive();

        $this->assertSame(PostStatus::ARCHIVED, $post->getStatus());
    }

    public function testIsPublished(): void
    {
        $post = new Post('Title', 'Content');
        $this->assertFalse($post->isPublished());

        $post->publish();
        $this->assertTrue($post->isPublished());
    }

    public function testIsDraft(): void
    {
        $post = new Post('Title', 'Content');
        $this->assertTrue($post->isDraft());

        $post->publish();
        $this->assertFalse($post->isDraft());
    }

    public function testIsArchived(): void
    {
        $post = new Post('Title', 'Content');
        $this->assertFalse($post->isArchived());

        $post->archive();
        $this->assertTrue($post->isArchived());
    }

    public function testToArray(): void
    {
        $post = new Post('Mon Article', '# Contenu');
        $post->setExcerpt('Description');
        $post->setAuthor('John');
        $post->addTag('tag-1');

        $array = $post->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('excerpt', $array);
        $this->assertArrayHasKey('author', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);

        $this->assertSame('Mon Article', $array['title']);
        $this->assertSame('draft', $array['status']);
    }

    public function testFromArray(): void
    {
        $original = new Post('Test', 'Content');
        $original->setExcerpt('Excerpt');
        $original->addTag('tag-1');
        $original->publish();

        $data = $original->toArray();
        $restored = Post::fromArray($data);

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getTitle(), $restored->getTitle());
        $this->assertSame($original->getSlug(), $restored->getSlug());
        $this->assertSame($original->getStatus(), $restored->getStatus());
        $this->assertSame($original->getTags(), $restored->getTags());
    }

    public function testUpdateTimestamp(): void
    {
        $post = new Post('Title', 'Content');
        $initialUpdatedAt = $post->getUpdatedAt();

        usleep(10000); // 10ms
        $post->setTitle('New Title');

        $this->assertGreaterThan($initialUpdatedAt, $post->getUpdatedAt());
    }

    public function testGetUrl(): void
    {
        $post = new Post('Mon Article', 'Content');

        $this->assertSame('/blog/posts/mon-article.html', $post->getUrl());
    }

    public function testWordCount(): void
    {
        $post = new Post('Title', 'This is a test with five words. And more words here.');

        $this->assertGreaterThan(0, $post->getWordCount());
    }

    public function testReadingTime(): void
    {
        // 200 mots = ~1 minute de lecture
        $content = str_repeat('word ', 200);
        $post = new Post('Title', $content);

        $this->assertSame(1, $post->getReadingTime());
    }
}
