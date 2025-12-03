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

namespace App\Service\Core\Template;

use App\Service\Core\Config\Config;
use App\Service\Core\Router;
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
        // Configuration des chemins
        if (!preg_match('#^(?:/|[A-Za-z]:[\/])#', $templatePath)) {
            $templatePath = Config::getProjectRoot().'/'.$templatePath;
        }

        $cachePath = Config::getProjectRoot().'/'.(string) Config::get('cache.dir', 'cache').'/'.(string) Config::get('template_cache_dir', 'template');

        // Initialisation du moteur Lunar
        $this->engine = new LunarEngine($templatePath, $cachePath);

        // Enregistrement des macros par défaut
        $this->registerDefaultMacros();
    }

    /**
     * Rendu d'un template avec injection de variables.
     *
     * @param string               $template  Nom du template (sans extension, fichier attendu en .tpl)
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
        // Macro pour les assets
        $baseUrl = $_SERVER['REQUEST_SCHEME'] ?? 'http://'.($_SERVER['HTTP_HOST'] ?? 'localhost');
        $this->engine->registerMacroInstance(new AssetMacro($baseUrl));

        // Macro pour les URLs (nécessite un adaptateur)
        $routerAdapter = new class implements RouterInterface {
            public function getRouteByName(string $name): ?array
            {
                return Router::getRouteByName($name);
            }
        };

        $this->engine->registerMacroInstance(new UrlMacro($routerAdapter));
    }
}
