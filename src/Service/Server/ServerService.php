<?php

declare(strict_types=1);

namespace App\Service\Server;

use App\Service\Core\Config\Config;

class ServerService
{
    private string $pidFile;

    public function __construct()
    {
        $this->pidFile = Config::getProjectRoot() . '/cache/server.pid';
    }

    /**
     * @return array{status: string, message: string}
     */
    public function start(string $host, int $port): array
    {
        if ($this->isRunning()) {
            return ['status' => 'error', 'message' => "Le serveur est déjà en cours d'exécution."];
        }

        $command = sprintf(
            'php -S %s:%d -t %s > %s 2>&1 & echo $!',
            $host,
            $port,
            Config::getProjectRoot() . '/public',
            Config::getProjectRoot() . '/log/server.log'
        );

        $pid = exec($command);
        file_put_contents($this->pidFile, $pid);

        // Attendre un court instant pour que le serveur démarre
        sleep(1);

        return ['status' => 'success', 'message' => "Serveur démarré sur http://{$host}:{$port} (PID: {$pid})"];
    }

    /**
     * @return array{status: string, message: string}
     */
    public function stop(): array
    {
        if (!$this->isRunning()) {
            return ['status' => 'error', 'message' => "Le serveur n'est pas en cours d'exécution."];
        }

        $pid = (int) file_get_contents($this->pidFile);
        exec("kill {$pid}");
        unlink($this->pidFile);

        return ['status' => 'success', 'message' => "Serveur arrêté."];
    }

    /**
     * @return array{status: string, message: string}
     */
    public function getStatus(): array
    {
        if ($this->isRunning()) {
            $pid = (int) file_get_contents($this->pidFile);
            return ['status' => 'success', 'message' => "Le serveur est en cours d'exécution (PID: {$pid})."];
        }

        return ['status' => 'error', 'message' => "Le serveur est arrêté."];
    }

    /**
     * @return array{status: string, message: string}
     */
    public function getLogs(): array
    {
        $logFile = Config::getProjectRoot() . '/log/server.log';
        if (!file_exists($logFile)) {
            return ['status' => 'error', 'message' => "Aucun fichier de log trouvé."];
        }

        return ['status' => 'success', 'message' => file_get_contents($logFile)];
    }

    private function isRunning(): bool
    {
        if (!file_exists($this->pidFile)) {
            return false;
        }

        $pid = (int) file_get_contents($this->pidFile);
        return file_exists("/proc/{$pid}");
    }
}
