<?php
/**
 * Tests unitaires pour MakeControllerCommand et MakeCommandCommand.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE COMMANDE "MAKE" ?
 * =============================================================================
 *
 * Les commandes "make" sont des générateurs de code (scaffolding).
 * Elles créent automatiquement des fichiers avec une structure de base.
 *
 * ```
 * AVANT                              APRÈS
 *
 *    $ bin/console                   src/Controller/
 *      make:controller               └── ArticleController.php
 *      Article                           (fichier généré)
 *
 *
 * CONTENU GÉNÉRÉ :
 *
 *    <?php
 *    namespace App\Controller;
 *
 *    class ArticleController extends BaseController
 *    {
 *        #[Route('/article', name: 'article_index')]
 *        public function index(): Response
 *        {
 *            return $this->render('article/index.html');
 *        }
 *    }
 * ```
 *
 * =============================================================================
 * AVANTAGES DU SCAFFOLDING
 * =============================================================================
 *
 * 1. PRODUCTIVITÉ : Création rapide de nouveaux composants
 * 2. COHÉRENCE : Tous les fichiers suivent la même structure
 * 3. BONNES PRATIQUES : Le code généré respecte les conventions
 * 4. APPRENTISSAGE : Montre aux débutants comment structurer le code
 *
 * @package Tests\Command
 */
declare(strict_types=1);

namespace Tests\Command;

use Lunar\Cli\CommandInterface;
use Lunar\Command\MakeCommandCommand;
use Lunar\Command\MakeControllerCommand;
use PHPUnit\Framework\TestCase;

class MakeCommandsTest extends TestCase
{
    // =========================================================================
    // TESTS DE MakeControllerCommand
    // =========================================================================

    public function testMakeControllerImplementsCommandInterface(): void
    {
        $command = new MakeControllerCommand();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testMakeControllerGetHelpReturnsString(): void
    {
        $command = new MakeControllerCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    public function testMakeControllerGetHelpContainsUsage(): void
    {
        $command = new MakeControllerCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('make:controller', $help);
    }

    public function testMakeControllerGetHelpContainsExample(): void
    {
        $command = new MakeControllerCommand();
        $help = $command->getHelp();

        // Devrait contenir un exemple d'utilisation
        $this->assertStringContainsString('contrôleur', $help);
    }

    public function testMakeControllerHasCommandAttribute(): void
    {
        $reflection = new \ReflectionClass(MakeControllerCommand::class);
        $attributes = $reflection->getAttributes();

        $hasCommandAttr = false;
        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $hasCommandAttr = true;
                $instance = $attr->newInstance();
                $this->assertSame('make:controller', $instance->name);
            }
        }

        $this->assertTrue($hasCommandAttr);
    }

    public function testMakeControllerHasExecuteMethod(): void
    {
        $this->assertTrue(method_exists(MakeControllerCommand::class, 'execute'));
    }

    // =========================================================================
    // TESTS DE MakeCommandCommand
    // =========================================================================

    public function testMakeCommandImplementsCommandInterface(): void
    {
        $command = new MakeCommandCommand();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testMakeCommandGetHelpReturnsString(): void
    {
        $command = new MakeCommandCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    public function testMakeCommandGetHelpContainsUsage(): void
    {
        $command = new MakeCommandCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('make:command', $help);
    }

    public function testMakeCommandHasCommandAttribute(): void
    {
        $reflection = new \ReflectionClass(MakeCommandCommand::class);
        $attributes = $reflection->getAttributes();

        $hasCommandAttr = false;
        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $hasCommandAttr = true;
                $instance = $attr->newInstance();
                $this->assertSame('make:command', $instance->name);
            }
        }

        $this->assertTrue($hasCommandAttr);
    }

    public function testMakeCommandHasExecuteMethod(): void
    {
        $this->assertTrue(method_exists(MakeCommandCommand::class, 'execute'));
    }

    // =========================================================================
    // TESTS COMMUNS AUX DEUX COMMANDES
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('makeCommandsProvider')]
    public function testMakeCommandsImplementInterface(string $commandClass): void
    {
        $command = new $commandClass();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('makeCommandsProvider')]
    public function testMakeCommandsHaveGetHelpMethod(string $commandClass): void
    {
        $this->assertTrue(method_exists($commandClass, 'getHelp'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('makeCommandsProvider')]
    public function testMakeCommandsHaveExecuteMethod(string $commandClass): void
    {
        $this->assertTrue(method_exists($commandClass, 'execute'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('makeCommandsProvider')]
    public function testMakeCommandsHelpIsNotTooShort(string $commandClass): void
    {
        $command = new $commandClass();
        $help = $command->getHelp();

        // L'aide devrait être suffisamment détaillée
        $this->assertGreaterThan(50, strlen($help));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function makeCommandsProvider(): array
    {
        return [
            'MakeControllerCommand' => [MakeControllerCommand::class],
            'MakeCommandCommand' => [MakeCommandCommand::class],
        ];
    }

    // =========================================================================
    // TESTS DE RouterDebugCommand
    // =========================================================================

    public function testRouterDebugImplementsCommandInterface(): void
    {
        $command = new \Lunar\Command\RouterDebugCommand();

        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testRouterDebugGetHelpReturnsString(): void
    {
        $command = new \Lunar\Command\RouterDebugCommand();
        $help = $command->getHelp();

        $this->assertIsString($help);
        $this->assertNotEmpty($help);
    }

    public function testRouterDebugGetHelpContainsUsage(): void
    {
        $command = new \Lunar\Command\RouterDebugCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('router:debug', $help);
    }

    public function testRouterDebugHasCommandAttribute(): void
    {
        $reflection = new \ReflectionClass(\Lunar\Command\RouterDebugCommand::class);
        $attributes = $reflection->getAttributes();

        $hasCommandAttr = false;
        foreach ($attributes as $attr) {
            if (str_contains($attr->getName(), 'Command')) {
                $hasCommandAttr = true;
                $instance = $attr->newInstance();
                $this->assertSame('router:debug', $instance->name);
            }
        }

        $this->assertTrue($hasCommandAttr);
    }
}
