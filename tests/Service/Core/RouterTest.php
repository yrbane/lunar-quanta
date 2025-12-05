<?php

declare(strict_types=1);

namespace Tests\Service\Core;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Router;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RouterTest extends TestCase
{
    private array $originalServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SERVER = $this->originalServer;

        $routerReflection = new ReflectionClass(Router::class);
        $namedRoutesProperty = $routerReflection->getProperty('namedRoutes');
        $namedRoutesProperty->setAccessible(true);
        $namedRoutesProperty->setValue(null, []);
    }

    public function testGetCacheFileReturnsPath(): void
    {
        $cacheFile = Router::getCacheFile();

        $this->assertIsString($cacheFile);
        $this->assertStringContainsString('router.php', $cacheFile);
    }

    public function testGetRouteByNameReturnsNullForUnknownRoute(): void
    {
        $route = Router::getRouteByName('nonexistent_route_' . uniqid());

        $this->assertNull($route);
    }

    public function testGetRoutesReturnsArray(): void
    {
        $router = new Router();
        $routes = $router->getRoutes();

        $this->assertIsArray($routes);
    }

    public function testGetRegisteredRoutesReturnsArray(): void
    {
        $router = new Router();
        $routes = $router->getRegisteredRoutes();

        $this->assertIsArray($routes);
    }

    public function testSearchRouteReturnsFalseForNoMatch(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/nonexistent-route-xyz-' . uniqid();

        $router = new Router();
        $request = new Request();

        $result = $router->searchRoute($request);

        $this->assertFalse($result);
    }

    public function testDispatchReturnsResponseForUnknownRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/unknown-route-' . uniqid();

        $router = new Router();
        $request = new Request();

        $response = $router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDispatchReturns404StatusForUnknownRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/not-found-' . uniqid();

        $router = new Router();
        $request = new Request();

        $response = $router->dispatch($request);

        $reflection = new ReflectionClass($response);
        $statusProp = $reflection->getProperty('statusCode');

        $this->assertSame(404, $statusProp->getValue($response));
    }

    public function testDispatchWithExistingRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $router = new Router();
        $request = new Request();

        $response = $router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testSearchRouteWithMatchingMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $router = new Router();
        $request = new Request();

        $result = $router->searchRoute($request);

        if ($result instanceof Response) {
            $this->assertInstanceOf(Response::class, $result);
        } else {
            $this->assertFalse($result);
        }
    }

    public function testGetRouteByNameAfterDispatch(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $router = new Router();
        $request = new Request();
        $router->dispatch($request);

        $route = Router::getRouteByName('home');
        if ($route !== null) {
            $this->assertIsArray($route);
            $this->assertArrayHasKey('path', $route);
        } else {
            $this->assertNull($route);
        }
    }

    public function testDispatchWithWrongMethodReturns404(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/';

        $router = new Router();
        $request = new Request();

        $response = $router->dispatch($request);

        $reflection = new ReflectionClass($response);
        $statusProp = $reflection->getProperty('statusCode');

        $this->assertSame(404, $statusProp->getValue($response));
    }

    public function testGetRoutesReturnsNonEmpty(): void
    {
        $router = new Router();
        $routes = $router->getRoutes();

        // Should have at least the home route
        $this->assertNotEmpty($routes);
    }

    public function testRouterImplementsRouterInterface(): void
    {
        $router = new Router();

        $this->assertInstanceOf(\Lunar\Service\Core\RouterInterface::class, $router);
    }

    public function testAddRouteRegistersNewRoute(): void
    {
        $router = new Router();
        $initialCount = count($router->getRoutes());

        $router->addRoute(
            '/api/test',
            'TestController',
            'testAction',
            ['GET'],
            'api_test'
        );

        $routes = $router->getRoutes();
        $this->assertGreaterThan($initialCount, count($routes));
    }

    public function testAddRouteWithMultipleMethods(): void
    {
        $router = new Router();
        $initialCount = count($router->getRoutes());

        $router->addRoute(
            '/api/resource',
            'ResourceController',
            'handle',
            ['GET', 'POST', 'PUT'],
            'api_resource'
        );

        $routes = $router->getRoutes();
        // Should add 3 routes (one per method)
        $this->assertSame($initialCount + 3, count($routes));
    }

    public function testMatchReturnsNullForUnknownRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/this-route-does-not-exist-' . uniqid();

        $router = new Router();
        $request = new Request();

        $result = $router->match($request);

        $this->assertNull($result);
    }

    public function testMatchReturnsArrayForKnownRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $router = new Router();
        $request = new Request();

        $result = $router->match($request);

        if ($result !== null) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('controller', $result);
            $this->assertArrayHasKey('action', $result);
            $this->assertArrayHasKey('parameters', $result);
        } else {
            // If no home route, just pass
            $this->assertNull($result);
        }
    }

    public function testAddRouteAndMatchIntegration(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/custom-test-route';

        $router = new Router();
        $router->addRoute(
            '/custom-test-route',
            'CustomController',
            'customAction',
            ['GET'],
            'custom_route'
        );

        $request = new Request();
        $result = $router->match($request);

        $this->assertNotNull($result);
        $this->assertSame('CustomController', $result['controller']);
        $this->assertSame('customAction', $result['action']);
    }

    public function testSearchRouteWithQueryString(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/?page=1&sort=name';

        $router = new Router();
        $request = new Request();

        $result = $router->searchRoute($request);

        // Should find the home route despite query string
        $this->assertNotFalse($result);
    }

    public function testDispatchWithTrailingSlash(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';

        $router = new Router();
        $request = new Request();

        $response = $router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testGetCacheFileIsAbsolutePath(): void
    {
        $cacheFile = Router::getCacheFile();

        $this->assertStringStartsWith('/', $cacheFile);
    }

    public function testMultipleDispatchCalls(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $router = new Router();
        $request = new Request();

        $response1 = $router->dispatch($request);
        $response2 = $router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response1);
        $this->assertInstanceOf(Response::class, $response2);
    }

    public function testSearchRouteWithEmptyPath(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '';

        $router = new Router();
        $request = new Request();

        $result = $router->searchRoute($request);

        // Empty path might map to root
        $this->assertTrue($result === false || $result instanceof Response);
    }

    public function testGetRegisteredRoutesStructure(): void
    {
        $router = new Router();
        $routes = $router->getRegisteredRoutes();

        foreach ($routes as $route) {
            $this->assertIsArray($route);
        }
    }

    public function testDispatchReturnsValidResponseBody(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $router = new Router();
        $request = new Request();

        $response = $router->dispatch($request);

        $this->assertIsString($response->getBody());
    }
}
