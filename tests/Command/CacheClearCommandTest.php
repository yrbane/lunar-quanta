<?php
/**
 * Tests unitaires pour CacheClearCommand.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE COMMANDE CLI ?
 * =============================================================================
 *
 * Une commande CLI (Command Line Interface) est un script exécutable depuis
 * le terminal. Elle suit un pattern simple :
 *
 * ```
 * ENTRÉE                    TRAITEMENT                    SORTIE
 *    │                          │                            │
 *    │  ./bin/console           │                            │
 *    │  cache:clear             │    CacheClearCommand       │    0 = succès
 *    │  --verbose               │    ├── parse arguments     │    1 = erreur
 *    │                          │    ├── execute logic       │
 *    └──────────────────────────┼────┤── output messages     │
 *                               │    └── return exit code    │
 *                               │                            │
 *                               └────────────────────────────┘
 * ```
 *
 * =============================================================================
 * STRATÉGIE DE TEST
 * =============================================================================
 *
 * 1. Créer un répertoire temporaire pour simuler le cache
 * 2. Exécuter la commande
 * 3. Vérifier que les fichiers sont supprimés
 * 4. Vérifier le code de retour
 *
 * @package Tests\Command
 */
declare(strict_types=1);

namespace Tests\Command;

use Lunar\Cli\CommandInterface;
use Lunar\Command\CacheClearCommand;
use PHPUnit\Framework\TestCase;

class CacheClearCommandTest extends TestCase
{
    private string $tempDir;
    private ?string $originalCacheDir = null;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/lunar_cache_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        // Sauvegarder la config originale si elle existe
        $this->originalCacheDir = getenv('CACHE_DIR') ?: null;
    }

    protected function tearDown(): void
    {
        // Nettoyer le répertoire temporaire
        $this->deleteDirectory($this->tempDir);

        // Restaurer la config
        if ($this->originalCacheDir !== null) {
            putenv('CACHE_DIR=' . $this->originalCacheDir);
        } else {
            putenv('CACHE_DIR');
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    // =========================================================================
    // TESTS D'INTERFACE
    // =========================================================================

    public function testImplementsCommandInterface(): void
    {
        $command = new CacheClearCommand();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testHasExecuteMethod(): void
    {
        $command = new CacheClearCommand();

        $this->assertTrue(method_exists($command, 'execute'));
    }

    public function testHasGetHelpMethod(): void
    {
        $command = new CacheClearCommand();

        $this->assertTrue(method_exists($command, 'getHelp'));
    }

    // =========================================================================
    // TESTS DE getHelp()
    // =========================================================================

    public function testGetHelpReturnsString(): void
    {
        $command = new CacheClearCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    public function testGetHelpContainsCommandName(): void
    {
        $command = new CacheClearCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('cache:clear', $help);
    }

    public function testGetHelpContainsUsageInfo(): void
    {
        $command = new CacheClearCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('Utilisation', $help);
        $this->assertStringContainsString('--help', $help);
    }

    // =========================================================================
    // TESTS DE deleteDirContent()
    // =========================================================================

    public function testDeleteDirContentRemovesFiles(): void
    {
        // Créer des fichiers de test
        file_put_contents($this->tempDir . '/file1.txt', 'content1');
        file_put_contents($this->tempDir . '/file2.txt', 'content2');

        $command = new CacheClearCommand();

        ob_start();
        $command->deleteDirContent($this->tempDir);
        ob_end_clean();

        $this->assertFileDoesNotExist($this->tempDir . '/file1.txt');
        $this->assertFileDoesNotExist($this->tempDir . '/file2.txt');
    }

    public function testDeleteDirContentRemovesNestedDirectories(): void
    {
        // Créer une structure imbriquée
        mkdir($this->tempDir . '/subdir1', 0777, true);
        mkdir($this->tempDir . '/subdir2/nested', 0777, true);
        file_put_contents($this->tempDir . '/subdir1/file.txt', 'content');
        file_put_contents($this->tempDir . '/subdir2/nested/file.txt', 'content');

        $command = new CacheClearCommand();

        ob_start();
        $command->deleteDirContent($this->tempDir);
        ob_end_clean();

        $this->assertDirectoryDoesNotExist($this->tempDir . '/subdir1');
        $this->assertDirectoryDoesNotExist($this->tempDir . '/subdir2');
    }

    public function testDeleteDirContentHandlesEmptyDirectory(): void
    {
        $command = new CacheClearCommand();

        ob_start();
        $command->deleteDirContent($this->tempDir);
        $output = ob_get_clean();

        // Ne devrait pas planter sur un répertoire vide
        $this->assertDirectoryExists($this->tempDir);
    }

    public function testDeleteDirContentHandlesNonExistentDirectory(): void
    {
        $command = new CacheClearCommand();
        $nonExistentDir = $this->tempDir . '/does_not_exist';

        ob_start();
        $command->deleteDirContent($nonExistentDir);
        $output = ob_get_clean();

        // Ne devrait pas planter
        $this->assertEmpty($output);
    }

    public function testDeleteDirContentOutputsDeletedFiles(): void
    {
        file_put_contents($this->tempDir . '/test.txt', 'content');

        $command = new CacheClearCommand();

        ob_start();
        $command->deleteDirContent($this->tempDir);
        $output = ob_get_clean();

        $this->assertStringContainsString('supprimé', $output);
    }

    // =========================================================================
    // TESTS D'ATTRIBUT #[Command]
    // =========================================================================

    public function testHasCommandAttribute(): void
    {
        $reflection = new \ReflectionClass(CacheClearCommand::class);
        $attributes = $reflection->getAttributes();

        $this->assertNotEmpty($attributes);

        $commandAttribute = null;
        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $commandAttribute = $attr;
                break;
            }
        }

        $this->assertNotNull($commandAttribute);
    }

    public function testCommandAttributeHasCorrectName(): void
    {
        $reflection = new \ReflectionClass(CacheClearCommand::class);
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $instance = $attr->newInstance();
                $this->assertSame('cache:clear', $instance->name);
                return;
            }
        }

        $this->fail('Command attribute not found');
    }
}
