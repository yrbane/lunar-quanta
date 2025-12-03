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

namespace App\Service\Core;

use App\Attribute\Route;
use App\Controller\ErrorController;
use App\Service\Core\Config\Config;
use App\Service\Core\Http\Request;
use App\Service\Core\Http\Response;

/**
 * Class Router.
 *
 * Gère les routes en scannant automatiquement toutes les classes du namespace \App\Controller
 * qui se trouvent dans le dossier "src/Controller" pour récupérer les attributs Route.
 */
class Router
{
    /**
     * @var string chemin vers le fichier de cache des routes
     */
    private string $cacheFile;

    /**
     * @var array<string, array<string, mixed>> liste des routes enregistrées
     */
    private array $routes = [];

    /**
     * @var array<string, array<string, mixed>> table de hachage des routes nommées
     */
    private static array $namedRoutes = [];

    /**
     * Le namespace des contrôleurs.
     * Celui-ci peut être mis en configuration si besoin.
     */
    private string $controllerNamespace = 'App\Controller\\';

    /**
     * Le chemin physique du dossier des contrôleurs.
     * Celui-ci peut être mis en configuration si besoin.
     */
    private string $controllerDir;

    /**
     * Constructeur.
     *
     * Scanne automatiquement le répertoire des contrôleurs pour enregistrer leurs routes.
     */
    public function __construct()
    {
        $this->cacheFile = self::getCacheFile();
        // Définir le répertoire des contrôleurs (relative à ce fichier)
        $controllerDir = realpath(Config::getProjectRoot().'/src/Controller');
        $this->controllerDir = false === $controllerDir ? '' : $controllerDir;
        if (is_file($this->cacheFile)) {
            $this->routes = include $this->cacheFile;
        } else {
            $this->registerAllControllerRoutes();
        }
    }

    /**
     * Récuppère le chemin du fichier de cache.
     */
    public static function getCacheFile(): string
    {
        return Config::getProjectRoot().'/'.(string) Config::get('cache.dir').'/router.php';
    }

    /**
     * Retourne la route associée à un nom.
     *
     * @param string $name nom de la route
     *
     * @return null|array<string, mixed> le tableau décrivant la route ou null si non trouvée
     */
    public static function getRouteByName(string $name): ?array
    {
        return self::$namedRoutes[$name] ?? null;
    }

    /**
     * Dispatch la requête entrante vers la route correspondante.
     *
     * @param Request $request L'objet Request
     *
     * @return Response L'objet Response résultant du traitement
     */
    public function dispatch(Request $request): Response
    {
        $response = $this->searchRoute($request);
        if ($response instanceof Response) {
            return $response;
        }
        // La route n'est peut-être pas en cache...
        $this->registerAllControllerRoutes();
        $response = $this->searchRoute($request);
        if ($response instanceof Response) {
            return $response;
        }
        // Si aucune route ne correspond, appel direct du ErrorController pour générer une page 404.
        $errorController = new ErrorController();

        // Appel de la méthode index avec le code 404 et message par défaut
        return $errorController->index($request, 404);
    }

    /**
     * Try to find a matching route for the given request.
     *
     * @return false|Response Response when a route matches, false otherwise
     */
    public function searchRoute(Request $request): bool|Response
    {
        foreach ($this->routes as $route) {
            if ($this->match($route, $request)) {
                $controller = new $route['controller']();
                $action = $route['action'];
                $result = $controller->{$action}($request);

                if ($result instanceof Response) {
                    return $result;
                }

                return new Response((string) $result);
            }
        }

        return false;
    }

    /**
     * Retourne la liste des routes actuellement enregistrées.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getRegisteredRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Retourne la liste des routes enregistrées.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Scanne le dossier des contrôleurs pour enregistrer toutes les routes.
     */
    private function registerAllControllerRoutes(): void
    {
        $controllerClasses = $this->getControllerClasses();
        foreach ($controllerClasses as $controllerClass) {
            $this->registerControllerRoutes($controllerClass);
        }
    }

    /**
     * Récupère la liste des classes du namespace \App\Controller à partir du dossier des contrôleurs.
     *
     * @return array<int, string> tableau des noms complets des classes
     */
    private function getControllerClasses(): array
    {
        $classes = [];
        if (false == $this->controllerDir) {
            return $classes;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->controllerDir));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $realPath = $file->getRealPath();
                if (false === $realPath) {
                    continue;
                }
                // Obtenir le chemin relatif par rapport au dossier des contrôleurs
                $relativePath = str_replace($this->controllerDir.DIRECTORY_SEPARATOR, '', $realPath);
                // Convertir les séparateurs de dossier en séparateurs de namespace
                $relativeClass = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                // Enlever l'extension .php pour obtenir le nom de la classe
                $className = $this->controllerNamespace.str_replace('.php', '', $relativeClass);
                if (class_exists($className)) {
                    $classes[] = $className;
                }
            }
        }

        return $classes;
    }

    /**
     * Enregistre les routes d'un contrôleur en utilisant ses attributs.
     *
     * @param string $controllerClass nom complet de la classe du contrôleur
     */
    private function registerControllerRoutes(string $controllerClass): void
    {
        /** @var class-string $controllerClass */
        $refClass = new \ReflectionClass($controllerClass);
        $basePath = '';

        // Optionnel : récupération d'une annotation de classe pour définir un préfixe de route
        $classAttributes = $refClass->getAttributes(Route::class);
        if (!empty($classAttributes)) {
            // On prend la première annotation pour définir le préfixe
            /** @var Route $routeAttribute */
            $routeAttribute = $classAttributes[0]->newInstance();
            $basePath = $routeAttribute->path;
        }

        // Parcours des méthodes publiques pour trouver les attributs de route
        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                /** @var Route $routeAttr */
                $routeAttr = $attribute->newInstance();
                $fullPath = $basePath.$routeAttr->path;
                foreach ($routeAttr->methods as $httpMethod) {
                    $route = [
                        'name' => $routeAttr->name,
                        'path' => $fullPath,
                        'method' => strtoupper($httpMethod),
                        'controller' => $controllerClass,
                        'action' => $method->getName(),
                    ];
                    $this->routes[$routeAttr->name] = $route;
                    // Si la route est nommée, on l'enregistre dans le tableau statique.
                    if (!empty($routeAttr->name)) {
                        self::$namedRoutes[$routeAttr->name] = $route;
                    }
                }
            }
        }

        // Mise en cache
        file_put_contents($this->cacheFile, '<?php return '.var_export($this->routes, true).';');
    }

    /**
     * Vérifie si une route correspond à la requête.
     *
     * @param array<string, mixed> $route   la route sous forme de tableau
     * @param Request              $request L'objet Request
     *
     * @return bool vrai si la route correspond, sinon faux
     */
    private function match(array $route, Request $request): bool
    {
        return $route['method'] === strtoupper($request->getMethod())
            && $route['path'] === $request->getUri();
    }
}
