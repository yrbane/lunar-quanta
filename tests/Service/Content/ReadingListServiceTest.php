<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\ReadingListService;
use PHPUnit\Framework\TestCase;

final class ReadingListServiceTest extends TestCase
{
    private ReadingListService $service;

    protected function setUp(): void
    {
        $this->service = new ReadingListService();
    }

    public function testGenerateButtonReturnsHtml(): void
    {
        $html = $this->service->generateButton('post-1', 'Mon Article', '/blog/posts/mon-article.html');

        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('data-post-id="post-1"', $html);
        $this->assertStringContainsString('data-post-title="Mon Article"', $html);
    }

    public function testGenerateButtonEscapesHtml(): void
    {
        $html = $this->service->generateButton('1', 'Article "avec" quotes', '/blog/test.html');

        $this->assertStringContainsString('&quot;avec&quot;', $html);
    }

    public function testGenerateWidgetReturnsHtml(): void
    {
        $html = $this->service->generateWidget();

        $this->assertStringContainsString('id="readingListWidget"', $html);
        $this->assertStringContainsString('id="readingListToggle"', $html);
        $this->assertStringContainsString('id="readingListDropdown"', $html);
        $this->assertStringContainsString('id="readingListItems"', $html);
    }

    public function testGenerateScriptReturnsJs(): void
    {
        $script = $this->service->generateScript();

        $this->assertStringContainsString('const ReadingList', $script);
        $this->assertStringContainsString('init()', $script);
        $this->assertStringContainsString('add(item)', $script);
        $this->assertStringContainsString('remove(id)', $script);
        $this->assertStringContainsString('localStorage', $script);
    }

    public function testGenerateScriptUsesStorageKey(): void
    {
        $this->service->setStorageKey('my-custom-key');
        $script = $this->service->generateScript();

        $this->assertStringContainsString('my-custom-key', $script);
    }

    public function testGenerateScriptUsesMaxItems(): void
    {
        $this->service->setMaxItems(25);
        $script = $this->service->generateScript();

        $this->assertStringContainsString('maxItems: 25', $script);
    }

    public function testGenerateCssReturnsValidCss(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('.la-bookmark-btn', $css);
        $this->assertStringContainsString('.la-reading-list-widget', $css);
        $this->assertStringContainsString('.la-reading-list-dropdown', $css);
    }

    public function testSetButtonClassChangesClass(): void
    {
        $this->service->setButtonClass('custom-btn');
        $html = $this->service->generateButton('1', 'Test', '/test');

        $this->assertStringContainsString('class="custom-btn"', $html);
    }

    public function testSetActiveClassUsedInCss(): void
    {
        $this->service->setActiveClass('active');
        $css = $this->service->generateCss();

        $this->assertStringContainsString('.active', $css);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setStorageKey('test-key')
            ->setMaxItems(10)
            ->setButtonClass('btn')
            ->setActiveClass('active');

        $this->assertSame($this->service, $result);
    }

    public function testSetMaxItemsEnforcesMinimum(): void
    {
        $this->service->setMaxItems(0);
        $script = $this->service->generateScript();

        $this->assertStringContainsString('maxItems: 1', $script);
    }

    public function testScriptContainsExportImport(): void
    {
        $script = $this->service->generateScript();

        $this->assertStringContainsString('export()', $script);
        $this->assertStringContainsString('import(data)', $script);
    }

    public function testScriptContainsGetAll(): void
    {
        $script = $this->service->generateScript();

        $this->assertStringContainsString('getAll()', $script);
    }

    public function testCssContainsResponsiveStyles(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('transition', $css);
        $this->assertStringContainsString('z-index', $css);
    }

    public function testWidgetContainsAccessibility(): void
    {
        $html = $this->service->generateWidget();

        $this->assertStringContainsString('title=', $html);
    }

    public function testButtonContainsAriaLabel(): void
    {
        $html = $this->service->generateButton('1', 'Test', '/test');

        $this->assertStringContainsString('aria-label=', $html);
    }
}
