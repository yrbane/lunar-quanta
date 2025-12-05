<?php

declare(strict_types=1);

namespace Lunar\Service\Queue;

/**
 * Interface pour le service de queue.
 */
interface QueueInterface
{
    /**
     * Ajoute un job à la queue par défaut.
     *
     * @return string L'ID unique du job
     */
    public function push(JobInterface $job): string;

    /**
     * Ajoute un job à une queue nommée.
     *
     * @return string L'ID unique du job
     */
    public function pushOn(string $queue, JobInterface $job): string;

    /**
     * Ajoute un job avec un délai.
     *
     * @param int $delay Délai en secondes
     * @return string L'ID unique du job
     */
    public function later(int $delay, JobInterface $job): string;
}
