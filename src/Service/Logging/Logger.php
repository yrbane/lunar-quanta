<?php

declare(strict_types=1);

namespace Lunar\Service\Logging;

use Lunar\Service\Logging\Handler\LogHandlerInterface;

/**
 * Logger principal compatible PSR-3.
 *
 * Architecture multi-handler inspirée de Monolog :
 * - Un logger peut avoir plusieurs handlers
 * - Chaque handler peut avoir un niveau minimum différent
 * - Le contexte est interpolé dans le message (placeholders {})
 * - Support des exceptions dans le contexte
 *
 * @example
 * ```php
 * // Logger simple fichier
 * $logger = new Logger('app', [
 *     new FileHandler('/var/log/app.log')
 * ]);
 *
 * // Logger multi-handler : tout en fichier, erreurs par email
 * $logger = new Logger('app', [
 *     new FileHandler('/var/log/app.log'),
 *     new FileHandler('/var/log/errors.log', LogLevel::ERROR),
 * ]);
 *
 * // Log avec contexte
 * $logger->info('User {username} logged in', ['username' => 'john']);
 *
 * // Log avec exception
 * $logger->error('Request failed', ['exception' => $e]);
 * ```
 */
final class Logger implements LoggerInterface
{
    /** @var array<LogHandlerInterface> */
    private array $handlers;

    /** @var array<string, mixed> */
    private array $globalContext = [];

    /**
     * @param string $channel Nom du canal (ex: 'app', 'security', 'sql')
     * @param array<LogHandlerInterface> $handlers Liste des handlers
     */
    public function __construct(
        private readonly string $channel,
        array $handlers = []
    ) {
        $this->handlers = $handlers;
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log(string $level, string|\Stringable $message, array $context = []): void
    {
        if (!LogLevel::isValid($level)) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }

        // Fusion du contexte global et local (local prioritaire)
        $mergedContext = array_merge($this->globalContext, $context);

        // Traitement des exceptions dans le contexte
        $processedContext = $this->processContext($mergedContext);

        // Interpolation des placeholders dans le message
        $interpolatedMessage = $this->interpolate((string) $message, $mergedContext);

        // Création de l'enregistrement
        $record = [
            'datetime' => new \DateTimeImmutable(),
            'channel' => $this->channel,
            'level' => $level,
            'message' => $interpolatedMessage,
            'context' => $processedContext,
        ];

        // Envoi à tous les handlers
        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($level)) {
                $handler->handle($record);
            }
        }
    }

    /**
     * Ajoute un handler au logger.
     */
    public function addHandler(LogHandlerInterface $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * Définit le contexte global ajouté à tous les logs.
     *
     * @param array<string, mixed> $context
     */
    public function setGlobalContext(array $context): self
    {
        $this->globalContext = $context;
        return $this;
    }

    /**
     * Ajoute des éléments au contexte global.
     *
     * @param array<string, mixed> $context
     */
    public function addGlobalContext(array $context): self
    {
        $this->globalContext = array_merge($this->globalContext, $context);
        return $this;
    }

    /**
     * Retourne le nom du canal.
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Interpole les placeholders {key} dans le message.
     *
     * PSR-3 spécifie que les placeholders sont entourés d'accolades.
     *
     * @param array<string, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];

        foreach ($context as $key => $value) {
            if ($key === 'exception') {
                continue; // Les exceptions ne sont pas interpolées
            }

            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replacements['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replacements);
    }

    /**
     * Traite le contexte pour convertir les objets en strings.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function processContext(array $context): array
    {
        $processed = [];

        foreach ($context as $key => $value) {
            if ($key === 'exception' && $value instanceof \Throwable) {
                $processed[$key] = $this->formatException($value);
            } elseif (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $processed[$key] = (string) $value;
                } elseif ($value instanceof \DateTimeInterface) {
                    $processed[$key] = $value->format(\DateTimeInterface::RFC3339);
                } else {
                    $processed[$key] = get_class($value);
                }
            } elseif (is_resource($value)) {
                $processed[$key] = 'resource(' . get_resource_type($value) . ')';
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }

    /**
     * Formate une exception en string lisible.
     */
    private function formatException(\Throwable $e): string
    {
        return sprintf(
            "%s: %s in %s:%d\nStack trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }
}
