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

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\AbstractCommand;
use Lunar\Cli\Helper\ConsoleHelper as C;

#[Command(name: 'hello:you', description: 'Demande ton nom')]
class HelloYouCommand extends AbstractCommand
{
    public function execute(array $args): int
    {
        C::title('Bienvenue dans la Console');
        C::subtitle('Nous allons faire connaissance...');

        $name = C::ask('Quel est ton prénom ?', 'Sébastien');
        C::success("Enchanté, {$name} !");

        return 0;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : hello:you
Demande ton nom.
Utilisation :
  ./bin/console hello:you [--help]
Options :
    --help         Affiche cette aide
Description :
    Cette commande te demande ton prénom et t'accueille chaleureusement.
    Elle est utile pour personnaliser l'expérience utilisateur dans la console.

    Exemples :
        ./bin/console hello:you
        ./bin/console hello:you --help

    Remarque :
        Tu peux appuyer sur Entrée pour accepter la valeur par défaut (Sébastien).
HELP;
    }
}
