<?php

declare(strict_types=1);

namespace Tests\Service\Cache;

use Lunar\Service\Cache\CacheService;
use PHPUnit\Framework\TestCase;

class CacheServiceTest extends TestCase
{
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
}
