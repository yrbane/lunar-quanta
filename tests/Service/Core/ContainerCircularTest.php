<?php

declare(strict_types=1);

namespace Tests\Service\Core;

use Lunar\Exception\ContainerException;
use Lunar\Service\Core\Container;
use PHPUnit\Framework\TestCase;

/**
 * Dedicated tests for Container circular dependency detection.
 */
class ContainerCircularTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testDirectCircularDependencyIsDetected(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->container->get(DirectCircularA::class);
    }

    public function testIndirectCircularDependencyIsDetected(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->container->get(IndirectA::class);
    }

    public function testCircularDependencyMessageContainsChain(): void
    {
        try {
            $this->container->get(DirectCircularA::class);
            $this->fail('Expected ContainerException was not thrown');
        } catch (ContainerException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('DirectCircularA', $message);
            $this->assertStringContainsString('DirectCircularB', $message);
            $this->assertStringContainsString('->', $message);
        }
    }

    public function testSelfReferencingDependencyIsDetected(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->container->get(SelfReferencing::class);
    }

    public function testLongCircularChainIsDetected(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->container->get(ChainA::class);
    }

    public function testNonCircularDependencyWorks(): void
    {
        $instance = $this->container->get(NonCircularEnd::class);
        $this->assertInstanceOf(NonCircularEnd::class, $instance);

        $instance = $this->container->get(NonCircularStart::class);
        $this->assertInstanceOf(NonCircularStart::class, $instance);
        $this->assertInstanceOf(NonCircularMiddle::class, $instance->middle);
        $this->assertInstanceOf(NonCircularEnd::class, $instance->middle->end);
    }
}

// Direct circular dependency: A -> B -> A
class DirectCircularA
{
    public function __construct(DirectCircularB $b)
    {
    }
}

class DirectCircularB
{
    public function __construct(DirectCircularA $a)
    {
    }
}

// Indirect circular dependency: A -> B -> C -> A
class IndirectA
{
    public function __construct(IndirectB $b)
    {
    }
}

class IndirectB
{
    public function __construct(IndirectC $c)
    {
    }
}

class IndirectC
{
    public function __construct(IndirectA $a)
    {
    }
}

// Self-referencing dependency
class SelfReferencing
{
    public function __construct(SelfReferencing $self)
    {
    }
}

// Long chain: A -> B -> C -> D -> E -> A
class ChainA
{
    public function __construct(ChainB $b)
    {
    }
}

class ChainB
{
    public function __construct(ChainC $c)
    {
    }
}

class ChainC
{
    public function __construct(ChainD $d)
    {
    }
}

class ChainD
{
    public function __construct(ChainE $e)
    {
    }
}

class ChainE
{
    public function __construct(ChainA $a)
    {
    }
}

// Non-circular dependencies for comparison
class NonCircularEnd
{
}

class NonCircularMiddle
{
    public NonCircularEnd $end;

    public function __construct(NonCircularEnd $end)
    {
        $this->end = $end;
    }
}

class NonCircularStart
{
    public NonCircularMiddle $middle;

    public function __construct(NonCircularMiddle $middle)
    {
        $this->middle = $middle;
    }
}
