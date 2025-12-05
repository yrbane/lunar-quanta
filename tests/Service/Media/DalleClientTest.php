<?php

declare(strict_types=1);

namespace Tests\Service\Media;

use Lunar\Service\Media\DalleClient;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour DalleClient.
 */
final class DalleClientTest extends TestCase
{
    public function testGetName(): void
    {
        $client = new DalleClient('test-key');

        $this->assertSame('dalle', $client->getName());
    }

    public function testSupportsGeneration(): void
    {
        $client = new DalleClient('test-key');

        $this->assertTrue($client->supportsGeneration());
    }

    public function testGenerateWithEmptyKeyReturnsNull(): void
    {
        $client = new DalleClient('');

        $result = $client->generate('A sunset');

        $this->assertNull($result);
    }

    public function testSearchCallsGenerate(): void
    {
        $client = new DalleClient('');

        // Sans clé API, devrait retourner un tableau vide
        $results = $client->search('nature');

        $this->assertSame([], $results);
    }

    public function testImplementsInterface(): void
    {
        $client = new DalleClient('test-key');

        $this->assertInstanceOf(\Lunar\Service\Media\ImageProviderInterface::class, $client);
    }

    public function testDefaultParameters(): void
    {
        $client = new DalleClient('test-key');

        // Le client devrait être créé sans erreur avec les paramètres par défaut
        $this->assertSame('dalle', $client->getName());
    }

    public function testCustomParameters(): void
    {
        $client = new DalleClient(
            apiKey: 'test-key',
            model: 'dall-e-2',
            size: '512x512',
            quality: 'hd'
        );

        $this->assertSame('dalle', $client->getName());
    }
}
