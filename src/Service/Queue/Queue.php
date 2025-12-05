<?php

declare(strict_types=1);

namespace Lunar\Service\Queue;

use Lunar\Service\Queue\Driver\DriverInterface;

/**
 * Service de queue principal.
 *
 * Fournit une interface simple pour pousser des jobs dans une queue.
 * Le driver sous-jacent détermine comment les jobs sont stockés
 * et exécutés (sync, fichier, Redis, etc.).
 *
 * @example
 * ```php
 * // Configuration
 * $queue = new Queue(new FileDriver('/path/to/queue'));
 *
 * // Ajouter un job
 * $queue->push(new SendEmailJob($to, $subject, $body));
 *
 * // Ajouter à une queue spécifique
 * $queue->pushOn('emails', new SendEmailJob(...));
 *
 * // Ajouter avec un délai
 * $queue->later(60, new SendReminderJob(...)); // Dans 1 minute
 * ```
 */
final class Queue implements QueueInterface
{
    private const DEFAULT_QUEUE = 'default';

    public function __construct(
        private readonly DriverInterface $driver
    ) {
    }

    public function push(JobInterface $job): string
    {
        return $this->driver->push(self::DEFAULT_QUEUE, $job);
    }

    public function pushOn(string $queue, JobInterface $job): string
    {
        return $this->driver->push($queue, $job);
    }

    public function later(int $delay, JobInterface $job): string
    {
        return $this->driver->push(self::DEFAULT_QUEUE, $job, $delay);
    }

    /**
     * Ajoute un job à une queue avec un délai.
     */
    public function laterOn(string $queue, int $delay, JobInterface $job): string
    {
        return $this->driver->push($queue, $job, $delay);
    }

    /**
     * Retourne le driver sous-jacent.
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }
}
