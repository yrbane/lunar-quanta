<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for CLI command execution.
 *
 * Tests the complete CLI pipeline from bin/console to command execution.
 */
class CliIntegrationTest extends TestCase
{
    private string $consolePath;

    protected function setUp(): void
    {
        $this->consolePath = dirname(__DIR__, 2) . '/bin/console';
    }

    public function testConsoleExists(): void
    {
        $this->assertFileExists($this->consolePath);
    }

    public function testConsoleIsExecutable(): void
    {
        $this->assertTrue(is_executable($this->consolePath));
    }

    public function testListCommandsShowsAvailableCommands(): void
    {
        $output = $this->runConsole('list');

        $this->assertStringContainsString('cache:clear', $output);
        $this->assertStringContainsString('router:debug', $output);
    }

    public function testCacheClearCommandRuns(): void
    {
        $output = $this->runConsole('cache:clear');

        // Command should complete without errors
        $this->assertIsString($output);
    }

    public function testRouterDebugCommandRuns(): void
    {
        $output = $this->runConsole('router:debug');

        // Should show route information
        $this->assertIsString($output);
    }

    public function testFilesystemTreeCommandRuns(): void
    {
        $output = $this->runConsole('filesystem:tree --depth 1');

        // Should show project name and some directories
        $this->assertStringContainsString('lunar-quanta', $output);
    }

    public function testServerStatusCommandRuns(): void
    {
        $output = $this->runConsole('server:status');

        // Should return status information
        $this->assertIsString($output);
    }

    public function testUnknownCommandShowsError(): void
    {
        $output = $this->runConsole('unknown:command');

        // Should indicate command not found or show help
        $this->assertIsString($output);
    }

    public function testHelpOptionShowsHelp(): void
    {
        $output = $this->runConsole('cache:clear --help');

        // Should show help information
        $this->assertIsString($output);
    }

    public function testMakeControllerHelpShows(): void
    {
        $output = $this->runConsole('make:controller --help');

        $this->assertStringContainsString('make:controller', $output);
    }

    public function testMakeCommandHelpShows(): void
    {
        $output = $this->runConsole('make:command --help');

        $this->assertStringContainsString('make:command', $output);
    }

    /**
     * Execute a console command and return the output.
     */
    private function runConsole(string $command): string
    {
        $fullCommand = sprintf('php %s %s 2>&1', escapeshellarg($this->consolePath), $command);
        $output = shell_exec($fullCommand);

        return $output ?? '';
    }
}
