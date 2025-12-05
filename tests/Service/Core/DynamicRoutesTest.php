<?php
/**
 * Tests des routes dynamiques du Router.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE ROUTE DYNAMIQUE ?
 * =============================================================================
 *
 * Une route dynamique contient des PLACEHOLDERS (espaces réservés) qui
 * capturent des parties variables de l'URL :
 *
 * ```
 * ROUTE STATIQUE                    ROUTE DYNAMIQUE
 *
 * /users                            /user/{id}
 *    │                                 │
 *    └─ Correspond UNIQUEMENT         └─ Correspond à :
 *       à /users                         /user/1
 *                                        /user/42
 *                                        /user/john
 *                                        etc.
 * ```
 *
 * @package Tests\Service\Core
 */
declare(strict_types=1);

namespace Tests\Service\Core;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Router;
use PHPUnit\Framework\TestCase;

class DynamicRoutesTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        // Crée un nouveau router
        $this->router = new Router();
    }

    // =========================================================================
    // TESTS DE LA MÉTHODE match() AVEC ROUTES STATIQUES
    // =========================================================================

    public function testMatchReturnsNullForUnknownRoute(): void
    {
        $request = $this->createRequest('GET', '/unknown-route-xyz');

        $result = $this->router->match($request);

        $this->assertNull($result);
    }

    // =========================================================================
    // TESTS DE LA MÉTHODE addRoute() AVEC ROUTES DYNAMIQUES
    // =========================================================================

    public function testAddRouteWithSingleParameter(): void
    {
        $this->router->addRoute(
            '/user/{id}',
            'TestController',
            'show',
            ['GET'],
            'user_show'
        );

        $routes = $this->router->getRoutes();

        $this->assertArrayHasKey('user_show_GET', $routes);
        $this->assertSame('/user/{id}', $routes['user_show_GET']['path']);
    }

    public function testAddRouteWithMultipleParameters(): void
    {
        $this->router->addRoute(
            '/user/{userId}/post/{postId}',
            'TestController',
            'showPost',
            ['GET'],
            'user_post'
        );

        $routes = $this->router->getRoutes();

        $this->assertArrayHasKey('user_post_GET', $routes);
        $this->assertSame('/user/{userId}/post/{postId}', $routes['user_post_GET']['path']);
    }

    // =========================================================================
    // TESTS D'EXTRACTION DE PARAMÈTRES
    // =========================================================================

    public function testMatchExtractsSingleParameter(): void
    {
        $this->router->addRoute(
            '/user/{id}',
            'TestController',
            'show',
            ['GET'],
            'user_show'
        );

        $request = $this->createRequest('GET', '/user/42');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertSame('TestController', $result['controller']);
        $this->assertSame('show', $result['action']);
        $this->assertSame(['id' => '42'], $result['parameters']);
    }

    public function testMatchExtractsMultipleParameters(): void
    {
        $this->router->addRoute(
            '/user/{userId}/post/{postId}',
            'TestController',
            'showPost',
            ['GET'],
            'user_post'
        );

        $request = $this->createRequest('GET', '/user/42/post/123');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertSame([
            'userId' => '42',
            'postId' => '123'
        ], $result['parameters']);
    }

    public function testMatchExtractsSlugParameter(): void
    {
        $this->router->addRoute(
            '/blog/{slug}',
            'BlogController',
            'show',
            ['GET'],
            'blog_show'
        );

        $request = $this->createRequest('GET', '/blog/mon-super-article');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertSame(['slug' => 'mon-super-article'], $result['parameters']);
    }

    public function testMatchWithApiVersionParameter(): void
    {
        $this->router->addRoute(
            '/api/{version}/users',
            'ApiController',
            'listUsers',
            ['GET'],
            'api_users'
        );

        $request = $this->createRequest('GET', '/api/v2/users');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertSame(['version' => 'v2'], $result['parameters']);
    }

    // =========================================================================
    // TESTS DE NON-CORRESPONDANCE
    // =========================================================================

    public function testMatchReturnsNullForWrongMethod(): void
    {
        $this->router->addRoute(
            '/user/{id}',
            'TestController',
            'show',
            ['GET'],
            'user_show'
        );

        $request = $this->createRequest('POST', '/user/42');
        $result = $this->router->match($request);

        $this->assertNull($result);
    }

    public function testMatchReturnsNullForPartialMatch(): void
    {
        $this->router->addRoute(
            '/user/{id}',
            'TestController',
            'show',
            ['GET'],
            'user_show'
        );

        // L'URL a un segment supplémentaire
        $request = $this->createRequest('GET', '/user/42/extra');
        $result = $this->router->match($request);

        $this->assertNull($result);
    }

    public function testMatchReturnsNullForMissingParameter(): void
    {
        $this->router->addRoute(
            '/user/{id}',
            'TestController',
            'show',
            ['GET'],
            'user_show'
        );

        // L'URL n'a pas le paramètre {id}
        $request = $this->createRequest('GET', '/user');
        $result = $this->router->match($request);

        $this->assertNull($result);
    }

    // =========================================================================
    // TESTS DE ROUTES STATIQUES VS DYNAMIQUES
    // =========================================================================

    public function testStaticRouteStillWorks(): void
    {
        $this->router->addRoute(
            '/users',
            'UserController',
            'list',
            ['GET'],
            'users_list'
        );

        $request = $this->createRequest('GET', '/users');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertSame([], $result['parameters']);
    }

    public function testStaticAndDynamicRoutesCoexist(): void
    {
        // Route statique
        $this->router->addRoute(
            '/users',
            'UserController',
            'list',
            ['GET'],
            'users_list'
        );

        // Route dynamique
        $this->router->addRoute(
            '/user/{id}',
            'UserController',
            'show',
            ['GET'],
            'user_show'
        );

        // Test route statique
        $request1 = $this->createRequest('GET', '/users');
        $result1 = $this->router->match($request1);
        $this->assertNotNull($result1);
        $this->assertSame('list', $result1['action']);

        // Test route dynamique
        $request2 = $this->createRequest('GET', '/user/42');
        $result2 = $this->router->match($request2);
        $this->assertNotNull($result2);
        $this->assertSame('show', $result2['action']);
        $this->assertSame(['id' => '42'], $result2['parameters']);
    }

    // =========================================================================
    // TESTS DES PARAMÈTRES DANS REQUEST
    // =========================================================================

    public function testRequestGetRouteParamReturnsDefault(): void
    {
        $request = $this->createRequest('GET', '/test');

        $this->assertNull($request->getRouteParam('id'));
        $this->assertSame('default', $request->getRouteParam('id', 'default'));
    }

    public function testRequestGetRouteParamsReturnsEmptyArray(): void
    {
        $request = $this->createRequest('GET', '/test');

        $this->assertSame([], $request->getRouteParams());
    }

    public function testRequestHasRouteParamReturnsFalse(): void
    {
        $request = $this->createRequest('GET', '/test');

        $this->assertFalse($request->hasRouteParam('id'));
    }

    public function testRequestRouteParamsViaSetAttribute(): void
    {
        $request = $this->createRequest('GET', '/user/42');

        // Simule ce que fait le Router
        $request->setAttribute('_route_params', ['id' => '42']);
        $request->setAttribute('_route_id', '42');

        $this->assertSame('42', $request->getRouteParam('id'));
        $this->assertSame(['id' => '42'], $request->getRouteParams());
        $this->assertTrue($request->hasRouteParam('id'));
        $this->assertFalse($request->hasRouteParam('other'));
    }

    // =========================================================================
    // TESTS DE getLastMatchedParams()
    // =========================================================================

    public function testGetLastMatchedParamsAfterMatch(): void
    {
        $this->router->addRoute(
            '/user/{id}',
            'TestController',
            'show',
            ['GET'],
            'user_show'
        );

        $request = $this->createRequest('GET', '/user/42');
        $this->router->match($request);

        $params = $this->router->getLastMatchedParams();

        $this->assertSame(['id' => '42'], $params);
    }

    public function testGetLastMatchedParamsEmptyAfterNoMatch(): void
    {
        $this->router->addRoute(
            '/user/{id}',
            'TestController',
            'show',
            ['GET'],
            'user_show'
        );

        $request = $this->createRequest('GET', '/unknown');
        $this->router->match($request);

        $params = $this->router->getLastMatchedParams();

        $this->assertSame([], $params);
    }

    // =========================================================================
    // TESTS DE CAS LIMITES
    // =========================================================================

    public function testParameterWithNumbers(): void
    {
        $this->router->addRoute(
            '/item/{id}',
            'ItemController',
            'show',
            ['GET'],
            'item_show'
        );

        $request = $this->createRequest('GET', '/item/12345');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertSame(['id' => '12345'], $result['parameters']);
    }

    public function testParameterWithLettersAndNumbers(): void
    {
        $this->router->addRoute(
            '/order/{reference}',
            'OrderController',
            'show',
            ['GET'],
            'order_show'
        );

        $request = $this->createRequest('GET', '/order/ORD2024001');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertSame(['reference' => 'ORD2024001'], $result['parameters']);
    }

    public function testParameterWithDashes(): void
    {
        $this->router->addRoute(
            '/article/{slug}',
            'ArticleController',
            'show',
            ['GET'],
            'article_show'
        );

        $request = $this->createRequest('GET', '/article/my-awesome-article-2024');
        $result = $this->router->match($request);

        $this->assertNotNull($result);
        $this->assertSame(['slug' => 'my-awesome-article-2024'], $result['parameters']);
    }

    public function testMultipleRoutesWithDifferentPatterns(): void
    {
        $this->router->addRoute('/user/{id}', 'UserController', 'show', ['GET'], 'user_show');
        $this->router->addRoute('/user/{id}/edit', 'UserController', 'edit', ['GET'], 'user_edit');
        $this->router->addRoute('/user/{id}/delete', 'UserController', 'delete', ['POST'], 'user_delete');

        // Test /user/42
        $result1 = $this->router->match($this->createRequest('GET', '/user/42'));
        $this->assertNotNull($result1);
        $this->assertSame('show', $result1['action']);

        // Test /user/42/edit
        $result2 = $this->router->match($this->createRequest('GET', '/user/42/edit'));
        $this->assertNotNull($result2);
        $this->assertSame('edit', $result2['action']);

        // Test /user/42/delete (POST)
        $result3 = $this->router->match($this->createRequest('POST', '/user/42/delete'));
        $this->assertNotNull($result3);
        $this->assertSame('delete', $result3['action']);
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    private function createRequest(string $method, string $uri): Request
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return new Request();
    }
}
