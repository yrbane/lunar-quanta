<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Csrf;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;
use Lunar\Service\Session\SessionInterface;

/**
 * Middleware that validates CSRF tokens on state-changing requests.
 *
 * Protects POST, PUT, PATCH, and DELETE requests by requiring
 * a valid CSRF token in the request body or headers.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    public const TOKEN_FIELD = '_csrf_token';
    public const TOKEN_HEADER = 'X-CSRF-Token';
    public const TOKEN_ID = 'csrf';

    private CsrfTokenManagerInterface $tokenManager;
    /** @var array<string> */
    private array $safeMethods = ['GET', 'HEAD', 'OPTIONS', 'TRACE'];

    public function __construct(?CsrfTokenManagerInterface $tokenManager = null)
    {
        $this->tokenManager = $tokenManager ?? $this->createDefaultTokenManager();
    }

    public function process(Request $request, callable $next): Response
    {
        // Safe methods don't need CSRF validation
        if (in_array($request->getMethod(), $this->safeMethods, true)) {
            $this->attachTokenManager($request);
            return $next($request);
        }

        // Validate token for state-changing methods
        $token = $this->extractToken($request);
        if (!$this->tokenManager->isValid(self::TOKEN_ID, $token)) {
            return new Response('CSRF token mismatch', 403);
        }

        $this->attachTokenManager($request);
        return $next($request);
    }

    /**
     * Extract CSRF token from request (body or header).
     */
    private function extractToken(Request $request): string
    {
        // Check header first (both parsed headers and raw HTTP_X_CSRF_TOKEN)
        $headers = $request->getHeaders();
        foreach ($headers as $key => $value) {
            if (is_string($key) && strtolower($key) === strtolower(self::TOKEN_HEADER)) {
                return is_string($value) ? $value : '';
            }
            if (is_string($value) && str_starts_with(strtolower($value), strtolower(self::TOKEN_HEADER . ':'))) {
                return trim(substr($value, strlen(self::TOKEN_HEADER) + 1));
            }
        }

        // Check server for HTTP header (fallback)
        $serverParams = $request->getServerParams();
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', self::TOKEN_HEADER));
        if (isset($serverParams[$headerKey]) && is_string($serverParams[$headerKey])) {
            return $serverParams[$headerKey];
        }

        // Check POST body
        $postParams = $request->getPostParams();
        $token = $postParams[self::TOKEN_FIELD] ?? '';
        return is_string($token) ? $token : '';
    }

    /**
     * Attach token manager to request for use in controllers.
     */
    private function attachTokenManager(Request $request): void
    {
        $request->setAttribute('csrf', $this->tokenManager);
    }

    /**
     * Create a default token manager (requires session in request).
     */
    private function createDefaultTokenManager(): CsrfTokenManagerInterface
    {
        // This will fail if no session is available - user should inject their own
        // or ensure SessionMiddleware runs before CsrfMiddleware
        return new class implements CsrfTokenManagerInterface {
            public function generate(string $tokenId): string
            {
                throw new \RuntimeException(
                    'CSRF token manager requires a session. ' .
                    'Either inject CsrfTokenManager with a session or ensure SessionMiddleware runs first.'
                );
            }

            public function isValid(string $tokenId, string $token): bool
            {
                throw new \RuntimeException(
                    'CSRF token manager requires a session. ' .
                    'Either inject CsrfTokenManager with a session or ensure SessionMiddleware runs first.'
                );
            }

            public function remove(string $tokenId): void
            {
                throw new \RuntimeException(
                    'CSRF token manager requires a session. ' .
                    'Either inject CsrfTokenManager with a session or ensure SessionMiddleware runs first.'
                );
            }
        };
    }

    /**
     * Create a CSRF middleware with session-based token manager.
     */
    public static function withSession(SessionInterface $session): self
    {
        return new self(new CsrfTokenManager($session));
    }
}
