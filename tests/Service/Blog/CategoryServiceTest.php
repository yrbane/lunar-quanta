<?php

declare(strict_types=1);

namespace Tests\Service\Blog;

use Lunar\Entity\Category;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour CategoryService.
 */
final class CategoryServiceTest extends TestCase
{
    private string $storagePath;
    private CategoryService $service;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_category_test_' . uniqid();
        mkdir($this->storagePath, 0755, true);

        $this->service = new CategoryService(
            new FileStorage($this->storagePath)
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function testCreateReturnsCategory(): void
    {
        $category = $this->service->create('Développement');

        $this->assertInstanceOf(Category::class, $category);
        $this->assertSame('Développement', $category->getName());
    }

    public function testCreateGeneratesSlug(): void
    {
        $category = $this->service->create('Développement Web');

        $this->assertSame('developpement-web', $category->getSlug());
    }

    public function testCreatePersistsCategory(): void
    {
        $category = $this->service->create('Test');

        $found = $this->service->find($category->getId());

        $this->assertNotNull($found);
        $this->assertSame($category->getId(), $found->getId());
    }

    public function testCreateGeneratesUniqueSlug(): void
    {
        $cat1 = $this->service->create('Test');
        $cat2 = $this->service->create('Test');
        $cat3 = $this->service->create('Test');

        $this->assertSame('test', $cat1->getSlug());
        $this->assertSame('test-1', $cat2->getSlug());
        $this->assertSame('test-2', $cat3->getSlug());
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function testUpdatePersistsChanges(): void
    {
        $category = $this->service->create('Original');
        $category->setName('Updated');
        $category->setDescription('New description');

        $this->service->update($category);

        $found = $this->service->find($category->getId());
        $this->assertSame('Updated', $found->getName());
        $this->assertSame('New description', $found->getDescription());
    }

    public function testUpdateReturnsCategory(): void
    {
        $category = $this->service->create('Test');
        $category->setName('New');

        $result = $this->service->update($category);

        $this->assertInstanceOf(Category::class, $result);
        $this->assertSame('New', $result->getName());
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function testDeleteRemovesCategory(): void
    {
        $category = $this->service->create('To Delete');
        $id = $category->getId();

        $result = $this->service->delete($id);

        $this->assertTrue($result);
        $this->assertNull($this->service->find($id));
    }

    public function testDeleteReturnsFalseForNonExistent(): void
    {
        $result = $this->service->delete('non-existent-id');

        $this->assertFalse($result);
    }

    // =========================================================================
    // FIND
    // =========================================================================

    public function testFindReturnsCategory(): void
    {
        $category = $this->service->create('Test');

        $found = $this->service->find($category->getId());

        $this->assertInstanceOf(Category::class, $found);
        $this->assertSame('Test', $found->getName());
    }

    public function testFindReturnsNullForNonExistent(): void
    {
        $found = $this->service->find('non-existent');

        $this->assertNull($found);
    }

    // =========================================================================
    // FIND BY SLUG
    // =========================================================================

    public function testFindBySlugReturnsCategory(): void
    {
        $category = $this->service->create('Développement Web');

        $found = $this->service->findBySlug('developpement-web');

        $this->assertNotNull($found);
        $this->assertSame($category->getId(), $found->getId());
    }

    public function testFindBySlugReturnsNullForNonExistent(): void
    {
        $found = $this->service->findBySlug('non-existent');

        $this->assertNull($found);
    }

    // =========================================================================
    // ALL
    // =========================================================================

    public function testAllReturnsEmptyArrayWhenNoCategories(): void
    {
        $all = $this->service->all();

        $this->assertSame([], $all);
    }

    public function testAllReturnsAllCategories(): void
    {
        $this->service->create('Cat 1');
        $this->service->create('Cat 2');
        $this->service->create('Cat 3');

        $all = $this->service->all();

        $this->assertCount(3, $all);
    }

    public function testAllSortsBySortOrderThenName(): void
    {
        $cat1 = $this->service->create('Beta');
        $cat1->setSortOrder(2);
        $this->service->update($cat1);

        $cat2 = $this->service->create('Alpha');
        $cat2->setSortOrder(1);
        $this->service->update($cat2);

        $cat3 = $this->service->create('Gamma');
        $cat3->setSortOrder(1);
        $this->service->update($cat3);

        $all = $this->service->all();

        $this->assertSame('Alpha', $all[0]->getName());
        $this->assertSame('Gamma', $all[1]->getName());
        $this->assertSame('Beta', $all[2]->getName());
    }

    // =========================================================================
    // COUNT
    // =========================================================================

    public function testCountReturnsZeroWhenEmpty(): void
    {
        $this->assertSame(0, $this->service->count());
    }

    public function testCountReturnsCorrectNumber(): void
    {
        $this->service->create('Cat 1');
        $this->service->create('Cat 2');

        $this->assertSame(2, $this->service->count());
    }

    // =========================================================================
    // EXISTS
    // =========================================================================

    public function testExistsReturnsTrueForExisting(): void
    {
        $category = $this->service->create('Test');

        $this->assertTrue($this->service->exists($category->getId()));
    }

    public function testExistsReturnsFalseForNonExistent(): void
    {
        $this->assertFalse($this->service->exists('non-existent'));
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

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
