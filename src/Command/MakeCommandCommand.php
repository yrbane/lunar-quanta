<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\AbstractCommand;
use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Cli\Helper\ConsoleHelper as C;
use Lunar\Config\Config;

/**
 * Commande pour générer une nouvelle commande CLI avec attributs PHP8.
 */
#[Command(
    name: 'make:command',
    description: 'Génère une nouvelle commande CLI interactive.'
)]
class MakeCommandCommand extends AbstractCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        if ($this->wantsHelp($args)) {
            C::info($this->getHelp());

            return 0;
        }

        C::title("Création d'une nouvelle commande CLI");

        $commandName = C::ask('Nom de la commande (ex: user:create)');

        // On génère un nom de classe à partir du nom de commande
        $className = ucfirst(implode('', array_map('ucfirst', explode(':', $commandName))));
        $classNameShort = ucfirst(C::ask('Nom de la classe (Command ajouté automatiquement)', $className));
        $className = $classNameShort.'Command';
        $description = C::ask('Description courte de la commande');

        // Collecte des arguments
        $arguments = [];
        while (C::confirm('Ajouter un argument ?', false)) {
            $argName = C::ask("Nom de l'argument (ex: username)");
            $argDesc = C::ask('Description de cet argument');
            $arguments[] = ['name' => $argName, 'description' => $argDesc];
        }

        // Dépendances injectées ?
        $dependencies = [];
        while (C::confirm('Ajouter une dépendance injectée ?', false)) {
            $depClass = C::ask('FQCN du service (ex: Lunar\Service\Core\Router)');
            $depVar = lcfirst(basename(str_replace('\\', '/', $depClass)));
            $dependencies[] = ['fqcn' => $depClass, 'var' => $depVar];
        }

        // Génération de la commande
        $commandPath = Config::resolvePath("src/Command/{$className}.php");
        $commandCode = $this->generateCommandClass($className, $commandName, $description, $arguments, $dependencies);
        file_put_contents($commandPath, $commandCode);
        C::success("Commande générée : src/Command/{$className}.php");

        // Génération du test unitaire
        $testDir = Config::resolvePath('tests/Command');
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        $testPath = "{$testDir}/{$className}Test.php";
        $testCode = $this->generateTestClass($className, $dependencies);
        file_put_contents($testPath, $testCode);
        C::success("Test généré : tests/Command/{$className}Test.php");

        return 0;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Génère une nouvelle commande CLI avec son test unitaire.

Usage :
  bin/console make:command

La commande vous demandera :
  - Le nom de la commande (ex: user:create)
  - Le nom de la classe
  - Une description
  - Les arguments éventuels
  - Les dépendances à injecter

Options :
  --help       Affiche cette aide
HELP;
    }

    /**
     * @param array<int, array{name: string, description: string}> $arguments
     * @param array<int, array{fqcn: string, var: string}>         $dependencies
     */
    private function generateCommandClass(
        string $className,
        string $commandName,
        string $description,
        array $arguments,
        array $dependencies
    ): string {
        // Use statements pour les dépendances
        $depUses = '';
        if (!empty($dependencies)) {
            $depUses = "\n".implode("\n", array_map(
                fn ($d) => "use {$d['fqcn']};",
                $dependencies
            ));
        }

        // Propriétés
        $depProps = '';
        if (!empty($dependencies)) {
            $depProps = "\n".implode("\n", array_map(
                fn ($d) => "    private {$this->shortClassName($d['fqcn'])} \${$d['var']};",
                $dependencies
            ))."\n";
        }

        // Constructeur (seulement si dépendances)
        $constructor = '';
        if (!empty($dependencies)) {
            $ctorParams = implode(', ', array_map(
                fn ($d) => "{$this->shortClassName($d['fqcn'])} \${$d['var']}",
                $dependencies
            ));
            $ctorBody = implode("\n", array_map(
                fn ($d) => "        \$this->{$d['var']} = \${$d['var']};",
                $dependencies
            ));
            $constructor = <<<CTOR

    public function __construct({$ctorParams})
    {
{$ctorBody}
    }

CTOR;
        }

        // Help block
        $helpBlock = $this->generateHelpBlock($commandName, $description, $arguments);

        return <<<PHP
<?php
/**
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\\Command;

use Lunar\\Cli\\Attribute\\Command;
use Lunar\\Cli\\AbstractCommand;
use Lunar\\Cli\\CommandInterface;
use Lunar\\Cli\\Helper\\ConsoleHelper as C;{$depUses}

/**
 * {$description}
 */
#[Command(
    name: '{$commandName}',
    description: '{$description}'
)]
class {$className} extends AbstractCommand implements CommandInterface
{{$depProps}{$constructor}
    public function execute(array \$args): int
    {
        if (\$this->wantsHelp(\$args)) {
            C::info(\$this->getHelp());

            return 0;
        }

        // TODO: implémenter la logique ici

        C::success('Commande exécutée avec succès !');

        return 0;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
{$helpBlock}
HELP;
    }
}

PHP;
    }

    /**
     * @param array<int, array{name: string, description: string}> $arguments
     */
    private function generateHelpBlock(string $commandName, string $description, array $arguments): string
    {
        $help = "{$description}\n\nUsage :\n  bin/console {$commandName}";

        if (!empty($arguments)) {
            $argList = implode(' ', array_map(fn ($a) => "<{$a['name']}>", $arguments));
            $help .= " {$argList}\n\nArguments :";
            foreach ($arguments as $arg) {
                $help .= "\n  <{$arg['name']}>  {$arg['description']}";
            }
        }

        $help .= "\n\nOptions :\n  --help  Affiche cette aide";

        return $help;
    }

    /**
     * @param array<int, array{fqcn: string, var: string}> $dependencies
     */
    private function generateTestClass(string $className, array $dependencies): string
    {
        $ctorArgs = empty($dependencies) ? '' : '/* TODO: mock dependencies */';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\\Command;

use PHPUnit\\Framework\\TestCase;
use Lunar\\Command\\{$className};

class {$className}Test extends TestCase
{
    public function testExecuteReturnsZero(): void
    {
        \$command = new {$className}({$ctorArgs});
        \$result = \$command->execute([]);

        \$this->assertSame(0, \$result);
    }

    public function testHelpReturnsString(): void
    {
        \$command = new {$className}({$ctorArgs});
        \$help = \$command->getHelp();

        \$this->assertIsString(\$help);
        \$this->assertNotEmpty(\$help);
    }
}

PHP;
    }

    /**
     * Extrait le nom court d'une classe depuis son FQCN.
     */
    private function shortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
