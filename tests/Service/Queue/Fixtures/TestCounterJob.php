<?php

declare(strict_types=1);

namespace Tests\Service\Queue\Fixtures;

use Lunar\Service\Queue\JobInterface;

class TestCounterJob implements JobInterface
{
    public static int $counter = 0;

    public function handle(): void
    {
        self::$counter++;
    }

    public function getPayload(): array
    {
        return [];
    }
}
