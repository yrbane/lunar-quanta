<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */

namespace App\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\AbstractCommand;
use Lunar\Cli\Helper\ConsoleHelper;

#[Command(name: 'style:ansi', description: 'Affiche toutes les couleurs ANSI supportées')]
class AnsiColorDemoCommand extends AbstractCommand
{
    public function execute(array $args): int
    {
        ConsoleHelper::title('Palette ANSI');
        ConsoleHelper::subtitle('Couleurs de texte');

        foreach (range(30, 37) as $code) {
            echo ConsoleHelper::color("Code {$code} → texte coloré", (string) $code).PHP_EOL;
        }

        ConsoleHelper::subtitle('Styles avancés');

        echo ConsoleHelper::color('Texte en gras', '1').PHP_EOL;
        echo ConsoleHelper::color('Texte souligné', '4').PHP_EOL;
        echo ConsoleHelper::color('Texte inversé', '7').PHP_EOL;

        ConsoleHelper::subtitle('Couleurs de fond');

        foreach (range(40, 47) as $code) {
            echo ConsoleHelper::color("Fond {$code}", (string) $code).PHP_EOL;
        }

        ConsoleHelper::subtitle('Couleur combinée (texte + fond)');

        echo ConsoleHelper::color('Texte rouge sur fond jaune', '31;43').PHP_EOL;
        echo ConsoleHelper::color('Texte vert sur fond bleu', '32;44').PHP_EOL;

        ConsoleHelper::success('Démo terminée !');

        return 0;
    }

    public function getHelp(): string
    {
        // TODO: Implement getHelp() method.
        return <<<'HELP'
Commande : style:ansi
Affiche toutes les couleurs ANSI supportées.

Utilisation :
  ./bin/console style:ansi [--help]
  
Options :
    --help         Affiche cette aide
    
Description :
    Cette commande affiche toutes les couleurs ANSI supportées par le terminal.
    Elle est utile pour tester les styles de texte et de fond disponibles.

Exemples :
    ./bin/console style:ansi
    ./bin/console style:ansi --help
HELP;
    }
}
