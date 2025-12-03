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

namespace Lunar\Service\Core\Config;

/**
 * Class Config.
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
     * Chemin absolu vers la racine du projet.
     */
    public static ?string $projectRoot = null;

    /**
     * Tableau interne stockant la configuration fusionnée.
     *
     * @var null|array<string, mixed>
     */
    protected static ?array $config = null;

    /**
     * Charge la configuration depuis un chemin (fichier unique ou dossier) et, éventuellement, utilise un cache.
     *
     * @param string      $path      chemin vers le fichier ou le dossier de configuration
     * @param null|string $cacheFile chemin optionnel vers un fichier de cache
     *
     * @throws \Exception si le chemin est invalide ou si un JSON est invalide
     */
    public static function load(string $path, ?string $cacheFile = null): void
    {
        // Utilisation du cache fichier si fourni et s'il existe
        if (null !== $cacheFile && file_exists($cacheFile)) {
            // On charge la configuration depuis le cache
            $cached = include $cacheFile;
            if (is_array($cached)) {
                // @var array<string, mixed> $cached
                self::$config = $cached;

                return;
            }
        }

        // Charge depuis un dossier ou un fichier
        if (is_dir($path)) {
            self::$config = self::loadFromDirectory($path);
        } elseif (file_exists($path)) {
            $json = file_get_contents($path);
            if (false === $json) {
                throw new \Exception("Unable to read configuration file: {$path}");
            }
            $data = json_decode($json, true);
            if (null === $data) {
                throw new \Exception("Invalid JSON in configuration file: {$path}");
            }
            // @var array<string, mixed> $data
            self::$config = $data;
        } else {
            throw new \Exception("Configuration path not found: {$path}");
        }

        // Écriture du cache si un fichier de cache est fourni
        if (null !== $cacheFile) {
            // On écrit le contenu en PHP (avec un return de l'array) pour une inclusion rapide
            $exported = var_export(self::$config, true);
            file_put_contents($cacheFile, "<?php\nreturn {$exported};\n");
        }
    }

    /**
     * Récupère la configuration actuelle.
     *
     * @return null|array<string, mixed> la configuration actuelle ou null si non chargée
     */
    public static function getAll(): ?array
    {
        return self::$config;
    }

    /**
     * Récupère une valeur de configuration en utilisant une clé "dot-notée".
     *
     * Exemple : Config::get('template.engine') pour obtenir la valeur associée.
     *
     * @param string $key     clé de configuration
     * @param mixed  $default valeur par défaut si la clé n'est pas trouvée
     *
     * @return mixed la valeur de configuration ou $default
     *
     * @throws \Exception si la configuration n'est pas chargée
     */
    public static function get(string $key, $default = null)
    {
        if (null === self::$config) {
            throw new \Exception('Configuration not loaded.');
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
            $root = realpath(__DIR__.'/../../../../');
            self::$projectRoot = false === $root ? '' : $root;
        }

        return self::$projectRoot;
    }

    /**
     * Charge et fusionne tous les fichiers JSON présents dans un dossier.
     *
     * @param string $directory chemin du dossier contenant les fichiers JSON
     *
     * @return array<string, mixed> la configuration fusionnée
     *
     * @throws \Exception si un fichier JSON est invalide
     */
    protected static function loadFromDirectory(string $directory): array
    {
        $configData = [];
        $files = glob(rtrim($directory, '/').'/*.json');
        if (false === $files) {
            return $configData;
        }
        foreach ($files as $file) {
            $json = file_get_contents($file);
            if (false === $json) {
                throw new \Exception("Unable to read configuration file: {$file}");
            }
            $data = json_decode($json, true);
            if (null === $data) {
                throw new \Exception("Invalid JSON in configuration file: {$file}");
            }

            /** @var array<string, mixed> $data */
            $configData = self::mergeArrays($configData, $data);
        }

        return $configData;
    }

    /**
     * Fusionne récursivement deux tableaux.
     *
     * En cas de conflit, les valeurs du second tableau remplacent celles du premier.
     *
     * @param array<string, mixed> $a premier tableau
     * @param array<string, mixed> $b second tableau
     *
     * @return array<string, mixed> tableau fusionné
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
}
