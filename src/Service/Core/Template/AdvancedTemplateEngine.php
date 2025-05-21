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
use App\Service\Core\Template\Macro\MacroInterface;

/**
 * Class AdvancedTemplateEngine.
 *
 * Un moteur de template avancé qui supporte :
 * - Les variables avec la syntaxe [[ ... ]].
 * - Les conditions avec [% if ... %], [% elseif ... %], [% else %], [% endif %].
 * - Les boucles avec [% for variable in array %] et [% endfor %].
 * - L'héritage et les blocs via [% extends 'parent.tpl' %], [% block blockName %] ... [% endblock %].
 * - Les macros avec la syntaxe ##macroName(arg1, arg2)##, par exemple pour générer des URLs.
 *
 * Les templates sources sont attendus dans un dossier (ex : template/) au format .tpl.
 * Les templates compilés seront stockés dans un dossier de cache.
 */
class AdvancedTemplateEngine
{
    /** @var string Chemin absolu vers le dossier des templates */
    protected string $templatePath;

    /** @var string Chemin absolu vers le dossier de cache des templates compilés */
    protected string $cachePath;

    /** @var array Liste des macros enregistrées */
    protected array $macros = [];

    /**
     * AdvancedTemplateEngine constructor.
     *
     * @param string  $templatePath répertoire où se trouvent les templates source
     *
     * Pour éviter les problèmes de chemins relatifs, on convertit les chemins en chemin absolu.
     */
    public function __construct(string $templatePath)
    {
        // Si $templatePath n'est pas absolu, on le préfixe par le répertoire courant
        if (!preg_match('#^(?:/|[A-Za-z]:[\/])#', $templatePath)) {
            $templatePath = Config::getProjectRoot() . '/' .$templatePath;
        }
        $absTemplatePath = realpath($templatePath);
        if (false === $absTemplatePath) {
            throw new \Exception("Template path not found: {$templatePath}");
        }
        $this->templatePath = $absTemplatePath;

        // Définition du cachePath
        $cachePath = Config::getProjectRoot().'/'.Config::get('cache.dir','cache').'/'.Config::get('template_cache_dir', 'template');

        $absCachePath = realpath($cachePath) ?: $cachePath;
        $this->cachePath = rtrim($absCachePath, '/');
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Rendu d'un template avec injection de variables.
     *
     * @param string $template  Nom du template (sans extension, fichier attendu en .tpl)
     * @param array  $variables variables à injecter dans le template
     *
     * @return string le contenu HTML généré
     *
     * @throws \Exception si le template source n'existe pas
     */
    public function render(string $template, array $variables = []): string
    {
        // Construit le chemin complet du fichier template
        $templateFile = $this->templatePath.'/'.$template.'.tpl';
        if (!file_exists($templateFile)) {
            throw new \Exception("Template not found: {$templateFile}");
        }

        $compiledFile = $this->cachePath.'/'.md5($templateFile).'.php';

        // Si le template compilé n'existe pas ou est périmé, le recompiler.
        if (!file_exists($compiledFile) || filemtime($compiledFile) < filemtime($templateFile)) {
            $compiled = $this->compileTemplate(file_get_contents($templateFile));
            file_put_contents($compiledFile, $compiled);
        }

        extract($variables, EXTR_OVERWRITE);

        //Variables par défaut
        $title = $title ?? 'Titre par défaut';
        $description = $description ?? 'Description par défaut';
        $keywords = $keywords ?? 'Mots-clés par défaut';
        $author = $author ?? 'Auteur par défaut';
        $charset = $charset ?? 'UTF-8';
        $viewport = $viewport ?? 'width=device-width, initial-scale=1.0';
        $lang = $lang ?? 'fr';
        $favicon = $favicon ?? '/favicon.ico';
        $baseUrl = $baseUrl ?? '/';
        $basePath = $basePath ?? '/';
     

        ob_start();
        try{
            include $compiledFile;

        }
        catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    /**
     * Charge toutes les macros contenues dans un dossier donné,
     * et les enregistre si elles implémentent MacroInterface.
     *
     * @param string $namespace espace de noms des macros
     * @param string $directory Chemin absolu vers le dossier contenant les fichiers .php des macros.
     */
    public function loadMacrosFromNamespace(string $namespace, string $directory): void
    {
        // Parcourt tous les fichiers PHP dans le répertoire
        foreach (glob($directory.'/*.php') as $file) {
            require_once $file;

            // Extrait le nom de la classe à partir du nom de fichier
            $className = $namespace.'\\'.pathinfo($file, PATHINFO_FILENAME);

            // Vérifie que la classe existe bien
            if (class_exists($className)) {
                $instance = new $className();

                // Vérifie que l’instance implémente bien MacroInterface
                if ($instance instanceof MacroInterface) {
                    $this->registerMacroInstance($instance);
                }
            }
        }
    }

    /**
     * Enregistre une macro réutilisable dans les templates.
     *
     * @param string   $name     nom de la macro
     * @param callable $callback fonction à appeler pour générer le contenu
     */
    public function registerMacro(string $name, callable $callback): void
    {
        // Stocke le callback dans le tableau des macros
        // Le moteur l'exécutera dynamiquement quand il rencontre ##macroName()##
        $this->macros[$name] = $callback;
    }

    /**
     * Enregistre une macro via une instance qui implémente MacroInterface.
     *
     * @param MacroInterface $macro instance de la macro
     */
    public function registerMacroInstance(MacroInterface $macro): void
    {
        // Le tableau [$macro, 'execute'] est un callable valide SI la méthode est publique
        $this->registerMacro($macro->getName(), [$macro, 'execute']);
    }

    /**
     * Appelle une macro enregistrée.
     *
     * @param string $name nom de la macro
     * @param array  $args arguments passés à la macro
     *
     * @return mixed résultat renvoyé par la macro
     *
     * @throws \Exception si la macro n'est pas définie
     */
    public function callMacro(string $name, array $args)
    {
        if (!isset($this->macros[$name])) {
            throw new \Exception("Macro {$name} is not defined.");
        }

        if (!is_callable($this->macros[$name])) {
            throw new \Exception("Macro {$name} is not callable.");
        }
        return $this->macros[$name][0]->{$this->macros[$name][1]}($args);
    }

    /**
     * Compile le template source en code PHP.
     *
     * @param string $source contenu du template source
     *
     * @return string code PHP généré
     */
    protected function compileTemplate(string $source): string
    {
        // Traitement de l'héritage (extends et blocs)
        $source = $this->processExtends($source);

        // Conversion des variables [[ ... ]] en affichage PHP sécurisé.
        $source = preg_replace_callback('/\[\[\s*(.+?)\s*\]\]/', function ($matches) {
            $expression = trim($matches[1]);
            // Si la première lettre n'est pas '$', on l'ajoute
            if ('' === $expression || '$' !== $expression[0]) {
                $expression = '$'.$expression;
            }

            return '<?= htmlspecialchars('.$expression.', ENT_QUOTES, \'UTF-8\') ?>';
        }, $source);

        // Traitement des conditions.
        $source = preg_replace('/\[%\s*if\s+(.*?)\s*%\]/', '<?php if ($1): ?>', $source);
        $source = preg_replace('/\[%\s*elseif\s+(.*?)\s*%\]/', '<?php elseif ($1): ?>', $source);
        $source = preg_replace('/\[%\s*else\s*%\]/', '<?php else: ?>', $source);
        $source = preg_replace('/\[%\s*endif\s*%\]/', '<?php endif; ?>', $source);

        // Traitement des boucles.
        $source = preg_replace_callback('/\[%\s*for\s+(\S+)\s+in\s+(\S+)\s*%\]/', function ($matches) {
            return '<?php foreach('.$matches[2].' as $'.$matches[1].'): ?>';
        }, $source);
        $source = preg_replace('/\[%\s*endfor\s*%\]/', '<?php endforeach; ?>', $source);

        // Traitement des macros avec la syntaxe ##macroName(arg1, arg2)##.
        $source = preg_replace_callback('/##(\w+)\((.*?)\)##/', function ($matches) {
            $macroName = $matches[1];
            $args = $matches[2];

            return '<?= $this->callMacro(\''.$macroName.'\', ['.$args.']) ?>';
        }, $source);

        // Nettoyage des éventuelles balises de blocs non remplacées
        $source = preg_replace('/\[%\s*block\s+\S+\s*%\]/', '', $source);

        return preg_replace('/\[%\s*endblock\s*%\]/', '', $source);
    }

    /**
     * Gère l'héritage de templates.
     *
     * @param string $source contenu du template enfant
     *
     * @return string contenu final après fusion avec le template parent
     *
     * @throws \Exception si le template parent n'existe pas
     */
    protected function processExtends(string $source): string
    {
        if (preg_match('/\[%\s*extends\s+[\'"](.+?)[\'"]\s*%\]/', $source, $matches)) {
            $parentTemplate = $matches[1];
            $source = preg_replace('/\[%\s*extends\s+[\'"].+?[\'"]\s*%\]/', '', $source);
            $blocks = $this->extractBlocks($source);
            $parentFile = "{$this->templatePath}/{$parentTemplate}";
            if (!file_exists($parentFile)) {
                throw new \Exception("Parent template not found: {$parentFile}");
            }
            $parentSource = file_get_contents($parentFile);

            return preg_replace_callback('/\[%\s*block\s+(\w+)\s*%\](.*?)\[%\s*endblock\s*%\]/s', function ($matches) use ($blocks) {
                $blockName = $matches[1];

                return $blocks[$blockName] ?? $matches[2];
            }, $parentSource);
        }

        return $source;
    }

    /**
     * Extrait les blocs du template.
     *
     * @param string $source contenu du template
     *
     * @return array<string, string> tableau associatif blockName => contenu
     */
    protected function extractBlocks(string $source): array
    {
        $blocks = [];
        if (preg_match_all('/\[%\s*block\s+(\w+)\s*%\](.*?)\[%\s*endblock\s*%\]/s', $source, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $blocks[$match[1]] = $match[2];
            }
        }

        return $blocks;
    }
}
