<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\LazyLoadService;
use PHPUnit\Framework\TestCase;

final class LazyLoadServiceTest extends TestCase
{
    private LazyLoadService $service;

    protected function setUp(): void
    {
        $this->service = new LazyLoadService();
    }

    public function testTransformImageAddDataSrc(): void
    {
        $img = '<img src="photo.jpg" alt="Test">';
        $result = $this->service->transformImage($img);

        $this->assertStringContainsString('data-src="photo.jpg"', $result);
        $this->assertStringContainsString('class="lazy', $result);
    }

    public function testTransformImageAddsLoadingLazy(): void
    {
        $img = '<img src="photo.jpg" alt="Test">';
        $result = $this->service->transformImage($img);

        $this->assertStringContainsString('loading="lazy"', $result);
    }

    public function testTransformImageWithExistingClass(): void
    {
        $img = '<img src="photo.jpg" class="my-image" alt="Test">';
        $result = $this->service->transformImage($img);

        $this->assertStringContainsString('class="my-image lazy', $result);
    }

    public function testTransformImageAddsAnimationClass(): void
    {
        $img = '<img src="photo.jpg" alt="Test">';

        $result = $this->service->setLoadingAnimation('blur')->transformImage($img);
        $this->assertStringContainsString('lazy-blur', $result);

        $result = $this->service->setLoadingAnimation('slide')->transformImage($img);
        $this->assertStringContainsString('lazy-slide', $result);
    }

    public function testTransformImageReturnsUnchangedIfNoSrc(): void
    {
        $img = '<img alt="Test">';
        $result = $this->service->transformImage($img);

        $this->assertSame($img, $result);
    }

    public function testProcessContentTransformsImages(): void
    {
        $html = '<div><img src="a.jpg"><img src="b.jpg"><img src="c.jpg"></div>';
        $result = $this->service->processContent($html, false);

        $this->assertStringContainsString('data-src="a.jpg"', $result);
        $this->assertStringContainsString('data-src="b.jpg"', $result);
        $this->assertStringContainsString('data-src="c.jpg"', $result);
    }

    public function testProcessContentSkipsFirstImage(): void
    {
        $html = '<div><img src="a.jpg"><img src="b.jpg"></div>';
        $result = $this->service->processContent($html, true);

        // First image should not have data-src
        $this->assertStringContainsString('src="a.jpg"', $result);
        $this->assertStringNotContainsString('data-src="a.jpg"', $result);

        // Second image should have data-src
        $this->assertStringContainsString('data-src="b.jpg"', $result);
    }

    public function testProcessContentSkipsAlreadyLazy(): void
    {
        $html = '<img src="placeholder.svg" data-src="real.jpg" class="lazy">';
        $result = $this->service->processContent($html, false);

        // Should not double-process
        $this->assertEquals(1, substr_count($result, 'data-src'));
    }

    public function testGeneratePlaceholderReturnsSvg(): void
    {
        $placeholder = $this->service->generatePlaceholder(100, 50);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $placeholder);

        $decoded = base64_decode(str_replace('data:image/svg+xml;base64,', '', $placeholder));
        $this->assertStringContainsString('width=\'100\'', $decoded);
        $this->assertStringContainsString('height=\'50\'', $decoded);
    }

    public function testGeneratePlaceholderWithCustomColor(): void
    {
        $this->service->setPlaceholderColor('#ff0000');
        $placeholder = $this->service->generatePlaceholder();

        $decoded = base64_decode(str_replace('data:image/svg+xml;base64,', '', $placeholder));
        $this->assertStringContainsString('#ff0000', $decoded);
    }

    public function testGenerateCssReturnsValidCss(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('.lazy', $css);
        $this->assertStringContainsString('.lazy.loaded', $css);
        $this->assertStringContainsString('.lazy-fade', $css);
        $this->assertStringContainsString('.lazy-blur', $css);
        $this->assertStringContainsString('.lazy-slide', $css);
    }

    public function testGenerateJsReturnsValidJs(): void
    {
        $js = $this->service->generateJs();

        $this->assertStringContainsString('IntersectionObserver', $js);
        $this->assertStringContainsString('data-src', $js);
        $this->assertStringContainsString('loaded', $js);
    }

    public function testGenerateJsWithCustomThreshold(): void
    {
        $js = $this->service->setThreshold(200)->generateJs();

        $this->assertStringContainsString("'200px 0px'", $js);
    }

    public function testGenerateAllReturnsAllParts(): void
    {
        $all = $this->service->generateAll();

        $this->assertArrayHasKey('css', $all);
        $this->assertArrayHasKey('js', $all);
    }

    public function testGenerateSnippetReturnsCompleteSnippet(): void
    {
        $snippet = $this->service->generateSnippet();

        $this->assertStringContainsString('<!-- Lazy Load Images -->', $snippet);
        $this->assertStringContainsString('<style>', $snippet);
        $this->assertStringContainsString('<script>', $snippet);
    }

    public function testWrapImageCreatesContainer(): void
    {
        $img = '<img src="photo.jpg">';
        $result = $this->service->wrapImage($img, 800, 600);

        $this->assertStringContainsString('lazy-container', $result);
        $this->assertStringContainsString('padding-bottom: 75%', $result);
        $this->assertStringContainsString($img, $result);
    }

    public function testDisableNativeLazy(): void
    {
        $img = '<img src="photo.jpg">';
        $result = $this->service->setUseNativeLazy(false)->transformImage($img);

        $this->assertStringNotContainsString('loading="lazy"', $result);
    }

    public function testInvalidAnimationDefaultsToFade(): void
    {
        $img = '<img src="photo.jpg">';
        $result = $this->service->setLoadingAnimation('invalid')->transformImage($img);

        $this->assertStringContainsString('lazy-fade', $result);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setPlaceholderColor('#ccc')
            ->setLoadingAnimation('blur')
            ->setThreshold(50)
            ->setUseNativeLazy(true);

        $this->assertSame($this->service, $result);
    }
}
