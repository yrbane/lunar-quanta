<?php

declare(strict_types=1);

namespace Tests\Service\Core\Debug;

use Lunar\Service\Core\Debug\Dumper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DumperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $reflection = new ReflectionClass(Dumper::class);
        $bufferProp = $reflection->getProperty('htmlBuffer');
        $bufferProp->setAccessible(true);
        $bufferProp->setValue(null, []);

        $shutdownProp = $reflection->getProperty('shutdownRegistered');
        $shutdownProp->setAccessible(true);
        $shutdownProp->setValue(null, false);
    }

    public function testDumpStringInCliMode(): void
    {
        ob_start();
        Dumper::dump('test string');
        $output = ob_get_clean();

        $this->assertStringContainsString('test string', $output);
    }

    public function testDumpIntegerInCliMode(): void
    {
        ob_start();
        Dumper::dump(42);
        $output = ob_get_clean();

        $this->assertStringContainsString('42', $output);
    }

    public function testDumpFloatInCliMode(): void
    {
        ob_start();
        Dumper::dump(3.14);
        $output = ob_get_clean();

        $this->assertStringContainsString('3.14', $output);
    }

    public function testDumpBooleanTrueInCliMode(): void
    {
        ob_start();
        Dumper::dump(true);
        $output = ob_get_clean();

        $this->assertStringContainsString('true', $output);
    }

    public function testDumpBooleanFalseInCliMode(): void
    {
        ob_start();
        Dumper::dump(false);
        $output = ob_get_clean();

        $this->assertStringContainsString('false', $output);
    }

    public function testDumpNullInCliMode(): void
    {
        ob_start();
        Dumper::dump(null);
        $output = ob_get_clean();

        $this->assertStringContainsString('null', $output);
    }

    public function testDumpArrayInCliMode(): void
    {
        ob_start();
        Dumper::dump(['foo' => 'bar']);
        $output = ob_get_clean();

        $this->assertStringContainsString('foo', $output);
        $this->assertStringContainsString('bar', $output);
    }

    public function testDumpEmptyArrayInCliMode(): void
    {
        ob_start();
        Dumper::dump([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('[]', $output);
    }

    public function testDumpObjectInCliMode(): void
    {
        $obj = new \stdClass();
        $obj->name = 'test';

        ob_start();
        Dumper::dump($obj);
        $output = ob_get_clean();

        $this->assertStringContainsString('stdClass', $output);
        $this->assertStringContainsString('name', $output);
    }

    public function testDumpMultipleValuesInCliMode(): void
    {
        ob_start();
        Dumper::dump('first', 'second', 42);
        $output = ob_get_clean();

        $this->assertStringContainsString('first', $output);
        $this->assertStringContainsString('second', $output);
        $this->assertStringContainsString('42', $output);
    }

    public function testDumpDeepNestedArrayInCliMode(): void
    {
        $deep = ['l1' => ['l2' => ['l3' => ['l4' => ['l5' => 'deep']]]]];

        ob_start();
        Dumper::dump($deep);
        $output = ob_get_clean();

        $this->assertStringContainsString('l1', $output);
    }

    public function testDumpCircularReferenceInCliMode(): void
    {
        $obj = new \stdClass();
        $obj->self = $obj;

        ob_start();
        Dumper::dump($obj);
        $output = ob_get_clean();

        $this->assertStringContainsString('référence circulaire', $output);
    }

    public function testFlushInCliModeDoesNothing(): void
    {
        ob_start();
        Dumper::flush();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testDumpResourceInCliMode(): void
    {
        $resource = fopen('php://memory', 'r');

        ob_start();
        Dumper::dump($resource);
        $output = ob_get_clean();

        fclose($resource);

        $this->assertStringContainsString('resource', $output);
    }

    public function testDumpClosedResourceInCliMode(): void
    {
        $resource = fopen('php://memory', 'r');
        fclose($resource);

        ob_start();
        Dumper::dump($resource);
        $output = ob_get_clean();

        $this->assertStringContainsString('resource', $output);
    }

    public function testDumpObjectWithMultipleProperties(): void
    {
        $obj = new \stdClass();
        $obj->name = 'Test';
        $obj->value = 42;
        $obj->active = true;

        ob_start();
        Dumper::dump($obj);
        $output = ob_get_clean();

        $this->assertStringContainsString('name', $output);
        $this->assertStringContainsString('value', $output);
        $this->assertStringContainsString('active', $output);
    }

    public function testDumpNestedObject(): void
    {
        $inner = new \stdClass();
        $inner->prop = 'inner value';

        $outer = new \stdClass();
        $outer->child = $inner;

        ob_start();
        Dumper::dump($outer);
        $output = ob_get_clean();

        $this->assertStringContainsString('child', $output);
    }
}
