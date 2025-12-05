<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\MarkdownParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le parseur Markdown.
 *
 * Implémentation sans dépendance externe, supportant :
 * - Titres (h1-h6)
 * - Paragraphes
 * - Emphase (gras, italique)
 * - Liens et images
 * - Listes (ordonnées et non-ordonnées)
 * - Code (inline et blocs)
 * - Citations
 */
final class MarkdownParserTest extends TestCase
{
    private MarkdownParser $parser;

    protected function setUp(): void
    {
        $this->parser = new MarkdownParser();
    }

    // =========================================================================
    // Titres
    // =========================================================================

    public function testParseHeading1(): void
    {
        $html = $this->parser->parse('# Titre niveau 1');

        $this->assertSame('<h1>Titre niveau 1</h1>', trim($html));
    }

    public function testParseHeading2(): void
    {
        $html = $this->parser->parse('## Titre niveau 2');

        $this->assertSame('<h2>Titre niveau 2</h2>', trim($html));
    }

    public function testParseHeading6(): void
    {
        $html = $this->parser->parse('###### Titre niveau 6');

        $this->assertSame('<h6>Titre niveau 6</h6>', trim($html));
    }

    public function testParseHeadingWithTrailingHashes(): void
    {
        $html = $this->parser->parse('## Titre ##');

        $this->assertSame('<h2>Titre</h2>', trim($html));
    }

    // =========================================================================
    // Paragraphes
    // =========================================================================

    public function testParseParagraph(): void
    {
        $html = $this->parser->parse('Ceci est un paragraphe.');

        $this->assertSame('<p>Ceci est un paragraphe.</p>', trim($html));
    }

    public function testParseMultipleParagraphs(): void
    {
        $markdown = "Premier paragraphe.\n\nDeuxième paragraphe.";
        $html = $this->parser->parse($markdown);

        $this->assertStringContainsString('<p>Premier paragraphe.</p>', $html);
        $this->assertStringContainsString('<p>Deuxième paragraphe.</p>', $html);
    }

    // =========================================================================
    // Emphase
    // =========================================================================

    public function testParseBold(): void
    {
        $html = $this->parser->parse('Texte **en gras** ici.');

        $this->assertStringContainsString('<strong>en gras</strong>', $html);
    }

    public function testParseBoldWithUnderscores(): void
    {
        $html = $this->parser->parse('Texte __en gras__ ici.');

        $this->assertStringContainsString('<strong>en gras</strong>', $html);
    }

    public function testParseItalic(): void
    {
        $html = $this->parser->parse('Texte *en italique* ici.');

        $this->assertStringContainsString('<em>en italique</em>', $html);
    }

    public function testParseItalicWithUnderscores(): void
    {
        $html = $this->parser->parse('Texte _en italique_ ici.');

        $this->assertStringContainsString('<em>en italique</em>', $html);
    }

    public function testParseBoldAndItalic(): void
    {
        $html = $this->parser->parse('Texte ***gras et italique*** ici.');

        $this->assertStringContainsString('<strong><em>gras et italique</em></strong>', $html);
    }

    // =========================================================================
    // Liens
    // =========================================================================

    public function testParseLink(): void
    {
        $html = $this->parser->parse('[Texte du lien](https://example.com)');

        $this->assertStringContainsString('<a href="https://example.com">Texte du lien</a>', $html);
    }

    public function testParseLinkWithTitle(): void
    {
        $html = $this->parser->parse('[Lien](https://example.com "Titre du lien")');

        $this->assertStringContainsString('title="Titre du lien"', $html);
    }

    public function testParseAutoLink(): void
    {
        $html = $this->parser->parse('<https://example.com>');

        $this->assertStringContainsString('<a href="https://example.com">https://example.com</a>', $html);
    }

    // =========================================================================
    // Images
    // =========================================================================

    public function testParseImage(): void
    {
        $html = $this->parser->parse('![Alt text](/images/photo.jpg)');

        $this->assertStringContainsString('<img src="/images/photo.jpg" alt="Alt text"', $html);
    }

    public function testParseImageWithTitle(): void
    {
        $html = $this->parser->parse('![Alt text](/images/photo.jpg "Titre image")');

        $this->assertStringContainsString('title="Titre image"', $html);
    }

    // =========================================================================
    // Listes
    // =========================================================================

    public function testParseUnorderedList(): void
    {
        $markdown = "- Item 1\n- Item 2\n- Item 3";
        $html = $this->parser->parse($markdown);

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<li>Item 2</li>', $html);
        $this->assertStringContainsString('<li>Item 3</li>', $html);
        $this->assertStringContainsString('</ul>', $html);
    }

    public function testParseUnorderedListWithAsterisks(): void
    {
        $markdown = "* Item 1\n* Item 2";
        $html = $this->parser->parse($markdown);

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
    }

    public function testParseOrderedList(): void
    {
        $markdown = "1. Premier\n2. Deuxième\n3. Troisième";
        $html = $this->parser->parse($markdown);

        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<li>Premier</li>', $html);
        $this->assertStringContainsString('</ol>', $html);
    }

    // =========================================================================
    // Code
    // =========================================================================

    public function testParseInlineCode(): void
    {
        $html = $this->parser->parse('Utilisez `echo` pour afficher.');

        $this->assertStringContainsString('<code>echo</code>', $html);
    }

    public function testParseCodeBlock(): void
    {
        $markdown = "```php\n<?php\necho 'Hello';\n```";
        $html = $this->parser->parse($markdown);

        $this->assertStringContainsString('<pre><code class="language-php">', $html);
        $this->assertStringContainsString('echo', $html);
        $this->assertStringContainsString('</code></pre>', $html);
    }

    public function testParseCodeBlockWithoutLanguage(): void
    {
        $markdown = "```\nsome code\n```";
        $html = $this->parser->parse($markdown);

        $this->assertStringContainsString('<pre><code>', $html);
    }

    public function testParseIndentedCodeBlock(): void
    {
        $markdown = "    <?php\n    echo 'Hello';";
        $html = $this->parser->parse($markdown);

        $this->assertStringContainsString('<pre><code>', $html);
    }

    // =========================================================================
    // Citations
    // =========================================================================

    public function testParseBlockquote(): void
    {
        $html = $this->parser->parse('> Ceci est une citation.');

        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('Ceci est une citation.', $html);
        $this->assertStringContainsString('</blockquote>', $html);
    }

    public function testParseMultilineBlockquote(): void
    {
        $markdown = "> Ligne 1\n> Ligne 2";
        $html = $this->parser->parse($markdown);

        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('Ligne 1', $html);
        $this->assertStringContainsString('Ligne 2', $html);
    }

    // =========================================================================
    // Lignes horizontales
    // =========================================================================

    public function testParseHorizontalRule(): void
    {
        $html = $this->parser->parse('---');

        $this->assertStringContainsString('<hr', $html);
    }

    public function testParseHorizontalRuleWithAsterisks(): void
    {
        $html = $this->parser->parse('***');

        $this->assertStringContainsString('<hr', $html);
    }

    // =========================================================================
    // Échappement
    // =========================================================================

    public function testEscapeHtmlEntities(): void
    {
        $html = $this->parser->parse('Utiliser <script> est dangereux.');

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testEscapeBackslash(): void
    {
        $html = $this->parser->parse('Texte \\*non italique\\*');

        $this->assertStringNotContainsString('<em>', $html);
        // Le backslash est retiré mais les astérisques sont préservées
        $this->assertStringContainsString('*non italique', $html);
    }

    // =========================================================================
    // Cas complexes
    // =========================================================================

    public function testParseComplexDocument(): void
    {
        $markdown = <<<'MARKDOWN'
# Mon Article

Ceci est un **paragraphe** avec du texte *important*.

## Liste de courses

- Pommes
- Poires
- Bananes

## Code exemple

```php
<?php
echo "Hello World";
```

> Citation importante

[Lien vers Google](https://google.com)
MARKDOWN;

        $html = $this->parser->parse($markdown);

        $this->assertStringContainsString('<h1>Mon Article</h1>', $html);
        $this->assertStringContainsString('<strong>paragraphe</strong>', $html);
        $this->assertStringContainsString('<em>important</em>', $html);
        $this->assertStringContainsString('<h2>Liste de courses</h2>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Pommes</li>', $html);
        $this->assertStringContainsString('<pre><code class="language-php">', $html);
        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('<a href="https://google.com">', $html);
    }

    public function testParseEmptyString(): void
    {
        $html = $this->parser->parse('');

        $this->assertSame('', $html);
    }

    public function testParseOnlyWhitespace(): void
    {
        $html = $this->parser->parse("   \n\n   ");

        $this->assertSame('', trim($html));
    }

    // =========================================================================
    // Extraction de métadonnées
    // =========================================================================

    public function testExtractTitle(): void
    {
        $markdown = "# Mon Titre\n\nContenu...";

        $title = $this->parser->extractTitle($markdown);

        $this->assertSame('Mon Titre', $title);
    }

    public function testExtractTitleReturnsNullIfNoHeading(): void
    {
        $markdown = "Juste du texte.";

        $title = $this->parser->extractTitle($markdown);

        $this->assertNull($title);
    }

    public function testExtractExcerpt(): void
    {
        $markdown = "# Titre\n\nCeci est le premier paragraphe qui fait office d'extrait.";

        $excerpt = $this->parser->extractExcerpt($markdown, 50);

        $this->assertStringStartsWith('Ceci est le premier', $excerpt);
        $this->assertLessThanOrEqual(53, strlen($excerpt)); // 50 + "..."
    }

    public function testExtractExcerptStripsMarkdown(): void
    {
        $markdown = "**Texte en gras** et *italique*.";

        $excerpt = $this->parser->extractExcerpt($markdown, 100);

        $this->assertStringNotContainsString('**', $excerpt);
        $this->assertStringNotContainsString('*', $excerpt);
        $this->assertStringContainsString('Texte en gras', $excerpt);
    }
}
