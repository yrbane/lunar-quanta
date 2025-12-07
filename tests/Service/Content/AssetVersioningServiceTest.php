<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\AssetVersioningService;
use PHPUnit\Framework\TestCase;

final class AssetVersioningServiceTest extends TestCase
{
    private AssetVersioningService $service;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/asset-versioning-test-' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/css');
        mkdir($this->tempDir . '/js');

        // Créer des fichiers de test
        file_put_contents($this->tempDir . '/css/style.css', 'body { color: red; }');
        file_put_contents($this->tempDir . '/js/app.js', 'console.log("test");');

        $this->service = new AssetVersioningService($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Nettoyer les fichiers de test
        array_map('unlink', glob($this->tempDir . '/css/*') ?: []);
        array_map('unlink', glob($this->tempDir . '/js/*') ?: []);
        array_map('unlink', glob($this->tempDir . '/*.*') ?: []);
        @rmdir($this->tempDir . '/css');
        @rmdir($this->tempDir . '/js');
        @rmdir($this->tempDir);
    }

    public function testVersionAddsHashParameter(): void
    {
        $url = $this->service->version('/css/style.css');

        $this->assertStringContainsString('?v=', $url);
        $this->assertMatchesRegularExpression('/\?v=[a-f0-9]{8}$/', $url);
    }

    public function testVersionReturnsOriginalIfFileNotExists(): void
    {
        $url = $this->service->version('/css/nonexistent.css');

        $this->assertSame('/css/nonexistent.css', $url);
    }

    public function testVersionWithExistingQueryString(): void
    {
        $url = $this->service->version('/css/style.css?foo=bar');

        $this->assertStringContainsString('&v=', $url);
    }

    public function testVersionWithCustomQueryParam(): void
    {
        $url = $this->service->setQueryParam('hash')->version('/css/style.css');

        $this->assertStringContainsString('?hash=', $url);
    }

    public function testVersionWithCustomHashLength(): void
    {
        $url = $this->service->setHashLength(12)->version('/css/style.css');

        $this->assertMatchesRegularExpression('/\?v=[a-f0-9]{12}$/', $url);
    }

    public function testVersionWithTimestamp(): void
    {
        $this->service->setUseContentHash(false);
        $url1 = $this->service->version('/css/style.css');

        // Le hash devrait être basé sur le timestamp
        $this->assertStringContainsString('?v=', $url1);
    }

    public function testVersionManyReturnsAllUrls(): void
    {
        $urls = ['/css/style.css', '/js/app.js'];
        $result = $this->service->versionMany($urls);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('/css/style.css', $result);
        $this->assertArrayHasKey('/js/app.js', $result);
        $this->assertStringContainsString('?v=', $result['/css/style.css']);
    }

    public function testGenerateHashReturnsValidHash(): void
    {
        $hash = $this->service->generateHash('/css/style.css');

        $this->assertNotNull($hash);
        $this->assertSame(8, strlen($hash));
    }

    public function testGenerateHashReturnsNullForMissingFile(): void
    {
        $hash = $this->service->generateHash('/nonexistent.css');

        $this->assertNull($hash);
    }

    public function testLoadManifest(): void
    {
        $manifest = ['/css/style.css' => '/css/style.css?v=custom123'];
        $this->service->loadManifest($manifest);

        $url = $this->service->version('/css/style.css');

        $this->assertSame('/css/style.css?v=custom123', $url);
    }

    public function testLoadManifestFromFile(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $manifest = ['/css/style.css' => '/css/style.css?v=fromfile'];
        file_put_contents($manifestPath, json_encode($manifest));

        $this->service->loadManifestFromFile($manifestPath);
        $url = $this->service->version('/css/style.css');

        $this->assertSame('/css/style.css?v=fromfile', $url);
    }

    public function testGenerateManifest(): void
    {
        $manifest = $this->service->generateManifest(['css/*.css']);

        $this->assertArrayHasKey('/css/style.css', $manifest);
        $this->assertStringContainsString('?v=', $manifest['/css/style.css']);
    }

    public function testSaveManifest(): void
    {
        $this->service->generateManifest(['css/*.css']);
        $manifestPath = $this->tempDir . '/manifest.json';

        $result = $this->service->saveManifest($manifestPath);

        $this->assertTrue($result);
        $this->assertFileExists($manifestPath);

        $saved = json_decode(file_get_contents($manifestPath), true);
        $this->assertArrayHasKey('/css/style.css', $saved);
    }

    public function testGetManifest(): void
    {
        $this->service->generateManifest(['css/*.css', 'js/*.js']);
        $manifest = $this->service->getManifest();

        $this->assertArrayHasKey('/css/style.css', $manifest);
        $this->assertArrayHasKey('/js/app.js', $manifest);
    }

    public function testProcessHtmlVersionsCssLinks(): void
    {
        $html = '<link rel="stylesheet" href="/css/style.css">';
        $result = $this->service->processHtml($html);

        $this->assertStringContainsString('?v=', $result);
    }

    public function testProcessHtmlVersionsJsScripts(): void
    {
        $html = '<script src="/js/app.js"></script>';
        $result = $this->service->processHtml($html);

        $this->assertStringContainsString('?v=', $result);
    }

    public function testProcessHtmlVersionsImages(): void
    {
        file_put_contents($this->tempDir . '/image.jpg', 'fake image data');

        $html = '<img src="/image.jpg" alt="Test">';
        $result = $this->service->processHtml($html);

        $this->assertStringContainsString('?v=', $result);
    }

    public function testCssLinkGeneratesValidHtml(): void
    {
        $html = $this->service->cssLink('/css/style.css');

        $this->assertStringContainsString('<link', $html);
        $this->assertStringContainsString('rel="stylesheet"', $html);
        $this->assertStringContainsString('?v=', $html);
    }

    public function testCssLinkWithAttributes(): void
    {
        $html = $this->service->cssLink('/css/style.css', ['media' => 'print']);

        $this->assertStringContainsString('media="print"', $html);
    }

    public function testJsScriptGeneratesValidHtml(): void
    {
        $html = $this->service->jsScript('/js/app.js');

        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('?v=', $html);
        $this->assertStringContainsString('</script>', $html);
    }

    public function testJsScriptWithAttributes(): void
    {
        $html = $this->service->jsScript('/js/app.js', ['defer' => true, 'type' => 'module']);

        $this->assertStringContainsString('defer', $html);
        $this->assertStringContainsString('type="module"', $html);
    }

    public function testImgSrcReturnsVersionedUrl(): void
    {
        file_put_contents($this->tempDir . '/photo.jpg', 'fake image');

        $src = $this->service->imgSrc('/photo.jpg');

        $this->assertStringContainsString('?v=', $src);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setPublicPath('/var/www')
            ->setHashAlgorithm('sha256')
            ->setHashLength(10)
            ->setQueryParam('hash')
            ->setUseContentHash(true)
            ->loadManifest([]);

        $this->assertSame($this->service, $result);
    }

    public function testHashLengthIsClamped(): void
    {
        $this->service->setHashLength(2);
        $url = $this->service->version('/css/style.css');
        $this->assertMatchesRegularExpression('/\?v=[a-f0-9]{4}$/', $url);

        $this->service->setHashLength(50);
        $url = $this->service->version('/css/style.css');
        $this->assertMatchesRegularExpression('/\?v=[a-f0-9]{32}$/', $url);
    }
}
