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
 * Middleware stack for processing HTTP requests through a chain of middlewares.
 *
 * Implements a FIFO (First In, First Out) execution order where:
 * - Middlewares are executed in the order they are added
 * - Each middleware wraps the next one
 * - Response flows back through the stack in reverse order
 */
class MiddlewareStack
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    /**
     * Add a middleware to the stack.
     *
     * @return $this For method chaining
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Add multiple middlewares at once.
     *
     * @param MiddlewareInterface[] $middlewares
     * @return $this For method chaining
     */
    public function addMany(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
        return $this;
    }

    /**
     * Process the request through all middlewares and the final handler.
     *
     * @param Request $request The incoming request
     * @param callable(Request): Response $finalHandler The final handler (usually the controller)
     * @return Response The response from the middleware chain
     */
    public function handle(Request $request, callable $finalHandler): Response
    {
        // Build the handler chain from inside out
        // The final handler is wrapped by each middleware in reverse order
        $handler = array_reduce(
            array_reverse($this->middlewares),
            fn(callable $next, MiddlewareInterface $middleware): callable =>
                fn(Request $req): Response => $middleware->process($req, $next),
            $finalHandler
        );

        return $handler($request);
    }
}
