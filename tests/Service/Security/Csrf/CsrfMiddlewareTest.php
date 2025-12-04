<?php

declare(strict_types=1);

namespace Tests\Service\Security\Csrf;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Security\Csrf\CsrfMiddleware;
use Lunar\Service\Security\Csrf\CsrfTokenManager;
use Lunar\Service\Security\Csrf\CsrfTokenManagerInterface;
use Lunar\Service\Session\SessionService;
use PHPUnit\Framework\TestCase;

class CsrfMiddlewareTest extends TestCase
{
    private SessionService $session;
    private CsrfTokenManager $tokenManager;
    private CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';

        $this->session = new SessionService(testMode: true);
        $this->tokenManager = new CsrfTokenManager($this->session);
        $this->middleware = new CsrfMiddleware($this->tokenManager);
    }

    public function testGetRequestsPassThrough(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getBody());
    }

    public function testHeadRequestsPassThrough(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testOptionsRequestsPassThrough(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPostWithoutTokenReturns403(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('CSRF token mismatch', $response->getBody());
    }

    public function testPostWithInvalidTokenReturns403(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[CsrfMiddleware::TOKEN_FIELD] = 'invalid_token';
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(403, $response->getStatusCode());

        unset($_POST[CsrfMiddleware::TOKEN_FIELD]);
    }

    public function testPostWithValidTokenInBodyPassesThrough(): void
    {
        $token = $this->tokenManager->generate(CsrfMiddleware::TOKEN_ID);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[CsrfMiddleware::TOKEN_FIELD] = $token;
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getBody());

        unset($_POST[CsrfMiddleware::TOKEN_FIELD]);
    }

    public function testPostWithValidTokenInHeaderPassesThrough(): void
    {
        $token = $this->tokenManager->generate(CsrfMiddleware::TOKEN_ID);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(200, $response->getStatusCode());

        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    public function testPutRequiresToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPatchRequiresToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeleteRequiresToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testTokenManagerIsAttachedToRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $capturedManager = null;
        $this->middleware->process($request, function (Request $req) use (&$capturedManager) {
            $capturedManager = $req->getAttribute('csrf');
            return new Response('ok');
        });

        $this->assertInstanceOf(CsrfTokenManagerInterface::class, $capturedManager);
    }

    public function testWithSessionFactoryMethod(): void
    {
        $session = new SessionService(testMode: true);
        $middleware = CsrfMiddleware::withSession($session);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testTokenManagerIsAccessibleAfterSuccessfulValidation(): void
    {
        $token = $this->tokenManager->generate(CsrfMiddleware::TOKEN_ID);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[CsrfMiddleware::TOKEN_FIELD] = $token;
        $request = new Request();

        $capturedManager = null;
        $this->middleware->process($request, function (Request $req) use (&$capturedManager) {
            $capturedManager = $req->getAttribute('csrf');
            return new Response('ok');
        });

        $this->assertInstanceOf(CsrfTokenManagerInterface::class, $capturedManager);

        unset($_POST[CsrfMiddleware::TOKEN_FIELD]);
    }

    public function testEmptyTokenReturns403(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[CsrfMiddleware::TOKEN_FIELD] = '';
        $request = new Request();

        $response = $this->middleware->process($request, fn($req) => new Response('ok'));

        $this->assertSame(403, $response->getStatusCode());

        unset($_POST[CsrfMiddleware::TOKEN_FIELD]);
    }
}
