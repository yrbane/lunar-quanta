<?php
declare(strict_types=1);

namespace Tests\Service\Command\TempCommands;

use App\Service\Command\AbstractCommand;
use App\Service\Core\Router;

class TestCommandWithSimpleDependency extends AbstractCommand
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function execute(array $args = []): int
    {
        return 0;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }
}
