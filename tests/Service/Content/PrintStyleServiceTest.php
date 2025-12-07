<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\PrintStyleService;
use PHPUnit\Framework\TestCase;

final class PrintStyleServiceTest extends TestCase
{
    private PrintStyleService $service;

    protected function setUp(): void
    {
        $this->service = new PrintStyleService();
    }

    public function testGenerateCssReturnsValidCss(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('@media print', $css);
        $this->assertStringContainsString('font-family', $css);
        $this->assertStringContainsString('@page', $css);
    }

    public function testGenerateCssWithCustomFont(): void
    {
        $css = $this->service->setFontFamily('Arial, sans-serif')->generateCss();

        $this->assertStringContainsString('font-family: Arial, sans-serif', $css);
    }

    public function testGenerateCssWithCustomFontSize(): void
    {
        $css = $this->service->setFontSize('14pt')->generateCss();

        $this->assertStringContainsString('font-size: 14pt', $css);
    }

    public function testGenerateCssWithCustomLineHeight(): void
    {
        $css = $this->service->setLineHeight('1.8')->generateCss();

        $this->assertStringContainsString('line-height: 1.8', $css);
    }

    public function testGenerateCssWithShowUrls(): void
    {
        $css = $this->service->setShowUrls(true)->generateCss();

        $this->assertStringContainsString('a[href^="http"]:after', $css);
        $this->assertStringContainsString('content: " (" attr(href) ")"', $css);
    }

    public function testGenerateCssWithoutShowUrls(): void
    {
        $css = $this->service->setShowUrls(false)->generateCss();

        $this->assertStringNotContainsString('a[href^="http"]:after', $css);
    }

    public function testGenerateCssWithPageNumbers(): void
    {
        $css = $this->service->setShowPageNumbers(true)->generateCss();

        $this->assertStringContainsString('@bottom-center', $css);
        $this->assertStringContainsString('counter(page)', $css);
    }

    public function testGenerateCssWithoutPageNumbers(): void
    {
        $css = $this->service->setShowPageNumbers(false)->generateCss();

        $this->assertStringNotContainsString('@bottom-center', $css);
    }

    public function testGenerateCssWithCodeBreakAvoidance(): void
    {
        $css = $this->service->setAvoidBreaksInCode(true)->generateCss();

        $this->assertStringContainsString('pre, code, blockquote', $css);
        $this->assertStringContainsString('page-break-inside: avoid', $css);
    }

    public function testGenerateCssHidesDefaultSelectors(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('.nav', $css);
        $this->assertStringContainsString('.sidebar', $css);
        $this->assertStringContainsString('display: none !important', $css);
    }

    public function testGenerateCssWithCustomHideSelectors(): void
    {
        $css = $this->service->setHideSelectors(['.my-element'])->generateCss();

        $this->assertStringContainsString('.my-element', $css);
        $this->assertStringNotContainsString('.nav', $css);
    }

    public function testAddHideSelectors(): void
    {
        $css = $this->service->addHideSelectors(['.custom-hide'])->generateCss();

        $this->assertStringContainsString('.nav', $css);
        $this->assertStringContainsString('.custom-hide', $css);
    }

    public function testGenerateCssIncludesTypographyRules(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('h1, h2, h3, h4, h5, h6', $css);
        $this->assertStringContainsString('orphans: 3', $css);
        $this->assertStringContainsString('widows: 3', $css);
    }

    public function testGenerateCssIncludesTableRules(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('border-collapse: collapse', $css);
        $this->assertStringContainsString('display: table-header-group', $css);
    }

    public function testGenerateCssIncludesPageSetup(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('margin: 2cm', $css);
        $this->assertStringContainsString('size: A4', $css);
    }

    public function testGenerateLinkReturnsValidHtml(): void
    {
        $link = $this->service->generateLink();

        $this->assertStringContainsString('<link', $link);
        $this->assertStringContainsString('media="print"', $link);
        $this->assertStringContainsString('rel="stylesheet"', $link);
    }

    public function testGenerateLinkWithCustomPath(): void
    {
        $link = $this->service->generateLink('/assets/print-styles.css');

        $this->assertStringContainsString('href="/assets/print-styles.css"', $link);
    }

    public function testGenerateInlineStyleReturnsValidHtml(): void
    {
        $style = $this->service->generateInlineStyle();

        $this->assertStringContainsString('<style media="print">', $style);
        $this->assertStringContainsString('</style>', $style);
        $this->assertStringContainsString('@media print', $style);
    }

    public function testGeneratePrintButtonReturnsValidHtml(): void
    {
        $button = $this->service->generatePrintButton();

        $this->assertStringContainsString('<button', $button);
        $this->assertStringContainsString('onclick="window.print()"', $button);
        $this->assertStringContainsString('Imprimer', $button);
        $this->assertStringContainsString('no-print', $button);
    }

    public function testGeneratePrintButtonWithCustomLabel(): void
    {
        $button = $this->service->generatePrintButton('Print this page', 'custom-class');

        $this->assertStringContainsString('Print this page', $button);
        $this->assertStringContainsString('class="custom-class', $button);
    }

    public function testGeneratePrintHeaderReturnsValidHtml(): void
    {
        $header = $this->service->generatePrintHeader('Mon Article', 'https://example.com/article');

        $this->assertStringContainsString('print-only', $header);
        $this->assertStringContainsString('Mon Article', $header);
        $this->assertStringContainsString('https://example.com/article', $header);
    }

    public function testGeneratePrintHeaderWithDate(): void
    {
        $header = $this->service->generatePrintHeader('Article', 'https://example.com', '2025-01-15');

        $this->assertStringContainsString('Imprimé le : 2025-01-15', $header);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setFontFamily('Helvetica')
            ->setFontSize('11pt')
            ->setLineHeight('1.6')
            ->setShowUrls(false)
            ->setShowPageNumbers(false)
            ->setAvoidBreaksInCode(true)
            ->setHideSelectors(['.custom'])
            ->addHideSelectors(['.extra']);

        $this->assertSame($this->service, $result);
    }
}
