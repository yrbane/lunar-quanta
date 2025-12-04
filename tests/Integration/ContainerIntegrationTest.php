<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lunar\Service\Core\Container;
use Lunar\Service\Core\ContainerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Container dependency injection.
 *
 * Tests real-world DI scenarios with multiple services.
 */
class ContainerIntegrationTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testContainerImplementsInterface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container);
    }

    public function testContainerResolvesSimpleService(): void
    {
        $service = $this->container->get(SimpleIntegrationService::class);

        $this->assertInstanceOf(SimpleIntegrationService::class, $service);
    }

    public function testContainerResolvesSingletonServices(): void
    {
        $service1 = $this->container->get(SimpleIntegrationService::class);
        $service2 = $this->container->get(SimpleIntegrationService::class);

        $this->assertSame($service1, $service2);
    }

    public function testContainerResolvesServiceWithDependencies(): void
    {
        $service = $this->container->get(ServiceWithIntegrationDependency::class);

        $this->assertInstanceOf(ServiceWithIntegrationDependency::class, $service);
        $this->assertInstanceOf(SimpleIntegrationService::class, $service->dependency);
    }

    public function testContainerResolvesDeepDependencyChain(): void
    {
        $service = $this->container->get(DeepServiceA::class);

        $this->assertInstanceOf(DeepServiceA::class, $service);
        $this->assertInstanceOf(DeepServiceB::class, $service->b);
        $this->assertInstanceOf(DeepServiceC::class, $service->b->c);
        $this->assertInstanceOf(DeepServiceD::class, $service->b->c->d);
    }

    public function testContainerSharesDependenciesAcrossServices(): void
    {
        // Get two services that share the same dependency
        $service1 = $this->container->get(SharerOne::class);
        $service2 = $this->container->get(SharerTwo::class);

        // They should have the same instance of SharedDependency
        $this->assertSame($service1->shared, $service2->shared);
    }

    public function testContainerHasReturnsTrueForExistingClass(): void
    {
        $this->assertTrue($this->container->has(SimpleIntegrationService::class));
    }

    public function testContainerHasReturnsFalseForNonExistingClass(): void
    {
        $this->assertFalse($this->container->has('NonExisting\\Class\\Name'));
    }

    public function testContainerHasReturnsTrueAfterResolution(): void
    {
        $this->container->get(SimpleIntegrationService::class);

        $this->assertTrue($this->container->has(SimpleIntegrationService::class));
    }

    public function testContainerResolvesMultipleIndependentServices(): void
    {
        $service1 = $this->container->get(IndependentA::class);
        $service2 = $this->container->get(IndependentB::class);

        $this->assertInstanceOf(IndependentA::class, $service1);
        $this->assertInstanceOf(IndependentB::class, $service2);
        $this->assertNotSame($service1, $service2);
    }
}

// Test classes for integration tests
class SimpleIntegrationService
{
}

class ServiceWithIntegrationDependency
{
    public SimpleIntegrationService $dependency;

    public function __construct(SimpleIntegrationService $dep)
    {
        $this->dependency = $dep;
    }
}

// Deep dependency chain
class DeepServiceD
{
}

class DeepServiceC
{
    public DeepServiceD $d;

    public function __construct(DeepServiceD $d)
    {
        $this->d = $d;
    }
}

class DeepServiceB
{
    public DeepServiceC $c;

    public function __construct(DeepServiceC $c)
    {
        $this->c = $c;
    }
}

class DeepServiceA
{
    public DeepServiceB $b;

    public function __construct(DeepServiceB $b)
    {
        $this->b = $b;
    }
}

// Shared dependency test
class SharedDependency
{
}

class SharerOne
{
    public SharedDependency $shared;

    public function __construct(SharedDependency $shared)
    {
        $this->shared = $shared;
    }
}

class SharerTwo
{
    public SharedDependency $shared;

    public function __construct(SharedDependency $shared)
    {
        $this->shared = $shared;
    }
}

// Independent services
class IndependentA
{
}

class IndependentB
{
}
