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

namespace Lunar\Service\Core;

use Lunar\Attribute\Route;
use Lunar\Config\Config;
use Lunar\Controller\ErrorController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;
use Lunar\Service\Core\Middleware\MiddlewareStack;

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
     */
    private string $controllerNamespace = 'Lunar\Controller\\';

    /**
     * Le chemin physique du dossier des contrôleurs.
     */
    private string $controllerDir;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->cacheFile = self::getCacheFile();
        $controllerDir = realpath(Config::getProjectRoot().'/src/Controller');
        $this->controllerDir = false === $controllerDir ? '' : $controllerDir;

        if (is_file($this->cacheFile) && !$this->isCacheStale()) {
            /** @var array<string, array<string, mixed>> $cachedRoutes */
            $cachedRoutes = include $this->cacheFile;
            $this->routes = $cachedRoutes;
        } else {
            $this->registerAllControllerRoutes();
        }
    }

    /**
     * Récuppère le chemin du fichier de cache.
     */
    public static function getCacheFile(): string
    {
        $cacheDir = Config::get('cache', 'cache.dir', 'cache');
        $cacheDirStr = is_string($cacheDir) ? $cacheDir : 'cache';

        return Config::resolvePath($cacheDirStr.'/router.php');
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
        $this->registerAllControllerRoutes();
        $response = $this->searchRoute($request);
        if ($response instanceof Response) {
            return $response;
        }
        $errorController = new ErrorController();

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
                return $this->executeRoute($route, $request);
            }
        }

        return false;
    }

    /**
     * Execute a matched route with its middlewares.
     *
     * @param array<string, mixed> $route The matched route
     * @param Request $request The request
     * @return Response The response
     */
    private function executeRoute(array $route, Request $request): Response
    {
        $controller = new $route['controller']();
        $action = $route['action'];

        // Build the final handler (controller action)
        $finalHandler = function (Request $req) use ($controller, $action): Response {
            $result = $controller->{$action}($req);
            if ($result instanceof Response) {
                return $result;
            }
            return new Response(is_string($result) ? $result : '');
        };

        // Get route middlewares
        /** @var array<class-string> $middlewareClasses */
        $middlewareClasses = $route['middlewares'] ?? [];

        if (empty($middlewareClasses)) {
            return $finalHandler($request);
        }

        // Build and execute middleware stack
        $stack = new MiddlewareStack();
        foreach ($middlewareClasses as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middleware = new $middlewareClass();
                if ($middleware instanceof MiddlewareInterface) {
                    $stack->add($middleware);
                }
            }
        }

        return $stack->handle($request, $finalHandler);
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
     * Check if the route cache is stale based on controller file modifications.
     *
     * @return bool true if cache is stale and needs regeneration
     */
    private function isCacheStale(): bool
    {
        if (!is_file($this->cacheFile)) {
            return true;
        }

        $cacheTime = filemtime($this->cacheFile);
        if (false === $cacheTime) {
            return true;
        }

        return $this->hasNewerControllerFiles($cacheTime);
    }

    /**
     * Check if any controller file is newer than the cache.
     *
     * @param int $cacheTime timestamp of the cache file
     *
     * @return bool true if any controller file is newer
     */
    private function hasNewerControllerFiles(int $cacheTime): bool
    {
        if ('' === $this->controllerDir || !is_dir($this->controllerDir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->controllerDir)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $fileTime = $file->getMTime();
                if ($fileTime > $cacheTime) {
                    return true;
                }
            }
        }

        return false;
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
                $relativePath = str_replace($this->controllerDir.DIRECTORY_SEPARATOR, '', $realPath);
                $relativeClass = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
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

        $classAttributes = $refClass->getAttributes(Route::class);
        if (!empty($classAttributes)) {
            /** @var Route $routeAttribute */
            $routeAttribute = $classAttributes[0]->newInstance();
            $basePath = $routeAttribute->path;
        }

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
                        'middlewares' => $routeAttr->middlewares,
                    ];
                    $this->routes[$routeAttr->name] = $route;
                    if (!empty($routeAttr->name)) {
                        self::$namedRoutes[$routeAttr->name] = $route;
                    }
                }
            }
        }

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
