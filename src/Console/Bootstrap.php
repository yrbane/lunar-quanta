<?php
/**
 * Bootstrap de la console Lunar Quanta.
 *
 * @since 1.0.0
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace App\Console;

use Lunar\Cli\BootstrapInterface;
use App\Service\Core\Config\Config;

/**
 * Class Bootstrap.
 *
 * Initialise l'environnement avant le lancement des commandes.
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * Initialise la configuration du framework.
     */
    public function boot(): void
    {
        try {
            Config::load(
                Config::getProjectRoot() . '/config',
                Config::getProjectRoot() . '/cache/config.php'
            );
        } catch (\Exception $e) {
            fwrite(STDERR, 'Configuration Error: ' . $e->getMessage() . "\n");
            exit(1);
        }
    }
}
