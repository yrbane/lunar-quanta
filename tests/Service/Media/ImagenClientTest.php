<?php

declare(strict_types=1);

namespace Tests\Service\Media;

use Lunar\Service\Media\ImagenClient;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour ImagenClient.
 */
final class ImagenClientTest extends TestCase
{
    public function testGetName(): void
    {
        $client = new ImagenClient('test-key', 'project-id');

        $this->assertSame('imagen', $client->getName());
    }

    public function testSupportsGeneration(): void
    {
        $client = new ImagenClient('test-key', 'project-id');

        $this->assertTrue($client->supportsGeneration());
    }

    public function testGenerateWithEmptyKeyReturnsNull(): void
    {
        $client = new ImagenClient('', 'project-id');

        $result = $client->generate('A sunset');

        $this->assertNull($result);
    }

    public function testGenerateWithEmptyProjectReturnsNull(): void
    {
        $client = new ImagenClient('test-key', '');

        $result = $client->generate('A sunset');

        $this->assertNull($result);
    }

    public function testSearchCallsGenerate(): void
    {
        $client = new ImagenClient('', '');

        // Sans clé API, devrait retourner un tableau vide
        $results = $client->search('nature');

        $this->assertSame([], $results);
    }

    public function testImplementsInterface(): void
    {
        $client = new ImagenClient('test-key', 'project-id');

        $this->assertInstanceOf(\Lunar\Service\Media\ImageProviderInterface::class, $client);
    }

    public function testDefaultParameters(): void
    {
        $client = new ImagenClient('test-key', 'project-id');

        $this->assertSame('imagen', $client->getName());
    }

    public function testCustomParameters(): void
    {
        $client = new ImagenClient(
            apiKey: 'test-key',
            projectId: 'my-project',
            location: 'europe-west1',
            model: 'imagegeneration@005'
        );

        $this->assertSame('imagen', $client->getName());
    }
}
