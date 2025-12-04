<?php

declare(strict_types=1);

namespace Tests\Service\Core\Template;

use Lunar\Service\Core\Template\LunarTemplateAdapter;
use PHPUnit\Framework\TestCase;

class LunarTemplateAdapterTest extends TestCase
{
    private string $templatePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templatePath = realpath(__DIR__ . '/../../../../template');
    }

    public function testConstructorCreatesEngine(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $this->assertInstanceOf(LunarTemplateAdapter::class, $adapter);
    }

    public function testGetEngineReturnsLunarEngine(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);
        $engine = $adapter->getEngine();

        $this->assertInstanceOf(\Lunar\Template\AdvancedTemplateEngine::class, $engine);
    }

    public function testTemplateExistsReturnsTrueForExistingTemplate(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $this->assertTrue($adapter->templateExists('error.html'));
    }

    public function testTemplateExistsReturnsFalseForMissingTemplate(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $this->assertFalse($adapter->templateExists('nonexistent_template_xyz.html'));
    }

    public function testRegisterMacroAddsMacro(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $adapter->registerMacro('test_macro', fn () => 'Hello from macro');

        $result = $adapter->callMacro('test_macro', []);
        $this->assertSame('Hello from macro', $result);
    }

    public function testCallMacroWithArguments(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $adapter->registerMacro('greet', fn (string $name) => "Hello, {$name}!");

        $result = $adapter->callMacro('greet', ['World']);
        $this->assertSame('Hello, World!', $result);
    }

    public function testRenderReturnsString(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $result = $adapter->render('error.html', [
            'title' => 'Test Error',
            'errorCode' => 404,
            'errorMessage' => 'Not Found',
        ]);

        $this->assertIsString($result);
        $this->assertStringContainsString('Test Error', $result);
    }

    public function testClearCacheDoesNotThrow(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $this->expectNotToPerformAssertions();
        $adapter->clearCache();
    }

    public function testConstructorWithRelativePath(): void
    {
        $adapter = new LunarTemplateAdapter('template');

        $this->assertInstanceOf(LunarTemplateAdapter::class, $adapter);
    }

    public function testClearCacheWithSpecificTemplate(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $this->expectNotToPerformAssertions();
        $adapter->clearCache('error.html');
    }

    public function testRenderVariablesAreAccessibleInTemplate(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $result = $adapter->render('error.html', [
            'title' => 'Custom Title',
            'errorCode' => 403,
            'errorMessage' => 'Forbidden Access',
        ]);

        $this->assertStringContainsString('Custom Title', $result);
        $this->assertStringContainsString('403', $result);
        $this->assertStringContainsString('Forbidden Access', $result);
    }
}
