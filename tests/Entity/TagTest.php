<?php

declare(strict_types=1);

namespace Tests\Entity;

use Lunar\Entity\Tag;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour l'entité Tag.
 *
 * Un tag est un mot-clé simple associé aux articles.
 * Contrairement aux catégories, les tags n'ont pas de hiérarchie.
 */
final class TagTest extends TestCase
{
    public function testConstructorGeneratesUniqueId(): void
    {
        $tag1 = new Tag('PHP');
        $tag2 = new Tag('PHP');

        $this->assertNotEmpty($tag1->getId());
        $this->assertNotSame($tag1->getId(), $tag2->getId());
    }

    public function testConstructorSetsName(): void
    {
        $tag = new Tag('PHP');

        $this->assertSame('PHP', $tag->getName());
    }

    public function testConstructorGeneratesSlugFromName(): void
    {
        $tag = new Tag('PHP Framework');

        $this->assertSame('php-framework', $tag->getSlug());
    }

    public function testSlugHandlesAccents(): void
    {
        $tag = new Tag('Développement Web');

        $this->assertSame('developpement-web', $tag->getSlug());
    }

    public function testSlugHandlesSpecialCharacters(): void
    {
        $tag = new Tag('C++ & C#');

        $this->assertSame('c-c', $tag->getSlug());
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $tag = new Tag('Test');
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $tag->getCreatedAt());
        $this->assertLessThanOrEqual($after, $tag->getCreatedAt());
    }

    public function testSetName(): void
    {
        $tag = new Tag('Old Name');
        $tag->setName('New Name');

        $this->assertSame('New Name', $tag->getName());
    }

    public function testSetNameDoesNotUpdateSlug(): void
    {
        $tag = new Tag('Original');
        $originalSlug = $tag->getSlug();

        $tag->setName('Changed');

        $this->assertSame($originalSlug, $tag->getSlug());
    }

    public function testSetSlug(): void
    {
        $tag = new Tag('Test');
        $tag->setSlug('custom-slug');

        $this->assertSame('custom-slug', $tag->getSlug());
    }

    public function testToArray(): void
    {
        $tag = new Tag('PHP');
        $array = $tag->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertSame('PHP', $array['name']);
        $this->assertSame('php', $array['slug']);
    }

    public function testFromArray(): void
    {
        $original = new Tag('PHP Framework');
        $data = $original->toArray();

        $restored = Tag::fromArray($data);

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getName(), $restored->getName());
        $this->assertSame($original->getSlug(), $restored->getSlug());
        // Les microsecondes sont perdues lors de la sérialisation ISO 8601
        $this->assertSame(
            $original->getCreatedAt()->format('Y-m-d H:i:s'),
            $restored->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    public function testFromArrayWithCustomSlug(): void
    {
        $data = [
            'id' => 'tag-123',
            'name' => 'Test Tag',
            'slug' => 'custom-slug',
            'createdAt' => '2024-01-15T10:30:00+00:00',
        ];

        $tag = Tag::fromArray($data);

        $this->assertSame('custom-slug', $tag->getSlug());
    }

    public function testArticleCount(): void
    {
        $tag = new Tag('PHP');

        $this->assertSame(0, $tag->getArticleCount());

        $tag->setArticleCount(5);

        $this->assertSame(5, $tag->getArticleCount());
    }

    public function testIncrementArticleCount(): void
    {
        $tag = new Tag('PHP');
        $tag->incrementArticleCount();
        $tag->incrementArticleCount();

        $this->assertSame(2, $tag->getArticleCount());
    }

    public function testDecrementArticleCount(): void
    {
        $tag = new Tag('PHP');
        $tag->setArticleCount(5);
        $tag->decrementArticleCount();

        $this->assertSame(4, $tag->getArticleCount());
    }

    public function testDecrementArticleCountDoesNotGoBelowZero(): void
    {
        $tag = new Tag('PHP');
        $tag->decrementArticleCount();

        $this->assertSame(0, $tag->getArticleCount());
    }
}
