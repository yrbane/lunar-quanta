<?php

declare(strict_types=1);

namespace Tests\Service\Media;

use Lunar\Service\Media\ImageOptimizer;
use Lunar\Service\Media\ImageProviderInterface;
use Lunar\Service\Media\ImageResult;
use Lunar\Service\Media\ImageService;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour ImageService.
 */
final class ImageServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/imageservice_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createOptimizer(): ImageOptimizer
    {
        return new ImageOptimizer($this->tempDir);
    }

    private function createMockProvider(string $name, bool $supportsGeneration = false): ImageProviderInterface
    {
        $provider = $this->createMock(ImageProviderInterface::class);
        $provider->method('getName')->willReturn($name);
        $provider->method('supportsGeneration')->willReturn($supportsGeneration);
        return $provider;
    }

    public function testAddProvider(): void
    {
        $service = new ImageService($this->createOptimizer());
        $provider = $this->createMockProvider('test');

        $result = $service->addProvider($provider);

        $this->assertSame($service, $result);
        $this->assertContains('test', $service->getProviders());
    }

    public function testGetProviders(): void
    {
        $service = new ImageService($this->createOptimizer());

        $this->assertSame([], $service->getProviders());

        $service->addProvider($this->createMockProvider('pexels'));
        $service->addProvider($this->createMockProvider('dalle', true));

        $providers = $service->getProviders();

        $this->assertCount(2, $providers);
        $this->assertContains('pexels', $providers);
        $this->assertContains('dalle', $providers);
    }

    public function testGetGenerativeProviders(): void
    {
        $service = new ImageService($this->createOptimizer());
        $service->addProvider($this->createMockProvider('pexels', false));
        $service->addProvider($this->createMockProvider('dalle', true));
        $service->addProvider($this->createMockProvider('imagen', true));

        $generative = $service->getGenerativeProviders();

        $this->assertCount(2, $generative);
        $this->assertContains('dalle', $generative);
        $this->assertContains('imagen', $generative);
        $this->assertNotContains('pexels', $generative);
    }

    public function testSearchQueriesNonGenerativeProviders(): void
    {
        $service = new ImageService($this->createOptimizer());

        $pexels = $this->createMock(ImageProviderInterface::class);
        $pexels->method('getName')->willReturn('pexels');
        $pexels->method('supportsGeneration')->willReturn(false);
        $pexels->expects($this->once())
            ->method('search')
            ->with('nature', 10)
            ->willReturn([
                new ImageResult('1', 'https://example.com/1.jpg', '', 'pexels', 800, 600, 'Nature'),
            ]);

        $dalle = $this->createMock(ImageProviderInterface::class);
        $dalle->method('getName')->willReturn('dalle');
        $dalle->method('supportsGeneration')->willReturn(true);
        $dalle->expects($this->never())->method('search');

        $service->addProvider($pexels);
        $service->addProvider($dalle);

        $results = $service->search('nature', 10);

        $this->assertCount(1, $results);
        $this->assertSame('pexels', $results[0]->provider);
    }

    public function testSearchLimitsResults(): void
    {
        $service = new ImageService($this->createOptimizer());

        $provider = $this->createMock(ImageProviderInterface::class);
        $provider->method('getName')->willReturn('pexels');
        $provider->method('supportsGeneration')->willReturn(false);
        $provider->method('search')->willReturn([
            new ImageResult('1', 'https://example.com/1.jpg', '', 'pexels', 800, 600, ''),
            new ImageResult('2', 'https://example.com/2.jpg', '', 'pexels', 800, 600, ''),
            new ImageResult('3', 'https://example.com/3.jpg', '', 'pexels', 800, 600, ''),
            new ImageResult('4', 'https://example.com/4.jpg', '', 'pexels', 800, 600, ''),
            new ImageResult('5', 'https://example.com/5.jpg', '', 'pexels', 800, 600, ''),
        ]);

        $service->addProvider($provider);

        $results = $service->search('test', 3);

        $this->assertCount(3, $results);
    }

    public function testSearchProviderSpecific(): void
    {
        $service = new ImageService($this->createOptimizer());

        $pexels = $this->createMock(ImageProviderInterface::class);
        $pexels->method('getName')->willReturn('pexels');
        $pexels->expects($this->once())
            ->method('search')
            ->with('flowers', 5)
            ->willReturn([
                new ImageResult('1', 'https://example.com/flower.jpg', '', 'pexels', 800, 600, 'Flowers'),
            ]);

        $service->addProvider($pexels);

        $results = $service->searchProvider('pexels', 'flowers', 5);

        $this->assertCount(1, $results);
    }

    public function testSearchProviderNotFound(): void
    {
        $service = new ImageService($this->createOptimizer());

        $results = $service->searchProvider('nonexistent', 'test');

        $this->assertSame([], $results);
    }

    public function testGenerateWithProvider(): void
    {
        $service = new ImageService($this->createOptimizer());

        $dalle = $this->createMock(ImageProviderInterface::class);
        $dalle->method('getName')->willReturn('dalle');
        $dalle->method('supportsGeneration')->willReturn(true);
        $dalle->expects($this->once())
            ->method('generate')
            ->with('A sunset')
            ->willReturn(new ImageResult('gen-1', 'https://example.com/generated.png', '', 'dalle', 1024, 1024, 'A sunset'));

        $service->addProvider($dalle);

        $result = $service->generate('A sunset', 'dalle');

        $this->assertNotNull($result);
        $this->assertSame('dalle', $result->provider);
        $this->assertSame('A sunset', $result->alt);
    }

    public function testGenerateProviderNotFound(): void
    {
        $service = new ImageService($this->createOptimizer());

        $result = $service->generate('A sunset', 'dalle');

        $this->assertNull($result);
    }

    public function testGenerateProviderDoesNotSupportGeneration(): void
    {
        $service = new ImageService($this->createOptimizer());

        $pexels = $this->createMock(ImageProviderInterface::class);
        $pexels->method('getName')->willReturn('pexels');
        $pexels->method('supportsGeneration')->willReturn(false);

        $service->addProvider($pexels);

        $result = $service->generate('A sunset', 'pexels');

        $this->assertNull($result);
    }

    public function testDownloadFromUrl(): void
    {
        $service = new ImageService($this->createOptimizer());

        // Sans vraie image, retourne null
        $result = $service->downloadFromUrl('https://example.com/nonexistent.jpg');

        $this->assertNull($result);
    }

    public function testDownloadFromUrlGeneratesFilename(): void
    {
        $service = new ImageService($this->createOptimizer());

        // Teste que la méthode accepte une URL sans filename
        $result = $service->downloadFromUrl('https://example.com/image.jpg', '');

        // Le résultat sera null car l'URL n'existe pas, mais pas d'erreur
        $this->assertNull($result);
    }

    public function testUpload(): void
    {
        $service = new ImageService($this->createOptimizer());

        // Créer une vraie image PNG pour tester
        $image = imagecreatetruecolor(100, 100);
        $blue = imagecolorallocate($image, 0, 0, 255);
        imagefill($image, 0, 0, $blue);
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        $result = $service->upload($imageData, 'test_upload.png');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('original', $result);
        $this->assertArrayHasKey('thumb', $result);
        $this->assertArrayHasKey('width', $result);
        $this->assertArrayHasKey('height', $result);
    }

    public function testUploadInvalidData(): void
    {
        $service = new ImageService($this->createOptimizer());

        $result = $service->upload('not an image', 'invalid.jpg');

        $this->assertNull($result);
    }

    public function testDelete(): void
    {
        $service = new ImageService($this->createOptimizer());

        // Créer un fichier de test
        $testFile = $this->tempDir . '/test_delete.jpg';
        file_put_contents($testFile, 'test');

        $result = $service->delete($testFile);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($testFile);
    }

    public function testDeleteNonexistent(): void
    {
        $service = new ImageService($this->createOptimizer());

        $result = $service->delete($this->tempDir . '/nonexistent.jpg');

        $this->assertFalse($result);
    }

    public function testMultipleProvidersSearch(): void
    {
        $service = new ImageService($this->createOptimizer());

        $pexels = $this->createMock(ImageProviderInterface::class);
        $pexels->method('getName')->willReturn('pexels');
        $pexels->method('supportsGeneration')->willReturn(false);
        $pexels->method('search')->willReturn([
            new ImageResult('p1', 'https://pexels.com/1.jpg', '', 'pexels', 800, 600, ''),
            new ImageResult('p2', 'https://pexels.com/2.jpg', '', 'pexels', 800, 600, ''),
        ]);

        $unsplash = $this->createMock(ImageProviderInterface::class);
        $unsplash->method('getName')->willReturn('unsplash');
        $unsplash->method('supportsGeneration')->willReturn(false);
        $unsplash->method('search')->willReturn([
            new ImageResult('u1', 'https://unsplash.com/1.jpg', '', 'unsplash', 800, 600, ''),
        ]);

        $service->addProvider($pexels);
        $service->addProvider($unsplash);

        $results = $service->search('nature', 10);

        $this->assertCount(3, $results);
    }

    public function testProviderOverwrite(): void
    {
        $service = new ImageService($this->createOptimizer());

        $provider1 = $this->createMockProvider('test');
        $provider2 = $this->createMockProvider('test');

        $service->addProvider($provider1);
        $service->addProvider($provider2);

        // Le deuxième écrase le premier
        $this->assertCount(1, $service->getProviders());
    }
}
