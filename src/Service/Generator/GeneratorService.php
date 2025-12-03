<?php
/**
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Generator;

use Lunar\Config\Config;

class GeneratorService
{
    public function generateController(string $name): string
    {
        $controllerName = ucfirst($name) . 'Controller';
        $controllerPath = Config::getProjectRoot() . "/src/Controller/{$controllerName}.php";
        $controllerNamespace = 'App\\Controller';

        $dir = dirname($controllerPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                return "Impossible de créer le répertoire {$dir}.";
            }
        }

        if (file_exists($controllerPath)) {
            return "Le fichier {$controllerPath} existe déjà !";
        }

        $baseName = strtolower((string) preg_replace('/[cC]ontroller$/', '', $controllerName));
        $routePath = '/' . $baseName;
        $routeName = "{$baseName}.index";

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$controllerNamespace};

use Lunar\\Service\\Core\\BaseController;
use Lunar\\Service\\Core\\Http\\Request;
use Lunar\\Service\\Core\\Http\\Response;
use Lunar\\Attribute\\Route;

/**
 * Contrôleur {$controllerName} généré automatiquement.
 */
class {$controllerName} extends BaseController
{
    /**
     * Affiche la page index.
     *
     * @param Request \$request
     *
     * @return Response
     */
    #[Route(path: '{$routePath}', methods: ['GET'], name: '{$routeName}')]
    public function index(Request \$request): Response
    {
        // TODO : implémenter la logique de l'action index
        \$html = \$this->render('{$baseName}/index', []);
        return new Response(\$html);
    }
}

PHP;

        file_put_contents($controllerPath, $content);

        return "Le contrôleur {$controllerName} a été créé dans {$controllerPath}.";
    }

    public function generateCommand(string $name): string
    {
        $commandName = ucfirst($name) . 'Command';
        $commandPath = Config::getProjectRoot() . "/src/Command/{$commandName}.php";
        $commandNamespace = 'App\\Command';

        $dir = dirname($commandPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                return "Impossible de créer le répertoire {$dir}.";
            }
        }

        if (file_exists($commandPath)) {
            return "Le fichier {$commandPath} existe déjà !";
        }

        $commandId = strtolower($name);

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$commandNamespace};

use Lunar\\Attribute\\Command;
use Lunar\\Service\\Command\\AbstractCommand;
use Lunar\\Service\\Command\\ConsoleHelper as C;

#[Command(
    name: "{$commandId}:run",
    description: "Description de la commande {$commandName}"
)]
class {$commandName} extends AbstractCommand
{
    public function execute(array \$args): int
    {
        if (\$this->wantsHelp(\$args)) {
            C::info(\$this->getHelp());
            return 0;
        }

        C::info("Exécution de la commande {$commandName}");

        return 0;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Description détaillée de la commande {$commandName}.

Usage:
  bin/console {$commandId}:run

Options:
  --help    Affiche cette aide.
HELP;
    }
}

PHP;

        file_put_contents($commandPath, $content);

        return "La commande {$commandName} a été créée dans {$commandPath}.";
    }
}
