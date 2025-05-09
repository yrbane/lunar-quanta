<?php

declare(strict_types=1);

namespace App\Command;

use App\Attribute\Command;
use App\Service\Command\AbstractCommand;
use App\Service\Command\CommandInterface;
use App\Service\Command\ConsoleHelper as C;

/**
 * Fait défiler en couleur les logs du serveur.
 */
#[Command(
    name: "server:logs",
    description: "Affiche les logs du serveur en couleur"
)]
class ServerLogsCommand extends AbstractCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $cwd     = \getcwd() ?: __DIR__ . '/../../..';
        $logFile = $cwd . '/log/server.log';

        if (!\is_file($logFile)) {
            C::error("Fichier de logs introuvable : {$logFile}");
            return 1;
        }

        C::info("Affichage des logs (Ctrl+C pour quitter)");

        // Coloration minimale : ERROR en rouge, WARNING en jaune, SUCCESS en vert
        $patterns = [
            's/ERROR/\\033[31m&\\033[0m/g',
            's/WARNING/\\033[33m&\\033[0m/g',
            's/SUCCESS/\\033[32m&\\033[0m/g',
        ];
        $sedExpr = implode('; ', $patterns);

        $cmd = sprintf(
            'tail -f %s | sed --unbuffered %s',
            escapeshellarg($logFile),
            escapeshellarg($sedExpr)
        );

        passthru($cmd, $exit);
        return $exit;
    }

    public function getHelp(): string
    {
        return <<<HELP
Cette commande fait défiler en couleur les logs du serveur PHP intégré.

Usage :
  bin/console server:logs

Options :
  --help       Affiche cette aide
HELP;
    }
}
