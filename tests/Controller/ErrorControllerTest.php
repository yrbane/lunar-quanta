<?php

declare(strict_types=1);

namespace Tests\Controller;

use Lunar\Controller\ErrorController;
use Lunar\Service\Core\Http\HttpStatus;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ErrorControllerTest extends TestCase
{
    private array $originalServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/error';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SERVER = $this->originalServer;
    }

    public function testIndexReturnsResponse(): void
    {
        $controller = new ErrorController();
        $request = new Request();

        $response = $controller->index($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testIndexReturns404ByDefault(): void
    {
        $controller = new ErrorController();
        $request = new Request();

        $response = $controller->index($request);

        $reflection = new ReflectionClass($response);
        $statusProp = $reflection->getProperty('statusCode');

        $this->assertSame(404, $statusProp->getValue($response));
    }

    public function testIndexWithCustomCode(): void
    {
        $controller = new ErrorController();
        $request = new Request();

        $response = $controller->index($request, 500);

        $reflection = new ReflectionClass($response);
        $statusProp = $reflection->getProperty('statusCode');

        $this->assertSame(500, $statusProp->getValue($response));
    }

    public function testIndexWithCustomMessage(): void
    {
        $controller = new ErrorController();
        $request = new Request();

        $response = $controller->index($request, 400, 'Custom error message');

        $reflection = new ReflectionClass($response);
        $contentProp = $reflection->getProperty('content');

        $content = $contentProp->getValue($response);
        $this->assertStringContainsString('Custom error message', $content);
    }

    public function testIndexWithNotFoundCode(): void
    {
        $controller = new ErrorController();
        $request = new Request();

        $response = $controller->index($request, HttpStatus::NOT_FOUND);

        $reflection = new ReflectionClass($response);
        $statusProp = $reflection->getProperty('statusCode');

        $this->assertSame(404, $statusProp->getValue($response));
    }

    public function testIndexWithInternalServerError(): void
    {
        $controller = new ErrorController();
        $request = new Request();

        $response = $controller->index($request, HttpStatus::INTERNAL_SERVER_ERROR);

        $reflection = new ReflectionClass($response);
        $statusProp = $reflection->getProperty('statusCode');

        $this->assertSame(500, $statusProp->getValue($response));
    }
}
