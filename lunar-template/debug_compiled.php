<?php
require_once 'vendor/autoload.php';

use Lunar\Template\AdvancedTemplateEngine;

$tempDir = sys_get_temp_dir() . '/debug-compiled-' . uniqid();
$cacheDir = sys_get_temp_dir() . '/debug-compiled-cache-' . uniqid();

mkdir($tempDir, 0755, true);

$engine = new AdvancedTemplateEngine($tempDir, $cacheDir);

// Create template with macro
$template = 'Output: ##test("hello", "world")##';
file_put_contents($tempDir . '/test.tpl', $template);

// Compile and see generated PHP
$reflection = new ReflectionClass($engine);
$method = $reflection->getMethod('compileTemplate');
$method->setAccessible(true);

$compiled = $method->invoke($engine, $template);
echo "Compiled PHP:\n";
echo $compiled . "\n\n";

// Parse macro arguments
$parseMethod = $reflection->getMethod('parseMacroArguments');
$parseMethod->setAccessible(true);

$parsed = $parseMethod->invoke($engine, '"hello", "world"');
echo "Parsed arguments: " . $parsed . "\n";

// Clean up
function removeDirectory($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

removeDirectory($tempDir);
if (is_dir($cacheDir)) {
    removeDirectory($cacheDir);
}