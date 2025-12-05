<?php

declare(strict_types=1);

namespace Lunar\Service\Logging\Handler;

use Lunar\Service\Logging\LogLevel;

/**
 * Handler qui stocke les logs en mémoire dans un array.
 *
 * Ce handler est principalement utilisé pour les tests :
 * - Vérifier qu'un log a été émis
 * - Inspecter le contenu des logs
 * - Éviter les effets de bord (pas d'écriture fichier)
 *
 * Ne pas utiliser en production (consommation mémoire).
 *
 * @example
 * ```php
 * $handler = new ArrayHandler();
 * $logger = new Logger('test', [$handler]);
 *
 * $logger->info('Test message');
 *
 * $logs = $handler->getLogs();
 * // $logs[0]['message'] === 'Test message'
 * ```
 */
final class ArrayHandler implements LogHandlerInterface
{
    /**
     * @var array<int, array{
     *     datetime: \DateTimeImmutable,
     *     channel: string,
     *     level: string,
     *     message: string,
     *     context: array<string, mixed>
     * }>
     */
    private array $logs = [];

    public function __construct(
        private readonly string $minimumLevel = LogLevel::DEBUG
    ) {
    }

    public function handle(array $record): void
    {
        if (!$this->isHandling($record['level'])) {
            return;
        }

        $this->logs[] = $record;
    }

    public function isHandling(string $level): bool
    {
        return LogLevel::isAtLeast($level, $this->minimumLevel);
    }

    /**
     * Retourne tous les logs enregistrés.
     *
     * @return array<int, array{
     *     datetime: \DateTimeImmutable,
     *     channel: string,
     *     level: string,
     *     message: string,
     *     context: array<string, mixed>
     * }>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Vide les logs enregistrés.
     */
    public function clear(): void
    {
        $this->logs = [];
    }

    /**
     * Vérifie si un message a été loggé.
     */
    public function hasLog(string $message, ?string $level = null): bool
    {
        foreach ($this->logs as $log) {
            if (str_contains($log['message'], $message)) {
                if ($level === null || $log['level'] === $level) {
                    return true;
                }
            }
        }

        return false;
    }
}
