<?php
declare(strict_types=1);

namespace Lunar\Template\Tests\Exception;

use Lunar\Template\Exception\TemplateException;
use PHPUnit\Framework\TestCase;

class TemplateExceptionTest extends TestCase
{
    public function testTemplateNotFound(): void
    {
        $templatePath = '/path/to/template.tpl';
        $exception = TemplateException::templateNotFound($templatePath);
        
        $this->assertInstanceOf(TemplateException::class, $exception);
        $this->assertSame("Template not found: {$templatePath}", $exception->getMessage());
    }

    public function testUnableToReadTemplate(): void
    {
        $templatePath = '/path/to/template.tpl';
        $exception = TemplateException::unableToReadTemplate($templatePath);
        
        $this->assertInstanceOf(TemplateException::class, $exception);
        $this->assertSame("Unable to read template: {$templatePath}", $exception->getMessage());
    }

    public function testUnableToCreateCacheDirectory(): void
    {
        $path = '/path/to/cache';
        $exception = TemplateException::unableToCreateCacheDirectory($path);
        
        $this->assertInstanceOf(TemplateException::class, $exception);
        $this->assertSame("Unable to create cache directory: {$path}", $exception->getMessage());
    }

    public function testDirectoryNotReadable(): void
    {
        $path = '/path/to/directory';
        $exception = TemplateException::directoryNotReadable($path);
        
        $this->assertInstanceOf(TemplateException::class, $exception);
        $this->assertSame("Directory is not readable: {$path}", $exception->getMessage());
    }

    public function testDirectoryNotWritable(): void
    {
        $path = '/path/to/directory';
        $exception = TemplateException::directoryNotWritable($path);
        
        $this->assertInstanceOf(TemplateException::class, $exception);
        $this->assertSame("Directory is not writable: {$path}", $exception->getMessage());
    }

    public function testMacroNotFound(): void
    {
        $macroName = 'undefinedMacro';
        $exception = TemplateException::macroNotFound($macroName);
        
        $this->assertInstanceOf(TemplateException::class, $exception);
        $this->assertSame("Macro '{$macroName}' is not defined", $exception->getMessage());
    }

    public function testParentTemplateNotFound(): void
    {
        $parentPath = '/path/to/parent.tpl';
        $exception = TemplateException::parentTemplateNotFound($parentPath);
        
        $this->assertInstanceOf(TemplateException::class, $exception);
        $this->assertSame("Parent template not found: {$parentPath}", $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $exception = TemplateException::templateNotFound('test.tpl');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(TemplateException::class);
        $this->expectExceptionMessage('Template not found: test.tpl');
        
        throw TemplateException::templateNotFound('test.tpl');
    }

    public function testExceptionCanBeCaught(): void
    {
        $caught = false;
        
        try {
            throw TemplateException::macroNotFound('testMacro');
        } catch (TemplateException $e) {
            $caught = true;
            $this->assertSame("Macro 'testMacro' is not defined", $e->getMessage());
        }
        
        $this->assertTrue($caught, 'Exception should have been caught');
    }

    public function testMultipleExceptionTypes(): void
    {
        $exceptions = [
            TemplateException::templateNotFound('template.tpl'),
            TemplateException::unableToReadTemplate('template.tpl'),
            TemplateException::unableToCreateCacheDirectory('/cache'),
            TemplateException::directoryNotReadable('/templates'),
            TemplateException::directoryNotWritable('/cache'),
            TemplateException::macroNotFound('macro'),
            TemplateException::parentTemplateNotFound('parent.tpl')
        ];
        
        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(TemplateException::class, $exception);
            $this->assertNotEmpty($exception->getMessage());
        }
    }

    public function testExceptionMessagesAreUnique(): void
    {
        $exceptions = [
            TemplateException::templateNotFound('test.tpl'),
            TemplateException::unableToReadTemplate('test.tpl'),
            TemplateException::unableToCreateCacheDirectory('test'),
            TemplateException::directoryNotReadable('test'),
            TemplateException::directoryNotWritable('test'),
            TemplateException::macroNotFound('test'),
            TemplateException::parentTemplateNotFound('test.tpl')
        ];
        
        $messages = array_map(fn($e) => $e->getMessage(), $exceptions);
        $uniqueMessages = array_unique($messages);
        
        $this->assertCount(count($messages), $uniqueMessages, 'All exception messages should be unique');
    }
}