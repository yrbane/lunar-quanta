<?php

declare(strict_types=1);

namespace Tests\Command;

use PHPUnit\Framework\TestCase;
use Lunar\Command\TotoCommand;

class TotoCommandTest extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        $command = new TotoCommand();
        $result = $command->execute([]);

        $this->assertSame(0, $result);
    }

    public function testHelpReturnsString(): void
    {
        $command = new TotoCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }
}
