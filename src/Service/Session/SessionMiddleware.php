<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Session;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;

/**
 * Middleware that starts the session and makes it available via request attributes.
 */
class SessionMiddleware implements MiddlewareInterface
{
    private SessionInterface $session;

    public function __construct(?SessionInterface $session = null)
    {
        $this->session = $session ?? new SessionService();
    }

    public function process(Request $request, callable $next): Response
    {
        // Start session and attach to request
        $this->session->start();
        $request->setAttribute('session', $this->session);

        return $next($request);
    }
}
