<?php
/**
 * Classe abstraite de compatibilite pour les commandes du framework.
 * Etend la classe du package lunar/cli.
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace App\Service\Command;

use Lunar\Cli\AbstractCommand as LunarAbstractCommand;

/**
 * Classe abstraite pour les commandes de la console.
 * Herite de Lunar\Cli\AbstractCommand pour la compatibilite.
 */
abstract class AbstractCommand extends LunarAbstractCommand implements CommandInterface
{
}
