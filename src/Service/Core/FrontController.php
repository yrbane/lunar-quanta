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

namespace Lunar\Service\Core;

use Lunar\Controller\ErrorController;
use Lunar\Service\Core\Http\HttpStatus;
use Lunar\Service\Core\Http\Request;

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

        // lunar-config charge automatiquement les fichiers config/*.json à la demande

        $request = new Request();
        $router = new Router();

        try {
            $response = $router->dispatch($request);
        } catch (\Throwable $e) {
            $errorController = new ErrorController();
            $response = $errorController->index($request, HttpStatus::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
        $response->send();
    }

    /**
     * Configure l'affichage des erreurs en fonction du mode debug.
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
        $envFile = __DIR__.'/../../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (false === $lines) {
                return;
            }
            foreach ($lines as $line) {
                $line = trim($line);
                if ('' === $line || str_starts_with($line, '#')) {
                    continue;
                }
                [$name, $value] = array_map('trim', explode('=', $line, 2));
                putenv("{$name}={$value}");
            }
        }
    }
}
