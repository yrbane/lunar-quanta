<?php
/**
 * Tests unitaires pour FilesystemTreeCommand.
 *
 * =============================================================================
 * FONCTIONNEMENT DE LA COMMANDE
 * =============================================================================
 *
 * Cette commande affiche l'arborescence du projet de manière visuelle :
 *
 * ```
 * lunar-quanta/
 * ├── src/
 * │   ├── Command/
 * │   ├── Controller/
 * │   └── Service/
 * ├── tests/
 * └── template/
 * ```
 *
 * OPTIONS :
 * - --depth N : Limite la profondeur d'exploration
 * - --all     : Inclut les dossiers normalement exclus (vendor, node_modules)
 *
 * =============================================================================
 * STRATÉGIE DE TEST
 * =============================================================================
 *
 * On crée une structure de fichiers temporaire pour tester l'affichage
 * sans dépendre de la structure réelle du projet.
 *
 * @package Tests\Command
 */
declare(strict_types=1);

namespace Tests\Command;

use Lunar\Cli\CommandInterface;
use Lunar\Command\FilesystemTreeCommand;
use PHPUnit\Framework\TestCase;

class FilesystemTreeCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/lunar_tree_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
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
        $command = new FilesystemTreeCommand();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testHasExecuteMethod(): void
    {
        $command = new FilesystemTreeCommand();

        $this->assertTrue(method_exists($command, 'execute'));
    }

    public function testHasGetHelpMethod(): void
    {
        $command = new FilesystemTreeCommand();

        $this->assertTrue(method_exists($command, 'getHelp'));
    }

    // =========================================================================
    // TESTS DE getHelp()
    // =========================================================================

    public function testGetHelpReturnsString(): void
    {
        $command = new FilesystemTreeCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    public function testGetHelpContainsCommandName(): void
    {
        $command = new FilesystemTreeCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('filesystem:tree', $help);
    }

    public function testGetHelpContainsOptions(): void
    {
        $command = new FilesystemTreeCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('--depth', $help);
        $this->assertStringContainsString('--all', $help);
        $this->assertStringContainsString('--help', $help);
    }

    public function testGetHelpContainsExamples(): void
    {
        $command = new FilesystemTreeCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('Exemples', $help);
    }

    // =========================================================================
    // TESTS D'ATTRIBUT #[Command]
    // =========================================================================

    public function testHasCommandAttribute(): void
    {
        $reflection = new \ReflectionClass(FilesystemTreeCommand::class);
        $attributes = $reflection->getAttributes();

        $this->assertNotEmpty($attributes);
    }

    public function testCommandAttributeHasCorrectName(): void
    {
        $reflection = new \ReflectionClass(FilesystemTreeCommand::class);
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $instance = $attr->newInstance();
                $this->assertSame('filesystem:tree', $instance->name);
                return;
            }
        }

        $this->fail('Command attribute not found');
    }

    public function testCommandAttributeHasDescription(): void
    {
        $reflection = new \ReflectionClass(FilesystemTreeCommand::class);
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $instance = $attr->newInstance();
                $this->assertNotEmpty($instance->description);
                return;
            }
        }

        $this->fail('Command attribute not found');
    }

    // =========================================================================
    // TESTS DES CONSTANTES DE FORMATAGE
    // =========================================================================

    public function testTreeSymbolsAreDefined(): void
    {
        $reflection = new \ReflectionClass(FilesystemTreeCommand::class);

        // Les constantes doivent exister pour le formatage de l'arbre
        $this->assertTrue($reflection->hasConstant('BRANCH'));
        $this->assertTrue($reflection->hasConstant('LAST_BRANCH'));
        $this->assertTrue($reflection->hasConstant('VERTICAL'));
        $this->assertTrue($reflection->hasConstant('SPACE'));
    }

    // =========================================================================
    // TESTS DE COMPORTEMENT
    // =========================================================================

    public function testExecuteReturnsZero(): void
    {
        $command = new FilesystemTreeCommand();

        ob_start();
        $result = $command->execute([]);
        ob_end_clean();

        $this->assertSame(0, $result);
    }

    public function testExecuteProducesOutput(): void
    {
        $command = new FilesystemTreeCommand();

        ob_start();
        $command->execute([]);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    // =========================================================================
    // TESTS DES EXCLUSIONS
    // =========================================================================

    public function testExcludedDirectoriesExist(): void
    {
        $reflection = new \ReflectionClass(FilesystemTreeCommand::class);
        $property = $reflection->getProperty('excludedDirs');
        $property->setAccessible(true);

        $command = new FilesystemTreeCommand();
        $excludedDirs = $property->getValue($command);

        $this->assertIsArray($excludedDirs);
        $this->assertContains('vendor', $excludedDirs);
        $this->assertContains('node_modules', $excludedDirs);
        $this->assertContains('.git', $excludedDirs);
    }
}
