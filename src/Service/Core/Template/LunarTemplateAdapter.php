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

namespace Lunar\Service\Core\Template;

use Lunar\Config\Config;
use Lunar\Service\Core\Router;
use Lunar\Template\AdvancedTemplateEngine as LunarEngine;
use Lunar\Template\Macro\AssetMacro;
use Lunar\Template\Macro\RouterInterface;
use Lunar\Template\Macro\UrlMacro;

/**
 * Adaptateur pour le moteur de templates Lunar.
 * Maintient la compatibilité avec l'ancienne interface tout en utilisant le package autonome.
 */
class LunarTemplateAdapter
{
    private LunarEngine $engine;

    public function __construct(string $templatePath)
    {
        if (!preg_match('#^(?:/|[A-Za-z]:[\/])#', $templatePath)) {
            $templatePath = Config::getProjectRoot().'/'.$templatePath;
        }

        $cacheDirConfig = Config::get('cache', 'cache.dir', 'cache');
        $cacheDir = is_string($cacheDirConfig) ? $cacheDirConfig : 'cache';

        $templateCacheDirConfig = Config::get('template', 'template.cache_path', 'template');
        $templateCacheDir = is_string($templateCacheDirConfig) ? $templateCacheDirConfig : 'template';
        $cachePath = Config::resolvePath($cacheDir.'/'.$templateCacheDir);

        $this->engine = new LunarEngine($templatePath, $cachePath);
        $this->registerDefaultMacros();
    }

    /**
     * Rendu d'un template avec injection de variables.
     *
     * @param string               $template  Nom du template (sans extension)
     * @param array<string, mixed> $variables Variables à injecter dans le template
     *
     * @return string Le contenu HTML généré
     */
    public function render(string $template, array $variables = []): string
    {
        return $this->engine->render($template, $variables);
    }

    /**
     * Enregistre une macro réutilisable dans les templates.
     *
     * @param string   $name     Nom de la macro
     * @param callable $callback Fonction à appeler pour générer le contenu
     */
    public function registerMacro(string $name, callable $callback): void
    {
        $this->engine->registerMacro($name, $callback);
    }

    /**
     * Appelle une macro enregistrée.
     *
     * @param string            $name Nom de la macro
     * @param array<int, mixed> $args Arguments passés à la macro
     *
     * @return mixed Résultat renvoyé par la macro
     */
    public function callMacro(string $name, array $args)
    {
        return $this->engine->callMacro($name, $args);
    }

    /**
     * Vide le cache des templates compilés.
     *
     * @param null|string $template Template spécifique à vider (optionnel)
     */
    public function clearCache(?string $template = null): void
    {
        $this->engine->clearCache($template);
    }

    /**
     * Vérifie si un template existe.
     *
     * @param string $template Nom du template
     */
    public function templateExists(string $template): bool
    {
        return $this->engine->templateExists($template);
    }

    /**
     * Retourne l'instance du moteur Lunar pour les utilisations avancées.
     */
    public function getEngine(): LunarEngine
    {
        return $this->engine;
    }

    /**
     * Enregistre les macros par défaut du framework.
     */
    private function registerDefaultMacros(): void
    {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? null;
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $schemeStr = is_string($scheme) ? $scheme : 'http';
        $hostStr = is_string($host) ? $host : 'localhost';
        $baseUrl = $schemeStr.'://'.$hostStr;
        $this->engine->registerMacroInstance(new AssetMacro($baseUrl));

        $routerAdapter = new class implements RouterInterface {
            /**
             * @return null|array{path: string, params?: array<string, string>}
             */
            public function getRouteByName(string $name): ?array
            {
                /** @var array{path: string, params?: array<string, string>}|null $route */
                $route = Router::getRouteByName($name);

                return $route;
            }
        };

        $this->engine->registerMacroInstance(new UrlMacro($routerAdapter));
    }
}
