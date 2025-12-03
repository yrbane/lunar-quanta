<?php
declare(strict_types=1);

namespace Lunar\Template\Tests;

use Lunar\Template\AdvancedTemplateEngine;
use Lunar\Template\Exception\TemplateException;
use Lunar\Template\Macro\MacroInterface;
use PHPUnit\Framework\TestCase;

class AdvancedTemplateEngineTest extends TestCase
{
    private string $templatesDir;
    private string $cacheDir;
    private AdvancedTemplateEngine $engine;

    protected function setUp(): void
    {
        $this->templatesDir = sys_get_temp_dir() . '/lunar-template-tests-' . uniqid();
        $this->cacheDir = sys_get_temp_dir() . '/lunar-cache-tests-' . uniqid();
        
        mkdir($this->templatesDir, 0755, true);
        
        $this->engine = new AdvancedTemplateEngine($this->templatesDir, $this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->templatesDir);
        if (is_dir($this->cacheDir)) {
            $this->removeDirectory($this->cacheDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createTemplate(string $name, string $content): void
    {
        $dir = dirname($this->templatesDir . '/' . $name);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->templatesDir . '/' . $name . '.tpl', $content);
    }

    public function testConstructorCreatesDirectories(): void
    {
        $this->assertTrue(is_dir($this->templatesDir));
        $this->assertTrue(is_dir($this->cacheDir));
    }

    public function testConstructorThrowsExceptionForNonExistentTemplateDir(): void
    {
        $this->expectException(TemplateException::class);
        $this->expectExceptionMessage('does not exist');
        
        new AdvancedTemplateEngine('/non/existent/path', $this->cacheDir);
    }

    public function testConstructorThrowsExceptionForUnreadableTemplateDir(): void
    {
        $unreadableDir = sys_get_temp_dir() . '/unreadable-' . uniqid();
        mkdir($unreadableDir, 0000);
        
        $this->expectException(TemplateException::class);
        $this->expectExceptionMessage('is not readable');
        
        try {
            new AdvancedTemplateEngine($unreadableDir, $this->cacheDir);
        } finally {
            chmod($unreadableDir, 0755);
            rmdir($unreadableDir);
        }
    }

    public function testRenderSimpleTemplate(): void
    {
        $this->createTemplate('simple', 'Hello [[ name ]]!');
        
        $result = $this->engine->render('simple', ['name' => 'World']);
        
        $this->assertSame('Hello World!', $result);
    }

    public function testRenderTemplateWithVariableDefaults(): void
    {
        $this->createTemplate('with-defaults', 'Title: [[ title ]] - Lang: [[ lang ]]');
        
        $result = $this->engine->render('with-defaults', ['title' => 'My Page']);
        
        $this->assertStringContainsString('Title: My Page', $result);
        $this->assertStringContainsString('Lang: en', $result);
    }

    public function testRenderThrowsExceptionForNonExistentTemplate(): void
    {
        $this->expectException(TemplateException::class);
        $this->expectExceptionMessage('Template not found');
        
        $this->engine->render('nonexistent');
    }

    public function testRenderWithConditionals(): void
    {
        $template = '[% if showGreeting %]Hello [[ name ]]![% endif %]';
        $this->createTemplate('conditionals', $template);
        
        $result = $this->engine->render('conditionals', [
            'showGreeting' => true,
            'name' => 'World'
        ]);
        
        $this->assertSame('Hello World!', $result);
    }

    public function testRenderWithElseIfAndElse(): void
    {
        $template = '[% if type == "admin" %]Admin[% elseif type == "user" %]User[% else %]Guest[% endif %]';
        $this->createTemplate('elseif', $template);
        
        $resultAdmin = $this->engine->render('elseif', ['type' => 'admin']);
        $resultUser = $this->engine->render('elseif', ['type' => 'user']);
        $resultGuest = $this->engine->render('elseif', ['type' => 'other']);
        
        $this->assertSame('Admin', $resultAdmin);
        $this->assertSame('User', $resultUser);
        $this->assertSame('Guest', $resultGuest);
    }

    public function testRenderWithLoops(): void
    {
        $template = '[% for item in items %][[ item ]], [% endfor %]';
        $this->createTemplate('loops', $template);
        
        $result = $this->engine->render('loops', ['items' => ['A', 'B', 'C']]);
        
        $this->assertSame('A, B, C, ', $result);
    }

    public function testRenderWithTemplateInheritance(): void
    {
        // Parent template
        $parentTemplate = <<<'TPL'
<html>
<head>
    <title>[% block title %]Default Title[% endblock %]</title>
</head>
<body>
    [% block content %]Default content[% endblock %]
</body>
</html>
TPL;
        $this->createTemplate('base', $parentTemplate);

        // Child template
        $childTemplate = <<<'TPL'
[% extends 'base.tpl' %]

[% block title %]My Page[% endblock %]

[% block content %]
    <h1>Welcome!</h1>
    <p>This is my page content.</p>
[% endblock %]
TPL;
        $this->createTemplate('child', $childTemplate);

        $result = $this->engine->render('child');
        
        $this->assertStringContainsString('<title>My Page</title>', $result);
        $this->assertStringContainsString('<h1>Welcome!</h1>', $result);
        $this->assertStringContainsString('<p>This is my page content.</p>', $result);
    }

    public function testRenderThrowsExceptionForNonExistentParentTemplate(): void
    {
        $childTemplate = '[% extends "nonexistent.tpl" %]';
        $this->createTemplate('child', $childTemplate);
        
        $this->expectException(TemplateException::class);
        $this->expectExceptionMessage('Parent template not found');
        
        $this->engine->render('child');
    }

    public function testRenderWithMacros(): void
    {
        $this->engine->registerMacro('greet', function($name) {
            return "Hello, {$name}!";
        });
        
        $template = '##greet("World")##';
        $this->createTemplate('macros', $template);
        
        $result = $this->engine->render('macros');
        
        $this->assertSame('Hello, World!', $result);
    }

    public function testRegisterMacroInstance(): void
    {
        $macro = new class implements MacroInterface {
            public function getName(): string
            {
                return 'test';
            }
            
            public function execute(array $args): string
            {
                return 'Test: ' . ($args[0] ?? 'default');
            }
        };
        
        $this->engine->registerMacroInstance($macro);
        
        $template = '##test("value")##';
        $this->createTemplate('macro-instance', $template);
        
        $result = $this->engine->render('macro-instance');
        
        $this->assertSame('Test: value', $result);
    }

    public function testCallMacroThrowsExceptionForUndefinedMacro(): void
    {
        $this->expectException(TemplateException::class);
        $this->expectExceptionMessage('is not defined');
        
        $this->engine->callMacro('undefined', []);
    }

    public function testTemplateExists(): void
    {
        $this->createTemplate('exists', 'content');
        
        $this->assertTrue($this->engine->templateExists('exists'));
        $this->assertFalse($this->engine->templateExists('nonexistent'));
    }

    public function testClearCache(): void
    {
        $this->createTemplate('cacheable', 'Hello World');
        
        // Render to create cache
        $this->engine->render('cacheable');
        
        // Verify cache file exists
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertCount(1, $cacheFiles);
        
        // Clear cache
        $this->engine->clearCache();
        
        // Verify cache is cleared
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertCount(0, $cacheFiles);
    }

    public function testClearSpecificTemplateCache(): void
    {
        $this->createTemplate('template1', 'Template 1');
        $this->createTemplate('template2', 'Template 2');
        
        // Render both to create cache
        $this->engine->render('template1');
        $this->engine->render('template2');
        
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertCount(2, $cacheFiles);
        
        // Clear specific template cache
        $this->engine->clearCache('template1');
        
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertCount(1, $cacheFiles);
    }

    public function testGetRegisteredMacros(): void
    {
        $this->engine->registerMacro('test1', function() { return 'test1'; });
        $this->engine->registerMacro('test2', function() { return 'test2'; });
        
        $macros = $this->engine->getRegisteredMacros();
        
        $this->assertCount(2, $macros);
        $this->assertArrayHasKey('test1', $macros);
        $this->assertArrayHasKey('test2', $macros);
    }

    public function testLoadMacrosFromDirectory(): void
    {
        // Create a temporary macro directory
        $macroDir = sys_get_temp_dir() . '/macros-' . uniqid();
        mkdir($macroDir, 0755, true);
        
        // Create a test macro class
        $macroCode = <<<'PHP'
<?php
namespace TestMacros;
use Lunar\Template\Macro\MacroInterface;

class TestMacro implements MacroInterface
{
    public function getName(): string
    {
        return 'test_directory';
    }
    
    public function execute(array $args): string
    {
        return 'Directory macro: ' . ($args[0] ?? 'default');
    }
}
PHP;
        file_put_contents($macroDir . '/TestMacro.php', $macroCode);
        
        try {
            $this->engine->loadMacrosFromDirectory('TestMacros', $macroDir);
            
            $macros = $this->engine->getRegisteredMacros();
            $this->assertArrayHasKey('test_directory', $macros);
            
            $result = $this->engine->callMacro('test_directory', ['value']);
            $this->assertSame('Directory macro: value', $result);
        } finally {
            unlink($macroDir . '/TestMacro.php');
            rmdir($macroDir);
        }
    }

    public function testLoadMacrosFromNonExistentDirectory(): void
    {
        // Should not throw exception, just silently do nothing
        $this->engine->loadMacrosFromDirectory('NonExistent', '/non/existent/path');
        
        $macros = $this->engine->getRegisteredMacros();
        $this->assertEmpty($macros);
    }

    public function testCacheInvalidationOnTemplateChange(): void
    {
        $this->createTemplate('changeable', 'Version 1');
        
        // First render
        $result1 = $this->engine->render('changeable');
        $this->assertSame('Version 1', $result1);
        
        // Simulate file modification time change
        sleep(1);
        $this->createTemplate('changeable', 'Version 2');
        
        // Second render should use new content
        $result2 = $this->engine->render('changeable');
        $this->assertSame('Version 2', $result2);
    }

    public function testXssProtection(): void
    {
        $this->createTemplate('xss', 'Value: [[ value ]]');
        
        $result = $this->engine->render('xss', [
            'value' => '<script>alert("xss")</script>'
        ]);
        
        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringContainsString('&lt;/script&gt;', $result);
    }

    public function testComplexNestedStructures(): void
    {
        $template = <<<'TPL'
[% if users %]
<ul>
[% for user in users %]
    <li>
        [[ user.name ]]
        [% if user.admin %] (Admin)[% endif %]
        [% if user.posts %]
        <ul>
        [% for post in user.posts %]
            <li>[[ post.title ]]</li>
        [% endfor %]
        </ul>
        [% endif %]
    </li>
[% endfor %]
</ul>
[% else %]
<p>No users found</p>
[% endif %]
TPL;
        
        $this->createTemplate('complex', $template);
        
        $data = [
            'users' => [
                [
                    'name' => 'John',
                    'admin' => true,
                    'posts' => [
                        ['title' => 'Post 1'],
                        ['title' => 'Post 2']
                    ]
                ],
                [
                    'name' => 'Jane',
                    'admin' => false,
                    'posts' => []
                ]
            ]
        ];
        
        $result = $this->engine->render('complex', $data);
        
        $this->assertStringContainsString('John (Admin)', $result);
        $this->assertStringContainsString('Jane', $result);
        $this->assertStringNotContainsString('Jane (Admin)', $result);
        $this->assertStringContainsString('Post 1', $result);
        $this->assertStringContainsString('Post 2', $result);
    }

    public function testPathNormalization(): void
    {
        // Test with backslashes
        $engineWindows = new AdvancedTemplateEngine(
            str_replace('/', '\\', $this->templatesDir),
            str_replace('/', '\\', $this->cacheDir . '_normalized')
        );
        
        $this->createTemplate('normalized', 'Normalized path test');
        
        $result = $engineWindows->render('normalized');
        $this->assertSame('Normalized path test', $result);
        
        // Cleanup
        $this->removeDirectory($this->cacheDir . '_normalized');
    }

    public function testVariableWithDollarPrefix(): void
    {
        $this->createTemplate('dollar', 'Hello [[ $name ]]!');
        
        $result = $this->engine->render('dollar', ['name' => 'World']);
        
        $this->assertSame('Hello World!', $result);
    }

    public function testEmptyVariableExpression(): void
    {
        $this->createTemplate('empty', 'Value: [[  ]]');
        
        $result = $this->engine->render('empty');
        
        // Should handle empty expression gracefully
        $this->assertStringContainsString('Value:', $result);
    }

    public function testBlockInheritanceWithoutOverride(): void
    {
        $parentTemplate = <<<'TPL'
<html>
<body>
    [% block content %]Default content[% endblock %]
    [% block sidebar %]Default sidebar[% endblock %]
</body>
</html>
TPL;
        $this->createTemplate('parent', $parentTemplate);

        $childTemplate = <<<'TPL'
[% extends 'parent.tpl' %]

[% block content %]New content[% endblock %]
TPL;
        $this->createTemplate('child_partial', $childTemplate);

        $result = $this->engine->render('child_partial');
        
        $this->assertStringContainsString('New content', $result);
        $this->assertStringContainsString('Default sidebar', $result);
    }
}