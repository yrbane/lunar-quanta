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

    public function testContainerImplementsInterface(): void
    {
        $this->assertInstanceOf(\Lunar\Service\Core\ContainerInterface::class, $this->container);
    }

    public function testMultipleDifferentServicesResolved(): void
    {
        $simple = $this->container->get(SimpleService::class);
        $withDep = $this->container->get(ServiceWithDependency::class);
        $nested = $this->container->get(ServiceWithNestedDependency::class);

        $this->assertInstanceOf(SimpleService::class, $simple);
        $this->assertInstanceOf(ServiceWithDependency::class, $withDep);
        $this->assertInstanceOf(ServiceWithNestedDependency::class, $nested);
    }

    public function testNewContainerHasEmptyInstances(): void
    {
        $container = new Container();

        // Internal instances should be empty but the container should work
        $this->assertFalse($container->has('NonExistentService'));
    }

    public function testGetCreatesNewInstanceEachContainer(): void
    {
        $container1 = new Container();
        $container2 = new Container();

        $instance1 = $container1->get(SimpleService::class);
        $instance2 = $container2->get(SimpleService::class);

        // Different containers should create different instances
        $this->assertNotSame($instance1, $instance2);
    }

    public function testHasWorksBeforeGet(): void
    {
        $container = new Container();

        // Should return true for existing class even before instantiation
        $this->assertTrue($container->has(SimpleService::class));
    }

    public function testDependencySharing(): void
    {
        $withDep1 = $this->container->get(ServiceWithDependency::class);
        $withDep2 = $this->container->get(ServiceWithDependency::class);

        // Same instance
        $this->assertSame($withDep1, $withDep2);
        // Same dependency
        $this->assertSame($withDep1->dependency, $withDep2->dependency);
    }

    public function testNestedDependenciesShareInstances(): void
    {
        $nested = $this->container->get(ServiceWithNestedDependency::class);
        $withDep = $this->container->get(ServiceWithDependency::class);
        $simple = $this->container->get(SimpleService::class);

        // All should share the same SimpleService
        $this->assertSame($simple, $nested->dependency->dependency);
        $this->assertSame($simple, $withDep->dependency);
    }

    public function testGetThrowsForInterface(): void
    {
        $this->expectException(ContainerException::class);

        $this->container->get(\Stringable::class);
    }

    public function testGetThrowsForAbstractClass(): void
    {
        $this->expectException(ContainerException::class);

        // PDO is not abstract, let's try with a known abstract class
        $this->container->get(\FilterIterator::class);
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
