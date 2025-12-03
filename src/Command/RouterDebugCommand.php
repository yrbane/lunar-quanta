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

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\AbstractCommand;
use Lunar\Cli\CommandInterface;
use Lunar\Cli\Helper\ConsoleHelper as C;
use Lunar\Cli\Helper\TableRenderer;
use Lunar\Service\Core\Router;

/**
 * Commande permettant de lister :
 *  - Toutes les classes de contrôleurs scannées par le Router,
 *  - Toutes les routes enregistrées.
 */
#[Command(
    name: 'router:debug',
    description: 'Liste toutes les classes de contrôleurs et les routes associées (ordre alpha).'
)]
class RouterDebugCommand extends AbstractCommand implements CommandInterface
{
    /**
     * Exécute la commande.
     *
     * @param string[] $args
     *
     * @return int Code de sortie
     */
    public function execute(array $args): int
    {
        // Vérifie si l'utilisateur veut juste l'aide
        if ($this->wantsHelp($args)) {
            C::info($this->getHelp());

            return 0;
        }

        // On vide le cache du router
        unlink(Router::getCacheFile());

        // Instancie le router
        $router = new Router();

        // 1) Récupérer la liste des classes
        $controllerClasses = $this->getControllerClasses($router);

        // 2) Récupérer la liste des routes
        $registeredRoutes = $this->getRoutes($router);

        // 3) Affichage
        C::title('Debug du Router');

        // a) Afficher les classes
        $this->displayControllerClasses($controllerClasses);

        // b) Afficher les routes
        $this->displayRoutes($registeredRoutes);

        return 0;
    }

    /**
     * Retourne l'aide détaillée pour cette commande.
     */
    public function getHelp(): string
    {
        return <<<'HELP'
Cette commande liste toutes les classes de contrôleurs détectées par le router, 
ainsi que toutes les routes enregistrées, triées en ordre alphabétique.

Utilisation :
  bin/console router:debug

Options:
  --help   Affiche cette aide
HELP;
    }

    /**
     * Récupère la liste des classes de contrôleurs (ordre alpha).
     *
     * @return string[] Liste de noms de classes
     */
    private function getControllerClasses(Router $router): array
    {
        // Soit on expose la méthode interne getControllerClasses() si on la rend publique,
        // soit on copie la logique ici, soit on use Reflection pour la forcer.
        //
        // Exemple en la rendant publique dans le Router:
        //   $classes = $router->getControllerClassesPublic();
        //   ...
        //
        // Pour la démo, on fait un "hack" Reflection pour accéder à la méthode privée existante :
        $refMethod = new \ReflectionMethod($router, 'getControllerClasses');
        $refMethod->setAccessible(true);

        /** @var string[] $classes */
        $classes = $refMethod->invoke($router);

        // On trie par ordre alphabétique
        sort($classes);

        return $classes;
    }

    /**
     * Récupère la liste des routes enregistrées (ordre alpha).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRoutes(Router $router): array
    {
        $routes = $router->getRegisteredRoutes();

        // On va trier par ordre alphabétique de "name" (ou "path" si pas de nom).
        usort($routes, function (array $a, array $b): int {
            $nameA = (string) ($a['name'] ?? $a['path']);
            $nameB = (string) ($b['name'] ?? $b['path']);

            return strcmp($nameA, $nameB);
        });

        return $routes;
    }

    /**
     * Affiche la liste des classes dans un tableau unique.
     *
     * @param string[] $controllerClasses
     */
    private function displayControllerClasses(array $controllerClasses): void
    {
        C::subtitle("Liste des classes de contrôleurs :\n");

        if (empty($controllerClasses)) {
            C::info("Aucune classe de contrôleur trouvée.\n");

            return;
        }

        // On construit un tableau simple, ex.: [ ['Classe'=>'App\Controller\HomeController'] , ... ]
        $rows = [];
        foreach ($controllerClasses as $class) {
            $rows[] = ['Classe' => $class];
        }

        TableRenderer::renderSingleTable($rows, [
            'columns' => ['Classe' => 'Classe'],
            'borderColor' => '35',    // magenta
            'headerColor' => '1;35',  // magenta gras
            'rowColor' => '0;37',     // gris clair
            'showHeaders' => true,
        ]);
        echo "\n";
    }

    /**
     * Affiche la liste des routes enregistrées dans un tableau unique.
     *
     * @param array<int, array<string, mixed>> $routes
     */
    private function displayRoutes(array $routes): void
    {
        C::subtitle("Liste des routes enregistrées :\n");

        if (empty($routes)) {
            C::info("Aucune route enregistrée.\n");

            return;
        }

        // On construit un tableau pour l'affichage
        // On va afficher "Name", "Path", "Method", "Controller::Action"
        $rows = [];
        foreach ($routes as $route) {
            $controllerAction = $route['controller'].'::'.$route['action'];
            $rows[] = [
                'Nom' => $route['name'] ?: '[non nommée]',
                'Méthode' => $route['method'] ?? '',
                'Path' => $route['path'] ?? '',
                'Contrôleur' => $controllerAction,
            ];
        }

        // On définit l'ordre et le label des colonnes
        $columns = [
            'Nom' => 'Nom',
            'Méthode' => 'Méthode',
            'Path' => 'Chemin',
            'Contrôleur' => 'Contrôleur::Action',
        ];

        TableRenderer::renderSingleTable($rows, [
            'columns' => $columns,
            'borderColor' => '35',
            'headerColor' => '1;35',
            'rowColor' => '0;37',
            'showHeaders' => true,
        ]);
        echo "\n";
    }
}
