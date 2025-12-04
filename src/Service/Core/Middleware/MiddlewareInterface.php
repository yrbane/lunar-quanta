<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Core\Middleware;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;

/**
 * Interface for HTTP middleware.
 *
 * Middlewares process requests and can either:
 * - Pass the request to the next handler
 * - Short-circuit and return a response directly
 */
interface MiddlewareInterface
{
    /**
     * Process the request and return a response.
     *
     * @param Request $request The incoming HTTP request
     * @param callable(Request): Response $next The next handler in the chain
     * @return Response The HTTP response
     */
    public function process(Request $request, callable $next): Response;
}
