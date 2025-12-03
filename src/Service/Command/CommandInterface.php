<?php
/**
 * Interface de compatibilite pour les commandes du framework.
 * Etend l'interface du package lunar/cli.
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace App\Service\Command;

use Lunar\Cli\CommandInterface as LunarCommandInterface;

/**
 * Interface CommandInterface.
 *
 * Alias de compatibilite vers Lunar\Cli\CommandInterface.
 */
interface CommandInterface extends LunarCommandInterface
{
}
