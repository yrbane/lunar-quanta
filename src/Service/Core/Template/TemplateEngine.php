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

/**
 * Class TemplateEngine.
 *
 * Moteur de template simplifié.
 */
class TemplateEngine
{
    private string $templatePath;

    /**
     * Constructeur.
     *
     * @param string $templatePath chemin vers le dossier des templates
     */
    public function __construct(string $templatePath)
    {
        $this->templatePath = rtrim($templatePath, '/');
    }

    /**
     * Rendu d’un template avec des variables.
     *
     * @param string $template  nom du template à utiliser
     * @param array  $variables variables à injecter
     *
     * @return string résultat du rendu
     */
    public function render(string $template, array $variables = []): string
    {
        $templateFile = $this->templatePath.'/'.$template.'.html.php';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException('Template not found: '.$templateFile);
        }
        // Extraction des variables pour qu'elles soient accessibles dans le template.
        extract($variables);
        ob_start();

        include $templateFile;

        return ob_get_clean();
    }
}
