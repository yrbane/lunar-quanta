<?php

declare(strict_types=1);

namespace Lunar\Service\Queue\Driver;

use Lunar\Service\Queue\JobInterface;

/**
 * Driver basé sur fichiers pour les queues.
 *
 * Stocke les jobs dans des fichiers JSON, sans dépendance externe
 * (pas de Redis, RabbitMQ, etc.). Adapté aux petites applications
 * ou quand les dépendances doivent être minimales.
 *
 * Structure des fichiers :
 * ```
 * data/queue/
 * ├── default/
 * │   ├── job_001.json
 * │   └── job_002.json
 * └── emails/
 *     └── job_003.json
 * ```
 *
 * @example
 * ```php
 * $driver = new FileDriver('/path/to/queue');
 * $queue = new Queue($driver);
 *
 * $queue->push(new SendEmailJob(...));
 * // Le job est sérialisé dans un fichier JSON
 * ```
 */
final class FileDriver implements DriverInterface
{
    private static int $sequence = 0;

    public function __construct(
        private readonly string $basePath
    ) {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    public function push(string $queue, JobInterface $job, int $delay = 0): string
    {
        $queuePath = $this->getQueuePath($queue);
        $jobId = $this->generateJobId();
        $availableAt = $delay > 0 ? time() + $delay : 0;

        $data = [
            'id' => $jobId,
            'class' => get_class($job),
            'payload' => $job->getPayload(),
            'available_at' => $availableAt,
            'created_at' => time(),
        ];

        $filename = $queuePath . '/' . $jobId . '.json';
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

        return $jobId;
    }

    public function pop(string $queue = 'default'): ?JobInterface
    {
        $queuePath = $this->getQueuePath($queue);

        if (!is_dir($queuePath)) {
            return null;
        }

        $files = glob($queuePath . '/*.json');
        if (empty($files)) {
            return null;
        }

        // Trier par nom (FIFO basé sur le timestamp du job ID)
        sort($files);

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            // Vérifier si le job est disponible (délai expiré)
            if ($data['available_at'] > 0 && $data['available_at'] > time()) {
                continue;
            }

            // Supprimer le fichier (job pris)
            unlink($file);

            // Recréer le job
            return $this->createJob($data);
        }

        return null;
    }

    public function hasJobs(string $queue = 'default'): bool
    {
        return $this->count($queue) > 0;
    }

    public function hasAvailableJobs(string $queue = 'default'): bool
    {
        $queuePath = $this->getQueuePath($queue);

        if (!is_dir($queuePath)) {
            return false;
        }

        $files = glob($queuePath . '/*.json');
        if (empty($files)) {
            return false;
        }

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            // Un job sans délai ou avec délai expiré est disponible
            if ($data['available_at'] === 0 || $data['available_at'] <= time()) {
                return true;
            }
        }

        return false;
    }

    public function count(string $queue = 'default'): int
    {
        $queuePath = $this->getQueuePath($queue);

        if (!is_dir($queuePath)) {
            return 0;
        }

        $files = glob($queuePath . '/*.json');
        return count($files);
    }

    /**
     * Retourne le chemin du dossier de la queue.
     */
    private function getQueuePath(string $queue): string
    {
        $path = $this->basePath . '/' . $queue;

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }

    /**
     * Génère un ID unique pour un job.
     *
     * Utilise microtime + séquence pour garantir l'ordre FIFO.
     */
    private function generateJobId(): string
    {
        $microtime = sprintf('%0.6f', microtime(true));
        $sequence = sprintf('%06d', ++self::$sequence);

        return sprintf('%s_%s_%s', $microtime, $sequence, bin2hex(random_bytes(4)));
    }

    /**
     * Recrée un job à partir des données sérialisées.
     *
     * @param array<string, mixed> $data
     */
    private function createJob(array $data): JobInterface
    {
        $class = $data['class'];
        $payload = $data['payload'];

        // Vérifier que la classe existe et implémente JobInterface
        if (!class_exists($class)) {
            throw new \RuntimeException("Job class not found: {$class}");
        }

        // Créer une instance avec le payload
        // On utilise la réflexion pour recréer le job
        $reflection = new \ReflectionClass($class);

        // Si le constructeur accepte un tableau (payload), on le passe
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            $job = $reflection->newInstance();
        } elseif ($constructor->getNumberOfParameters() === 1) {
            $job = $reflection->newInstance($payload);
        } else {
            // Essayer de passer les valeurs du payload comme arguments nommés
            $job = $reflection->newInstanceArgs($payload);
        }

        if (!$job instanceof JobInterface) {
            throw new \RuntimeException("Class does not implement JobInterface: {$class}");
        }

        return $job;
    }
}
