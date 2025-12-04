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

use Lunar\Cli\AbstractCommand;
use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Cli\Helper\ConsoleHelper as C;
use Lunar\Config\Config;

/**
 * Fait défiler en couleur les logs du serveur.
 */
#[Command(
    name: 'server:logs',
    description: 'Affiche les logs du serveur en couleur'
)]
class ServerLogsCommand extends AbstractCommand implements CommandInterface
{
    // Codes ANSI
    private const RESET = "\033[0m";
    private const BOLD = "\033[1m";
    private const DIM = "\033[2m";

    // Couleurs
    private const RED = "\033[31m";
    private const GREEN = "\033[32m";
    private const YELLOW = "\033[33m";
    private const BLUE = "\033[34m";
    private const MAGENTA = "\033[35m";
    private const CYAN = "\033[36m";
    private const WHITE = "\033[37m";
    private const GRAY = "\033[90m";

    // Couleurs de fond
    private const BG_RED = "\033[41m";

    public function execute(array $args): int
    {
        $logFile = Config::getProjectRoot().'/log/server.log';

        if (!\is_file($logFile)) {
            C::error("Fichier de logs introuvable : {$logFile}");
            C::warning("Lancez d'abord le serveur avec : bin/console server:start");

            return 1;
        }

        // Nombre de lignes à afficher au démarrage
        $namedArgs = $this->parseNamedArgs($args);
        $lines = (int) $this->getOptionValue($namedArgs, 'lines', '20');
        $follow = !$this->hasFlag($args, 'no-follow');

        C::subtitle('Logs du serveur PHP');
        echo self::DIM."Fichier : {$logFile}".self::RESET."\n";
        if ($follow) {
            echo self::DIM.'Ctrl+C pour quitter'.self::RESET."\n";
        }
        echo "\n";

        // Afficher la légende
        $this->showLegend();

        // Afficher les dernières lignes existantes
        $this->showLastLines($logFile, $lines);

        // Si --no-follow, on s'arrête là
        if (!$follow) {
            return 0;
        }

        echo self::DIM.'─── Suivi en temps réel ───'.self::RESET."\n";

        // Ouvrir le fichier en mode lecture pour suivre
        $handle = fopen($logFile, 'r');
        if (!$handle) {
            C::error("Impossible d'ouvrir le fichier de logs");

            return 1;
        }

        // Aller à la fin du fichier
        fseek($handle, 0, SEEK_END);

        // Boucle infinie pour suivre le fichier (Ctrl+C pour quitter)
        // @phpstan-ignore while.alwaysTrue
        while (true) {
            $line = fgets($handle);
            if (false !== $line) {
                echo $this->colorizeLine(trim($line))."\n";
            } else {
                // Attendre un peu avant de réessayer
                usleep(100000); // 100ms
                clearstatcache(true, $logFile);
            }
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : server:logs
Affiche les logs du serveur PHP en couleur avec suivi en temps réel.

Usage :
  bin/console server:logs [options]

Options :
  --lines=N    Nombre de lignes à afficher au démarrage (défaut: 20)
  --no-follow  Affiche les logs et quitte (sans suivi)
  --help       Affiche cette aide

Coloration :
  - Codes HTTP : 2xx (vert), 3xx (cyan), 4xx (jaune), 5xx (rouge)
  - Méthodes : GET (vert), POST (bleu), PUT (jaune), DELETE (rouge)
  - Erreurs PHP : fond rouge
  - Timestamps : gris

Exemples :
  bin/console server:logs              # 20 dernières lignes + suivi
  bin/console server:logs --lines=50   # 50 dernières lignes + suivi
  bin/console server:logs --no-follow  # Affiche et quitte

HELP;
    }

    /**
     * Affiche les N dernières lignes du fichier.
     */
    private function showLastLines(string $file, int $count): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $lines) {
            return;
        }

        $lastLines = array_slice($lines, -$count);
        foreach ($lastLines as $line) {
            echo $this->colorizeLine($line)."\n";
        }
    }

    /**
     * Affiche la légende des couleurs.
     */
    private function showLegend(): void
    {
        echo self::DIM."─────────────────────────────────────────────────\n".self::RESET;
        echo self::BOLD.'Légende : '.self::RESET;
        echo self::GREEN.'2xx '.self::RESET;
        echo self::CYAN.'3xx '.self::RESET;
        echo self::YELLOW.'4xx '.self::RESET;
        echo self::RED.'5xx '.self::RESET;
        echo self::DIM.'| '.self::RESET;
        echo self::GREEN.'GET '.self::RESET;
        echo self::BLUE.'POST '.self::RESET;
        echo self::YELLOW.'PUT '.self::RESET;
        echo self::RED.'DELETE'.self::RESET;
        echo "\n";
        echo self::DIM."─────────────────────────────────────────────────\n".self::RESET;
    }

    /**
     * Colorise une ligne de log.
     */
    private function colorizeLine(string $line): string
    {
        // Format typique du serveur PHP: [Date] IP:Port [Code]: Method URI
        // Exemple: [Wed Dec  3 16:00:00 2025] 127.0.0.1:54321 [200]: GET /index.php

        // Coloriser la date/heure
        $line = (string) preg_replace(
            '/^\[([^\]]+)\]/',
            self::GRAY.'[$1]'.self::RESET,
            $line
        );

        // Coloriser l'IP:Port
        $line = (string) preg_replace(
            '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d+)/',
            self::DIM.'$1'.self::RESET,
            $line
        );

        // Coloriser les codes de statut HTTP
        $line = (string) preg_replace_callback(
            '/\[(\d{3})\]:/',
            fn ($m) => '['.$this->colorizeStatusCode($m[1]).']:',
            $line
        );

        // Coloriser les méthodes HTTP
        $line = (string) preg_replace_callback(
            '/\b(GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS)\b/',
            fn ($m) => $this->colorizeMethod($m[1]),
            $line
        );

        // Coloriser les URLs/chemins
        $line = (string) preg_replace(
            '#(/[^\s]*)#',
            self::CYAN.'$1'.self::RESET,
            $line
        );

        // Coloriser les erreurs PHP
        $line = (string) preg_replace(
            '/(PHP Fatal error|PHP Warning|PHP Notice|PHP Parse error)/i',
            self::BG_RED.self::WHITE.self::BOLD.' $1 '.self::RESET,
            $line
        );

        // Coloriser "Accepted" et "Closing"
        $line = (string) preg_replace(
            '/\bAccepted\b/',
            self::GREEN.'Accepted'.self::RESET,
            $line
        );

        return (string) preg_replace(
            '/\bClosing\b/',
            self::YELLOW.'Closing'.self::RESET,
            $line
        );
    }

    /**
     * Colorise un code de statut HTTP.
     */
    private function colorizeStatusCode(string $code): string
    {
        $color = match (true) {
            str_starts_with($code, '2') => self::GREEN.self::BOLD,
            str_starts_with($code, '3') => self::CYAN.self::BOLD,
            str_starts_with($code, '4') => self::YELLOW.self::BOLD,
            str_starts_with($code, '5') => self::RED.self::BOLD,
            default => self::WHITE,
        };

        return $color.$code.self::RESET;
    }

    /**
     * Colorise une méthode HTTP.
     */
    private function colorizeMethod(string $method): string
    {
        $color = match ($method) {
            'GET' => self::GREEN,
            'POST' => self::BLUE,
            'PUT', 'PATCH' => self::YELLOW,
            'DELETE' => self::RED,
            'HEAD', 'OPTIONS' => self::MAGENTA,
            default => self::WHITE,
        };

        return $color.self::BOLD.$method.self::RESET;
    }
}
