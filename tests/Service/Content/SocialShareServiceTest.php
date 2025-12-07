<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\SocialShareService;
use PHPUnit\Framework\TestCase;

final class SocialShareServiceTest extends TestCase
{
    private SocialShareService $service;

    protected function setUp(): void
    {
        $this->service = new SocialShareService();
    }

    public function testGenerateButtonsReturnsHtml(): void
    {
        $result = $this->service->generateButtons(
            'https://example.com/article',
            'My Article',
            'Description'
        );

        $this->assertStringContainsString('<a href=', $result);
        $this->assertStringContainsString('twitter', $result);
    }

    public function testGenerateButtonReturnsValidLink(): void
    {
        $result = $this->service->generateButton(
            'twitter',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('href=', $result);
        $this->assertStringContainsString('twitter.com', $result);
    }

    public function testGenerateButtonIncludesTitle(): void
    {
        $result = $this->service->generateButton(
            'twitter',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('Partager sur Twitter', $result);
    }

    public function testGenerateButtonOpensInNewTab(): void
    {
        $result = $this->service->generateButton(
            'twitter',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('rel="noopener"', $result);
    }

    public function testSetOpenInNewTabDisablesNewTab(): void
    {
        $this->service->setOpenInNewTab(false);
        $result = $this->service->generateButton(
            'twitter',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringNotContainsString('target="_blank"', $result);
    }

    public function testSetIncludeTextAddsText(): void
    {
        $this->service->setIncludeText(true);
        $result = $this->service->generateButton(
            'twitter',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('<span>Twitter</span>', $result);
    }

    public function testGetShareUrlForTwitter(): void
    {
        $url = $this->service->getShareUrl(
            'twitter',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('twitter.com/intent/tweet', $url);
        $this->assertStringContainsString('url=', $url);
        $this->assertStringContainsString('text=', $url);
    }

    public function testGetShareUrlForFacebook(): void
    {
        $url = $this->service->getShareUrl(
            'facebook',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('facebook.com/sharer', $url);
    }

    public function testGetShareUrlForLinkedin(): void
    {
        $url = $this->service->getShareUrl(
            'linkedin',
            'https://example.com/article',
            'My Article',
            'Description'
        );

        $this->assertStringContainsString('linkedin.com/shareArticle', $url);
        $this->assertStringContainsString('summary=', $url);
    }

    public function testGetShareUrlForReddit(): void
    {
        $url = $this->service->getShareUrl(
            'reddit',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('reddit.com/submit', $url);
    }

    public function testGetShareUrlForWhatsapp(): void
    {
        $url = $this->service->getShareUrl(
            'whatsapp',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('wa.me', $url);
    }

    public function testGetShareUrlForTelegram(): void
    {
        $url = $this->service->getShareUrl(
            'telegram',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('t.me/share', $url);
    }

    public function testGetShareUrlForEmail(): void
    {
        $url = $this->service->getShareUrl(
            'email',
            'https://example.com/article',
            'My Article',
            'Description'
        );

        $this->assertStringContainsString('mailto:', $url);
        $this->assertStringContainsString('subject=', $url);
    }

    public function testGetShareUrlForUnknownReturnsNull(): void
    {
        $url = $this->service->getShareUrl(
            'unknown',
            'https://example.com/article',
            'My Article'
        );

        $this->assertNull($url);
    }

    public function testGenerateButtonForUnknownReturnsNull(): void
    {
        $result = $this->service->generateButton(
            'unknown',
            'https://example.com/article',
            'My Article'
        );

        $this->assertNull($result);
    }

    public function testSetNetworksChangesNetworks(): void
    {
        $this->service->setNetworks(['facebook', 'linkedin']);
        $result = $this->service->generateButtons(
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('facebook', $result);
        $this->assertStringContainsString('linkedin', $result);
        $this->assertStringNotContainsString('twitter.com', $result);
    }

    public function testSetButtonClassChangesClass(): void
    {
        $this->service->setButtonClass('custom-btn');
        $result = $this->service->generateButton(
            'twitter',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('class="custom-btn"', $result);
    }

    public function testSetIconClassChangesIconClass(): void
    {
        $this->service->setIconClass('custom-icon');
        $result = $this->service->generateButton(
            'twitter',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('class="custom-icon"', $result);
    }

    public function testGeneratePopupScriptReturnsJs(): void
    {
        $result = $this->service->generatePopupScript();

        $this->assertStringContainsString('addEventListener', $result);
        $this->assertStringContainsString('window.open', $result);
    }

    public function testGenerateCssReturnsCss(): void
    {
        $result = $this->service->generateCss();

        $this->assertStringContainsString('.social-share-btn', $result);
        $this->assertStringContainsString('display:', $result);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setNetworks(['twitter'])
            ->setButtonClass('btn')
            ->setIconClass('icon')
            ->setOpenInNewTab(true)
            ->setIncludeText(false);

        $this->assertSame($this->service, $result);
    }

    public function testXIsAliasForTwitter(): void
    {
        $url = $this->service->getShareUrl(
            'x',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('twitter.com', $url);
    }

    public function testHnIsAliasForHackerNews(): void
    {
        $url = $this->service->getShareUrl(
            'hn',
            'https://example.com/article',
            'My Article'
        );

        $this->assertStringContainsString('news.ycombinator.com', $url);
    }

    public function testUrlIsEncoded(): void
    {
        $url = $this->service->getShareUrl(
            'twitter',
            'https://example.com/article?param=value',
            'My Article'
        );

        $this->assertStringContainsString('%3F', $url); // Encoded ?
        $this->assertStringContainsString('%3D', $url); // Encoded =
    }
}
