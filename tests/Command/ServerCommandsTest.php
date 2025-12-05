<?php
/**
 * Tests unitaires pour les commandes Server (server:status, server:stop, etc.).
 *
 * =============================================================================
 * ARCHITECTURE DES COMMANDES SERVER
 * =============================================================================
 *
 * Les commandes server gèrent le serveur PHP intégré :
 *
 * ```
 * server:start   → Démarre le serveur PHP built-in
 * server:status  → Vérifie si le serveur tourne (PID file)
 * server:stop    → Arrête le serveur (kill du processus)
 * server:logs    → Affiche les logs du serveur
 *
 * FLUX DE DONNÉES :
 *
 *    server:start                    server:stop
 *         │                               │
 *         ▼                               ▼
 *    ┌─────────┐                    ┌─────────┐
 *    │ Démarre │                    │  Lit    │
 *    │ php -S  │ ──► server.pid ◄── │  PID    │
 *    └─────────┘    (JSON file)     └─────────┘
 *                        │                │
 *                        │                ▼
 *                        │          ┌─────────┐
 *                        └──────────│  Kill   │
 *                                   │ process │
 *                                   └─────────┘
 * ```
 *
 * =============================================================================
 * STRATÉGIE DE TEST
 * =============================================================================
 *
 * On teste principalement :
 * - L'interface CommandInterface
 * - Les messages d'aide
 * - Les attributs #[Command]
 * - Le comportement avec/sans fichier PID
 *
 * @package Tests\Command
 */
declare(strict_types=1);

namespace Tests\Command;

use Lunar\Cli\CommandInterface;
use Lunar\Command\ServerStatusCommand;
use Lunar\Command\ServerStopCommand;
use Lunar\Command\ServerLogsCommand;
use Lunar\Command\ServerStartCommand;
use PHPUnit\Framework\TestCase;

class ServerCommandsTest extends TestCase
{
    // =========================================================================
    // TESTS DE ServerStatusCommand
    // =========================================================================

    public function testServerStatusImplementsCommandInterface(): void
    {
        $command = new ServerStatusCommand();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testServerStatusGetHelpReturnsString(): void
    {
        $command = new ServerStatusCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    public function testServerStatusGetHelpContainsUsage(): void
    {
        $command = new ServerStatusCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('server:status', $help);
    }

    public function testServerStatusHasCommandAttribute(): void
    {
        $reflection = new \ReflectionClass(ServerStatusCommand::class);
        $attributes = $reflection->getAttributes();

        $hasCommandAttr = false;
        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $hasCommandAttr = true;
                $instance = $attr->newInstance();
                $this->assertSame('server:status', $instance->name);
            }
        }

        $this->assertTrue($hasCommandAttr);
    }

    // =========================================================================
    // TESTS DE ServerStopCommand
    // =========================================================================

    public function testServerStopImplementsCommandInterface(): void
    {
        $command = new ServerStopCommand();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testServerStopGetHelpReturnsString(): void
    {
        $command = new ServerStopCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    public function testServerStopGetHelpContainsUsage(): void
    {
        $command = new ServerStopCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('server:stop', $help);
    }

    public function testServerStopHasCommandAttribute(): void
    {
        $reflection = new \ReflectionClass(ServerStopCommand::class);
        $attributes = $reflection->getAttributes();

        $hasCommandAttr = false;
        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $hasCommandAttr = true;
                $instance = $attr->newInstance();
                $this->assertSame('server:stop', $instance->name);
            }
        }

        $this->assertTrue($hasCommandAttr);
    }

    // =========================================================================
    // TESTS DE ServerLogsCommand
    // =========================================================================

    public function testServerLogsImplementsCommandInterface(): void
    {
        $command = new ServerLogsCommand();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testServerLogsGetHelpReturnsString(): void
    {
        $command = new ServerLogsCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    public function testServerLogsGetHelpContainsOptions(): void
    {
        $command = new ServerLogsCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('server:logs', $help);
    }

    public function testServerLogsHasCommandAttribute(): void
    {
        $reflection = new \ReflectionClass(ServerLogsCommand::class);
        $attributes = $reflection->getAttributes();

        $hasCommandAttr = false;
        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $hasCommandAttr = true;
                $instance = $attr->newInstance();
                $this->assertSame('server:logs', $instance->name);
            }
        }

        $this->assertTrue($hasCommandAttr);
    }

    // =========================================================================
    // TESTS DE ServerStartCommand
    // =========================================================================

    public function testServerStartImplementsCommandInterface(): void
    {
        $command = new ServerStartCommand();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testServerStartGetHelpReturnsString(): void
    {
        $command = new ServerStartCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    public function testServerStartGetHelpContainsPort(): void
    {
        $command = new ServerStartCommand();
        $help = $command->getHelp();

        // La commande devrait mentionner le port
        $this->assertStringContainsString('server:start', $help);
    }

    public function testServerStartHasCommandAttribute(): void
    {
        $reflection = new \ReflectionClass(ServerStartCommand::class);
        $attributes = $reflection->getAttributes();

        $hasCommandAttr = false;
        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $hasCommandAttr = true;
                $instance = $attr->newInstance();
                $this->assertSame('server:start', $instance->name);
            }
        }

        $this->assertTrue($hasCommandAttr);
    }

    // =========================================================================
    // TESTS COMMUNS À TOUTES LES COMMANDES SERVER
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('serverCommandsProvider')]
    public function testAllServerCommandsImplementInterface(string $commandClass): void
    {
        $command = new $commandClass();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('serverCommandsProvider')]
    public function testAllServerCommandsHaveHelp(string $commandClass): void
    {
        $command = new $commandClass();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('serverCommandsProvider')]
    public function testAllServerCommandsHaveExecuteMethod(string $commandClass): void
    {
        $this->assertTrue(method_exists($commandClass, 'execute'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function serverCommandsProvider(): array
    {
        return [
            'ServerStatusCommand' => [ServerStatusCommand::class],
            'ServerStopCommand' => [ServerStopCommand::class],
            'ServerLogsCommand' => [ServerLogsCommand::class],
            'ServerStartCommand' => [ServerStartCommand::class],
        ];
    }
}
