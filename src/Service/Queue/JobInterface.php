<?php

declare(strict_types=1);

namespace Lunar\Service\Queue;

/**
 * Interface pour les jobs exécutables en queue.
 *
 * Un job encapsule une tâche à exécuter de manière asynchrone.
 * Il peut contenir des données (payload) nécessaires à son exécution.
 *
 * @example
 * ```php
 * class SendEmailJob implements JobInterface
 * {
 *     public function __construct(
 *         private readonly string $to,
 *         private readonly string $subject,
 *         private readonly string $body
 *     ) {}
 *
 *     public function handle(): void
 *     {
 *         // Envoyer l'email
 *         mail($this->to, $this->subject, $this->body);
 *     }
 *
 *     public function getPayload(): array
 *     {
 *         return [
 *             'to' => $this->to,
 *             'subject' => $this->subject,
 *             'body' => $this->body
 *         ];
 *     }
 * }
 * ```
 */
interface JobInterface
{
    /**
     * Exécute le job.
     */
    public function handle(): void;

    /**
     * Retourne les données du job.
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array;
}
