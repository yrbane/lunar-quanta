<?php

declare(strict_types=1);

namespace Lunar\Service\Logging\Handler;

use Lunar\Service\Logging\LogLevel;
use Lunar\Service\Logging\Formatter\LogFormatterInterface;
use Lunar\Service\Logging\Formatter\LineFormatter;

/**
 * Handler qui écrit les logs dans un fichier.
 *
 * Caractéristiques :
 * - Crée automatiquement le répertoire si nécessaire
 * - Append mode (ne remplace pas le fichier existant)
 * - Support de différents formateurs
 * - Niveau minimum configurable
 *
 * @example
 * ```php
 * $handler = new FileHandler('/var/log/app.log');
 * $logger = new Logger('app', [$handler]);
 *
 * $logger->info('Application started');
 * // Écrit dans /var/log/app.log :
 * // [2025-01-15 10:30:00] app.INFO: Application started
 * ```
 */
final class FileHandler implements LogHandlerInterface
{
    private readonly LogFormatterInterface $formatter;

    /** @var resource|null */
    private $stream = null;

    public function __construct(
        private readonly string $filepath,
        private readonly string $minimumLevel = LogLevel::DEBUG,
        ?LogFormatterInterface $formatter = null
    ) {
        $this->formatter = $formatter ?? new LineFormatter();
    }

    public function handle(array $record): void
    {
        if (!$this->isHandling($record['level'])) {
            return;
        }

        $this->ensureStreamOpen();

        $formatted = $this->formatter->format($record);
        fwrite($this->stream, $formatted);
    }

    public function isHandling(string $level): bool
    {
        return LogLevel::isAtLeast($level, $this->minimumLevel);
    }

    /**
     * Ferme le stream de fichier.
     */
    public function close(): void
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Assure que le stream est ouvert.
     */
    private function ensureStreamOpen(): void
    {
        if ($this->stream !== null) {
            return;
        }

        $dir = dirname($this->filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stream = fopen($this->filepath, 'a');
        if ($stream === false) {
            throw new \RuntimeException("Cannot open log file: {$this->filepath}");
        }

        $this->stream = $stream;
    }
}
