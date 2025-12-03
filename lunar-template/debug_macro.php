<?php
require_once 'vendor/autoload.php';

use Lunar\Template\AdvancedTemplateEngine;

$tempDir = sys_get_temp_dir() . '/debug-' . uniqid();
$cacheDir = sys_get_temp_dir() . '/debug-cache-' . uniqid();

mkdir($tempDir, 0755, true);

$engine = new AdvancedTemplateEngine($tempDir, $cacheDir);

// Register simple macro
$engine->registerMacro('test', function($arg) {
    echo "Macro called with: ";
    var_dump($arg);
    return "Result: " . $arg;
});

// Create template with macro
file_put_contents($tempDir . '/test.tpl', 'Output: ##test("hello")##');

try {
    $result = $engine->render('test');
    echo "Template result: " . $result . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

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