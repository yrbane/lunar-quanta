<?php

declare(strict_types=1);

namespace App\Service\Router;

use App\Service\Core\Router;

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