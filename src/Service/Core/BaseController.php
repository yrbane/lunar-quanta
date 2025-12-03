<?php
/**
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Core;

use Lunar\Config\Config;
use Lunar\Service\Core\Template\LunarTemplateAdapter;

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
        $engineClass = (string) Config::get('template', 'template.engine', LunarTemplateAdapter::class);
        $templatePath = Config::resolvePath(
            (string) Config::get('template', 'template.template_path', 'template')
        );

        if (!class_exists($engineClass)) {
            throw new \Exception("Template engine class {$engineClass} does not exist.");
        }

        /** @var LunarTemplateAdapter $engine */
        $engine = new $engineClass($templatePath);

        return $engine->render($template, $variables);
    }
}
