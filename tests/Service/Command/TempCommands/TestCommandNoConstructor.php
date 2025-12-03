<?php
declare(strict_types=1);

namespace Tests\Service\Command\TempCommands;

use App\Service\Command\AbstractCommand;

class TestCommandNoConstructor extends AbstractCommand
{
    public function execute(array $args = []): int
    {
        return 0;
    }

    public function __construct()
    {
        // This constructor should not be called by CommandFactory in testMakeCommandNoConstructor
    }

    public function execute(): string
    {
        return 'No constructor executed';
    }
}
