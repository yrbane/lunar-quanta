<?php

declare(strict_types=1);

namespace Tests\Service\Core\Debug;

use Lunar\Service\Core\Debug\DumperHtmlRenderer;
use PHPUnit\Framework\TestCase;

class DumperHtmlRendererTest extends TestCase
{
    private DumperHtmlRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new DumperHtmlRenderer();
    }

    public function testRenderStringValue(): void
    {
        $html = $this->renderer->render('Hello, World!', '/test/file.php', 42);

        $this->assertStringContainsString('Hello, World!', $html);
        $this->assertStringContainsString('/test/file.php', $html);
        $this->assertStringContainsString('42', $html);
        $this->assertStringContainsString('class="dump"', $html);
        $this->assertStringContainsString('string', $html);
    }

    public function testRenderIntegerValue(): void
    {
        $html = $this->renderer->render(123, '/test.php', 1);

        $this->assertStringContainsString('123', $html);
        $this->assertStringContainsString('int', $html);
        $this->assertStringContainsString('class="number"', $html);
    }

    public function testRenderFloatValue(): void
    {
        $html = $this->renderer->render(3.14, '/test.php', 1);

        $this->assertStringContainsString('3.14', $html);
        $this->assertStringContainsString('float', $html);
    }

    public function testRenderBooleanTrue(): void
    {
        $html = $this->renderer->render(true, '/test.php', 1);

        $this->assertStringContainsString('true', $html);
        $this->assertStringContainsString('class="bool"', $html);
    }

    public function testRenderBooleanFalse(): void
    {
        $html = $this->renderer->render(false, '/test.php', 1);

        $this->assertStringContainsString('false', $html);
    }

    public function testRenderNull(): void
    {
        $html = $this->renderer->render(null, '/test.php', 1);

        $this->assertStringContainsString('null', $html);
        $this->assertStringContainsString('class="null"', $html);
    }

    public function testRenderEmptyArray(): void
    {
        $html = $this->renderer->render([], '/test.php', 1);

        $this->assertStringContainsString('[]', $html);
        $this->assertStringContainsString('class="array"', $html);
    }

    public function testRenderArrayWithValues(): void
    {
        $html = $this->renderer->render(['foo' => 'bar', 'num' => 42], '/test.php', 1);

        $this->assertStringContainsString('foo', $html);
        $this->assertStringContainsString('bar', $html);
        $this->assertStringContainsString('num', $html);
        $this->assertStringContainsString('42', $html);
    }

    public function testRenderNestedArray(): void
    {
        $html = $this->renderer->render(['level1' => ['level2' => 'value']], '/test.php', 1);

        $this->assertStringContainsString('level1', $html);
        $this->assertStringContainsString('level2', $html);
        $this->assertStringContainsString('value', $html);
    }

    public function testRenderObject(): void
    {
        $obj = new \stdClass();
        $obj->property = 'value';

        $html = $this->renderer->render($obj, '/test.php', 1);

        $this->assertStringContainsString('stdClass', $html);
        $this->assertStringContainsString('property', $html);
        $this->assertStringContainsString('value', $html);
        $this->assertStringContainsString('class="object"', $html);
    }

    public function testRenderEscapesHtmlInString(): void
    {
        $html = $this->renderer->render('<script>alert("xss")</script>', '/test.php', 1);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderEscapesHtmlInFilePath(): void
    {
        $html = $this->renderer->render('test', '/path/<evil>/file.php', 1);

        $this->assertStringNotContainsString('<evil>', $html);
        $this->assertStringContainsString('&lt;evil&gt;', $html);
    }

    public function testRenderMaxDepthLimitsNesting(): void
    {
        $deepArray = ['l1' => ['l2' => ['l3' => ['l4' => ['l5' => 'deep']]]]];

        $html = $this->renderer->render($deepArray, '/test.php', 1);

        $this->assertStringContainsString('[…]', $html);
    }

    public function testRenderCircularReferenceObject(): void
    {
        $obj = new \stdClass();
        $obj->self = $obj;

        $html = $this->renderer->render($obj, '/test.php', 1);

        $this->assertStringContainsString('référence circulaire', $html);
    }
}
