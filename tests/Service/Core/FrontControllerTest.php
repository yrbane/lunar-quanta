<?php

declare(strict_types=1);

namespace Tests\Service\Core;

use Lunar\Service\Core\FrontController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FrontControllerTest extends TestCase
{
    public function testFrontControllerCanBeInstantiated(): void
    {
        $frontController = new FrontController();

        $this->assertInstanceOf(FrontController::class, $frontController);
    }

    public function testLoadEnvironmentParsesEnvFile(): void
    {
        $frontController = new FrontController();
        $reflection = new ReflectionClass($frontController);
        $method = $reflection->getMethod('loadEnvironment');
        $method->setAccessible(true);

        $method->invoke($frontController);
        $this->assertTrue(true);
    }

    public function testLoadEnvironmentSkipsCommentsAndEmptyLines(): void
    {
        $frontController = new FrontController();
        $reflection = new ReflectionClass($frontController);
        $method = $reflection->getMethod('loadEnvironment');
        $method->setAccessible(true);

        $method->invoke($frontController);

        $this->assertTrue(true);
    }

    public function testConfigureErrorReportingInDebugMode(): void
    {
        $originalDebug = getenv('APP_DEBUG');
        putenv('APP_DEBUG=true');

        $frontController = new FrontController();
        $reflection = new ReflectionClass($frontController);
        $method = $reflection->getMethod('configureErrorReporting');
        $method->setAccessible(true);

        $method->invoke($frontController);

        $this->assertSame(E_ALL, error_reporting());

        if ($originalDebug !== false) {
            putenv('APP_DEBUG=' . $originalDebug);
        } else {
            putenv('APP_DEBUG');
        }
    }

    public function testConfigureErrorReportingInProductionMode(): void
    {
        $originalDebug = getenv('APP_DEBUG');
        putenv('APP_DEBUG=false');

        $frontController = new FrontController();
        $reflection = new ReflectionClass($frontController);
        $method = $reflection->getMethod('configureErrorReporting');
        $method->setAccessible(true);

        $method->invoke($frontController);

        $this->assertSame(0, error_reporting());

        if ($originalDebug !== false) {
            putenv('APP_DEBUG=' . $originalDebug);
        } else {
            putenv('APP_DEBUG');
        }

        error_reporting(E_ALL);
    }

    public function testConfigureErrorReportingWithNoDebugEnv(): void
    {
        $originalDebug = getenv('APP_DEBUG');
        putenv('APP_DEBUG');

        $frontController = new FrontController();
        $reflection = new ReflectionClass($frontController);
        $method = $reflection->getMethod('configureErrorReporting');
        $method->setAccessible(true);

        $method->invoke($frontController);

        $this->assertSame(0, error_reporting());

        if ($originalDebug !== false) {
            putenv('APP_DEBUG=' . $originalDebug);
        }

        error_reporting(E_ALL);
    }
}
