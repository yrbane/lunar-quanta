<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

namespace Lunar\Service\Core;

use Lunar\Service\Core\Http\Request;

/**
 * Interface for the routing system.
 *
 * Defines the contract for route registration, matching, and URL generation.
 */
interface RouterInterface
{
    /**
     * Register a route from controller attributes.
     *
     * @param string        $path       URL pattern
     * @param string        $controller Fully qualified class name
     * @param string        $action     Method name
     * @param array<string> $methods    HTTP methods
     * @param null|string   $name       Route name
     */
    public function addRoute(
        string $path,
        string $controller,
        string $action,
        array $methods = ['GET'],
        ?string $name = null
    ): void;

    /**
     * Match a request to a registered route.
     *
     * @return null|array{controller: string, action: string, parameters: array<string, mixed>}
     */
    public function match(Request $request): ?array;

    /**
     * Get all registered routes.
     *
     * @return array<array{path: string, controller: string, action: string, methods: array<string>}>
     */
    public function getRoutes(): array;
}
