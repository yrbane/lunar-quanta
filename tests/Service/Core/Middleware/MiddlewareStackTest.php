<?php

declare(strict_types=1);

namespace Tests\Service\Core\Middleware;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;
use Lunar\Service\Core\Middleware\MiddlewareStack;
use PHPUnit\Framework\TestCase;

class MiddlewareStackTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
    }

    public function testEmptyStackCallsFinalHandler(): void
    {
        $stack = new MiddlewareStack();
        $request = new Request();

        $response = $stack->handle($request, fn($req) => new Response('final'));

        $this->assertSame('final', $response->getBody());
    }

    public function testMiddlewareIsExecuted(): void
    {
        $stack = new MiddlewareStack();
        $middleware = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                $response = $next($request);
                return new Response('middleware:' . $response->getBody());
            }
        };

        $stack->add($middleware);
        $request = new Request();

        $response = $stack->handle($request, fn($req) => new Response('final'));

        $this->assertSame('middleware:final', $response->getBody());
    }

    public function testMiddlewaresExecuteInOrder(): void
    {
        $stack = new MiddlewareStack();

        $first = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                $response = $next($request);
                return new Response('first:' . $response->getBody());
            }
        };

        $second = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                $response = $next($request);
                return new Response('second:' . $response->getBody());
            }
        };

        $stack->add($first)->add($second);
        $request = new Request();

        $response = $stack->handle($request, fn($req) => new Response('final'));

        // First middleware wraps second which wraps final
        $this->assertSame('first:second:final', $response->getBody());
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $stack = new MiddlewareStack();

        $blocker = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                return new Response('blocked', 403);
            }
        };

        $neverCalled = new class implements MiddlewareInterface {
            public bool $called = false;
            public function process(Request $request, callable $next): Response
            {
                $this->called = true;
                return $next($request);
            }
        };

        $stack->add($blocker)->add($neverCalled);
        $request = new Request();

        $response = $stack->handle($request, fn($req) => new Response('final'));

        $this->assertSame('blocked', $response->getBody());
        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($neverCalled->called);
    }

    public function testMiddlewareCanModifyRequest(): void
    {
        $stack = new MiddlewareStack();

        $modifier = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                $request->setAttribute('modified', true);
                return $next($request);
            }
        };

        $stack->add($modifier);
        $request = new Request();

        $capturedRequest = null;
        $stack->handle($request, function($req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return new Response('ok');
        });

        $this->assertTrue($capturedRequest->getAttribute('modified'));
    }

    public function testAddReturnsStackForChaining(): void
    {
        $stack = new MiddlewareStack();
        $middleware = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };

        $result = $stack->add($middleware);

        $this->assertSame($stack, $result);
    }

    public function testMultipleMiddlewaresCanBeAddedAtOnce(): void
    {
        $stack = new MiddlewareStack();

        $first = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                $response = $next($request);
                return new Response('A:' . $response->getBody());
            }
        };

        $second = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                $response = $next($request);
                return new Response('B:' . $response->getBody());
            }
        };

        $stack->addMany([$first, $second]);
        $request = new Request();

        $response = $stack->handle($request, fn($req) => new Response('end'));

        $this->assertSame('A:B:end', $response->getBody());
    }
}
