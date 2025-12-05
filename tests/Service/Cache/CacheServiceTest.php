<?php

declare(strict_types=1);

namespace Tests\Service\Cache;

use Lunar\Service\Cache\CacheService;
use PHPUnit\Framework\TestCase;

class CacheServiceTest extends TestCase
{
    private string $testCacheDir;

    protected function setUp(): void
    {
        // Create a test cache directory
        $this->testCacheDir = sys_get_temp_dir() . '/lunar_test_cache_' . uniqid();
    }

    protected function tearDown(): void
    {
        // Clean up test directory if it exists
        if (is_dir($this->testCacheDir)) {
            $this->recursiveDelete($this->testCacheDir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        rmdir($dir);
    }

    public function testClearReturnsArray(): void
    {
        $service = new CacheService();
        $result = $service->clear();

        $this->assertIsArray($result);
    }

    public function testClearReturnsArrayOfResults(): void
    {
        $service = new CacheService();
        $results = $service->clear();

        foreach ($results as $result) {
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
            $this->assertContains($result['status'], ['success', 'error']);
        }
    }

    public function testClearCanBeCalledMultipleTimes(): void
    {
        $service = new CacheService();

        $result1 = $service->clear();
        $result2 = $service->clear();

        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
    }

    public function testResultStatusIsString(): void
    {
        $service = new CacheService();
        $results = $service->clear();

        foreach ($results as $result) {
            $this->assertIsString($result['status']);
            $this->assertIsString($result['message']);
        }
    }

    public function testClearReturnsNonEmptyArray(): void
    {
        $service = new CacheService();
        $results = $service->clear();

        // Should have at least one result message
        $this->assertNotEmpty($results);
    }

    public function testResultsHaveExpectedStructure(): void
    {
        $service = new CacheService();
        $results = $service->clear();

        foreach ($results as $result) {
            // Verify exact keys
            $this->assertCount(2, $result);
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
        }
    }

    public function testClearServiceInstantiation(): void
    {
        $service = new CacheService();
        $this->assertInstanceOf(CacheService::class, $service);
    }

    public function testResultMessagesAreNotEmpty(): void
    {
        $service = new CacheService();
        $results = $service->clear();

        foreach ($results as $result) {
            $this->assertNotEmpty($result['message']);
            $this->assertTrue(strlen($result['message']) > 0);
        }
    }

    public function testMultipleInstancesClearIndependently(): void
    {
        $service1 = new CacheService();
        $service2 = new CacheService();

        $result1 = $service1->clear();
        $result2 = $service2->clear();

        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
    }

    public function testClearReturnsSameFormatEachTime(): void
    {
        $service = new CacheService();

        for ($i = 0; $i < 3; $i++) {
            $results = $service->clear();

            $this->assertIsArray($results);
            foreach ($results as $result) {
                $this->assertArrayHasKey('status', $result);
                $this->assertArrayHasKey('message', $result);
            }
        }
    }

    public function testStatusValuesAreValid(): void
    {
        $service = new CacheService();
        $results = $service->clear();
        $validStatuses = ['success', 'error'];

        foreach ($results as $result) {
            $this->assertContains(
                $result['status'],
                $validStatuses,
                "Status '{$result['status']}' is not a valid status"
            );
        }
    }
}
