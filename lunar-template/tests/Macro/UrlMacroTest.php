<?php
declare(strict_types=1);

namespace Lunar\Template\Tests\Macro;

use Lunar\Template\Macro\UrlMacro;
use Lunar\Template\Macro\RouterInterface;
use PHPUnit\Framework\TestCase;

class UrlMacroTest extends TestCase
{
    private RouterInterface $router;
    private UrlMacro $macro;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->macro = new UrlMacro($this->router);
    }

    public function testGetName(): void
    {
        $this->assertSame('url', $this->macro->getName());
    }

    public function testExecuteWithValidRoute(): void
    {
        $route = ['path' => '/blog/post/123'];
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('blog.show')
            ->willReturn($route);

        $result = $this->macro->execute(['blog.show']);
        $this->assertSame('/blog/post/123', $result);
    }

    public function testExecuteWithNonExistentRoute(): void
    {
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('nonexistent.route')
            ->willReturn(null);

        $result = $this->macro->execute(['nonexistent.route']);
        $this->assertSame('#ROUTE nonexistent.route NOT FOUND !!!', $result);
    }

    public function testExecuteWithEmptyRouteName(): void
    {
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('')
            ->willReturn(null);

        $result = $this->macro->execute(['']);
        $this->assertSame('#ROUTE  NOT FOUND !!!', $result);
    }

    public function testExecuteWithNoArguments(): void
    {
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('')
            ->willReturn(null);

        $result = $this->macro->execute([]);
        $this->assertSame('#ROUTE  NOT FOUND !!!', $result);
    }

    public function testExecuteWithValidParameters(): void
    {
        $route = ['path' => '/user/profile'];
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('user.profile')
            ->willReturn($route);

        $params = json_encode(['id' => 123, 'tab' => 'settings']);
        $result = $this->macro->execute(['user.profile', $params]);
        
        $this->assertSame('/user/profile?id=123&tab=settings', $result);
    }

    public function testExecuteWithEmptyParameters(): void
    {
        $route = ['path' => '/home'];
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('home')
            ->willReturn($route);

        $result = $this->macro->execute(['home', '{}']);
        $this->assertSame('/home', $result);
    }

    public function testExecuteWithInvalidJsonParameters(): void
    {
        $route = ['path' => '/test'];
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('test.route')
            ->willReturn($route);

        $result = $this->macro->execute(['test.route', 'invalid-json']);
        $this->assertSame('/test', $result);
    }

    public function testExecuteWithNullParameters(): void
    {
        $route = ['path' => '/api/users'];
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('api.users')
            ->willReturn($route);

        $result = $this->macro->execute(['api.users', null]);
        $this->assertSame('/api/users', $result);
    }

    public function testExecuteWithComplexParameters(): void
    {
        $route = ['path' => '/search'];
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('search')
            ->willReturn($route);

        $params = json_encode([
            'query' => 'test search',
            'filters' => ['category' => 'blog', 'status' => 'published'],
            'page' => 2
        ]);
        
        $result = $this->macro->execute(['search', $params]);
        
        $this->assertStringContainsString('/search?', $result);
        $this->assertStringContainsString('query=test+search', $result);
        $this->assertStringContainsString('page=2', $result);
    }

    public function testExecuteWithRouteContainingQueryString(): void
    {
        $route = ['path' => '/page?existing=param'];
        $this->router->expects($this->once())
            ->method('getRouteByName')
            ->with('page')
            ->willReturn($route);

        $params = json_encode(['new' => 'value']);
        $result = $this->macro->execute(['page', $params]);
        
        $this->assertSame('/page?existing=param?new=value', $result);
    }
}