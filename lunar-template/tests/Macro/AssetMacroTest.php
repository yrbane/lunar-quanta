<?php
declare(strict_types=1);

namespace Lunar\Template\Tests\Macro;

use Lunar\Template\Macro\AssetMacro;
use PHPUnit\Framework\TestCase;

class AssetMacroTest extends TestCase
{
    private AssetMacro $macro;

    protected function setUp(): void
    {
        $this->macro = new AssetMacro('/assets');
    }

    public function testGetName(): void
    {
        $this->assertSame('asset', $this->macro->getName());
    }

    public function testExecuteWithValidPath(): void
    {
        $result = $this->macro->execute(['css/style.css']);
        $this->assertSame('/assets/css/style.css', $result);
    }

    public function testExecuteWithLeadingSlash(): void
    {
        $result = $this->macro->execute(['/css/style.css']);
        $this->assertSame('/assets/css/style.css', $result);
    }

    public function testExecuteWithEmptyPath(): void
    {
        $result = $this->macro->execute(['']);
        $this->assertSame('', $result);
    }

    public function testExecuteWithNoArguments(): void
    {
        $result = $this->macro->execute([]);
        $this->assertSame('', $result);
    }

    public function testExecuteWithMultiplePathSegments(): void
    {
        $result = $this->macro->execute(['images/icons/favicon.ico']);
        $this->assertSame('/assets/images/icons/favicon.ico', $result);
    }

    public function testConstructorWithTrailingSlash(): void
    {
        $macro = new AssetMacro('/assets/');
        $result = $macro->execute(['css/style.css']);
        $this->assertSame('/assets/css/style.css', $result);
    }

    public function testConstructorWithEmptyBaseUrl(): void
    {
        $macro = new AssetMacro('');
        $result = $macro->execute(['css/style.css']);
        $this->assertSame('/css/style.css', $result);
    }

    public function testConstructorWithDefaultBaseUrl(): void
    {
        $macro = new AssetMacro();
        $result = $macro->execute(['css/style.css']);
        $this->assertSame('/css/style.css', $result);
    }

    public function testExecuteWithQueryString(): void
    {
        $result = $this->macro->execute(['css/style.css?v=1.0']);
        $this->assertSame('/assets/css/style.css?v=1.0', $result);
    }

    public function testExecuteWithFragment(): void
    {
        $result = $this->macro->execute(['page.html#section']);
        $this->assertSame('/assets/page.html#section', $result);
    }
}