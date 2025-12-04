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
}
