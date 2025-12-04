<?php

declare(strict_types=1);

namespace Tests\Service\Core\Http;

use Lunar\Service\Core\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $response = new Response();

        $this->expectOutputString('');

        // Use reflection to check private properties
        $reflection = new \ReflectionClass($response);

        $contentProp = $reflection->getProperty('content');
        $this->assertSame('', $contentProp->getValue($response));

        $statusProp = $reflection->getProperty('statusCode');
        $this->assertSame(200, $statusProp->getValue($response));

        $headersProp = $reflection->getProperty('headers');
        $this->assertSame([], $headersProp->getValue($response));
    }

    public function testConstructorWithContent(): void
    {
        $response = new Response('<h1>Hello</h1>');

        $reflection = new \ReflectionClass($response);
        $contentProp = $reflection->getProperty('content');

        $this->assertSame('<h1>Hello</h1>', $contentProp->getValue($response));
    }

    public function testConstructorWithStatusCode(): void
    {
        $response = new Response('Not Found', 404);

        $reflection = new \ReflectionClass($response);
        $statusProp = $reflection->getProperty('statusCode');

        $this->assertSame(404, $statusProp->getValue($response));
    }

    public function testConstructorWithHeaders(): void
    {
        $headers = ['Content-Type: application/json', 'X-Custom: value'];
        $response = new Response('{}', 200, $headers);

        $reflection = new \ReflectionClass($response);
        $headersProp = $reflection->getProperty('headers');

        $this->assertSame($headers, $headersProp->getValue($response));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendOutputsContent(): void
    {
        $response = new Response('Hello World');

        $this->expectOutputString('Hello World');
        $response->send();
    }

    public function testGetBody(): void
    {
        $response = new Response('Test Content');

        $this->assertSame('Test Content', $response->getBody());
    }

    public function testGetStatusCode(): void
    {
        $response = new Response('', 201);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testGetHeaders(): void
    {
        $headers = ['X-Test: value'];
        $response = new Response('', 200, $headers);

        $this->assertSame($headers, $response->getHeaders());
    }

    public function testGetBodyReturnsEmptyStringByDefault(): void
    {
        $response = new Response();

        $this->assertSame('', $response->getBody());
    }

    public function testGetStatusCodeReturns200ByDefault(): void
    {
        $response = new Response();

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetHeadersReturnsEmptyArrayByDefault(): void
    {
        $response = new Response();

        $this->assertSame([], $response->getHeaders());
    }
}
