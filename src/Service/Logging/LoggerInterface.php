<?php

declare(strict_types=1);

namespace Lunar\Service\Logging;

/**
 * Interface de logger compatible PSR-3.
 *
 * Cette interface définit les 8 méthodes de log correspondant aux
 * 8 niveaux RFC 5424, plus la méthode générique log().
 *
 * Bien que nous n'implémentions pas directement Psr\Log\LoggerInterface
 * (car nous n'avons pas de dépendances), notre interface est 100%
 * compatible avec PSR-3.
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function emergency(string|\Stringable $message, array $context = []): void;

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function alert(string|\Stringable $message, array $context = []): void;

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function critical(string|\Stringable $message, array $context = []): void;

    /**
     * Runtime errors that do not require immediate action.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function error(string|\Stringable $message, array $context = []): void;

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function warning(string|\Stringable $message, array $context = []): void;

    /**
     * Normal but significant events.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function notice(string|\Stringable $message, array $context = []): void;

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function info(string|\Stringable $message, array $context = []): void;

    /**
     * Detailed debug information.
     *
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function debug(string|\Stringable $message, array $context = []): void;

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level One of LogLevel constants
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function log(string $level, string|\Stringable $message, array $context = []): void;
}
