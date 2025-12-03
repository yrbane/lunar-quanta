<?php
/**
 * Rendu de tableaux de compatibilite pour le framework.
 * Alias vers le renderer du package lunar/cli.
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace App\Service\Command;

use Lunar\Cli\Helper\TableRenderer as LunarTableRenderer;

/**
 * Class TableRenderer.
 *
 * Alias de compatibilite vers Lunar\Cli\Helper\TableRenderer.
 */
class TableRenderer extends LunarTableRenderer
{
}
