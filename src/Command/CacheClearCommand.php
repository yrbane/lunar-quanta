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

use App\Attribute\Command;
use App\Service\Command\CommandInterface;
use App\Service\Core\Config\Config;

#[Command(name: 'cache:clear', description: 'Supprime les fichiers du cache.')]
class CacheClearCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $cacheDir = Config::getProjectRoot().'/cache';

        foreach (glob($cacheDir.'/*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        echo "🧹 Cache vidé avec succès.\n";

        return 0;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : cache:clear
Supprime les fichiers du cache.

Utilisation :
  ./bin/console cache:clear [--help]
  
Options :
    --help         Affiche cette aide
    
Description :
    Cette commande supprime tous les fichiers du répertoire de cache.
    Elle est utile pour libérer de l'espace disque ou résoudre des problèmes liés au cache.
    
Exemples :
    ./bin/console cache:clear
    ./bin/console cache:clear --help
    
Remarque :
    Assurez-vous d'avoir les permissions nécessaires pour supprimer les fichiers du cache.  
    
HELP;
    }
}
