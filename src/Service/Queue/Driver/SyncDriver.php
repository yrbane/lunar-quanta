<?php

declare(strict_types=1);

namespace Lunar\Service\Queue\Driver;

use Lunar\Service\Queue\JobInterface;

/**
 * Driver synchrone pour les tests et le développement.
 *
 * Exécute les jobs immédiatement au lieu de les mettre en queue.
 * Utile pour les tests où on veut voir les résultats immédiatement.
 *
 * @example
 * ```php
 * $queue = new Queue(new SyncDriver());
 * $queue->push(new SendEmailJob($to, $subject, $body));
 * // L'email est envoyé immédiatement
 * ```
 */
final class SyncDriver implements DriverInterface
{
    private int $jobCounter = 0;

    public function push(string $queue, JobInterface $job, int $delay = 0): string
    {
        // Exécution immédiate (ignore le délai en mode sync)
        $job->handle();

        return 'sync_' . ++$this->jobCounter;
    }

    public function pop(string $queue = 'default'): ?JobInterface
    {
        // En mode sync, les jobs sont exécutés immédiatement
        // Il n'y a jamais de job en attente
        return null;
    }

    public function hasJobs(string $queue = 'default'): bool
    {
        return false;
    }

    public function hasAvailableJobs(string $queue = 'default'): bool
    {
        return false;
    }

    public function count(string $queue = 'default'): int
    {
        return 0;
    }
}
