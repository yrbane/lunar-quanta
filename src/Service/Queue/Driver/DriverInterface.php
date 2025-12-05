<?php

declare(strict_types=1);

namespace Lunar\Service\Queue\Driver;

use Lunar\Service\Queue\JobInterface;

/**
 * Interface pour les drivers de queue.
 *
 * Un driver gère le stockage et la récupération des jobs.
 */
interface DriverInterface
{
    /**
     * Ajoute un job à la queue.
     *
     * @param string $queue Nom de la queue
     * @param JobInterface $job Le job à ajouter
     * @param int $delay Délai en secondes (0 = immédiat)
     * @return string L'ID unique du job
     */
    public function push(string $queue, JobInterface $job, int $delay = 0): string;

    /**
     * Récupère et retire le prochain job de la queue.
     *
     * @param string $queue Nom de la queue
     * @return JobInterface|null Le job ou null si la queue est vide
     */
    public function pop(string $queue = 'default'): ?JobInterface;

    /**
     * Vérifie si la queue contient des jobs.
     */
    public function hasJobs(string $queue = 'default'): bool;

    /**
     * Vérifie si la queue contient des jobs disponibles (non délayés).
     */
    public function hasAvailableJobs(string $queue = 'default'): bool;

    /**
     * Compte le nombre de jobs dans la queue.
     */
    public function count(string $queue = 'default'): int;
}
