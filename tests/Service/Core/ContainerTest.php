<?php

declare(strict_types=1);

namespace Tests\Service\Core;

use Lunar\Exception\ContainerException;
use Lunar\Service\Core\Container;
use PHPUnit\Framework\TestCase;

class SimpleService
{
}

class ServiceWithDependency
{
    public SimpleService $dependency;

    public function __construct(SimpleService $simpleService)
    {
        $this->dependency = $simpleService;
    }
}

class ServiceWithNestedDependency
{
    public ServiceWithDependency $dependency;

    public function __construct(ServiceWithDependency $service)
    {
        $this->dependency = $service;
    }
}

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testGetInstantiatesSimpleClass(): void
    {
        $instance = $this->container->get(SimpleService::class);

        $this->assertInstanceOf(SimpleService::class, $instance);
    }

    public function testGetReturnsSameInstanceForSameClass(): void
    {
        $instance1 = $this->container->get(SimpleService::class);
        $instance2 = $this->container->get(SimpleService::class);

        $this->assertSame($instance1, $instance2);
    }

    public function testGetResolvesConstructorDependencies(): void
    {
        $instance = $this->container->get(ServiceWithDependency::class);

        $this->assertInstanceOf(ServiceWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleService::class, $instance->dependency);
    }

    public function testGetResolvesNestedDependencies(): void
    {
        $instance = $this->container->get(ServiceWithNestedDependency::class);

        $this->assertInstanceOf(ServiceWithNestedDependency::class, $instance);
        $this->assertInstanceOf(ServiceWithDependency::class, $instance->dependency);
        $this->assertInstanceOf(SimpleService::class, $instance->dependency->dependency);
    }

    public function testGetSharesDependenciesAsSingletons(): void
    {
        $service1 = $this->container->get(ServiceWithDependency::class);
        $simple = $this->container->get(SimpleService::class);

        $this->assertSame($simple, $service1->dependency);
    }

    public function testGetThrowsExceptionForNonInstantiableClass(): void
    {
        $this->expectException(ContainerException::class);

        $this->container->get(\Countable::class);
    }

    public function testHasReturnsTrueForExistingClass(): void
    {
        $this->assertTrue($this->container->has(SimpleService::class));
    }

    public function testHasReturnsFalseForNonExistingClass(): void
    {
        $this->assertFalse($this->container->has('NonExistingClass'));
    }

    public function testHasReturnsTrueForResolvedService(): void
    {
        $this->container->get(SimpleService::class);
        $this->assertTrue($this->container->has(SimpleService::class));
    }

    public function testCircularDependencyDetection(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        // Create classes that have circular dependency
        $this->container->get(CircularA::class);
    }
}

// Test classes for circular dependency
class CircularA
{
    public function __construct(CircularB $b)
    {
    }
}

class CircularB
{
    public function __construct(CircularA $a)
    {
    }
}
