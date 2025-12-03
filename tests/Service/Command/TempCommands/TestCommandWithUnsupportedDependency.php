<?php
declare(strict_types=1);

namespace Tests\Service\Command\TempCommands;

use App\Service\Command\AbstractCommand;

class TestCommandWithUnsupportedDependency extends AbstractCommand
{
    public function __construct(object $unsupportedDependency)
    {
    }

    public function execute(array $args = []): int
    {
        return 0;
    }
}
