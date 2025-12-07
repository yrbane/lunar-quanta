<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\ReadingProgressService;
use PHPUnit\Framework\TestCase;

final class ReadingProgressServiceTest extends TestCase
{
    private ReadingProgressService $service;

    protected function setUp(): void
    {
        $this->service = new ReadingProgressService();
    }

    public function testGenerateCssReturnsValidCss(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('.reading-progress-container', $css);
        $this->assertStringContainsString('.reading-progress-bar', $css);
        $this->assertStringContainsString('position: fixed', $css);
        $this->assertStringContainsString('top: 0', $css);
    }

    public function testGenerateCssWithBottomPosition(): void
    {
        $css = $this->service->setPosition('bottom')->generateCss();

        $this->assertStringContainsString('bottom: 0', $css);
        $this->assertStringNotContainsString('top: 0', $css);
    }

    public function testGenerateCssWithCustomHeight(): void
    {
        $css = $this->service->setHeight(8)->generateCss();

        $this->assertStringContainsString('height: 8px', $css);
    }

    public function testGenerateCssWithCustomColor(): void
    {
        $css = $this->service->setColor('#ff0000')->generateCss();

        $this->assertStringContainsString('background: #ff0000', $css);
    }

    public function testGenerateCssWithCustomBackgroundColor(): void
    {
        $css = $this->service->setBackgroundColor('#f0f0f0')->generateCss();

        $this->assertStringContainsString('background: #f0f0f0', $css);
    }

    public function testGenerateCssWithCustomZIndex(): void
    {
        $css = $this->service->setZIndex(1000)->generateCss();

        $this->assertStringContainsString('z-index: 1000', $css);
    }

    public function testGenerateHtmlReturnsValidHtml(): void
    {
        $html = $this->service->generateHtml();

        $this->assertStringContainsString('class="reading-progress-container"', $html);
        $this->assertStringContainsString('class="reading-progress-bar"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function testGenerateHtmlWithGradient(): void
    {
        $html = $this->service->generateHtml(true);

        $this->assertStringContainsString('class="reading-progress-bar gradient"', $html);
    }

    public function testGenerateJsReturnsValidJs(): void
    {
        $js = $this->service->generateJs();

        $this->assertStringContainsString('.reading-progress-bar', $js);
        $this->assertStringContainsString('addEventListener', $js);
        $this->assertStringContainsString('requestAnimationFrame', $js);
    }

    public function testGenerateJsWithCustomSelector(): void
    {
        $js = $this->service->setTargetSelector('.blog-post')->generateJs();

        $this->assertStringContainsString('.blog-post', $js);
    }

    public function testGenerateAllReturnsAllParts(): void
    {
        $all = $this->service->generateAll();

        $this->assertArrayHasKey('head', $all);
        $this->assertArrayHasKey('body_start', $all);
        $this->assertArrayHasKey('body_end', $all);
        $this->assertStringContainsString('<style>', $all['head']);
        $this->assertStringContainsString('<script>', $all['body_end']);
    }

    public function testGenerateAllNonInline(): void
    {
        $all = $this->service->generateAll(false, false);

        $this->assertArrayHasKey('css', $all);
        $this->assertArrayHasKey('html', $all);
        $this->assertArrayHasKey('js', $all);
        $this->assertStringNotContainsString('<style>', $all['css']);
    }

    public function testGenerateSnippetReturnsCompleteSnippet(): void
    {
        $snippet = $this->service->generateSnippet();

        $this->assertStringContainsString('<!-- Reading Progress Bar -->', $snippet);
        $this->assertStringContainsString('<style>', $snippet);
        $this->assertStringContainsString('<script>', $snippet);
        $this->assertStringContainsString('reading-progress-container', $snippet);
    }

    public function testHeightIsClamped(): void
    {
        $css = $this->service->setHeight(0)->generateCss();
        $this->assertStringContainsString('height: 1px', $css);

        $css = $this->service->setHeight(100)->generateCss();
        $this->assertStringContainsString('height: 20px', $css);
    }

    public function testInvalidPositionDefaultsToTop(): void
    {
        $css = $this->service->setPosition('invalid')->generateCss();
        $this->assertStringContainsString('top: 0', $css);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setPosition('bottom')
            ->setHeight(6)
            ->setColor('#00ff00')
            ->setBackgroundColor('#000')
            ->setZIndex(5000)
            ->setTargetSelector('.content');

        $this->assertSame($this->service, $result);
    }
}
