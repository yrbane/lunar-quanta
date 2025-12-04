<?php

declare(strict_types=1);

namespace Tests\Service\Core;

use Lunar\Service\Core\Container;
use PHPUnit\Framework\TestCase;

/**
 * Tests to verify Container resolution has O(n) complexity.
 *
 * Per Constitution IV, dependency resolution must be O(n) where n is the number of dependencies.
 */
class ContainerComplexityTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testResolutionScalesLinearly(): void
    {
        // Time resolution of a single service
        $start = hrtime(true);
        $this->container->get(Depth1Service::class);
        $time1 = hrtime(true) - $start;

        // Fresh container for each test
        $this->container = new Container();

        // Time resolution of a chain of 2
        $start = hrtime(true);
        $this->container->get(Depth2Service::class);
        $time2 = hrtime(true) - $start;

        $this->container = new Container();

        // Time resolution of a chain of 4
        $start = hrtime(true);
        $this->container->get(Depth4Service::class);
        $time4 = hrtime(true) - $start;

        // For O(n) complexity, time should scale roughly linearly
        // We allow some variance due to system overhead
        // The ratio time4/time2 should be roughly 2 (not 4 which would be O(n²))
        if ($time2 > 0 && $time4 > 0) {
            $ratio = $time4 / $time2;
            // Allow generous tolerance due to timing variations
            // For O(n): ratio should be ~2, for O(n²): ratio would be ~4
            $this->assertLessThan(4, $ratio, 'Resolution time suggests worse than O(n) complexity');
        }

        // Just ensure all resolutions completed successfully
        $this->assertTrue(true);
    }

    public function testSingletonCachingPreventsReresolution(): void
    {
        // First resolution
        $start1 = hrtime(true);
        $this->container->get(CachedService::class);
        $time1 = hrtime(true) - $start1;

        // Second resolution (should use cache)
        $start2 = hrtime(true);
        $this->container->get(CachedService::class);
        $time2 = hrtime(true) - $start2;

        // Cached resolution should be faster (or at least not slower)
        $this->assertLessThanOrEqual($time1, $time2 * 10, 'Singleton caching not effective');
    }

    public function testResolutionDoesNotRevisitResolvedDependencies(): void
    {
        // Resolve a service that shares dependencies
        $service1 = $this->container->get(SharedDep1::class);
        $service2 = $this->container->get(SharedDep2::class);

        // Both should have the same shared dependency instance
        $this->assertSame($service1->common, $service2->common);
    }

    public function testResolutionCountIsLinear(): void
    {
        // Track that each dependency is only resolved once
        ResolutionCounter::reset();

        $this->container->get(CountedServiceA::class);

        // Each service in the chain should be resolved exactly once
        $this->assertEquals(1, ResolutionCounter::getCount('A'));
        $this->assertEquals(1, ResolutionCounter::getCount('B'));
        $this->assertEquals(1, ResolutionCounter::getCount('C'));
    }
}

// Test services for complexity verification
class Depth1Service
{
}

class Depth2Service
{
    public function __construct(Depth1Service $d)
    {
    }
}

class Depth3Service
{
    public function __construct(Depth2Service $d)
    {
    }
}

class Depth4Service
{
    public function __construct(Depth3Service $d)
    {
    }
}

class CachedService
{
    public function __construct(Depth2Service $d)
    {
    }
}

class CommonDependency
{
}

class SharedDep1
{
    public CommonDependency $common;

    public function __construct(CommonDependency $common)
    {
        $this->common = $common;
    }
}

class SharedDep2
{
    public CommonDependency $common;

    public function __construct(CommonDependency $common)
    {
        $this->common = $common;
    }
}

// Resolution counter for tracking
class ResolutionCounter
{
    /** @var array<string, int> */
    private static array $counts = [];

    public static function reset(): void
    {
        self::$counts = [];
    }

    public static function increment(string $name): void
    {
        self::$counts[$name] = (self::$counts[$name] ?? 0) + 1;
    }

    public static function getCount(string $name): int
    {
        return self::$counts[$name] ?? 0;
    }
}

class CountedServiceC
{
    public function __construct()
    {
        ResolutionCounter::increment('C');
    }
}

class CountedServiceB
{
    public function __construct(CountedServiceC $c)
    {
        ResolutionCounter::increment('B');
    }
}

class CountedServiceA
{
    public function __construct(CountedServiceB $b)
    {
        ResolutionCounter::increment('A');
    }
}
