<?php
declare(strict_types=1);

namespace Tests\Service\Core;

use PHPUnit\Framework\TestCase;
use App\Service\Core\Router;
use Lunar\Template\Macro\RouterInterface;
use App\Service\Core\Config\Config;
use ReflectionClass;

final class RouterImplementsInterfaceTest extends TestCase
{
    private string $tempCacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempCacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'router_cache_' . uniqid();
        if (!is_dir($this->tempCacheDir)) {
            mkdir($this->tempCacheDir, 0777, true);
        }

        // Set up Config for testing
        Config::clear();
        $configReflection = new ReflectionClass(Config::class);
        $projectRootProperty = $configReflection->getProperty('projectRoot');
        $projectRootProperty->setAccessible(true);
        $projectRootProperty->setValue(null, realpath(__DIR__ . '/../../..')); // Actual project root

        // Mock Config::get('cache.dir')
        $configProperty = $configReflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue(null, ['cache' => ['dir' => str_replace(realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR, '', $this->tempCacheDir)]]);

        // Ensure namedRoutes is clear for each test
        $routerReflection = new ReflectionClass(Router::class);
        $namedRoutesProperty = $routerReflection->getProperty('namedRoutes');
        $namedRoutesProperty->setAccessible(true);
        $namedRoutesProperty->setValue(null, []);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up temp cache directory
        if (is_dir($this->tempCacheDir)) {
            $this->deleteDirectory($this->tempCacheDir);
        }

        // Clear Config after each test
        Config::clear();
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testRouterImplementsRouterInterface(): void
    {
        $this->assertInstanceOf(RouterInterface::class, new Router());
    }
}