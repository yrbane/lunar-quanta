<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\MinificationService;
use PHPUnit\Framework\TestCase;

final class MinificationServiceTest extends TestCase
{
    private MinificationService $service;

    protected function setUp(): void
    {
        $this->service = new MinificationService();
    }

    public function testHtmlRemovesComments(): void
    {
        $html = '<div><!-- comment -->content</div>';
        $result = $this->service->html($html);

        $this->assertStringNotContainsString('<!-- comment -->', $result);
        $this->assertStringContainsString('content', $result);
    }

    public function testHtmlPreservesConditionalComments(): void
    {
        $html = '<!--[if IE]>IE only<![endif]-->';
        $result = $this->service->html($html);

        $this->assertStringContainsString('<!--[if IE]>', $result);
    }

    public function testHtmlRemovesWhitespaceBetweenTags(): void
    {
        $html = '<div>   </div>   <span>   </span>';
        $result = $this->service->html($html);

        $this->assertStringNotContainsString('>   <', $result);
        $this->assertStringContainsString('><', $result);
    }

    public function testHtmlPreservesPreContent(): void
    {
        $html = '<pre>   formatted   code   </pre>';
        $result = $this->service->html($html);

        $this->assertStringContainsString('<pre>   formatted   code   </pre>', $result);
    }

    public function testHtmlPreservesCodeContent(): void
    {
        $html = '<code>   code   </code>';
        $result = $this->service->html($html);

        $this->assertStringContainsString('<code>   code   </code>', $result);
    }

    public function testHtmlPreservesScriptContent(): void
    {
        $html = '<script>var x = 1;   var y = 2;</script>';
        $result = $this->service->html($html);

        $this->assertStringContainsString('var x = 1;   var y = 2;', $result);
    }

    public function testHtmlSimplifiesBooleanAttributes(): void
    {
        $html = '<input disabled="disabled" required="required">';
        $result = $this->service->html($html);

        $this->assertStringContainsString('disabled', $result);
        $this->assertStringNotContainsString('disabled="disabled"', $result);
    }

    public function testCssRemovesComments(): void
    {
        $css = '/* comment */ .class { color: red; }';
        $result = $this->service->css($css);

        $this->assertStringNotContainsString('/* comment */', $result);
    }

    public function testCssRemovesWhitespace(): void
    {
        $css = '.class {   color:   red;   }';
        $result = $this->service->css($css);

        $this->assertStringNotContainsString('   ', $result);
        $this->assertStringContainsString('.class{color:red}', $result);
    }

    public function testCssRemovesLastSemicolon(): void
    {
        $css = '.class { color: red; }';
        $result = $this->service->css($css);

        $this->assertStringNotContainsString(';}', $result);
        $this->assertStringContainsString('}', $result);
    }

    public function testCssShortensZeroUnits(): void
    {
        $css = '.class { margin: 0px; padding: 0em; }';
        $result = $this->service->css($css);

        $this->assertStringNotContainsString('0px', $result);
        $this->assertStringNotContainsString('0em', $result);
    }

    public function testCssRemovesLeadingZero(): void
    {
        $css = '.class { opacity: 0.5; }';
        $result = $this->service->css($css);

        $this->assertStringContainsString('.5', $result);
        $this->assertStringNotContainsString('0.5', $result);
    }

    public function testCssShortensHexColors(): void
    {
        $css = '.class { color: #ff0000; }';
        $result = $this->service->css($css);

        $this->assertStringContainsString('#f00', $result);
    }

    public function testJsRemovesSingleLineComments(): void
    {
        $js = "var x = 1; // comment\nvar y = 2;";
        $result = $this->service->js($js);

        $this->assertStringNotContainsString('// comment', $result);
    }

    public function testJsRemovesMultiLineComments(): void
    {
        $js = 'var x = 1; /* comment */ var y = 2;';
        $result = $this->service->js($js);

        $this->assertStringNotContainsString('/* comment */', $result);
    }

    public function testJsPreservesStrings(): void
    {
        $js = 'var x = "hello   world";';
        $result = $this->service->js($js);

        $this->assertStringContainsString('"hello   world"', $result);
    }

    public function testJsPreservesSingleQuoteStrings(): void
    {
        $js = "var x = 'hello   world';";
        $result = $this->service->js($js);

        $this->assertStringContainsString("'hello   world'", $result);
    }

    public function testJsPreservesTemplateLiterals(): void
    {
        $js = 'var x = `hello   ${name}`;';
        $result = $this->service->js($js);

        $this->assertStringContainsString('`hello   ${name}`', $result);
    }

    public function testJsPreservesRegex(): void
    {
        $js = 'var x = /hello   world/gi;';
        $result = $this->service->js($js);

        $this->assertStringContainsString('/hello   world/gi', $result);
    }

    public function testJsRemovesWhitespace(): void
    {
        $js = 'var   x   =   1;';
        $result = $this->service->js($js);

        $this->assertStringNotContainsString('   ', $result);
    }

    public function testJsPreservesKeywordSpacing(): void
    {
        $js = 'return value;';
        $result = $this->service->js($js);

        $this->assertStringContainsString('return ', $result);
    }

    public function testJsonMinifies(): void
    {
        $json = '{
            "name": "test",
            "value": 123
        }';
        $result = $this->service->json($json);

        $this->assertSame('{"name":"test","value":123}', $result);
    }

    public function testJsonReturnsOriginalOnInvalid(): void
    {
        $invalid = '{invalid json}';
        $result = $this->service->json($invalid);

        $this->assertSame($invalid, $result);
    }

    public function testFileDetectsType(): void
    {
        $html = '<div>   content   </div>';
        $result = $this->service->file($html, 'html');

        $this->assertStringNotContainsString('   ', $result);
    }

    public function testFileReturnsOriginalForUnknownType(): void
    {
        $content = 'some content';
        $result = $this->service->file($content, 'unknown');

        $this->assertSame($content, $result);
    }

    public function testGetCompressionRatioCalculatesCorrectly(): void
    {
        $original = str_repeat(' ', 100);
        $minified = str_repeat(' ', 50);

        $ratio = $this->service->getCompressionRatio($original, $minified);

        $this->assertSame(50.0, $ratio);
    }

    public function testGetCompressionRatioHandlesEmpty(): void
    {
        $ratio = $this->service->getCompressionRatio('', '');

        $this->assertSame(0.0, $ratio);
    }

    public function testMinifyWithStatsReturnsAllData(): void
    {
        $content = '.class {   color: red;   }';
        $stats = $this->service->minifyWithStats($content, 'css');

        $this->assertArrayHasKey('minified', $stats);
        $this->assertArrayHasKey('original_size', $stats);
        $this->assertArrayHasKey('minified_size', $stats);
        $this->assertArrayHasKey('savings', $stats);
        $this->assertLessThan($stats['original_size'], $stats['minified_size']);
    }

    public function testMinifyFileReturnsNullForMissingFile(): void
    {
        $result = $this->service->minifyFile('/nonexistent/file.css');

        $this->assertNull($result);
    }

    public function testMinifyFileWorksWithRealFile(): void
    {
        $tempFile = sys_get_temp_dir() . '/test.css';
        file_put_contents($tempFile, '.class {   color: red;   }');

        $result = $this->service->minifyFile($tempFile);

        $this->assertNotNull($result);
        $this->assertStringContainsString('.class{color:red}', $result);

        unlink($tempFile);
    }

    public function testMinifyAndSaveCreatesMinFile(): void
    {
        $tempDir = sys_get_temp_dir();
        $sourceFile = $tempDir . '/test.css';
        file_put_contents($sourceFile, '.class {   color: red;   }');

        $result = $this->service->minifyAndSave($sourceFile);
        $minFile = $tempDir . '/test.min.css';

        $this->assertTrue($result);
        $this->assertFileExists($minFile);

        $content = file_get_contents($minFile);
        $this->assertStringContainsString('.class{color:red}', $content);

        unlink($sourceFile);
        unlink($minFile);
    }

    public function testMinifyAndSaveWithCustomDest(): void
    {
        $tempDir = sys_get_temp_dir();
        $sourceFile = $tempDir . '/source.css';
        $destFile = $tempDir . '/dest.css';
        file_put_contents($sourceFile, '.class { color: red; }');

        $result = $this->service->minifyAndSave($sourceFile, $destFile);

        $this->assertTrue($result);
        $this->assertFileExists($destFile);

        unlink($sourceFile);
        unlink($destFile);
    }

    public function testSetRemoveCommentsDisablesCommentRemoval(): void
    {
        $css = '/* comment */ .class { color: red; }';
        $result = $this->service->setRemoveComments(false)->css($css);

        $this->assertStringContainsString('/* comment */', $result);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setRemoveComments(true)
            ->setRemoveWhitespace(true)
            ->setPreserveLineBreaks(false)
            ->setCollapseInlineStyles(true);

        $this->assertSame($this->service, $result);
    }
}
