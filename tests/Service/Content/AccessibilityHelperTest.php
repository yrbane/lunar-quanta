<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\AccessibilityHelper;
use PHPUnit\Framework\TestCase;

final class AccessibilityHelperTest extends TestCase
{
    private AccessibilityHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new AccessibilityHelper();
    }

    public function testGenerateSkipLinkReturnsValidHtml(): void
    {
        $html = $this->helper->generateSkipLink();

        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('skip-link', $html);
        $this->assertStringContainsString('#main-content', $html);
        $this->assertStringContainsString('Aller au contenu principal', $html);
    }

    public function testGenerateSkipLinkWithCustomTarget(): void
    {
        $html = $this->helper->setSkipLinkTarget('#content')->generateSkipLink();

        $this->assertStringContainsString('href="#content"', $html);
    }

    public function testGenerateSkipLinkWithCustomText(): void
    {
        $html = $this->helper->setSkipLinkText('Skip to main')->generateSkipLink();

        $this->assertStringContainsString('Skip to main', $html);
    }

    public function testEnhanceLinkAddsAriaLabel(): void
    {
        $link = '<a href="/article">Lire</a>';
        $result = $this->helper->enhanceLink($link, 'Lire article complet');

        $this->assertStringContainsString('aria-label="Lire article complet"', $result);
    }

    public function testEnhanceLinkDoesNotDuplicateAriaLabel(): void
    {
        $link = '<a href="/article" aria-label="Existant">Lire</a>';
        $result = $this->helper->enhanceLink($link, 'Nouveau');

        $this->assertStringContainsString('aria-label="Existant"', $result);
        $this->assertStringNotContainsString('aria-label="Nouveau"', $result);
    }

    public function testEnhanceLinkHandlesExternalLinks(): void
    {
        $link = '<a href="https://example.com">Lien</a>';
        $result = $this->helper->enhanceLink($link, null, true);

        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
        $this->assertStringContainsString('external-link-icon', $result);
    }

    public function testEnhanceImageAddsAlt(): void
    {
        $img = '<img src="photo.jpg">';
        $result = $this->helper->enhanceImage($img, 'Description');

        $this->assertStringContainsString('alt="Description"', $result);
    }

    public function testEnhanceImageDoesNotDuplicateAlt(): void
    {
        $img = '<img src="photo.jpg" alt="Existant">';
        $result = $this->helper->enhanceImage($img, 'Nouveau');

        $this->assertStringContainsString('alt="Existant"', $result);
        $this->assertStringNotContainsString('alt="Nouveau"', $result);
    }

    public function testEnhanceImageHandlesDecorativeImages(): void
    {
        $img = '<img src="decorative.jpg">';
        $result = $this->helper->enhanceImage($img, null, true);

        $this->assertStringContainsString('alt=""', $result);
        $this->assertStringContainsString('role="presentation"', $result);
    }

    public function testEnhanceButtonAddsAriaLabel(): void
    {
        $button = '<button >X</button>';
        $result = $this->helper->enhanceButton($button, 'Fermer');

        $this->assertStringContainsString('aria-label="Fermer"', $result);
    }

    public function testEnhanceButtonAddsAriaExpanded(): void
    {
        $button = '<button >Menu</button>';
        $result = $this->helper->enhanceButton($button, null, 'false');

        $this->assertStringContainsString('aria-expanded="false"', $result);
    }

    public function testEnhanceFormAddsAriaDescribedBy(): void
    {
        $form = '<form action="/submit">...</form>';
        $result = $this->helper->enhanceForm($form, 'form-help');

        $this->assertStringContainsString('aria-describedby="form-help"', $result);
    }

    public function testEnhanceTableAddsRole(): void
    {
        $table = '<table><tr><td>Data</td></tr></table>';
        $result = $this->helper->enhanceTable($table);

        $this->assertStringContainsString('role="table"', $result);
    }

    public function testEnhanceTableAddsCaption(): void
    {
        $table = '<table><tr><td>Data</td></tr></table>';
        $result = $this->helper->enhanceTable($table, 'Titre du tableau');

        $this->assertStringContainsString('<caption>Titre du tableau</caption>', $result);
    }

    public function testEnhanceTableAddsSummary(): void
    {
        $table = '<table><tr><td>Data</td></tr></table>';
        $result = $this->helper->enhanceTable($table, null, 'Description du tableau');

        $this->assertStringContainsString('aria-label="Description du tableau"', $result);
    }

    public function testProcessContentAddsAltToImages(): void
    {
        $html = '<div><img src="photo.jpg"></div>';
        $result = $this->helper->processContent($html);

        $this->assertStringContainsString('alt=', $result);
    }

    public function testProcessContentEnhancesExternalLinks(): void
    {
        $html = '<a href="https://external.com">External</a>';
        $result = $this->helper->processContent($html);

        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('rel="noopener', $result);
    }

    public function testProcessContentAddsScopeToTh(): void
    {
        $html = '<table><tr><th>Header</th></tr></table>';
        $result = $this->helper->processContent($html);

        $this->assertStringContainsString('scope="col"', $result);
    }

    public function testGenerateCssReturnsValidCss(): void
    {
        $css = $this->helper->generateCss();

        $this->assertStringContainsString('.skip-link', $css);
        $this->assertStringContainsString(':focus-visible', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('.sr-only', $css);
    }

    public function testScreenReaderTextReturnsHiddenSpan(): void
    {
        $html = $this->helper->screenReaderText('Texte caché');

        $this->assertStringContainsString('sr-only', $html);
        $this->assertStringContainsString('Texte caché', $html);
    }

    public function testLoadingMessageReturnsAccessibleDiv(): void
    {
        $html = $this->helper->loadingMessage();

        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('aria-busy="true"', $html);
    }

    public function testAlertMessageReturnsAccessibleDiv(): void
    {
        $html = $this->helper->alertMessage('Message', 'error');

        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('aria-live="assertive"', $html);
    }

    public function testAlertMessageInfoUsesStatus(): void
    {
        $html = $this->helper->alertMessage('Info', 'info');

        $this->assertStringContainsString('role="status"', $html);
    }

    public function testHasAllImageAltsReturnsTrueWhenAllHaveAlt(): void
    {
        $html = '<img src="a.jpg" alt="A"><img src="b.jpg" alt="B">';

        $this->assertTrue($this->helper->hasAllImageAlts($html));
    }

    public function testHasAllImageAltsReturnsFalseWhenMissing(): void
    {
        $html = '<img src="a.jpg" alt="A"><img src="b.jpg">';

        $this->assertFalse($this->helper->hasAllImageAlts($html));
    }

    public function testCheckIssuesDetectsMissingAlt(): void
    {
        $html = '<img src="photo.jpg">';
        $issues = $this->helper->checkIssues($html);

        $this->assertArrayHasKey('missing_alt', $issues);
        $this->assertNotEmpty($issues['missing_alt']);
    }

    public function testCheckIssuesDetectsEmptyLinks(): void
    {
        $html = '<a href="/page"></a>';
        $issues = $this->helper->checkIssues($html);

        $this->assertArrayHasKey('empty_links', $issues);
    }

    public function testCheckIssuesDetectsEmptyButtons(): void
    {
        $html = '<button></button>';
        $issues = $this->helper->checkIssues($html);

        $this->assertArrayHasKey('empty_buttons', $issues);
    }

    public function testCheckIssuesReturnsEmptyForValidHtml(): void
    {
        $html = '<img src="a.jpg" alt="A"><a href="/">Link</a><button>Click</button>';
        $issues = $this->helper->checkIssues($html);

        $this->assertEmpty($issues);
    }

    public function testFluentInterface(): void
    {
        $result = $this->helper
            ->setSkipLinkTarget('#content')
            ->setSkipLinkText('Skip');

        $this->assertSame($this->helper, $result);
    }
}
