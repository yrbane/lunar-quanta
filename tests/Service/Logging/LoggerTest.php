<?php

declare(strict_types=1);

namespace Tests\Service\Logging;

use Lunar\Service\Logging\Logger;
use Lunar\Service\Logging\LogLevel;
use Lunar\Service\Logging\Handler\LogHandlerInterface;
use Lunar\Service\Logging\Handler\FileHandler;
use Lunar\Service\Logging\Handler\ArrayHandler;
use Lunar\Service\Logging\Formatter\LogFormatterInterface;
use Lunar\Service\Logging\Formatter\LineFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le système de logging PSR-3 compatible.
 *
 * PSR-3 définit une interface standard pour les loggers PHP.
 * Notre implémentation respecte cette interface tout en restant
 * sans dépendance externe (Constitution II).
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
final class LoggerTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/lunar_test_' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    // =========================================================================
    // Tests des niveaux de log (PSR-3 Log Levels)
    // =========================================================================

    public function testEmergencyLogsWithCorrectLevel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->emergency('System is unusable');

        $logs = $handler->getLogs();
        $this->assertCount(1, $logs);
        $this->assertSame(LogLevel::EMERGENCY, $logs[0]['level']);
        $this->assertSame('System is unusable', $logs[0]['message']);
    }

    public function testAlertLogsWithCorrectLevel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->alert('Action must be taken immediately');

        $logs = $handler->getLogs();
        $this->assertSame(LogLevel::ALERT, $logs[0]['level']);
    }

    public function testCriticalLogsWithCorrectLevel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->critical('Critical conditions');

        $logs = $handler->getLogs();
        $this->assertSame(LogLevel::CRITICAL, $logs[0]['level']);
    }

    public function testErrorLogsWithCorrectLevel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->error('Runtime errors');

        $logs = $handler->getLogs();
        $this->assertSame(LogLevel::ERROR, $logs[0]['level']);
    }

    public function testWarningLogsWithCorrectLevel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->warning('Exceptional occurrences that are not errors');

        $logs = $handler->getLogs();
        $this->assertSame(LogLevel::WARNING, $logs[0]['level']);
    }

    public function testNoticeLogsWithCorrectLevel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->notice('Normal but significant events');

        $logs = $handler->getLogs();
        $this->assertSame(LogLevel::NOTICE, $logs[0]['level']);
    }

    public function testInfoLogsWithCorrectLevel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->info('Interesting events');

        $logs = $handler->getLogs();
        $this->assertSame(LogLevel::INFO, $logs[0]['level']);
    }

    public function testDebugLogsWithCorrectLevel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->debug('Detailed debug information');

        $logs = $handler->getLogs();
        $this->assertSame(LogLevel::DEBUG, $logs[0]['level']);
    }

    // =========================================================================
    // Tests de l'interpolation de contexte (PSR-3 Context)
    // =========================================================================

    public function testContextInterpolation(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->info('User {username} logged in from {ip}', [
            'username' => 'john_doe',
            'ip' => '192.168.1.1'
        ]);

        $logs = $handler->getLogs();
        $this->assertSame('User john_doe logged in from 192.168.1.1', $logs[0]['message']);
    }

    public function testContextWithMissingPlaceholderIsPreserved(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->info('User {username} has role {role}', [
            'username' => 'jane'
            // 'role' is missing
        ]);

        $logs = $handler->getLogs();
        $this->assertSame('User jane has role {role}', $logs[0]['message']);
    }

    public function testContextWithExtraDataIsStoredInContext(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->info('User logged in', [
            'user_id' => 42,
            'session_id' => 'abc123'
        ]);

        $logs = $handler->getLogs();
        $this->assertSame(42, $logs[0]['context']['user_id']);
        $this->assertSame('abc123', $logs[0]['context']['session_id']);
    }

    public function testContextWithExceptionIncludesStackTrace(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $exception = new \RuntimeException('Something went wrong', 500);
        $logger->error('An error occurred', ['exception' => $exception]);

        $logs = $handler->getLogs();
        $this->assertArrayHasKey('exception', $logs[0]['context']);
        $this->assertStringContainsString('RuntimeException', $logs[0]['context']['exception']);
        $this->assertStringContainsString('Something went wrong', $logs[0]['context']['exception']);
    }

    // =========================================================================
    // Tests du canal (channel) de log
    // =========================================================================

    public function testLoggerHasChannel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('app', [$handler]);

        $logger->info('Test message');

        $logs = $handler->getLogs();
        $this->assertSame('app', $logs[0]['channel']);
    }

    public function testDifferentChannelsForDifferentLoggers(): void
    {
        $handler = new ArrayHandler();
        $appLogger = new Logger('app', [$handler]);
        $securityLogger = new Logger('security', [$handler]);

        $appLogger->info('App event');
        $securityLogger->warning('Security event');

        $logs = $handler->getLogs();
        $this->assertSame('app', $logs[0]['channel']);
        $this->assertSame('security', $logs[1]['channel']);
    }

    // =========================================================================
    // Tests du FileHandler
    // =========================================================================

    public function testFileHandlerWritesToFile(): void
    {
        $handler = new FileHandler($this->logFile);
        $logger = new Logger('test', [$handler]);

        $logger->info('Test message');

        $this->assertFileExists($this->logFile);
        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('Test message', $content);
        $this->assertStringContainsString('INFO', $content);
    }

    public function testFileHandlerAppendsToExistingFile(): void
    {
        $handler = new FileHandler($this->logFile);
        $logger = new Logger('test', [$handler]);

        $logger->info('First message');
        $logger->info('Second message');

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('First message', $content);
        $this->assertStringContainsString('Second message', $content);
    }

    public function testFileHandlerCreatesDirectoryIfNotExists(): void
    {
        $tempDir = sys_get_temp_dir() . '/lunar_logs_test_' . uniqid();
        $logFile = $tempDir . '/app.log';

        try {
            $handler = new FileHandler($logFile);
            $logger = new Logger('test', [$handler]);
            $logger->info('Test');

            $this->assertFileExists($logFile);
        } finally {
            if (file_exists($logFile)) {
                unlink($logFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    // =========================================================================
    // Tests du niveau minimum
    // =========================================================================

    public function testHandlerRespectsMinimumLevel(): void
    {
        $handler = new ArrayHandler(LogLevel::WARNING);
        $logger = new Logger('test', [$handler]);

        $logger->debug('Debug message');  // Should be ignored
        $logger->info('Info message');    // Should be ignored
        $logger->warning('Warning message'); // Should be logged
        $logger->error('Error message');  // Should be logged

        $logs = $handler->getLogs();
        $this->assertCount(2, $logs);
        $this->assertSame('Warning message', $logs[0]['message']);
        $this->assertSame('Error message', $logs[1]['message']);
    }

    // =========================================================================
    // Tests des handlers multiples
    // =========================================================================

    public function testLoggerCanHaveMultipleHandlers(): void
    {
        $arrayHandler = new ArrayHandler();
        $fileHandler = new FileHandler($this->logFile);
        $logger = new Logger('test', [$arrayHandler, $fileHandler]);

        $logger->info('Multi-handler test');

        // ArrayHandler received the log
        $logs = $arrayHandler->getLogs();
        $this->assertCount(1, $logs);

        // FileHandler wrote to file
        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('Multi-handler test', $content);
    }

    // =========================================================================
    // Tests du formatter
    // =========================================================================

    public function testLineFormatterFormatsCorrectly(): void
    {
        $formatter = new LineFormatter();

        $record = [
            'datetime' => new \DateTimeImmutable('2025-01-15 10:30:00'),
            'channel' => 'app',
            'level' => LogLevel::INFO,
            'message' => 'Test message',
            'context' => ['user_id' => 42]
        ];

        $formatted = $formatter->format($record);

        $this->assertStringContainsString('[2025-01-15 10:30:00]', $formatted);
        $this->assertStringContainsString('app.INFO', $formatted);
        $this->assertStringContainsString('Test message', $formatted);
        $this->assertStringContainsString('"user_id":42', $formatted);
    }

    public function testLineFormatterOmitsEmptyContext(): void
    {
        $formatter = new LineFormatter();

        $record = [
            'datetime' => new \DateTimeImmutable('2025-01-15 10:30:00'),
            'channel' => 'app',
            'level' => LogLevel::INFO,
            'message' => 'Test message',
            'context' => []
        ];

        $formatted = $formatter->format($record);

        $this->assertStringNotContainsString('{}', $formatted);
    }

    // =========================================================================
    // Tests de la méthode log() générique
    // =========================================================================

    public function testLogMethodWithStringLevel(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $logger->log(LogLevel::ERROR, 'Error via log()');

        $logs = $handler->getLogs();
        $this->assertSame(LogLevel::ERROR, $logs[0]['level']);
    }

    public function testLogMethodWithInvalidLevelThrowsException(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level');

        $logger->log('invalid_level', 'Test');
    }

    // =========================================================================
    // Tests du timestamp
    // =========================================================================

    public function testLogRecordIncludesTimestamp(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);

        $before = new \DateTimeImmutable();
        $logger->info('Test');
        $after = new \DateTimeImmutable();

        $logs = $handler->getLogs();
        $timestamp = $logs[0]['datetime'];

        $this->assertInstanceOf(\DateTimeImmutable::class, $timestamp);
        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }

    // =========================================================================
    // Tests de l'ajout dynamique de handlers
    // =========================================================================

    public function testCanAddHandlerAfterConstruction(): void
    {
        $logger = new Logger('test');
        $handler = new ArrayHandler();

        $logger->addHandler($handler);
        $logger->info('Test after adding handler');

        $logs = $handler->getLogs();
        $this->assertCount(1, $logs);
    }

    // =========================================================================
    // Tests de contexte global (processors)
    // =========================================================================

    public function testLoggerCanHaveGlobalContext(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);
        $logger->setGlobalContext([
            'app_version' => '1.0.0',
            'environment' => 'test'
        ]);

        $logger->info('Test message', ['request_id' => 'abc123']);

        $logs = $handler->getLogs();
        $context = $logs[0]['context'];

        $this->assertSame('1.0.0', $context['app_version']);
        $this->assertSame('test', $context['environment']);
        $this->assertSame('abc123', $context['request_id']);
    }

    public function testLocalContextOverridesGlobalContext(): void
    {
        $handler = new ArrayHandler();
        $logger = new Logger('test', [$handler]);
        $logger->setGlobalContext(['user' => 'global_user']);

        $logger->info('Test', ['user' => 'local_user']);

        $logs = $handler->getLogs();
        $this->assertSame('local_user', $logs[0]['context']['user']);
    }
}
