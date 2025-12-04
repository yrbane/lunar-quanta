<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;

/**
 * Middleware that requires authentication.
 *
 * Blocks unauthenticated requests with a 401 response or redirects to login.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private Authenticator $authenticator;
    private ?string $redirectUrl;

    /**
     * @param Authenticator $authenticator The authenticator service
     * @param string|null $redirectUrl Optional URL to redirect unauthenticated users
     */
    public function __construct(Authenticator $authenticator, ?string $redirectUrl = null)
    {
        $this->authenticator = $authenticator;
        $this->redirectUrl = $redirectUrl;
    }

    public function process(Request $request, callable $next): Response
    {
        if ($this->authenticator->guest()) {
            if (null !== $this->redirectUrl) {
                return new Response('', 302, ['Location: ' . $this->redirectUrl]);
            }

            return new Response('Unauthorized', 401);
        }

        // Attach user to request for easy access in controllers
        $user = $this->authenticator->user();
        $request->setAttribute('user', $user);
        $request->setAttribute('auth', $this->authenticator);

        return $next($request);
    }
}
