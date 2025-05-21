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

use App\Controller\ErrorController;
use App\Service\Core\Config\Config;
use App\Service\Core\Http\HttpStatus;
use App\Service\Core\Http\Request;

/**
 * Class FrontController.
 *
 * Point d'entrée principal de l'application.
 * Il se charge de charger l'environnement, de configurer l'affichage des erreurs,
 * de créer la requête, de dispatcher celle-ci via le Router et d'envoyer la réponse.
 */
class FrontController
{
    /**
     * Exécute le cycle complet de la requête HTTP.
     */
    public function run(): void
    {
        $this->loadEnvironment();
        $this->configureErrorReporting();

        // Charge la configuration depuis le dossier "config" avec un fichier de cache optionnel.
        try {
            Config::load(Config::getProjectRoot().'/config', Config::getProjectRoot().'/cache/config.php');
        } catch (\Exception $e) {
            exit('Configuration Error: '.$e->getMessage());
        }

        $request = new Request();
        $router = new Router();

        try {
            $response = $router->dispatch($request);
        } catch (\Throwable $e) {
            // En cas d'exception, crée une réponse d'erreur 500 via l'ErrorController.
            $errorController = new ErrorController();
            // Vous pouvez transmettre le message de l'exception pour le débogage en mode développement.
            $response = $errorController->index($request, HttpStatus::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
        $response->send();
    }

    /**
     * Configure l'affichage des erreurs en fonction du mode debug.
     *
     * Si la variable d'environnement APP_DEBUG est à "true", alors
     * toutes les erreurs seront affichées, sinon elles seront masquées.
     */
    private function configureErrorReporting(): void
    {
        if ('true' === getenv('APP_DEBUG')) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
    }

    /**
     * Charge les variables d'environnement depuis le fichier .env.
     */
    private function loadEnvironment(): void
    {
        $envFile = __DIR__ . '/../../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                // Ignorer les commentaires ou les lignes vides
                if ('' === $line || str_starts_with($line, '#')) {
                    continue;
                }
                [$name, $value] = array_map('trim', explode('=', $line, 2));
                dump($name, $value);
                putenv("{$name}={$value}");
            }
        }
        $env = getenv();
        ksort($env);
        dump($env);
    }
}
