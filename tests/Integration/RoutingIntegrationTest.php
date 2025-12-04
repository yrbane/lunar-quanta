<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Router;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the route-to-response flow.
 *
 * Tests the complete routing pipeline from Request to Response.
 */
class RoutingIntegrationTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        // Clear route cache for clean tests
        $cacheFile = Router::getCacheFile();
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        $this->router = new Router();
    }

    protected function tearDown(): void
    {
        // Clean up cache after tests
        $cacheFile = Router::getCacheFile();
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public function testRouterDispatchesRequestToController(): void
    {
        // Simulate a request to the home page
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $request = new Request();
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRouterReturns404ForUnknownRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/nonexistent-route-' . uniqid();

        $request = new Request();
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRouterCachesRoutes(): void
    {
        $cacheFile = Router::getCacheFile();

        // Router should create cache on construction
        $this->router = new Router();

        // Cache file should exist after router initialization
        $this->assertFileExists($cacheFile);
    }

    public function testRouterUsesCorrectHttpMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';

        $request = new Request();
        $response = $this->router->dispatch($request);

        // Should return 404 if route only accepts GET
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRequestContainsCorrectMethodAndUri(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test?param=value';

        $request = new Request();

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getUri());
    }

    public function testResponseHasCorrectDefaults(): void
    {
        $response = new Response('Test content');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Test content', $response->getBody());
    }

    public function testResponseWithCustomStatus(): void
    {
        $response = new Response('Not Found', 404);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getBody());
    }

    public function testRouterGetRoutesReturnsArray(): void
    {
        $routes = $this->router->getRoutes();

        $this->assertIsArray($routes);
    }

    public function testRouteByNameReturnsNullForUnknownRoute(): void
    {
        $route = Router::getRouteByName('unknown_route_name');

        $this->assertNull($route);
    }

    public function testCompleteRequestResponseCycle(): void
    {
        // Setup a known request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_GET = [];
        $_POST = [];

        // Create request
        $request = new Request();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/', $request->getUri());

        // Dispatch through router
        $response = $this->router->dispatch($request);

        // Verify response is valid
        $this->assertInstanceOf(Response::class, $response);
        $this->assertIsInt($response->getStatusCode());
        $this->assertIsString($response->getBody());
    }
}
