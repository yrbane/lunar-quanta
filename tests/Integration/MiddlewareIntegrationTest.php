<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;
use Lunar\Service\Core\Middleware\MiddlewareStack;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for middleware system.
 */
class MiddlewareIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
    }

    public function testMiddlewareCanAddHeaders(): void
    {
        $headerMiddleware = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                $response = $next($request);
                return new Response(
                    $response->getBody(),
                    $response->getStatusCode(),
                    array_merge($response->getHeaders(), ['X-Custom-Header: test'])
                );
            }
        };

        $stack = new MiddlewareStack();
        $stack->add($headerMiddleware);

        $request = new Request();
        $response = $stack->handle($request, fn($req) => new Response('ok'));

        $this->assertContains('X-Custom-Header: test', $response->getHeaders());
    }

    public function testAuthMiddlewareBlocksUnauthorized(): void
    {
        $authMiddleware = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                if (!$request->getAttribute('authenticated', false)) {
                    return new Response('Unauthorized', 401);
                }
                return $next($request);
            }
        };

        $stack = new MiddlewareStack();
        $stack->add($authMiddleware);

        // Unauthenticated request
        $request = new Request();
        $response = $stack->handle($request, fn($req) => new Response('Protected content'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Unauthorized', $response->getBody());
    }

    public function testAuthMiddlewareAllowsAuthenticated(): void
    {
        $authMiddleware = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                if (!$request->getAttribute('authenticated', false)) {
                    return new Response('Unauthorized', 401);
                }
                return $next($request);
            }
        };

        // Simulating a session middleware that sets authenticated
        $sessionMiddleware = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                $request->setAttribute('authenticated', true);
                return $next($request);
            }
        };

        $stack = new MiddlewareStack();
        $stack->add($sessionMiddleware)->add($authMiddleware);

        $request = new Request();
        $response = $stack->handle($request, fn($req) => new Response('Protected content'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Protected content', $response->getBody());
    }

    public function testLoggingMiddleware(): void
    {
        $log = [];

        $loggingMiddleware = new class($log) implements MiddlewareInterface {
            /** @var array<string> */
            private array $log;

            /** @param array<string> $log */
            public function __construct(array &$log)
            {
                $this->log = &$log;
            }

            public function process(Request $request, callable $next): Response
            {
                $this->log[] = 'Before: ' . $request->getUri();
                $response = $next($request);
                $this->log[] = 'After: ' . $response->getStatusCode();
                return $response;
            }
        };

        $stack = new MiddlewareStack();
        $stack->add($loggingMiddleware);

        $request = new Request();
        $stack->handle($request, fn($req) => new Response('ok', 200));

        $this->assertSame(['Before: /test', 'After: 200'], $log);
    }

    public function testCorsMiddleware(): void
    {
        $corsMiddleware = new class implements MiddlewareInterface {
            public function process(Request $request, callable $next): Response
            {
                $response = $next($request);
                return new Response(
                    $response->getBody(),
                    $response->getStatusCode(),
                    array_merge($response->getHeaders(), [
                        'Access-Control-Allow-Origin: *',
                        'Access-Control-Allow-Methods: GET, POST, PUT, DELETE',
                    ])
                );
            }
        };

        $stack = new MiddlewareStack();
        $stack->add($corsMiddleware);

        $request = new Request();
        $response = $stack->handle($request, fn($req) => new Response('ok'));

        $this->assertContains('Access-Control-Allow-Origin: *', $response->getHeaders());
        $this->assertContains('Access-Control-Allow-Methods: GET, POST, PUT, DELETE', $response->getHeaders());
    }

    public function testRateLimitMiddleware(): void
    {
        $requestCount = 0;
        $maxRequests = 3;

        $rateLimitMiddleware = new class($requestCount, $maxRequests) implements MiddlewareInterface {
            private int $requestCount;
            private int $maxRequests;

            public function __construct(int &$requestCount, int $maxRequests)
            {
                $this->requestCount = &$requestCount;
                $this->maxRequests = $maxRequests;
            }

            public function process(Request $request, callable $next): Response
            {
                if ($this->requestCount >= $this->maxRequests) {
                    return new Response('Too Many Requests', 429);
                }
                $this->requestCount++;
                return $next($request);
            }
        };

        $stack = new MiddlewareStack();
        $stack->add($rateLimitMiddleware);

        $request = new Request();

        // First 3 requests should succeed
        for ($i = 0; $i < 3; $i++) {
            $response = $stack->handle($request, fn($req) => new Response('ok'));
            $this->assertSame(200, $response->getStatusCode());
        }

        // 4th request should be rate limited
        $response = $stack->handle($request, fn($req) => new Response('ok'));
        $this->assertSame(429, $response->getStatusCode());
    }

    public function testMiddlewareExecutionOrder(): void
    {
        $order = [];

        $first = new class($order) implements MiddlewareInterface {
            /** @var array<string> */
            private array $order;

            /** @param array<string> $order */
            public function __construct(array &$order)
            {
                $this->order = &$order;
            }

            public function process(Request $request, callable $next): Response
            {
                $this->order[] = 'first-before';
                $response = $next($request);
                $this->order[] = 'first-after';
                return $response;
            }
        };

        $second = new class($order) implements MiddlewareInterface {
            /** @var array<string> */
            private array $order;

            /** @param array<string> $order */
            public function __construct(array &$order)
            {
                $this->order = &$order;
            }

            public function process(Request $request, callable $next): Response
            {
                $this->order[] = 'second-before';
                $response = $next($request);
                $this->order[] = 'second-after';
                return $response;
            }
        };

        $stack = new MiddlewareStack();
        $stack->add($first)->add($second);

        $request = new Request();
        $stack->handle($request, function ($req) use (&$order) {
            $order[] = 'controller';
            return new Response('ok');
        });

        $this->assertSame([
            'first-before',
            'second-before',
            'controller',
            'second-after',
            'first-after',
        ], $order);
    }
}
