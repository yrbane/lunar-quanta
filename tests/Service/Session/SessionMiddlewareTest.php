<?php

declare(strict_types=1);

namespace Tests\Service\Session;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Session\SessionInterface;
use Lunar\Service\Session\SessionMiddleware;
use Lunar\Service\Session\SessionService;
use PHPUnit\Framework\TestCase;

class SessionMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
    }

    public function testMiddlewareStartsSession(): void
    {
        $session = new SessionService(testMode: true);
        $middleware = new SessionMiddleware($session);

        $request = new Request();
        $response = $middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getBody());
    }

    public function testMiddlewareAttachesSessionToRequest(): void
    {
        $session = new SessionService(testMode: true);
        $middleware = new SessionMiddleware($session);

        $request = new Request();
        $capturedSession = null;

        $middleware->process($request, function (Request $req) use (&$capturedSession) {
            $capturedSession = $req->getAttribute('session');
            return new Response('ok');
        });

        $this->assertInstanceOf(SessionInterface::class, $capturedSession);
    }

    public function testSessionDataIsAccessibleInHandler(): void
    {
        $session = new SessionService(testMode: true);
        $session->set('user_id', 42);

        $middleware = new SessionMiddleware($session);
        $request = new Request();

        $result = null;
        $middleware->process($request, function (Request $req) use (&$result) {
            /** @var SessionInterface $session */
            $session = $req->getAttribute('session');
            $result = $session->get('user_id');
            return new Response('ok');
        });

        $this->assertSame(42, $result);
    }

    public function testHandlerCanSetSessionData(): void
    {
        $session = new SessionService(testMode: true);
        $middleware = new SessionMiddleware($session);
        $request = new Request();

        $middleware->process($request, function (Request $req) {
            /** @var SessionInterface $session */
            $session = $req->getAttribute('session');
            $session->set('visited', true);
            return new Response('ok');
        });

        $this->assertTrue($session->get('visited'));
    }

    public function testFlashMessagesWork(): void
    {
        $session = new SessionService(testMode: true);
        $middleware = new SessionMiddleware($session);
        $request = new Request();

        $middleware->process($request, function (Request $req) {
            /** @var SessionInterface $session */
            $session = $req->getAttribute('session');
            $session->flash('success', 'Item saved!');
            return new Response('ok');
        });

        $this->assertSame('Item saved!', $session->getFlash('success'));
        $this->assertNull($session->getFlash('success')); // Consumed
    }

    public function testDefaultSessionIsCreatedWhenNoneProvided(): void
    {
        // This test verifies the constructor default, but we can't easily test
        // without hitting real sessions, so we just verify it doesn't throw
        $middleware = new SessionMiddleware();
        $this->assertInstanceOf(SessionMiddleware::class, $middleware);
    }

    public function testMiddlewarePassesResponseFromHandler(): void
    {
        $session = new SessionService(testMode: true);
        $middleware = new SessionMiddleware($session);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('custom body', 201, ['X-Test: value']));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('custom body', $response->getBody());
        $this->assertContains('X-Test: value', $response->getHeaders());
    }
}
