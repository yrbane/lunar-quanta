<?php
/**
 * Helper de console de compatibilite pour le framework.
 * Etend le helper du package lunar/cli avec des fonctionnalites specifiques.
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace App\Service\Command;

use Lunar\Cli\Helper\ConsoleHelper as LunarConsoleHelper;

/**
 * Class ConsoleHelper.
 *
 * Etend Lunar\Cli\Helper\ConsoleHelper avec des fonctionnalites
 * specifiques au framework (icones de fichiers, etc.).
 */
class ConsoleHelper extends LunarConsoleHelper
{
    /**
     * Affiche un titre stylise encadre en ASCII et colore.
     * Surcharge pour utiliser les caracteres Unicode.
     *
     * @param string $text le texte du titre
     */
    public static function title(string $text): void
    {
        $border = str_repeat('═', strlen($text) + 8);
        echo "\n" . self::color('╔' . $border . '╗', '35') . "\n";
        echo self::color('║    ' . $text . '    ║', '1;35') . "\n";
        echo self::color('╚' . $border . '╝', '35') . "\n\n";
    }

    /**
     * Affiche un sous-titre stylise.
     */
    public static function subtitle(string $text): void
    {
        echo self::color("➤ {$text}\n", '1;34');
    }

    /**
     * Retourne une icone en fonction du type de fichier.
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
     * Retourne une couleur ANSI suggeree pour un type de fichier.
     */
    public static function fileColor(string $path): string
    {
        if (is_dir($path)) {
            return '1;34';
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return match (strtolower($ext)) {
            'php' => '35',
            'json' => '33',
            'md' => '36',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => '32',
            'js', 'ts' => '33',
            'html', 'htm' => '31',
            'css', 'scss' => '36',
            'lock' => '90',
            default => '0;37',
        };
    }
}
