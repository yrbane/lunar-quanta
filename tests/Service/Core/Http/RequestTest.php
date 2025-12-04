<?php

declare(strict_types=1);

namespace Tests\Service\Core\Http;

use Lunar\Service\Core\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
    }

    public function testGetMethodReturnsRequestMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test';

        $request = new Request();

        $this->assertSame('POST', $request->getMethod());
    }

    public function testGetMethodDefaultsToGet(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $_SERVER['REQUEST_URI'] = '/';

        $request = new Request();

        $this->assertSame('GET', $request->getMethod());
    }

    public function testGetUriReturnsPath(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/123?param=value';

        $request = new Request();

        $this->assertSame('/users/123', $request->getUri());
    }

    public function testGetUriDefaultsToSlash(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['REQUEST_URI']);

        $request = new Request();

        $this->assertSame('/', $request->getUri());
    }

    public function testGetQueryParamsReturnsGetArray(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_GET = ['foo' => 'bar', 'baz' => '123'];

        $request = new Request();

        $this->assertSame(['foo' => 'bar', 'baz' => '123'], $request->getQueryParams());
    }

    public function testGetPostParamsReturnsPostArray(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $_POST = ['username' => 'john', 'password' => 'secret'];

        $request = new Request();

        $this->assertSame(['username' => 'john', 'password' => 'secret'], $request->getPostParams());
    }

    public function testGetServerParamsReturnsServerArray(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new Request();
        $serverParams = $request->getServerParams();

        $this->assertSame('localhost', $serverParams['HTTP_HOST']);
    }

    public function testGetHeadersReturnsArray(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $request = new Request();

        $this->assertIsArray($request->getHeaders());
    }

    public function testGetHeadersExtractsHttpHeaders(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

        $request = new Request();
        $headers = $request->getHeaders();

        $this->assertIsArray($headers);
    }

}
