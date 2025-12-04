<?php

declare(strict_types=1);

namespace Tests\Performance;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Router;
use Lunar\Service\Core\Template\LunarTemplateAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Performance benchmarks per Constitution IV requirements.
 *
 * Route matching: < 1ms
 * Template rendering: < 5ms
 */
class PerformanceBenchmarkTest extends TestCase
{
    private const ROUTE_TIME_LIMIT_MS = 1.0;
    private const TEMPLATE_TIME_LIMIT_MS = 5.0;
    private const ITERATIONS = 100;

    public function testRouteMatchingPerformance(): void
    {
        // Setup
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $router = new Router();
        $request = new Request();

        // Warm up
        $router->searchRoute($request);

        // Benchmark
        $start = hrtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $router->searchRoute($request);
        }
        $end = hrtime(true);

        $avgTimeMs = (($end - $start) / 1e6) / self::ITERATIONS;

        $this->assertLessThan(
            self::ROUTE_TIME_LIMIT_MS,
            $avgTimeMs,
            sprintf('Route matching took %.3fms, limit is %.1fms', $avgTimeMs, self::ROUTE_TIME_LIMIT_MS)
        );
    }

    public function testTemplateRenderingPerformance(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/template';

        // Create test template
        $testTemplate = $templatePath . '/perf_test.html.tpl';
        file_put_contents($testTemplate, '<p>Hello [[ name ]]</p>');

        try {
            $adapter = new LunarTemplateAdapter($templatePath);

            // Warm up
            $adapter->render('perf_test.html', ['name' => 'Test']);

            // Benchmark
            $start = hrtime(true);
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $adapter->render('perf_test.html', ['name' => 'Test']);
            }
            $end = hrtime(true);

            $avgTimeMs = (($end - $start) / 1e6) / self::ITERATIONS;

            $this->assertLessThan(
                self::TEMPLATE_TIME_LIMIT_MS,
                $avgTimeMs,
                sprintf('Template rendering took %.3fms, limit is %.1fms', $avgTimeMs, self::TEMPLATE_TIME_LIMIT_MS)
            );
        } finally {
            @unlink($testTemplate);
        }
    }

    public function testRouterInstantiationPerformance(): void
    {
        // Clear cache for clean test
        $cacheFile = Router::getCacheFile();
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        // Benchmark first instantiation (cold start)
        $start = hrtime(true);
        new Router();
        $coldStartMs = ($end = hrtime(true) - $start) / 1e6;

        // Should complete in reasonable time even with route scanning
        $this->assertLessThan(50, $coldStartMs, 'Cold router start should be < 50ms');

        // Benchmark cached instantiation (warm start)
        $start = hrtime(true);
        for ($i = 0; $i < 10; $i++) {
            new Router();
        }
        $warmAvgMs = ((hrtime(true) - $start) / 1e6) / 10;

        $this->assertLessThan(5, $warmAvgMs, 'Warm router start should be < 5ms');
    }

    public function testContainerResolutionPerformance(): void
    {
        $container = new \Lunar\Service\Core\Container();

        // Benchmark simple resolution
        $start = hrtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $container = new \Lunar\Service\Core\Container();
            $container->get(\Lunar\Service\Core\Http\Request::class);
        }
        $end = hrtime(true);

        $avgTimeMs = (($end - $start) / 1e6) / self::ITERATIONS;

        // Container resolution should be fast
        $this->assertLessThan(1.0, $avgTimeMs, 'Container resolution should be < 1ms');
    }

    public function testEncryptionPerformance(): void
    {
        $encryption = new \Lunar\Service\Security\EncryptionService('benchmark_key');
        $data = str_repeat('Benchmark data for encryption. ', 10);

        // Warm up
        $encrypted = $encryption->encrypt($data);
        $encryption->decrypt($encrypted);

        // Benchmark encryption
        $start = hrtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $encryption->encrypt($data);
        }
        $encryptAvgMs = ((hrtime(true) - $start) / 1e6) / self::ITERATIONS;

        // Benchmark decryption
        $encrypted = $encryption->encrypt($data);
        $start = hrtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $encryption->decrypt($encrypted);
        }
        $decryptAvgMs = ((hrtime(true) - $start) / 1e6) / self::ITERATIONS;

        // Encryption/decryption should be reasonably fast
        $this->assertLessThan(1.0, $encryptAvgMs, 'Encryption should be < 1ms');
        $this->assertLessThan(1.0, $decryptAvgMs, 'Decryption should be < 1ms');
    }
}
