<?php
/**
 * Attribut Command de compatibilite pour le framework.
 * Alias vers l'attribut du package lunar/cli.
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace App\Attribute;

use Lunar\Cli\Attribute\Command as LunarCommand;

/**
 * Attribut Command.
 *
 * Alias de compatibilite vers Lunar\Cli\Attribute\Command.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Command extends LunarCommand
{
}
