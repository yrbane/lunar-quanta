<?php

declare(strict_types=1);

namespace App\Command;

use App\Attribute\Command;
use App\Service\Command\AbstractCommand;
use App\Service\Command\CommandInterface;
use App\Service\Command\ConsoleHelper as C;

/**
 * Arrête le serveur PHP intégré en arrière-plan.
 */
#[Command(
    name: "server:stop",
    description: "Arrête le serveur web en arrière-plan"
)]
class ServerStopCommand extends AbstractCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $cwd     = \getcwd() ?: __DIR__ . '/../../..';
        $pidFile = $cwd . '/log/server.pid';

        if (!\is_file($pidFile)) {
            C::error("Aucun serveur démarré.");
            return 1;
        }

        $data = json_decode(file_get_contents($pidFile), true);
        $pid  = $data['pid'] ?? null;

        if (!\is_int($pid)) {
            C::error("Fichier PID invalide.");
            return 2;
        }

        // Tentative d'arrêt
        if (\function_exists('posix_kill')) {
            $ok = @posix_kill($pid, SIGTERM);
        } else {
            exec("kill {$pid}", $out, $code);
            $ok = ($code === 0);
        }

        if (!$ok) {
            C::error(sprintf("Échec de l'arrêt du processus %d.", $pid));
            return 3;
        }

        @unlink($pidFile);
        C::success(sprintf("Serveur (PID %d) arrêté.", $pid));
        return 0;
    }

    public function getHelp(): string
    {
        return <<<HELP
Cette commande arrête le serveur PHP intégré en arrière-plan.

Usage :
  bin/console server:stop

Options :
  --help       Affiche cette aide
HELP;
    }
}
