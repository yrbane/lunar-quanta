<?php

declare(strict_types=1);

namespace App\Command;

use App\Attribute\Command;
use App\Service\Command\AbstractCommand;
use App\Service\Command\CommandInterface;
use App\Service\Command\ConsoleHelper as C;
use RuntimeException;

/**
 * Commande pour générer une nouvelle commande CLI avec attributs PHP8.
 */
#[Command(
    name: "make:command",
    description: "Génère une nouvelle commande CLI interactive."
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

        $commandName = C::ask("Nom de la commande (ex: user:create)");

        //On implode et on met des majuscules pour avoir un nom de classe correct
        $className = ucfirst(implode('', array_map('ucfirst', explode(':', $commandName))));
        $classNameShort = ucfirst(C::ask("Nom de la classe (Command est automatiquement ajouté à la fin...)",$className));
        $className = $classNameShort . 'Command';
        $description = C::ask("Description courte de la commande");

        // Collecte des arguments
        $arguments = [];
        while (C::confirm("Ajouter un argument ?", false)) {
            $argName = C::ask("Nom de l'argument (ex: username)");
            $argDesc = C::ask("Description de cet argument");
            $arguments[] = ['name' => $argName, 'description' => $argDesc];
        }

        // Dépendances injectées ?
        $dependencies = [];
        while (C::confirm("Ajouter une dépendance injectée dans le constructeur ?", false)) {
            $depClass = C::ask("FQCN du service (ex: App\\Service\\UserManager)");
            $depVar = lcfirst(basename(str_replace('\\', '/', $depClass)));
            $dependencies[] = ['fqcn' => $depClass, 'var' => $depVar];
        }

        // Génération de la commande
        $commandCode = $this->generateCommandClass($className, $commandName, $description, $arguments, $dependencies);
        file_put_contents(__DIR__ . "/$className.php", $commandCode);
        C::success("Commande générée : src/Command/$className.php");

        // Génération du test unitaire
        $testCode = $this->generateTestClass($classNameShort);
        $testPath = dirname(__DIR__, 2) . "/tests/Command/{$classNameShort}CommandTest.php";
        file_put_contents($testPath, $testCode);
        C::success("Test généré : tests/Command/{$classNameShort}CommandTest.php");

        return 0;
    }

    public function getHelp(): string
    {
        return <<<HELP
Cette commande interactive permet de générer une nouvelle commande CLI dans le framework.

Usage :
  bin/console make:command

Options :
  --help       Affiche cette aide
HELP;
    }

    /**
     * @param array<int, array{name: string, description: string}> $arguments
     * @param array<int, array{fqcn: string, var: string}>         $dependencies
     */
    private function generateCommandClass(string $className, string $commandName, string $description, array $arguments, array $dependencies): string
    {
        $argList = implode(' ', array_map(fn($a) => '<' . $a['name'] . '>', $arguments));
        $argsDoc = implode(PHP_EOL, array_map(fn($a) => " *   <{$a['name']}> : {$a['description']}", $arguments));

        $depProps = implode("\n", array_map(fn($d) => "    private {$d['fqcn']} \${$d['var']};", $dependencies));
        $depCtorParams = implode(', ', array_map(fn($d) => "{$d['fqcn']} \${$d['var']}", $dependencies));
        $depCtorBody = implode("\n", array_map(fn($d) => "        \$this->{$d['var']} = \${$d['var']};", $dependencies));

        $helpBlock = <<<HELP
Cette commande {$description}.

Usage :
  bin/console {$commandName} {$argList}

Arguments :
{$argsDoc}

Options :
  --help       Affiche cette aide
HELP;

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Command;

use App\Attribute\Command;
use App\Service\Command\AbstractCommand;
use App\Service\Command\CommandInterface;
use App\Service\Command\ConsoleHelper as C;

/**
 * Commande générée automatiquement.
 */
#[Command(
    name: "{$commandName}",
    description: "{$description}"
)]
class {$className} extends AbstractCommand implements CommandInterface
{
{$depProps}

    public function __construct({$depCtorParams})
    {
{$depCtorBody}
    }

    public function execute(array \$args): int
    {
        if (\$this->wantsHelp(\$args)) {
            C::info(\$this->getHelp());
            return 0;
        }

        // TODO: implémenter la logique ici

        C::success("Commande exécutée avec succès !");
        return 0;
    }

    public function getHelp(): string
    {
        return <<<HELP
{$helpBlock}
HELP;
    }
}
PHP;
    }

    private function generateTestClass(string $classNameShort): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Command;

use PHPUnit\Framework\TestCase;
use App\Command\{$classNameShort}Command;

class {$classNameShort}CommandTest extends TestCase
{
    public function testExecute(): void
    {
        \$command = new {$classNameShort}Command(/* mock dependencies ici */);
        \$result = \$command->execute([]);
        \$this->assertSame(0, \$result);
    }
}
PHP;
    }
}
