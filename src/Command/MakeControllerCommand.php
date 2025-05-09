<?php

declare(strict_types=1);

namespace App\Command;

use App\Attribute\Command;
use App\Attribute\Route;
use App\Service\Command\AbstractCommand;
use App\Service\Command\CommandInterface;
use App\Service\Command\ConsoleHelper as C;

/**
 * Commande générée automatiquement.
 */
#[Command(
    name: "make:controller",
    description: "Permet de créer un nouveau contrôleur."
)]
class MakeControllerCommand extends AbstractCommand
{
    public function __construct()
    {
        // Ici, on pourrait injecter des services si besoin
    }

    /**
     * Exécuté quand on lance `bin/console make:controller`.
     *
     * @param string[] $args Arguments de la ligne de commande
     *
     * @return int Code de sortie (0 = succès, >0 = erreur)
     */
    public function execute(array $args): int
    {
        // --help
        if ($this->wantsHelp($args)) {
            C::info($this->getHelp());
            return 0;
        }

        // Demande à l'utilisateur le nom du contrôleur (sans le suffixe "Controller")
        $controllerName = C::ask("Nom du contrôleur (ex : User)");
        $controllerClass = ucfirst($controllerName) . 'Controller';

        // Chemin vers le fichier à générer
        $controllerPath = dirname(__DIR__, 2) . "/src/Controller/{$controllerClass}.php";
        $controllerNamespace = "App\\Controller";

        // Création du répertoire si nécessaire
        $dir = dirname($controllerPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                C::error("Impossible de créer le répertoire {$dir}.");
                return 1;
            }
        }

        // Protection contre l'écrasement
        if (file_exists($controllerPath)) {
            C::error("Le fichier {$controllerPath} existe déjà !");
            return 1;
        }

        // Préparation des routes et du nom de route
        $baseName = strtolower((string) preg_replace('/Controller$/', '', $controllerClass));
        $routePath = '/' . $baseName;
        $routeName = "{$baseName}.index";

        // Génération du contenu du contrôleur
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$controllerNamespace};

use App\Service\Core\BaseController;
use App\Service\Core\Http\Request;
use App\Service\Core\Http\Response;
use App\Attribute\Route;

/**
 * Contrôleur {$controllerClass} généré automatiquement.
 */
class {$controllerClass} extends BaseController
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

        // Écriture du fichier
        file_put_contents($controllerPath, $content);

        C::success("Le contrôleur {$controllerClass} a été créé dans {$controllerPath}.");
        return 0;
    }

    /**
     * Aide détaillée affichée avec `--help`.
     */
    public function getHelp(): string
    {
        return <<<HELP
Cette commande permet de créer un nouveau contrôleur.

Usage :
  bin/console make:controller

Options :
  --help       Affiche cette aide
HELP;
    }
}
