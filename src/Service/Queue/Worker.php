<?php

declare(strict_types=1);

namespace Lunar\Service\Queue;

use Lunar\Service\Queue\Driver\DriverInterface;

/**
 * Worker pour traiter les jobs de la queue.
 *
 * Le worker récupère et exécute les jobs en attente.
 * Il peut être lancé en tant que processus de fond.
 *
 * @example
 * ```php
 * $worker = new Worker(new FileDriver('/path/to/queue'));
 *
 * // Traiter un seul job
 * $worker->processNext();
 *
 * // Traiter tous les jobs en attente
 * $worker->processAll();
 *
 * // Boucle de travail (pour un daemon)
 * while (true) {
 *     $worker->processNext();
 *     sleep(1);
 * }
 * ```
 */
final class Worker
{
    private int $processedCount = 0;
    private int $failedCount = 0;

    /** @var callable|null */
    private $failureHandler = null;

    public function __construct(
        private readonly DriverInterface $driver,
        private readonly string $queue = 'default'
    ) {
    }

    /**
     * Définit un handler personnalisé pour les jobs échoués.
     *
     * @param callable(JobInterface, \Throwable): void $handler
     */
    public function setFailureHandler(callable $handler): self
    {
        $this->failureHandler = $handler;
        return $this;
    }

    /**
     * Traite le prochain job de la queue.
     *
     * @return bool True si un job a été traité, false si la queue est vide
     */
    public function processNext(): bool
    {
        $job = $this->driver->pop($this->queue);

        if ($job === null) {
            return false;
        }

        try {
            $job->handle();
            $this->processedCount++;
        } catch (\Throwable $e) {
            $this->failedCount++;
            $this->handleFailedJob($job, $e);
        }

        return true;
    }

    /**
     * Traite tous les jobs en attente.
     *
     * @return int Le nombre de jobs traités
     */
    public function processAll(): int
    {
        $count = 0;

        while ($this->processNext()) {
            $count++;
        }

        return $count;
    }

    /**
     * Traite les jobs pendant un certain temps.
     *
     * @param int $seconds Durée maximale en secondes
     * @param int $sleep Pause entre chaque vérification (microsecondes)
     * @return int Le nombre de jobs traités
     */
    public function work(int $seconds, int $sleep = 100000): int
    {
        $count = 0;
        $endTime = time() + $seconds;

        while (time() < $endTime) {
            if ($this->processNext()) {
                $count++;
            } else {
                usleep($sleep);
            }
        }

        return $count;
    }

    /**
     * Retourne le nombre de jobs traités avec succès.
     */
    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    /**
     * Retourne le nombre de jobs échoués.
     */
    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    /**
     * Réinitialise les compteurs.
     */
    public function resetCounters(): void
    {
        $this->processedCount = 0;
        $this->failedCount = 0;
    }

    /**
     * Gère un job qui a échoué.
     */
    private function handleFailedJob(JobInterface $job, \Throwable $exception): void
    {
        // Utiliser le handler personnalisé s'il est défini
        if ($this->failureHandler !== null) {
            ($this->failureHandler)($job, $exception);
            return;
        }

        // Comportement par défaut : log l'erreur
        // On pourrait implémenter :
        // - Retry avec backoff exponentiel
        // - Stockage dans une table "failed_jobs"
        // - Notification par email
        error_log(sprintf(
            "Job failed: %s - %s\nPayload: %s",
            get_class($job),
            $exception->getMessage(),
            json_encode($job->getPayload())
        ));
    }
}
