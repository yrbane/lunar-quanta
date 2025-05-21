<?php
declare(strict_types=1);

namespace App\Service\Core\Config;

/**
 * Class Config
 *
 * Système de configuration basé sur des fichiers JSON.
 *
 * Cette classe permet de charger tous les fichiers JSON présents dans un dossier
 * (par exemple, "config/") et de fusionner leur contenu en un tableau unique.
 * Un système de cache est intégré pour éviter des rechargements inutiles.
 *
 * Exemple d'utilisation :
 *   \App\Service\Config\Config::load('/path/to/config', '/path/to/cache/config.cache.php');
 *   $engine = \App\Service\Config\Config::get('template.engine');
 */
class Config
{
    /**
     * Tableau interne stockant la configuration fusionnée.
     *
     * @var array<string, mixed>|null
     */
    protected static ?array $config = null;

    /**
     * Chemin absolu vers la racine du projet
     *
     * @var ?string
     */
    public static ?string $projectRoot=null;

    /**
     * Charge la configuration depuis un chemin (fichier unique ou dossier) et, éventuellement, utilise un cache.
     *
     * @param string $path Chemin vers le fichier ou le dossier de configuration.
     * @param string|null $cacheFile Chemin optionnel vers un fichier de cache.
     * @return void
     * @throws \Exception Si le chemin est invalide ou si un JSON est invalide.
     */
    public static function load(string $path, ?string $cacheFile = null): void
    {
        // Utilisation du cache fichier si fourni et s'il existe
        if ($cacheFile !== null && file_exists($cacheFile)) {
            // On charge la configuration depuis le cache
            $cached = include $cacheFile;
            if (is_array($cached)) {
                self::$config = $cached;
                return;
            }
        }

        // Charge depuis un dossier ou un fichier
        if (is_dir($path)) {
            self::$config = self::loadFromDirectory($path);
        } elseif (file_exists($path)) {
            $json = file_get_contents($path);
            $data = json_decode($json, true);
            if ($data === null) {
                throw new \Exception("Invalid JSON in configuration file: {$path}");
            }
            self::$config = $data;
        } else {
            throw new \Exception("Configuration path not found: {$path}");
        }

        // Écriture du cache si un fichier de cache est fourni
        if ($cacheFile !== null) {
            // On écrit le contenu en PHP (avec un return de l'array) pour une inclusion rapide
            $exported = var_export(self::$config, true);
            file_put_contents($cacheFile, "<?php\nreturn {$exported};\n");
        }
    }

    /**
     * Récupère la configuration actuelle.
     *
     * @return array<string, mixed>|null La configuration actuelle ou null si non chargée.
     */
    public static function getAll(): ?array
    {
        return self::$config;
    }

    /**
     * Charge et fusionne tous les fichiers JSON présents dans un dossier.
     *
     * @param string $directory           Chemin du dossier contenant les fichiers JSON.
     * @return array<string, mixed>       La configuration fusionnée.
     * @throws \Exception Si un fichier JSON est invalide.
     */
    protected static function loadFromDirectory(string $directory): array
    {
        $configData = [];
        $files = glob(rtrim($directory, '/') . '/*.json');
        foreach ($files as $file) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);
            if ($data === null) {
                throw new \Exception("Invalid JSON in configuration file: {$file}");
            }
            $configData = self::mergeArrays($configData, $data);
        }
        return $configData;
    }

    /**
     * Fusionne récursivement deux tableaux.
     *
     * En cas de conflit, les valeurs du second tableau remplacent celles du premier.
     *
     * @param array<string, mixed> $a Premier tableau.
     * @param array<string, mixed> $b Second tableau.
     * @return array<string, mixed> Tableau fusionné.
     */
    protected static function mergeArrays(array $a, array $b): array
    {
        foreach ($b as $key => $value) {
            if (isset($a[$key]) && is_array($a[$key]) && is_array($value)) {
                $a[$key] = self::mergeArrays($a[$key], $value);
            } else {
                $a[$key] = $value;
            }
        }
        return $a;
    }

    /**
     * Récupère une valeur de configuration en utilisant une clé "dot-notée".
     *
     * Exemple : Config::get('template.engine') pour obtenir la valeur associée.
     *
     * @param string $key Clé de configuration.
     * @param mixed $default Valeur par défaut si la clé n'est pas trouvée.
     * @return mixed La valeur de configuration ou $default.
     * @throws \Exception Si la configuration n'est pas chargée.
     */
    public static function get(string $key, $default = null)
    {
        if (self::$config === null) {
            throw new \Exception("Configuration not loaded.");
        }
        $keys = explode('.', $key);
        $value = self::$config;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }

    /**
     * Vide la configuration actuelle.
     *
     * Utile pour forcer un rechargement ou lors des tests.
     */
    public static function clear(): void
    {
        self::$config = null;
    }

    /**
     * Retourne le chemin absolu de la racine du projet.
     *
     * Cette méthode calcule la racine en se basant sur la position de la classe Config.
     * Ici, nous partons du principe que la classe se trouve dans "src/Service/Config",
     * donc la racine se trouve 3 niveaux au-dessus.
     */
    public static function getProjectRoot(): string
    {
        if (null === self::$projectRoot) {
            self::$projectRoot = realpath(__DIR__ . '/../../../../');
        }

        return self::$projectRoot;
    }
}
