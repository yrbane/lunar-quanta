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

namespace App\Command;

use App\Attribute\Command;
use App\Service\Command\AbstractCommand;
use App\Service\Command\CommandInterface;
use App\Service\Command\ConsoleHelper as C;

/**
 * Démarre le serveur PHP intégré en arrière-plan.
 */
#[Command(
    name: 'server:start',
    description: 'Permet de lancer un serveur web en arrière-plan'
)]
class ServerStartCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @param array<string> $args [0] = port
     */
    public function execute(array $args): int
    {
        if ($this->wantsHelp($args)) {
            C::info($this->getHelp());

            return 0;
        }

        // Récupération et validation du port
        $port = $args[0] ?? null;
        if (null === $port || !ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            C::error('Le port est manquant ou invalide (1-65535).');
            C::info($this->getHelp());

            return 1;
        }
        $port = (int) $port;

        // Chemins
        $cwd = \getcwd() ?: __DIR__.'/../../..';
        $public = $cwd.'/public';
        $logDir = $cwd.'/log';
        $logFile = $logDir.'/server.log';
        $pidFile = $logDir.'/server.pid';

        // Vérifications et création
        if (!\is_dir($public)) {
            C::error("Le dossier public/ n'existe pas: {$public}");

            return 2;
        }
        if (!\is_dir($logDir) && false === @\mkdir($logDir, 0755, true)) {
            C::error("Impossible de créer le dossier de logs: {$logDir}");

            return 3;
        }

        // Lancement en arrière-plan (+ récupération du PID)
        $cmd = sprintf(
            'php -S 127.0.0.1:%d -t %s > %s 2>&1 & echo $!',
            $port,
            escapeshellarg($public),
            escapeshellarg($logFile)
        );
        exec($cmd, $output, $exitCode);
        if (0 !== $exitCode || !isset($output[0]) || !ctype_digit($output[0])) {
            C::error('Impossible de démarrer le serveur.');

            return 4;
        }

        $pid = (int) $output[0];
        file_put_contents($pidFile, json_encode(['pid' => $pid, 'port' => $port]));

        C::success(sprintf('Serveur démarré en arrière-plan (PID %d) sur http://127.0.0.1:%d', $pid, $port));
        C::info("Logs : {$logFile}");

        return 0;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Cette commande démarre le serveur PHP intégré en arrière-plan.

Usage :
  bin/console server:start <port>

Arguments :
 *   <port> : port utilisé (1-65535)

Options :
  --help       Affiche cette aide
HELP;
    }
}
