<?php

declare(strict_types=1);

namespace Lunar\Service\Queue;

/**
 * Classe de base pour les jobs.
 *
 * Fournit une implémentation de base pour les jobs avec payload.
 *
 * @example
 * ```php
 * class SendEmailJob extends Job
 * {
 *     public function __construct(
 *         private readonly string $to,
 *         private readonly string $subject,
 *         private readonly string $body
 *     ) {
 *         parent::__construct([
 *             'to' => $to,
 *             'subject' => $subject,
 *             'body' => $body
 *         ]);
 *     }
 *
 *     public function handle(): void
 *     {
 *         mail($this->to, $this->subject, $this->body);
 *     }
 * }
 * ```
 */
abstract class Job implements JobInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        protected readonly array $payload = []
    ) {
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
