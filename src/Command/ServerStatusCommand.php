<?php

declare(strict_types=1);

namespace App\Command;

use App\Attribute\Command;
use App\Service\Command\AbstractCommand;
use App\Service\Command\CommandInterface;
use App\Service\Command\ConsoleHelper as C;

/**
 * Affiche l'état du serveur embeddé.
 */
#[Command(
    name: "server:status",
    description: "Affiche l'état du serveur web"
)]
class ServerStatusCommand extends AbstractCommand implements CommandInterface
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
        $port = $data['port'] ?? null;

        if (!\is_int($pid)) {
            C::error("Fichier PID invalide.");
            return 2;
        }

        // Vérifier si le processus tourne
        $running = false;
        if (\function_exists('posix_kill')) {
            $running = @posix_kill($pid, 0);
        } else {
            exec("ps -p {$pid}", $out, $code);
            $running = ($code === 0 && \count($out) > 1);
        }

        if ($running) {
            C::success(sprintf("Serveur en cours (PID %d)", $pid));
            C::info(sprintf("URL   : http://127.0.0.1:%d", $port ?? 0));
            C::info("Logs  : " . $cwd . "/log/server.log");
            return 0;
        }

        C::error(sprintf("Aucun processus trouvé pour le PID %d", $pid));
        return 3;
    }

    public function getHelp(): string
    {
        return <<<HELP
Cette commande affiche l'état du serveur PHP intégré.

Usage :
  bin/console server:status

Options :
  --help       Affiche cette aide
HELP;
    }
}
