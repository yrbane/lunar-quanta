<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\AnchorLinkService;
use PHPUnit\Framework\TestCase;

final class AnchorLinkServiceTest extends TestCase
{
    private AnchorLinkService $service;

    protected function setUp(): void
    {
        $this->service = new AnchorLinkService();
    }

    public function testGenerateSlugFromSimpleText(): void
    {
        $this->assertSame('hello-world', $this->service->generateSlug('Hello World'));
    }

    public function testGenerateSlugFromAccentedText(): void
    {
        $this->assertSame('cafe-francais', $this->service->generateSlug('Café français'));
    }

    public function testGenerateSlugFromSpecialCharacters(): void
    {
        $this->assertSame('test-123', $this->service->generateSlug('Test!@#$%^&*() 123'));
    }

    public function testGenerateSlugFromHtmlContent(): void
    {
        $this->assertSame('my-title', $this->service->generateSlug('<strong>My</strong> Title'));
    }

    public function testGenerateSlugFromEmptyText(): void
    {
        $this->assertSame('section', $this->service->generateSlug(''));
    }

    public function testAddAnchorToHeading(): void
    {
        $html = '<h2>Mon Titre</h2>';
        $result = $this->service->addAnchor($html);

        $this->assertStringContainsString('id="mon-titre"', $result);
        $this->assertStringContainsString('href="#mon-titre"', $result);
        $this->assertStringContainsString('class="anchor-link"', $result);
    }

    public function testAddAnchorPreservesExistingId(): void
    {
        $html = '<h2 id="custom-id">Mon Titre</h2>';
        $result = $this->service->addAnchor($html);

        $this->assertStringContainsString('id="custom-id"', $result);
        $this->assertStringContainsString('href="#custom-id"', $result);
        $this->assertStringNotContainsString('id="mon-titre"', $result);
    }

    public function testAddAnchorWithAfterPosition(): void
    {
        $html = '<h2>Mon Titre</h2>';
        $result = $this->service->setLinkPosition('after')->addAnchor($html);

        // Le lien doit être après le texte
        $this->assertMatchesRegularExpression('/Mon Titre.*anchor-link/', $result);
    }

    public function testAddAnchorWithWrapPosition(): void
    {
        $html = '<h2>Mon Titre</h2>';
        $result = $this->service->setLinkPosition('wrap')->addAnchor($html);

        $this->assertStringContainsString('anchor-link-wrap', $result);
        $this->assertStringContainsString('>Mon Titre</a>', $result);
    }

    public function testAddAnchorWithCustomSymbol(): void
    {
        $html = '<h2>Mon Titre</h2>';
        $result = $this->service->setLinkSymbol('§')->addAnchor($html);

        $this->assertStringContainsString('>§</a>', $result);
    }

    public function testAddAnchorWithCustomClass(): void
    {
        $html = '<h2>Mon Titre</h2>';
        $result = $this->service->setLinkClass('heading-link')->addAnchor($html);

        $this->assertStringContainsString('class="heading-link"', $result);
    }

    public function testAddAnchorWithDisabledId(): void
    {
        $html = '<h2>Mon Titre</h2>';
        $result = $this->service->setAddId(false)->addAnchor($html);

        $this->assertStringNotContainsString('id="mon-titre"', $result);
    }

    public function testAddAnchorIgnoresExcludedLevels(): void
    {
        $html = '<h1>Titre Principal</h1>';
        $result = $this->service->setLevels([2, 3])->addAnchor($html);

        $this->assertSame($html, $result);
    }

    public function testAddAnchorHandlesAllLevels(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $html = "<h{$i}>Titre Niveau {$i}</h{$i}>";
            $result = $this->service->addAnchor($html);

            $this->assertStringContainsString('anchor-link', $result, "Level h{$i} should have anchor");
        }
    }

    public function testProcessContentTransformsAllHeadings(): void
    {
        $html = '<h1>Titre 1</h1><p>Texte</p><h2>Titre 2</h2><h3>Titre 3</h3>';
        $result = $this->service->processContent($html);

        $this->assertStringContainsString('id="titre-1"', $result);
        $this->assertStringContainsString('id="titre-2"', $result);
        $this->assertStringContainsString('id="titre-3"', $result);
    }

    public function testProcessContentOnlyTargetsSpecifiedLevels(): void
    {
        $html = '<h1>Titre 1</h1><h2>Titre 2</h2><h3>Titre 3</h3>';
        $result = $this->service->setLevels([2])->processContent($html);

        $this->assertStringNotContainsString('id="titre-1"', $result);
        $this->assertStringContainsString('id="titre-2"', $result);
        $this->assertStringNotContainsString('id="titre-3"', $result);
    }

    public function testProcessContentPreservesNonHeadingContent(): void
    {
        $html = '<div><p>Paragraphe</p><h2>Titre</h2><ul><li>Item</li></ul></div>';
        $result = $this->service->processContent($html);

        $this->assertStringContainsString('<p>Paragraphe</p>', $result);
        $this->assertStringContainsString('<ul><li>Item</li></ul>', $result);
    }

    public function testGenerateCssReturnsValidCss(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('.anchor-link', $css);
        $this->assertStringContainsString('scroll-margin-top', $css);
        $this->assertStringContainsString('transition', $css);
    }

    public function testGenerateCssWithCustomClass(): void
    {
        $css = $this->service->setLinkClass('my-anchor')->generateCss();

        $this->assertStringContainsString('.my-anchor', $css);
    }

    public function testGenerateJsReturnsValidJs(): void
    {
        $js = $this->service->generateJs();

        $this->assertStringContainsString('scrollIntoView', $js);
        $this->assertStringContainsString('smooth', $js);
        $this->assertStringContainsString('pushState', $js);
    }

    public function testReturnsSameHtmlForNonHeading(): void
    {
        $html = '<div>Not a heading</div>';
        $result = $this->service->addAnchor($html);

        $this->assertSame($html, $result);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setLinkPosition('after')
            ->setLinkClass('custom')
            ->setLinkSymbol('→')
            ->setAddId(true)
            ->setLevels([2, 3])
            ->setVisibleOnHover(false);

        $this->assertSame($this->service, $result);
    }

    public function testInvalidPositionDefaultsToBefore(): void
    {
        $html = '<h2>Test</h2>';
        $result = $this->service->setLinkPosition('invalid')->addAnchor($html);

        // Le lien doit être avant le texte
        $this->assertMatchesRegularExpression('/anchor-link.*Test/', $result);
    }
}
