<?php

declare(strict_types=1);

namespace Tests\Service\Storage;

use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

class FileStorageTest extends TestCase
{
    private string $tempDir;
    private FileStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/file_storage_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->storage = new FileStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }

    public function testSaveAndFind(): void
    {
        $this->storage->save('test-1', ['title' => 'Hello']);
        $data = $this->storage->find('test-1');

        $this->assertSame('Hello', $data['title']);
    }

    public function testFindReturnsNullForMissing(): void
    {
        $this->assertNull($this->storage->find('nonexistent'));
    }

    public function testExists(): void
    {
        $this->storage->save('exists-1', ['data' => true]);

        $this->assertTrue($this->storage->exists('exists-1'));
        $this->assertFalse($this->storage->exists('nope'));
    }

    public function testDelete(): void
    {
        $this->storage->save('del-1', ['data' => true]);
        $this->storage->delete('del-1');

        $this->assertNull($this->storage->find('del-1'));
    }

    public function testAll(): void
    {
        $this->storage->save('a-1', ['id' => 'a-1', 'v' => 1]);
        $this->storage->save('a-2', ['id' => 'a-2', 'v' => 2]);

        $all = $this->storage->all();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('a-1', $all);
        $this->assertArrayHasKey('a-2', $all);
    }

    public function testCount(): void
    {
        $this->assertSame(0, $this->storage->count());
        $this->storage->save('c-1', ['id' => 'c-1']);
        $this->assertSame(1, $this->storage->count());
    }

    public function testClear(): void
    {
        $this->storage->save('cl-1', ['id' => 'cl-1']);
        $this->storage->save('cl-2', ['id' => 'cl-2']);
        $this->storage->clear();

        $this->assertSame(0, $this->storage->count());
    }

    public function testSaveRejectsEmptyIdAfterSanitization(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->storage->save('../../..', ['data' => 'malicious']);
    }

    public function testFindRejectsEmptyIdAfterSanitization(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->storage->find('///');
    }

    public function testDeleteRejectsEmptyIdAfterSanitization(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->storage->delete('...');
    }

    public function testPathTraversalIsSanitized(): void
    {
        $this->storage->save('safe-id', ['id' => 'safe-id', 'secret' => 'data']);

        // Path traversal characters are stripped
        $result = $this->storage->find('../../safe-id');
        // After sanitization '../../safe-id' becomes 'safe-id'
        $this->assertNotNull($result);
        $this->assertSame('data', $result['secret']);
    }
}
