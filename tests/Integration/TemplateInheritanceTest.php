<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lunar\Service\Core\Template\LunarTemplateAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for template inheritance and advanced features.
 */
class TemplateInheritanceTest extends TestCase
{
    private string $templatePath;
    private string $cachePath;
    private LunarTemplateAdapter $adapter;

    protected function setUp(): void
    {
        $this->templatePath = dirname(__DIR__, 2) . '/template';
        $this->cachePath = dirname(__DIR__, 2) . '/cache/template';

        // Ensure cache directory exists
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }

        $this->adapter = new LunarTemplateAdapter($this->templatePath);
    }

    protected function tearDown(): void
    {
        // Clean up test templates
        $testFiles = glob($this->templatePath . '/test_*.html.tpl');
        if ($testFiles) {
            foreach ($testFiles as $file) {
                @unlink($file);
            }
        }
    }

    public function testTemplateAdapterCanBeInstantiated(): void
    {
        $this->assertInstanceOf(LunarTemplateAdapter::class, $this->adapter);
    }

    public function testTemplateExistsMethod(): void
    {
        // base.html.tpl should exist
        $this->assertTrue($this->adapter->templateExists('base.html'));
    }

    public function testRenderSimpleTemplate(): void
    {
        // Create a simple test template using Lunar template syntax
        $templateContent = '<p>Hello [[ name ]]</p>';
        file_put_contents($this->templatePath . '/test_simple.html.tpl', $templateContent);

        $output = $this->adapter->render('test_simple.html', ['name' => 'World']);

        $this->assertStringContainsString('Hello', $output);
        $this->assertStringContainsString('World', $output);
    }

    public function testRenderWithVariableEscaping(): void
    {
        $templateContent = '<p>[[ content ]]</p>';
        file_put_contents($this->templatePath . '/test_escape.html.tpl', $templateContent);

        $output = $this->adapter->render('test_escape.html', ['content' => '<script>alert("xss")</script>']);

        // Should be escaped
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testRenderWithConditional(): void
    {
        $templateContent = '[% if show %]Visible[% endif %]';
        file_put_contents($this->templatePath . '/test_if.html.tpl', $templateContent);

        $outputTrue = $this->adapter->render('test_if.html', ['show' => true]);
        $this->assertStringContainsString('Visible', $outputTrue);

        $outputFalse = $this->adapter->render('test_if.html', ['show' => false]);
        $this->assertStringNotContainsString('Visible', $outputFalse);
    }

    public function testRenderWithLoop(): void
    {
        $templateContent = '[% for item in items %][[ item ]][% endfor %]';
        file_put_contents($this->templatePath . '/test_loop.html.tpl', $templateContent);

        $output = $this->adapter->render('test_loop.html', ['items' => ['A', 'B', 'C']]);

        $this->assertStringContainsString('A', $output);
        $this->assertStringContainsString('B', $output);
        $this->assertStringContainsString('C', $output);
    }

    public function testClearCache(): void
    {
        // Create and render a template to generate cache
        $templateContent = '<p>Cache test</p>';
        file_put_contents($this->templatePath . '/test_cache.html.tpl', $templateContent);

        $this->adapter->render('test_cache.html', []);

        // Clear cache should not throw
        $this->adapter->clearCache('test_cache.html');

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testGetEngineReturnsInstance(): void
    {
        $engine = $this->adapter->getEngine();

        $this->assertNotNull($engine);
    }

    public function testTemplateNotExistsReturnsFalse(): void
    {
        $this->assertFalse($this->adapter->templateExists('nonexistent_template_xyz.html'));
    }

    public function testRenderWithEmptyVariables(): void
    {
        $templateContent = '<p>Static content</p>';
        file_put_contents($this->templatePath . '/test_static.html.tpl', $templateContent);

        $output = $this->adapter->render('test_static.html', []);

        $this->assertStringContainsString('Static content', $output);
    }
}
