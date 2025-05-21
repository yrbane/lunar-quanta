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

use App\Service\Core\Config\Config;
use App\Service\Core\Template\AdvancedTemplateEngine;

/**
 * Class BaseController.
 *
 * Fournit des fonctionnalités communes aux contrôleurs.
 * Inclut notamment une méthode render pour faciliter l'affichage des templates.
 */
abstract class BaseController
{
    public function __construct() {}

    /**
     * Render a template using the configured templating engine.
     *
     * @param string               $template  name of the template (without extension)
     * @param array<string, mixed> $variables variables to inject in the template
     *
     * @return string rendered HTML content
     */
    protected function render(string $template, array $variables = []): string
    {
        // Récupère la configuration du templating
        $engineClass = (string) Config::get('template.engine', AdvancedTemplateEngine::class);
        /** @var class-string<AdvancedTemplateEngine> $engineClass */
        $templatePath = Config::get('template.template_path', Config::getProjectRoot().'/template');

        // Instancie dynamiquement le moteur de template choisi
        if (!class_exists($engineClass)) {
            throw new \Exception("Template engine class {$engineClass} does not exist.");
        }
        /** @var AdvancedTemplateEngine $engine */
        $engine = new $engineClass($templatePath);
        $engine->loadMacrosFromNamespace('App\Service\Core\Template\Macro', Config::getProjectRoot().'/src/Service/Core/Template/Macro');

        return $engine->render($template, $variables);
    }
}
