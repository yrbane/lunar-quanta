<?php

declare(strict_types=1);

namespace Tests\Attribute;

use Lunar\Attribute\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $route = new Route('/test');

        $this->assertSame('/test', $route->path);
        $this->assertSame(['GET'], $route->methods);
        $this->assertNull($route->name);
    }

    public function testConstructorWithCustomMethods(): void
    {
        $route = new Route('/api/users', ['POST', 'PUT']);

        $this->assertSame('/api/users', $route->path);
        $this->assertSame(['POST', 'PUT'], $route->methods);
    }

    public function testConstructorWithName(): void
    {
        $route = new Route('/users', ['GET'], 'user_list');

        $this->assertSame('/users', $route->path);
        $this->assertSame('user_list', $route->name);
    }

    public function testConstructorWithAllParameters(): void
    {
        $route = new Route('/api/products/{id}', ['GET', 'DELETE'], 'product_show');

        $this->assertSame('/api/products/{id}', $route->path);
        $this->assertSame(['GET', 'DELETE'], $route->methods);
        $this->assertSame('product_show', $route->name);
    }

    public function testRouteIsAttribute(): void
    {
        $reflection = new \ReflectionClass(Route::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertCount(1, $attributes);

        $attr = $attributes[0]->newInstance();
        $this->assertSame(\Attribute::TARGET_METHOD, $attr->flags);
    }

    public function testEmptyPath(): void
    {
        $route = new Route('');

        $this->assertSame('', $route->path);
    }

    public function testMultipleHttpMethods(): void
    {
        $route = new Route('/resource', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);

        $this->assertCount(5, $route->methods);
        $this->assertContains('GET', $route->methods);
        $this->assertContains('POST', $route->methods);
        $this->assertContains('PUT', $route->methods);
        $this->assertContains('PATCH', $route->methods);
        $this->assertContains('DELETE', $route->methods);
    }
}
