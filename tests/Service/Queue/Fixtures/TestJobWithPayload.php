<?php

declare(strict_types=1);

namespace Tests\Service\Queue\Fixtures;

use Lunar\Service\Queue\JobInterface;

class TestJobWithPayload implements JobInterface
{
    public function __construct(
        private readonly array $payload
    ) {
    }

    public function handle(): void
    {
        // Do nothing
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
