<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le sanitizer HTML.
 *
 * Le sanitizer nettoie le HTML pour éviter les attaques XSS
 * tout en préservant le formatage légitime.
 */
final class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new HtmlSanitizer();
    }

    // =========================================================================
    // Balises autorisées
    // =========================================================================

    public function testAllowsSafeHtmlTags(): void
    {
        $html = '<p>Paragraphe</p>';

        $this->assertSame('<p>Paragraphe</p>', $this->sanitizer->sanitize($html));
    }

    public function testAllowsHeadings(): void
    {
        $html = '<h1>Titre</h1><h2>Sous-titre</h2>';

        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsFormatting(): void
    {
        $html = '<strong>Gras</strong> <em>Italique</em>';

        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsLists(): void
    {
        $html = '<ul><li>Item</li></ul><ol><li>Numéro</li></ol>';

        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsBlockquote(): void
    {
        $html = '<blockquote>Citation</blockquote>';

        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsCode(): void
    {
        $html = '<code>inline</code><pre>block</pre>';

        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsHorizontalRule(): void
    {
        $html = '<hr>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertTrue($result === '<hr>' || $result === '<hr />');
    }

    public function testAllowsBreak(): void
    {
        $html = 'Ligne 1<br>Ligne 2';

        $result = $this->sanitizer->sanitize($html);
        $this->assertTrue(
            $result === 'Ligne 1<br>Ligne 2' ||
            $result === 'Ligne 1<br />Ligne 2'
        );
    }

    // =========================================================================
    // Liens
    // =========================================================================

    public function testAllowsLinksWithHref(): void
    {
        $html = '<a href="https://example.com">Lien</a>';

        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsLinksWithTitle(): void
    {
        $html = '<a href="https://example.com" title="Titre">Lien</a>';

        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsLinksWithTarget(): void
    {
        $html = '<a href="https://example.com" target="_blank">Lien</a>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringContainsString('target="_blank"', $result);
    }

    public function testAddsRelNoopenerToExternalLinks(): void
    {
        $html = '<a href="https://example.com" target="_blank">Lien</a>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
    }

    public function testRemovesJavascriptFromLinks(): void
    {
        $html = '<a href="javascript:alert(1)">XSS</a>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function testRemovesDataUrlFromLinks(): void
    {
        $html = '<a href="data:text/html,<script>alert(1)</script>">XSS</a>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('data:', $result);
    }

    // =========================================================================
    // Images
    // =========================================================================

    public function testAllowsImages(): void
    {
        $html = '<img src="/images/photo.jpg" alt="Photo">';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringContainsString('src="/images/photo.jpg"', $result);
        $this->assertStringContainsString('alt="Photo"', $result);
    }

    public function testAllowsImageDimensions(): void
    {
        $html = '<img src="/img.jpg" width="100" height="100">';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringContainsString('width="100"', $result);
        $this->assertStringContainsString('height="100"', $result);
    }

    public function testRemovesOnerrorFromImages(): void
    {
        $html = '<img src="/img.jpg" onerror="alert(1)">';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('onerror', $result);
    }

    // =========================================================================
    // Scripts et styles dangereux
    // =========================================================================

    public function testRemovesScriptTags(): void
    {
        $html = '<script>alert("XSS")</script><p>Safe</p>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('<p>Safe</p>', $result);
    }

    public function testRemovesStyleTags(): void
    {
        $html = '<style>body { display: none; }</style><p>Safe</p>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('<style>', $result);
        $this->assertStringContainsString('<p>Safe</p>', $result);
    }

    public function testRemovesIframeTags(): void
    {
        $html = '<iframe src="https://evil.com"></iframe>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('<iframe', $result);
    }

    public function testRemovesObjectTags(): void
    {
        $html = '<object data="exploit.swf"></object>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('<object', $result);
    }

    public function testRemovesEmbedTags(): void
    {
        $html = '<embed src="exploit.swf">';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('<embed', $result);
    }

    public function testRemovesFormTags(): void
    {
        $html = '<form action="https://evil.com"><input type="text"></form>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('<form', $result);
    }

    // =========================================================================
    // Attributs dangereux
    // =========================================================================

    public function testRemovesOnEventHandlers(): void
    {
        $handlers = [
            'onclick',
            'onload',
            'onerror',
            'onmouseover',
            'onfocus',
            'onsubmit',
        ];

        foreach ($handlers as $handler) {
            $html = sprintf('<div %s="alert(1)">Test</div>', $handler);
            $result = $this->sanitizer->sanitize($html);
            $this->assertStringNotContainsString($handler, $result, "Handler $handler not removed");
        }
    }

    public function testRemovesStyleAttribute(): void
    {
        $html = '<p style="background: url(javascript:alert(1))">Test</p>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('style=', $result);
    }

    // =========================================================================
    // Encodage et cas limites
    // =========================================================================

    public function testHandlesMalformedHtml(): void
    {
        $html = '<p>Unclosed paragraph<strong>Bold';

        // Doit retourner du HTML valide sans planter
        $result = $this->sanitizer->sanitize($html);
        $this->assertIsString($result);
    }

    public function testHandlesEncodedJavascript(): void
    {
        $html = '<a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(1)">XSS</a>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('javascript', strtolower($result));
    }

    public function testHandlesNestedDangerousTags(): void
    {
        $html = '<div><script><script>alert(1)</script></script></div>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('<script', $result);
    }

    public function testPreservesTextContent(): void
    {
        $html = '<p>Contenu important à préserver!</p>';

        $result = $this->sanitizer->sanitize($html);
        $this->assertStringContainsString('Contenu important à préserver!', $result);
    }

    public function testHandlesEmptyInput(): void
    {
        $this->assertSame('', $this->sanitizer->sanitize(''));
    }

    public function testHandlesPlainText(): void
    {
        $text = 'Juste du texte sans HTML';

        $this->assertSame($text, $this->sanitizer->sanitize($text));
    }

    // =========================================================================
    // Configuration
    // =========================================================================

    public function testCustomAllowedTags(): void
    {
        $sanitizer = new HtmlSanitizer(['p', 'br']);
        $html = '<p>OK</p><strong>Removed</strong>';

        $result = $sanitizer->sanitize($html);
        $this->assertStringContainsString('<p>OK</p>', $result);
        $this->assertStringNotContainsString('<strong>', $result);
        $this->assertStringContainsString('Removed', $result); // Text preserved
    }

    public function testStripAllTags(): void
    {
        $sanitizer = new HtmlSanitizer([]);
        $html = '<p>Text <strong>bold</strong></p>';

        $result = $sanitizer->sanitize($html);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringContainsString('Text bold', $result);
    }

    // =========================================================================
    // Méthodes utilitaires
    // =========================================================================

    public function testStripTags(): void
    {
        $html = '<p>Texte <strong>avec</strong> balises</p>';

        $text = HtmlSanitizer::stripTags($html);

        $this->assertSame('Texte avec balises', $text);
    }

    public function testEscapeHtml(): void
    {
        $text = '<script>alert("XSS")</script>';

        $escaped = HtmlSanitizer::escapeHtml($text);

        $this->assertSame('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $escaped);
    }
}
