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
 * Middleware that requires guests (not authenticated).
 *
 * Useful for login/register pages that should redirect
 * authenticated users elsewhere.
 */
class GuestMiddleware implements MiddlewareInterface
{
    private Authenticator $authenticator;
    private string $redirectUrl;

    /**
     * @param Authenticator $authenticator The authenticator service
     * @param string $redirectUrl URL to redirect authenticated users to
     */
    public function __construct(Authenticator $authenticator, string $redirectUrl = '/')
    {
        $this->authenticator = $authenticator;
        $this->redirectUrl = $redirectUrl;
    }

    public function process(Request $request, callable $next): Response
    {
        if ($this->authenticator->check()) {
            return new Response('', 302, ['Location: ' . $this->redirectUrl]);
        }

        return $next($request);
    }
}
