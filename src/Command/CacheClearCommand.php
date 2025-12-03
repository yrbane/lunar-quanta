<?php
/**
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Config\Config;

#[Command(name: 'cache:clear', description: 'Supprime les fichiers du cache.')]
class CacheClearCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $cacheDir = Config::resolvePath(
            (string) Config::get('cache', 'cache.dir', 'cache')
        );

        if (!is_dir($cacheDir)) {
            echo "Le répertoire de cache n'existe pas.\n";

            return 1;
        }
        $this->deleteDirContent($cacheDir);

        echo "Cache vidé avec succès.\n";

        return 0;
    }

    public function deleteDirContent(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*');

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDirContent($file);
                rmdir($file);
            } else {
                echo $file . " supprimé.\n";
                unlink($file);
            }
        }
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
