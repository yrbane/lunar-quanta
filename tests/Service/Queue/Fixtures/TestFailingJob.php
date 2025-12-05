<?php

declare(strict_types=1);

namespace Tests\Service\Queue\Fixtures;

use Lunar\Service\Queue\JobInterface;

class TestFailingJob implements JobInterface
{
    public function handle(): void
    {
        throw new \RuntimeException('Job failed intentionally');
    }

    public function getPayload(): array
    {
        return [];
    }
}
