<?php

declare(strict_types=1);

namespace Tests\Service\Media;

use Lunar\Service\Media\PexelsClient;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour PexelsClient.
 */
final class PexelsClientTest extends TestCase
{
    public function testGetName(): void
    {
        $client = new PexelsClient('test-key');

        $this->assertSame('pexels', $client->getName());
    }

    public function testSupportsGeneration(): void
    {
        $client = new PexelsClient('test-key');

        $this->assertFalse($client->supportsGeneration());
    }

    public function testGenerateReturnsNull(): void
    {
        $client = new PexelsClient('test-key');

        $result = $client->generate('A sunset');

        $this->assertNull($result);
    }

    public function testSearchWithEmptyKeyReturnsEmpty(): void
    {
        $client = new PexelsClient('');

        $results = $client->search('nature');

        $this->assertSame([], $results);
    }

    public function testCuratedWithEmptyKeyReturnsEmpty(): void
    {
        $client = new PexelsClient('');

        $results = $client->curated();

        $this->assertSame([], $results);
    }

    public function testImplementsInterface(): void
    {
        $client = new PexelsClient('test-key');

        $this->assertInstanceOf(\Lunar\Service\Media\ImageProviderInterface::class, $client);
    }
}
