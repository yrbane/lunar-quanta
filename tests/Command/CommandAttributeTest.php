<?php

declare(strict_types=1);

namespace Tests\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Command\CacheClearCommand;
use Lunar\Command\FilesystemTreeCommand;
use Lunar\Command\MakeCommandCommand;
use Lunar\Command\MakeControllerCommand;
use Lunar\Command\RouterDebugCommand;
use Lunar\Command\ServerLogsCommand;
use Lunar\Command\ServerStartCommand;
use Lunar\Command\ServerStatusCommand;
use Lunar\Command\ServerStopCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for command attributes and interface implementation.
 */
class CommandAttributeTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function commandClassProvider(): array
    {
        return [
            'CacheClearCommand' => [CacheClearCommand::class],
            'FilesystemTreeCommand' => [FilesystemTreeCommand::class],
            'MakeCommandCommand' => [MakeCommandCommand::class],
            'MakeControllerCommand' => [MakeControllerCommand::class],
            'RouterDebugCommand' => [RouterDebugCommand::class],
            'ServerLogsCommand' => [ServerLogsCommand::class],
            'ServerStartCommand' => [ServerStartCommand::class],
            'ServerStatusCommand' => [ServerStatusCommand::class],
            'ServerStopCommand' => [ServerStopCommand::class],
        ];
    }

    /**
     * @param class-string $commandClass
     */
    #[DataProvider('commandClassProvider')]
    public function testCommandImplementsInterface(string $commandClass): void
    {
        $this->assertTrue(
            is_subclass_of($commandClass, CommandInterface::class),
            "{$commandClass} must implement CommandInterface"
        );
    }

    /**
     * @param class-string $commandClass
     */
    #[DataProvider('commandClassProvider')]
    public function testCommandHasAttribute(string $commandClass): void
    {
        $reflection = new \ReflectionClass($commandClass);
        $attributes = $reflection->getAttributes(Command::class);

        $this->assertNotEmpty($attributes, "{$commandClass} must have #[Command] attribute");
    }

    /**
     * @param class-string $commandClass
     */
    #[DataProvider('commandClassProvider')]
    public function testCommandAttributeHasName(string $commandClass): void
    {
        $reflection = new \ReflectionClass($commandClass);
        $attributes = $reflection->getAttributes(Command::class);

        /** @var Command $command */
        $command = $attributes[0]->newInstance();

        $this->assertNotEmpty($command->name, "{$commandClass} must have a command name");
        $this->assertStringContainsString(':', $command->name, "Command name should follow 'namespace:action' pattern");
    }

    /**
     * @param class-string $commandClass
     */
    #[DataProvider('commandClassProvider')]
    public function testCommandAttributeHasDescription(string $commandClass): void
    {
        $reflection = new \ReflectionClass($commandClass);
        $attributes = $reflection->getAttributes(Command::class);

        /** @var Command $command */
        $command = $attributes[0]->newInstance();

        $this->assertNotEmpty($command->description, "{$commandClass} must have a description");
    }

    /**
     * @param class-string $commandClass
     */
    #[DataProvider('commandClassProvider')]
    public function testCommandHasExecuteMethod(string $commandClass): void
    {
        $this->assertTrue(
            method_exists($commandClass, 'execute'),
            "{$commandClass} must have execute() method"
        );
    }

    /**
     * @param class-string $commandClass
     */
    #[DataProvider('commandClassProvider')]
    public function testCommandHasGetHelpMethod(string $commandClass): void
    {
        $this->assertTrue(
            method_exists($commandClass, 'getHelp'),
            "{$commandClass} must have getHelp() method"
        );
    }

    /**
     * @param class-string $commandClass
     */
    #[DataProvider('commandClassProvider')]
    public function testGetHelpReturnsNonEmptyString(string $commandClass): void
    {
        /** @var CommandInterface $command */
        $command = new $commandClass();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help, "{$commandClass}::getHelp() must return non-empty string");
    }

    public function testCacheClearCommandName(): void
    {
        $reflection = new \ReflectionClass(CacheClearCommand::class);
        $attributes = $reflection->getAttributes(Command::class);

        /** @var Command $command */
        $command = $attributes[0]->newInstance();

        $this->assertEquals('cache:clear', $command->name);
    }

    public function testRouterDebugCommandName(): void
    {
        $reflection = new \ReflectionClass(RouterDebugCommand::class);
        $attributes = $reflection->getAttributes(Command::class);

        /** @var Command $command */
        $command = $attributes[0]->newInstance();

        $this->assertEquals('router:debug', $command->name);
    }

    public function testFilesystemTreeCommandName(): void
    {
        $reflection = new \ReflectionClass(FilesystemTreeCommand::class);
        $attributes = $reflection->getAttributes(Command::class);

        /** @var Command $command */
        $command = $attributes[0]->newInstance();

        $this->assertEquals('filesystem:tree', $command->name);
    }

    public function testServerCommandsExist(): void
    {
        $serverCommands = [
            ServerStartCommand::class => 'server:start',
            ServerStopCommand::class => 'server:stop',
            ServerStatusCommand::class => 'server:status',
            ServerLogsCommand::class => 'server:logs',
        ];

        foreach ($serverCommands as $class => $expectedName) {
            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(Command::class);

            /** @var Command $command */
            $command = $attributes[0]->newInstance();

            $this->assertEquals($expectedName, $command->name);
        }
    }

    public function testMakeCommandsExist(): void
    {
        $makeCommands = [
            MakeControllerCommand::class => 'make:controller',
            MakeCommandCommand::class => 'make:command',
        ];

        foreach ($makeCommands as $class => $expectedName) {
            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(Command::class);

            /** @var Command $command */
            $command = $attributes[0]->newInstance();

            $this->assertEquals($expectedName, $command->name);
        }
    }
}
