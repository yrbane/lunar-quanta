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

namespace Lunar\Template;

use Lunar\Template\Macro\MacroInterface;
use Lunar\Template\Exception\TemplateException;

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

    /** @var array<string, callable> Liste des macros enregistrées */
    protected array $macros = [];

    /**
     * AdvancedTemplateEngine constructor.
     *
     * @param string  $templatePath répertoire où se trouvent les templates source
     * @param string  $cachePath répertoire où seront stockés les templates compilés
     */
    public function __construct(string $templatePath, string $cachePath)
    {
        // Normalisation des chemins
        $this->templatePath = $this->normalizePath($templatePath);
        $this->cachePath = $this->normalizePath($cachePath);

        // Vérification et création des répertoires
        $this->ensureDirectoryExists($this->templatePath, 'Template directory');
        $this->ensureDirectoryExists($this->cachePath, 'Cache directory', true);
    }

    /**
     * Normalise un chemin de fichier.
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * S'assure qu'un répertoire existe.
     *
     * @param string $path
     * @param string $description
     * @param bool $create
     * @throws \Exception
     */
    private function ensureDirectoryExists(string $path, string $description, bool $create = false): void
    {
        if (!is_dir($path)) {
            if ($create) {
                if (!mkdir($path, 0755, true) && !is_dir($path)) {
                    throw TemplateException::unableToCreateCacheDirectory($path);
                }
            } else {
                throw TemplateException::directoryNotFound($path);
            }
        }

        if (!is_readable($path)) {
            throw TemplateException::directoryNotReadable($path);
        }

        if ($create && !is_writable($path)) {
            throw TemplateException::directoryNotWritable($path);
        }
    }

    /**
     * Rendu d'un template avec injection de variables.
     *
     * @param string               $template  Nom du template (sans extension, fichier attendu en .tpl)
     * @param array<string, mixed> $variables variables à injecter dans le template
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
            throw TemplateException::templateNotFound($templateFile);
        }

        $compiledFile = $this->cachePath.'/'.md5($templateFile).'.php';

        // Si le template compilé n'existe pas ou est périmé, le recompiler.
        if (!file_exists($compiledFile) || filemtime($compiledFile) < filemtime($templateFile)) {
            $source = file_get_contents($templateFile);
            if ($source === false) {
                throw TemplateException::unableToReadTemplate($templateFile);
            }
            $compiled = $this->compileTemplate($source);
            file_put_contents($compiledFile, $compiled);
        }

        extract($variables, EXTR_OVERWRITE);

        // Variables par défaut (optionnelles)
        $this->setDefaultVariables();
     
        // Variable pour accéder au moteur dans le contexte du template
        $engine = $this;

        ob_start();
        try{
            include $compiledFile;

        }
        catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
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
     * @param string                  $name nom de la macro
     * @param array<int, mixed>       $args arguments passés à la macro
     *
     * @return mixed résultat renvoyé par la macro
     *
     * @throws \Exception si la macro n'est pas définie
     */
    public function callMacro(string $name, array $args)
    {
        if (!isset($this->macros[$name])) {
            throw TemplateException::macroNotFound($name);
        }

        return $this->macros[$name](...$args);
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
        $source = preg_replace_callback('/\[\[\s*(.*?)\s*\]\]/', function ($matches) {
            $expression = trim($matches[1]);
            
            // Si l'expression est vide, retourner une chaîne vide
            if ('' === $expression) {
                return '';
            }
            
            // Si la première lettre n'est pas '$', on l'ajoute
            if ('$' !== $expression[0]) {
                $expression = '$'.$expression;
            }

            return '<?= htmlspecialchars('.$expression.', ENT_QUOTES, \'UTF-8\') ?>';
        }, $source);

        // Traitement des conditions.
        $source = preg_replace_callback('/\[%\s*if\s+(.*?)\s*%\]/', function ($matches) {
            $condition = $this->addDollarToVariables($matches[1]);
            return '<?php if ('.$condition.'): ?>';
        }, $source);
        $source = preg_replace_callback('/\[%\s*elseif\s+(.*?)\s*%\]/', function ($matches) {
            $condition = $this->addDollarToVariables($matches[1]);
            return '<?php elseif ('.$condition.'): ?>';
        }, $source);
        $source = preg_replace('/\[%\s*else\s*%\]/', '<?php else: ?>', $source);
        $source = preg_replace('/\[%\s*endif\s*%\]/', '<?php endif; ?>', $source);

        // Traitement des boucles.
        $source = preg_replace_callback('/\[%\s*for\s+(\S+)\s+in\s+(\S+)\s*%\]/', function ($matches) {
            $variable = ltrim($matches[1], '$');
            $array = $this->addDollarToVariables($matches[2]);
            return '<?php foreach('.$array.' as $'.$variable.'): ?>';
        }, $source);
        $source = preg_replace('/\[%\s*endfor\s*%\]/', '<?php endforeach; ?>', $source);

        // Traitement des macros avec la syntaxe ##macroName(arg1, arg2)##.
        $source = preg_replace_callback('/##(\w+)\((.*?)\)##/', function ($matches) {
            $macroName = $matches[1];
            $args = $matches[2];

            // Parse les arguments pour créer un tableau PHP
            $parsedArgs = $this->parseMacroArguments($args);
            
            return '<?= $this->callMacro(\''.$macroName.'\', '.$parsedArgs.') ?>';
        }, $source);

        // Nettoyage des éventuelles balises de blocs non remplacées
        $source = preg_replace('/\[%\s*block\s+\S+\s*%\]/', '', $source);

        return (string) preg_replace('/\[%\s*endblock\s*%\]/', '', $source);
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
            $source = preg_replace('/\[%\s*extends\s+[\'"](.+?)[\'"]\s*%\]/', '', $source);
            $blocks = $this->extractBlocks($source);
            $parentFile = "{$this->templatePath}/{$parentTemplate}";
            if (!file_exists($parentFile)) {
                throw TemplateException::parentTemplateNotFound($parentFile);
            }
            $parentSource = file_get_contents($parentFile);
            if ($parentSource === false) {
                throw TemplateException::unableToReadTemplate($parentFile);
            }

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

    /**
     * Parse les arguments d'une macro en tableau PHP.
     *
     * @param string $args Arguments de la macro
     * @return string Code PHP pour le tableau d'arguments
     */
    protected function parseMacroArguments(string $args): string
    {
        if (empty(trim($args))) {
            return '[]';
        }

        // Sépare les arguments par les virgules (en tenant compte des chaînes)
        $arguments = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($args); $i++) {
            $char = $args[$i];
            
            if (!$inQuotes && ($char === '"' || $char === "'")) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($inQuotes && $char === $quoteChar) {
                $inQuotes = false;
                $current .= $char;
            } elseif (!$inQuotes && $char === ',') {
                $arguments[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        if (!empty(trim($current))) {
            $arguments[] = trim($current);
        }

        return '[' . implode(', ', $arguments) . ']';
    }

    /**
     * Ajoute le préfixe $ aux variables dans les expressions PHP.
     *
     * @param string $expression Expression PHP
     * @return string Expression avec variables préfixées
     */
    protected function addDollarToVariables(string $expression): string
    {
        // Remplace les identifiants qui ne sont pas des fonctions par des variables
        return preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)\b/', function ($matches) {
            $var = $matches[1];
            
            // Ne pas modifier les mots-clés PHP, constantes, ou appels de fonctions
            $phpKeywords = ['true', 'false', 'null', 'and', 'or', 'not', 'isset', 'empty', 'array', 'count'];
            if (in_array(strtolower($var), $phpKeywords)) {
                return $var;
            }
            
            // Ne pas modifier si c'est déjà une variable ou une fonction
            if (str_starts_with($var, '$')) {
                return $var;
            }
            
            // Ajouter le préfixe $
            return '$' . $var;
        }, $expression);
    }

    /**
     * Définit des variables par défaut pour les templates.
     * Cette méthode peut être surchargée pour personnaliser les variables par défaut.
     */
    protected function setDefaultVariables(): void
    {
        // Variables définies uniquement si elles n'existent pas déjà
        if (!isset($title)) $title = '';
        if (!isset($description)) $description = '';
        if (!isset($keywords)) $keywords = '';
        if (!isset($author)) $author = '';
        if (!isset($charset)) $charset = 'UTF-8';
        if (!isset($viewport)) $viewport = 'width=device-width, initial-scale=1.0';
        if (!isset($lang)) $lang = 'en';
        if (!isset($favicon)) $favicon = '/favicon.ico';
        if (!isset($baseUrl)) $baseUrl = '/';
        if (!isset($basePath)) $basePath = '/';
    }

    /**
     * Charge toutes les macros d'un répertoire.
     *
     * @param string $namespace Namespace des classes de macros
     * @param string $directory Répertoire contenant les fichiers de macros
     */
    public function loadMacrosFromDirectory(string $namespace, string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            require_once $file;
            
            $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);
            
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                
                if ($reflection->implementsInterface(MacroInterface::class) && $reflection->isInstantiable()) {
                    $constructor = $reflection->getConstructor();
                    
                    if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
                        $instance = new $className();
                        $this->registerMacroInstance($instance);
                    }
                }
            }
        }
    }

    /**
     * Vérifie si un template existe.
     *
     * @param string $template Nom du template
     * @return bool
     */
    public function templateExists(string $template): bool
    {
        $templateFile = $this->templatePath . '/' . $template . '.tpl';
        return file_exists($templateFile);
    }

    /**
     * Vide le cache des templates compilés.
     *
     * @param string|null $template Template spécifique à vider (optionnel)
     */
    public function clearCache(?string $template = null): void
    {
        if ($template !== null) {
            $templateFile = $this->templatePath . '/' . $template . '.tpl';
            $compiledFile = $this->cachePath . '/' . md5($templateFile) . '.php';
            
            if (file_exists($compiledFile)) {
                unlink($compiledFile);
            }
        } else {
            $files = glob($this->cachePath . '/*.php');
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Retourne la liste des macros enregistrées.
     *
     * @return array<string, callable>
     */
    public function getRegisteredMacros(): array
    {
        return $this->macros;
    }
}