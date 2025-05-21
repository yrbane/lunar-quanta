<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */

namespace App\Service\Command;

/**
 * Class ConsoleHelper.
 *
 * Fournit un ensemble de méthodes utilitaires pour améliorer
 * l'expérience utilisateur dans le terminal CLI du framework.
 * Inclut des fonctions de coloration ANSI, d'interaction utilisateur,
 * d'affichage stylé (titres, tableaux, barres de progression).
 */
class ConsoleHelper
{
    /**
     * Définit l'encodage par défaut utilisé pour les calculs de largeur dans les tableaux.
     * Peut être modifié si le terminal ne supporte pas UTF-8.
     */
    protected const TERMINAL_ENCODING = 'UTF-8';

    /** @var string Encodage actif utilisé dans le terminal */
    protected static string $terminalEncoding = self::TERMINAL_ENCODING;

    /**
     * Détermine si le terminal semble compatible UTF-8 via la variable d'environnement LANG.
     */
    public static function isUtf8Compatible(): bool
    {
        $lang = getenv('LANG');

        return is_string($lang) && str_contains($lang, 'UTF-8');
    }

    /**
     * Affiche un tableau formaté avec entêtes et lignes, coloré.
     * Gère correctement les largeurs de colonnes en UTF-8 (ou encodage forcé).
     *
     * @param array<int, string>                     $headers entêtes de colonnes
     * @param array<int, array<int|string, mixed>>   $rows    données à afficher (tableau de tableaux)
     */
    public static function table(array $headers, array $rows): void
    {
        $encoding = self::TERMINAL_ENCODING;
        $lengths = array_map(fn ($h) => mb_strwidth($h, $encoding), $headers);

        foreach ($rows as $row) {
            foreach ($row as $i => $col) {
                $colWidth = mb_strwidth((string) $col, $encoding);
                $lengths[$i] = max($lengths[$i] ?? 0, $colWidth);
            }
        }

        $line = '+'.implode('+', array_map(fn ($l) => str_repeat('-', $l + 2), $lengths))."+\n";
        echo self::color($line, '35');

        // En-têtes en magenta gras
        echo self::color('| ', '35');
        echo implode(self::color(' | ', '35'), array_map(
            fn ($h, $i) => self::color(str_pad($h, $lengths[$i] + mb_strlen($h) - mb_strwidth($h, $encoding)), '1;35'),
            $headers,
            array_keys($headers)
        ));
        echo self::color(" |\n", '35');

        echo self::color($line, '35');

        // Contenu des lignes (couleur claire)
        foreach ($rows as $index => $row) {
            echo self::color('| ', '34');
            echo implode(self::color(' | ', '34'), array_map(
                fn ($col, $i) => self::color(str_pad(
                    $col,
                    $lengths[$i] + mb_strlen($col) - mb_strwidth($col, $encoding)
                ), '0;37'),
                $row,
                array_keys($row)
            ));
            echo self::color(" |\n", '34');
        }

        echo self::color($line, '35');
    }

    /**
     * Permet de redéfinir dynamiquement l'encodage utilisé par la console.
     *
     * @param string $encoding ex: 'UTF-8', 'ISO-8859-1', etc
     */
    public static function setTerminalEncoding(string $encoding): void
    {
        self::$terminalEncoding = $encoding;
    }

    /**
     * Retourne l'encodage actuellement utilisé par la console.
     */
    public static function getTerminalEncoding(): string
    {
        return self::$terminalEncoding;
    }

    /**
     * Applique une couleur ANSI à une chaîne de texte.
     *
     * @param string $text      le texte à colorer
     * @param string $colorCode le code ANSI (ex: '32' pour vert)
     *
     * @return string le texte coloré
     */
    public static function color(string $text, string $colorCode): string
    {
        return "\033[{$colorCode}m{$text}\033[0m";
    }

    /**
     * Affiche un message de succès (vert).
     */
    public static function success(string $message): void
    {
        echo self::color("✅ {$message}\n", '32');
    }

    /**
     * Affiche un message d'erreur (rouge).
     */
    public static function error(string $message): void
    {
        echo self::color("❌ {$message}\n", '31');
    }

    /**
     * Affiche un message d'information (cyan).
     */
    public static function info(string $message): void
    {
        echo self::color("ℹ️  {$message}\n", '36');
    }

    /**
     * Pose une question simple à l'utilisateur.
     *
     * @param string      $question le message à afficher
     * @param null|string $default  valeur par défaut si l'utilisateur ne saisit rien
     *
     * @return string la réponse de l'utilisateur
     */
    public static function ask(string $question, ?string $default = null): string
    {
        $prompt = $default ? "{$question} [{$default}] " : "{$question} ";
        echo self::color($prompt, '33');
        $answer = trim(fgets(STDIN));

        return '' !== $answer ? $answer : ($default ?? '');
    }

    /**
     * Pose une question dont la réponse doit rester masquée (mot de passe).
     *
     * @return string réponse saisie sans affichage
     */
    public static function askHidden(string $question): string
    {
        echo self::color($question.' ', '33');
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir().'prompt_password.vbs';
            file_put_contents($vbscript, 'wscript.echo(InputBox("'.$question.'","",""))');
            $command = 'cscript //nologo '.escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
        } else {
            system('stty -echo');
            $password = trim(fgets(STDIN));
            system('stty echo');
        }
        echo "\n";

        return $password;
    }

    /**
     * Demande une confirmation Oui/Non à l'utilisateur.
     *
     * @param bool $default valeur par défaut (true = oui)
     *
     * @return bool résultat de la confirmation
     */
    public static function confirm(string $question, bool $default = true): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        $response = strtolower(trim(self::ask("{$question} [{$defaultText}]", $default ? 'y' : 'n')));

        return 'y' === $response;
    }

    /**
     * Affiche un titre stylisé encadré en ASCII et coloré.
     *
     * @param string $text le texte du titre
     */
    public static function title(string $text): void
    {
        $border = str_repeat('═', strlen($text) + 8);
        echo "\n".self::color('╔'.$border.'╗', '35')."\n";
        echo self::color('║    '.$text.'    ║', '1;35')."\n";
        echo self::color('╚'.$border.'╝', '35')."\n\n";
    }

    /**
     * Affiche un sous-titre stylisé.
     */
    public static function subtitle(string $text): void
    {
        echo self::color("➤ {$text}\n", '1;34');
    }

    /**
     * Affiche une barre de progression sur une ligne.
     *
     * @param int $current valeur actuelle
     * @param int $total   valeur finale
     * @param int $width   largeur de la barre
     */
    public static function progressBar(int $current, int $total, int $width = 50): void
    {
        $percent = ($current / $total);
        $filled = (int)round($percent * $width);
        $bar = str_repeat('█', $filled).str_repeat('░', $width - $filled);
        $percentDisplay = str_pad(round($percent * 100).'%', 4, ' ', STR_PAD_LEFT);
        echo "\r".self::color("Progress: [{$bar}] {$percentDisplay}", '36');
        if ($current === $total) {
            echo "\n";
        }
    }

    /**
     * Retourne une icône en fonction du type de fichier.
     *
     * @param string $path
     */
    public static function fileIcon(string $path): string
    {
        if (is_dir($path)) {
            return '📁';
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return match (strtolower($ext)) {
            'php' => '🐘',
            'json' => '🧾',
            'md' => '📘',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => '🖼️',
            'js', 'ts' => '📜',
            'html', 'htm' => '🌐',
            'css', 'scss' => '🎨',
            'lock' => '🔒',
            default => '📄',
        };
    }

    /**
     * Retourne une couleur ANSI suggérée pour un type de fichier.
     *
     * @param string $path
     */
    public static function fileColor(string $path): string
    {
        if (is_dir($path)) {
            return '1;34';
        } // dossier en bleu gras
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return match (strtolower($ext)) {
            'php' => '35',     // violet
            'json' => '33',    // jaune
            'md' => '36',      // cyan
            'jpg', 'jpeg', 'png', 'gif', 'webp' => '32', // vert
            'js', 'ts' => '33',
            'html', 'htm' => '31',
            'css', 'scss' => '36',
            'lock' => '90',    // gris foncé
            default => '0;37', // gris clair
        };
    }
}
