<?php

declare(strict_types=1);

namespace Tests\Entity;

use Lunar\Entity\Category;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour l'entité Category.
 */
final class CategoryTest extends TestCase
{
    public function testConstructorSetsNameAndGeneratesSlug(): void
    {
        $category = new Category('Développement Web');

        $this->assertSame('Développement Web', $category->getName());
        $this->assertSame('developpement-web', $category->getSlug());
    }

    public function testConstructorGeneratesUniqueId(): void
    {
        $category1 = new Category('Test');
        $category2 = new Category('Test');

        $this->assertNotSame($category1->getId(), $category2->getId());
    }

    public function testConstructorSetsCreatedAtAndUpdatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $category = new Category('Test');
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $category->getCreatedAt());
        $this->assertLessThanOrEqual($after, $category->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $category->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $category->getUpdatedAt());
    }

    public function testDefaultValues(): void
    {
        $category = new Category('Test');

        $this->assertSame('', $category->getDescription());
        $this->assertSame('#6b7280', $category->getColor());
        $this->assertSame(0, $category->getSortOrder());
    }

    public function testSetName(): void
    {
        $category = new Category('Ancien Nom');
        $category->setName('Nouveau Nom');

        $this->assertSame('Nouveau Nom', $category->getName());
    }

    public function testSetSlug(): void
    {
        $category = new Category('Test');
        $category->setSlug('custom-slug');

        $this->assertSame('custom-slug', $category->getSlug());
    }

    public function testSetDescription(): void
    {
        $category = new Category('Test');
        $category->setDescription('Ma description');

        $this->assertSame('Ma description', $category->getDescription());
    }

    public function testSetColor(): void
    {
        $category = new Category('Test');
        $category->setColor('#ff0000');

        $this->assertSame('#ff0000', $category->getColor());
    }

    public function testSetSortOrder(): void
    {
        $category = new Category('Test');
        $category->setSortOrder(5);

        $this->assertSame(5, $category->getSortOrder());
    }

    public function testGetUrl(): void
    {
        $category = new Category('Développement PHP');

        $this->assertSame('/blog/categories/developpement-php.html', $category->getUrl());
    }

    public function testSettersUpdateUpdatedAt(): void
    {
        $category = new Category('Test');
        $initialUpdatedAt = $category->getUpdatedAt();

        usleep(1000); // 1ms

        $category->setName('New Name');
        $this->assertGreaterThan($initialUpdatedAt, $category->getUpdatedAt());
    }

    public function testToArray(): void
    {
        $category = new Category('Test Category');
        $category->setDescription('Test description');
        $category->setColor('#3b82f6');
        $category->setSortOrder(10);

        $array = $category->toArray();

        $this->assertSame($category->getId(), $array['id']);
        $this->assertSame('Test Category', $array['name']);
        $this->assertSame('test-category', $array['slug']);
        $this->assertSame('Test description', $array['description']);
        $this->assertSame('#3b82f6', $array['color']);
        $this->assertSame(10, $array['sortOrder']);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'test-id-123',
            'name' => 'From Array',
            'slug' => 'from-array',
            'description' => 'Description test',
            'color' => '#ff5500',
            'sortOrder' => 3,
            'createdAt' => '2024-01-15T10:00:00+00:00',
            'updatedAt' => '2024-01-16T15:30:00+00:00',
        ];

        $category = Category::fromArray($data);

        $this->assertSame('test-id-123', $category->getId());
        $this->assertSame('From Array', $category->getName());
        $this->assertSame('from-array', $category->getSlug());
        $this->assertSame('Description test', $category->getDescription());
        $this->assertSame('#ff5500', $category->getColor());
        $this->assertSame(3, $category->getSortOrder());
        $this->assertSame('2024-01-15', $category->getCreatedAt()->format('Y-m-d'));
        $this->assertSame('2024-01-16', $category->getUpdatedAt()->format('Y-m-d'));
    }

    public function testToArrayFromArrayRoundtrip(): void
    {
        $original = new Category('Test Roundtrip');
        $original->setDescription('Description roundtrip');
        $original->setColor('#abcdef');
        $original->setSortOrder(7);

        $array = $original->toArray();
        $restored = Category::fromArray($array);

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getName(), $restored->getName());
        $this->assertSame($original->getSlug(), $restored->getSlug());
        $this->assertSame($original->getDescription(), $restored->getDescription());
        $this->assertSame($original->getColor(), $restored->getColor());
        $this->assertSame($original->getSortOrder(), $restored->getSortOrder());
    }

    public function testFluentInterface(): void
    {
        $category = new Category('Test');

        $result = $category
            ->setName('Updated')
            ->setSlug('updated-slug')
            ->setDescription('Desc')
            ->setColor('#000000')
            ->setSortOrder(1);

        $this->assertSame($category, $result);
    }
}
