<?php

declare(strict_types=1);

namespace Tests\Service\Core\Debug;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../src/Service/Core/Debug/helpers.php';

class HelpersTest extends TestCase
{
    public function testDumpFunctionExists(): void
    {
        $this->assertTrue(function_exists('dump'));
    }

    public function testDumpFlushFunctionExists(): void
    {
        $this->assertTrue(function_exists('dump_flush'));
    }

    public function testDumpFunctionOutputsValue(): void
    {
        ob_start();
        dump('test value');
        $output = ob_get_clean();

        $this->assertStringContainsString('test value', $output);
    }

    public function testDumpFlushInCliDoesNothing(): void
    {
        ob_start();
        dump_flush();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }
}
