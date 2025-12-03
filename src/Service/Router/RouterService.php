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

namespace Lunar\Service\Router;

use Lunar\Service\Core\Router;

class RouterService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        $router = new Router();

        return $router->getRoutes();
    }
}
