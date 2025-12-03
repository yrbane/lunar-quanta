<?php
/**
 * Bootstrap de la console Lunar Quanta.
 *
 * @since 1.0.0
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Console;

use Lunar\Cli\BootstrapInterface;

/**
 * Class Bootstrap.
 *
 * Initialise l'environnement avant le lancement des commandes.
 * Note: lunar-config charge les fichiers à la demande, pas besoin de load() explicite.
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * Initialise l'environnement du framework.
     */
    public function boot(): void
    {
        // lunar-config charge automatiquement les fichiers config/*.json à la demande
        // Plus besoin de chargement explicite
    }
}
