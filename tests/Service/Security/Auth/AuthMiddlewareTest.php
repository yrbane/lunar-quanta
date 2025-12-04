<?php

declare(strict_types=1);

namespace Tests\Service\Security\Auth;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Security\Auth\Authenticator;
use Lunar\Service\Security\Auth\AuthMiddleware;
use Lunar\Service\Security\Auth\GuestMiddleware;
use Lunar\Service\Security\Auth\PasswordHasher;
use Lunar\Service\Security\Auth\RoleMiddleware;
use Lunar\Service\Session\SessionService;
use PHPUnit\Framework\TestCase;

class AuthMiddlewareTest extends TestCase
{
    private SessionService $session;
    private Authenticator $auth;

    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';

        $this->session = new SessionService(testMode: true);
        $hasher = new PasswordHasher();
        $provider = new TestUserProvider($hasher);
        $this->auth = new Authenticator($provider, $hasher, $this->session);
    }

    public function testAuthMiddlewareBlocksUnauthenticated(): void
    {
        $middleware = new AuthMiddleware($this->auth);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('protected'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Unauthorized', $response->getBody());
    }

    public function testAuthMiddlewareAllowsAuthenticated(): void
    {
        $this->auth->attempt('john@example.com', 'password123');

        $middleware = new AuthMiddleware($this->auth);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('protected'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('protected', $response->getBody());
    }

    public function testAuthMiddlewareRedirectsWhenConfigured(): void
    {
        $middleware = new AuthMiddleware($this->auth, '/login');
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('protected'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertContains('Location: /login', $response->getHeaders());
    }

    public function testAuthMiddlewareAttachesUserToRequest(): void
    {
        $this->auth->attempt('john@example.com', 'password123');

        $middleware = new AuthMiddleware($this->auth);
        $request = new Request();

        $capturedUser = null;
        $middleware->process($request, function (Request $req) use (&$capturedUser) {
            $capturedUser = $req->getAttribute('user');
            return new Response('ok');
        });

        $this->assertNotNull($capturedUser);
        $this->assertSame('john@example.com', $capturedUser->getIdentifier());
    }

    public function testAuthMiddlewareAttachesAuthenticatorToRequest(): void
    {
        $this->auth->attempt('john@example.com', 'password123');

        $middleware = new AuthMiddleware($this->auth);
        $request = new Request();

        $capturedAuth = null;
        $middleware->process($request, function (Request $req) use (&$capturedAuth) {
            $capturedAuth = $req->getAttribute('auth');
            return new Response('ok');
        });

        $this->assertInstanceOf(Authenticator::class, $capturedAuth);
    }

    public function testGuestMiddlewareAllowsGuests(): void
    {
        $middleware = new GuestMiddleware($this->auth);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('login form'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('login form', $response->getBody());
    }

    public function testGuestMiddlewareRedirectsAuthenticated(): void
    {
        $this->auth->attempt('john@example.com', 'password123');

        $middleware = new GuestMiddleware($this->auth, '/dashboard');
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('login form'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertContains('Location: /dashboard', $response->getHeaders());
    }

    public function testRoleMiddlewareBlocksUnauthenticated(): void
    {
        $middleware = new RoleMiddleware($this->auth, ['ROLE_ADMIN']);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('admin'));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRoleMiddlewareBlocksUnauthorizedRole(): void
    {
        $this->auth->attempt('john@example.com', 'password123'); // Only has ROLE_USER

        $middleware = new RoleMiddleware($this->auth, ['ROLE_ADMIN']);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('admin'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Forbidden', $response->getBody());
    }

    public function testRoleMiddlewareAllowsAuthorizedRole(): void
    {
        $this->auth->attempt('jane@example.com', 'secret456'); // Has ROLE_ADMIN

        $middleware = new RoleMiddleware($this->auth, ['ROLE_ADMIN']);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('admin'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('admin', $response->getBody());
    }

    public function testRoleMiddlewareAllowsAnyRoleByDefault(): void
    {
        $this->auth->attempt('jane@example.com', 'secret456'); // Has ROLE_USER and ROLE_ADMIN

        $middleware = new RoleMiddleware($this->auth, ['ROLE_SUPERUSER', 'ROLE_ADMIN']);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('content'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRoleMiddlewareRequireAllRoles(): void
    {
        $this->auth->attempt('jane@example.com', 'secret456'); // Has ROLE_USER and ROLE_ADMIN

        $middleware = new RoleMiddleware($this->auth, ['ROLE_USER', 'ROLE_ADMIN'], requireAll: true);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('content'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRoleMiddlewareRequireAllRolesBlocksIfMissing(): void
    {
        $this->auth->attempt('jane@example.com', 'secret456'); // Has ROLE_USER and ROLE_ADMIN

        $middleware = new RoleMiddleware($this->auth, ['ROLE_USER', 'ROLE_SUPERUSER'], requireAll: true);
        $request = new Request();

        $response = $middleware->process($request, fn($req) => new Response('content'));

        $this->assertSame(403, $response->getStatusCode());
    }
}
