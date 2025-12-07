<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\DarkModeService;
use PHPUnit\Framework\TestCase;

final class DarkModeServiceTest extends TestCase
{
    private DarkModeService $service;

    protected function setUp(): void
    {
        $this->service = new DarkModeService();
    }

    public function testGenerateCssReturnsValidCss(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('data-theme', $css);
        $this->assertStringContainsString('--bg-primary', $css);
        $this->assertStringContainsString('--text-primary', $css);
    }

    public function testGenerateCssIncludesLightTheme(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('[data-theme="light"]', $css);
        $this->assertStringContainsString('color-scheme: light', $css);
    }

    public function testGenerateCssIncludesDarkTheme(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('[data-theme="dark"]', $css);
        $this->assertStringContainsString('color-scheme: dark', $css);
    }

    public function testGenerateCssWithCustomDataAttribute(): void
    {
        $css = $this->service->setDataAttribute('data-color-mode')->generateCss();

        $this->assertStringContainsString('[data-color-mode="light"]', $css);
        $this->assertStringContainsString('[data-color-mode="dark"]', $css);
    }

    public function testGenerateCssWithSystemPreference(): void
    {
        $css = $this->service->setRespectSystemPreference(true)->generateCss();

        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $css);
    }

    public function testGenerateCssWithoutSystemPreference(): void
    {
        $css = $this->service->setRespectSystemPreference(false)->generateCss();

        $this->assertStringNotContainsString('@media (prefers-color-scheme: dark)', $css);
    }

    public function testGenerateCssWithCustomColors(): void
    {
        $css = $this->service
            ->setLightColors(['--custom-color' => '#ff0000'])
            ->setDarkColors(['--custom-color' => '#00ff00'])
            ->generateCss();

        $this->assertStringContainsString('--custom-color: #ff0000', $css);
        $this->assertStringContainsString('--custom-color: #00ff00', $css);
    }

    public function testGenerateCssIncludesToggleStyles(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('.theme-toggle', $css);
        $this->assertStringContainsString('.icon-sun', $css);
        $this->assertStringContainsString('.icon-moon', $css);
    }

    public function testGenerateJsReturnsValidJs(): void
    {
        $js = $this->service->generateJs();

        $this->assertStringContainsString('localStorage', $js);
        $this->assertStringContainsString('toggleTheme', $js);
        $this->assertStringContainsString('setTheme', $js);
        $this->assertStringContainsString('ThemeManager', $js);
    }

    public function testGenerateJsWithCustomStorageKey(): void
    {
        $js = $this->service->setStorageKey('color-preference')->generateJs();

        $this->assertStringContainsString("'color-preference'", $js);
    }

    public function testGenerateJsWithCustomDefaultTheme(): void
    {
        $js = $this->service->setDefaultTheme('dark')->generateJs();

        $this->assertStringContainsString("'dark'", $js);
    }

    public function testGenerateJsListensToSystemPreference(): void
    {
        $js = $this->service->generateJs();

        $this->assertStringContainsString("matchMedia('(prefers-color-scheme: dark)')", $js);
    }

    public function testGenerateJsDispatchesEvent(): void
    {
        $js = $this->service->generateJs();

        $this->assertStringContainsString('themechange', $js);
        $this->assertStringContainsString('CustomEvent', $js);
    }

    public function testGenerateNoFlashScriptReturnsValidJs(): void
    {
        $script = $this->service->generateNoFlashScript();

        $this->assertStringContainsString('localStorage', $script);
        $this->assertStringContainsString('setAttribute', $script);
        $this->assertStringContainsString('prefers-color-scheme', $script);
    }

    public function testGenerateNoFlashScriptWithCustomKey(): void
    {
        $script = $this->service->setStorageKey('my-theme')->generateNoFlashScript();

        $this->assertStringContainsString("'my-theme'", $script);
    }

    public function testGenerateToggleReturnsValidHtml(): void
    {
        $html = $this->service->generateToggle();

        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('theme-toggle', $html);
        $this->assertStringContainsString('icon-sun', $html);
        $this->assertStringContainsString('icon-moon', $html);
        $this->assertStringContainsString('aria-label', $html);
    }

    public function testGenerateToggleWithCustomClass(): void
    {
        $html = $this->service->generateToggle('my-toggle-btn');

        $this->assertStringContainsString('class="my-toggle-btn"', $html);
    }

    public function testGenerateSelectorReturnsValidHtml(): void
    {
        $html = $this->service->generateSelector();

        $this->assertStringContainsString('theme-selector', $html);
        $this->assertStringContainsString('data-theme-value="light"', $html);
        $this->assertStringContainsString('data-theme-value="dark"', $html);
        $this->assertStringContainsString('data-theme-value="system"', $html);
    }

    public function testGenerateSelectorWithCustomClass(): void
    {
        $html = $this->service->generateSelector('custom-selector');

        $this->assertStringContainsString('class="custom-selector"', $html);
    }

    public function testGenerateAllReturnsAllParts(): void
    {
        $all = $this->service->generateAll();

        $this->assertArrayHasKey('css', $all);
        $this->assertArrayHasKey('js', $all);
        $this->assertArrayHasKey('noFlashScript', $all);
        $this->assertArrayHasKey('toggle', $all);
    }

    public function testInvalidDefaultThemeFallsBackToSystem(): void
    {
        $js = $this->service->setDefaultTheme('invalid')->generateJs();

        $this->assertStringContainsString("DEFAULT_THEME = 'system'", $js);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setStorageKey('theme')
            ->setDefaultTheme('system')
            ->setDataAttribute('data-theme')
            ->setRespectSystemPreference(true)
            ->setLightColors(['--bg' => '#fff'])
            ->setDarkColors(['--bg' => '#000']);

        $this->assertSame($this->service, $result);
    }
}
