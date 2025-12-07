<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\PerformanceHelper;
use PHPUnit\Framework\TestCase;

final class PerformanceHelperTest extends TestCase
{
    private PerformanceHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new PerformanceHelper();
    }

    public function testPreloadGeneratesValidLink(): void
    {
        $link = $this->helper->preload('/css/style.css', 'style');

        $this->assertStringContainsString('rel="preload"', $link);
        $this->assertStringContainsString('href="/css/style.css"', $link);
        $this->assertStringContainsString('as="style"', $link);
    }

    public function testPreloadWithType(): void
    {
        $link = $this->helper->preload('/font.woff2', 'font', 'font/woff2');

        $this->assertStringContainsString('type="font/woff2"', $link);
    }

    public function testPreloadWithCrossorigin(): void
    {
        $link = $this->helper->preload('/font.woff2', 'font', null, true);

        $this->assertStringContainsString('crossorigin', $link);
    }

    public function testPreconnectGeneratesValidLink(): void
    {
        $link = $this->helper->preconnect('https://fonts.googleapis.com');

        $this->assertStringContainsString('rel="preconnect"', $link);
        $this->assertStringContainsString('href="https://fonts.googleapis.com"', $link);
    }

    public function testPreconnectWithCrossorigin(): void
    {
        $link = $this->helper->preconnect('https://fonts.gstatic.com', true);

        $this->assertStringContainsString('crossorigin', $link);
    }

    public function testDnsPrefetchGeneratesValidLink(): void
    {
        $link = $this->helper->dnsPrefetch('https://cdn.example.com');

        $this->assertStringContainsString('rel="dns-prefetch"', $link);
        $this->assertStringContainsString('href="https://cdn.example.com"', $link);
    }

    public function testPrefetchGeneratesValidLink(): void
    {
        $link = $this->helper->prefetch('/next-page.html');

        $this->assertStringContainsString('rel="prefetch"', $link);
        $this->assertStringContainsString('href="/next-page.html"', $link);
    }

    public function testPrefetchWithAs(): void
    {
        $link = $this->helper->prefetch('/script.js', 'script');

        $this->assertStringContainsString('as="script"', $link);
    }

    public function testGenerateResourceHintsReturnsAllHints(): void
    {
        $hints = $this->helper
            ->addDnsPrefetch('https://dns.example.com')
            ->addPreconnect('https://preconnect.example.com', true)
            ->addPreload('/style.css', 'style')
            ->addPrefetch('/next.html')
            ->generateResourceHints();

        $this->assertStringContainsString('dns-prefetch', $hints);
        $this->assertStringContainsString('preconnect', $hints);
        $this->assertStringContainsString('preload', $hints);
        $this->assertStringContainsString('prefetch', $hints);
    }

    public function testInlineCriticalCssMinifies(): void
    {
        $css = '.class {   color:  red;   }';
        $result = $this->helper->inlineCriticalCss($css);

        $this->assertStringContainsString('<style>', $result);
        $this->assertStringContainsString('</style>', $result);
        $this->assertStringNotContainsString('   ', $result);
    }

    public function testAsyncCssGeneratesValidHtml(): void
    {
        $html = $this->helper->asyncCss('/css/non-critical.css');

        $this->assertStringContainsString('rel="preload"', $html);
        $this->assertStringContainsString('as="style"', $html);
        $this->assertStringContainsString('onload=', $html);
        $this->assertStringContainsString('<noscript>', $html);
    }

    public function testDeferScriptGeneratesValidHtml(): void
    {
        $html = $this->helper->deferScript('/js/app.js');

        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('src="/js/app.js"', $html);
        $this->assertStringContainsString('defer', $html);
    }

    public function testAsyncScriptGeneratesValidHtml(): void
    {
        $html = $this->helper->asyncScript('/js/analytics.js');

        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('async', $html);
    }

    public function testLcpImageAddsFetchPriority(): void
    {
        $img = '<img src="hero.jpg" alt="Hero">';
        $result = $this->helper->lcpImage($img);

        $this->assertStringContainsString('fetchpriority="high"', $result);
    }

    public function testLcpImageRemovesLazyLoading(): void
    {
        $img = '<img src="hero.jpg" loading="lazy" alt="Hero">';
        $result = $this->helper->lcpImage($img);

        $this->assertStringNotContainsString('loading="lazy"', $result);
    }

    public function testLcpImageDoesNotDuplicateFetchPriority(): void
    {
        $img = '<img src="hero.jpg" fetchpriority="high" alt="Hero">';
        $result = $this->helper->lcpImage($img);

        $this->assertSame(1, substr_count($result, 'fetchpriority'));
    }

    public function testFixedDimensionsImageAddsDimensions(): void
    {
        $img = '<img src="photo.jpg" alt="Photo">';
        $result = $this->helper->fixedDimensionsImage($img, 800, 600);

        $this->assertStringContainsString('width="800"', $result);
        $this->assertStringContainsString('height="600"', $result);
    }

    public function testFixedDimensionsImageDoesNotDuplicate(): void
    {
        $img = '<img src="photo.jpg" width="100" height="100" alt="Photo">';
        $result = $this->helper->fixedDimensionsImage($img, 800, 600);

        $this->assertStringContainsString('width="100"', $result);
        $this->assertStringContainsString('height="100"', $result);
    }

    public function testAspectRatioContainerGeneratesValidHtml(): void
    {
        $html = $this->helper->aspectRatioContainer('<img src="img.jpg">', 0.5625);

        $this->assertStringContainsString('padding-bottom:56.25%', $html);
        $this->assertStringContainsString('position:relative', $html);
        $this->assertStringContainsString('<img src="img.jpg">', $html);
    }

    public function testGenerateWebVitalsJsReturnsValidJs(): void
    {
        $js = $this->helper->generateWebVitalsJs();

        $this->assertStringContainsString('PerformanceObserver', $js);
        $this->assertStringContainsString('largest-contentful-paint', $js);
        $this->assertStringContainsString('first-input', $js);
        $this->assertStringContainsString('layout-shift', $js);
        $this->assertStringContainsString('LCP', $js);
        $this->assertStringContainsString('FID', $js);
        $this->assertStringContainsString('CLS', $js);
    }

    public function testGeneratePerformanceMetaReturnsValidMeta(): void
    {
        $meta = $this->helper->generatePerformanceMeta();

        $this->assertStringContainsString('viewport', $meta);
        $this->assertStringContainsString('X-UA-Compatible', $meta);
    }

    public function testOptimizedFontLoadingGeneratesValidHtml(): void
    {
        $fonts = [
            ['family' => 'Inter', 'url' => '/fonts/inter.woff2'],
            ['family' => 'Roboto', 'url' => '/fonts/roboto.woff2'],
        ];

        $html = $this->helper->optimizedFontLoading($fonts);

        $this->assertStringContainsString('preload', $html);
        $this->assertStringContainsString('as="font"', $html);
        $this->assertStringContainsString('FontFace', $html);
        $this->assertStringContainsString('fonts-loaded', $html);
    }

    public function testFluentInterface(): void
    {
        $result = $this->helper
            ->addPreconnect('https://example.com')
            ->addDnsPrefetch('https://example2.com')
            ->addPreload('/style.css', 'style')
            ->addPrefetch('/page.html');

        $this->assertSame($this->helper, $result);
    }
}
