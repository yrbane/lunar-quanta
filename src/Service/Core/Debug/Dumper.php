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

namespace Lunar\Service\Core\Debug;

use Lunar\Cli\Helper\ConsoleHelper;
use Lunar\Cli\Helper\TableRenderer;

/**
 * Classe utilitaire de débogage permettant d'afficher joliment
 * le contenu de n'importe quelle variable en CLI ou en HTML.
 */
final class Dumper
{
    private const MAX_DEPTH = 4;

    /** @var list<string> Buffer HTML */
    private static array $htmlBuffer = [];
    private static bool $shutdownRegistered = false;
    private static ?DumperHtmlRenderer $htmlRenderer = null;

    /**
     * Dump une ou plusieurs variables.
     */
    public static function dump(mixed ...$vars): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $file = $trace['file'] ?? 'n/a';
        $line = $trace['line'] ?? 0;

        if (PHP_SAPI === 'cli') {
            foreach ($vars as $var) {
                self::cliHeader($file, $line, $var);
                self::dumpCli($var);
            }

            return;
        }

        // HTML : utiliser le renderer
        self::$htmlRenderer ??= new DumperHtmlRenderer();

        foreach ($vars as $var) {
            self::$htmlBuffer[] = self::$htmlRenderer->render($var, $file, $line);
        }

        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function([self::class, 'flush']);
        }
    }

    /**
     * Force l'émission de tous les dumps HTML.
     */
    public static function flush(): void
    {
        if (PHP_SAPI === 'cli' || [] === self::$htmlBuffer) {
            return;
        }

        echo implode('', self::$htmlBuffer);
        self::$htmlBuffer = [];
    }

    /* -----------------------------------------------------------------
     *  CLI - Entête
     * ----------------------------------------------------------------*/

    private static function cliHeader(string $file, int $line, mixed $var): void
    {
        $msg = sprintf('%s:%d  |  %s', $file, $line, get_debug_type($var));
        ConsoleHelper::subtitle($msg);
    }

    /* -----------------------------------------------------------------
     *  CLI - Dump
     * ----------------------------------------------------------------*/

    /**
     * @param null|\SplObjectStorage<object, mixed> $seen
     */
    private static function dumpCli(mixed $var, int $level = 0, ?\SplObjectStorage $seen = null): void
    {
        $seen ??= new \SplObjectStorage();

        match (gettype($var)) {
            'boolean' => self::printBool($var),
            'integer', 'double' => self::printNumber($var),
            'string' => self::printString($var),
            'NULL' => self::printNull(),
            'array' => self::renderCliArray($var, $level, $seen),
            'object' => self::renderCliObject($var, $level, $seen),
            default => self::printResource($var),
        };
    }

    private static function printBool(bool $var): void
    {
        echo ConsoleHelper::color($var ? 'true' : 'false', $var ? '32' : '31').PHP_EOL;
    }

    private static function printNumber(float|int $var): void
    {
        echo ConsoleHelper::color((string) $var, '33').PHP_EOL;
    }

    private static function printString(string $var): void
    {
        echo ConsoleHelper::color('"'.$var.'"', '36').PHP_EOL;
    }

    private static function printNull(): void
    {
        echo ConsoleHelper::color('null', '35').PHP_EOL;
    }

    private static function printResource(mixed $var): void
    {
        $type = is_resource($var) ? get_resource_type($var) : 'unknown';
        echo ConsoleHelper::color('resource('.$type.')', '90').PHP_EOL;
    }

    /**
     * @param array<int|string, mixed>              $array
     * @param null|\SplObjectStorage<object, mixed> $seen
     */
    private static function renderCliArray(array $array, int $level, ?\SplObjectStorage $seen = null): void
    {
        if ($level >= self::MAX_DEPTH) {
            echo ConsoleHelper::color('[…]', '90').PHP_EOL;

            return;
        }

        if ([] === $array) {
            echo ConsoleHelper::color('[]', '90').PHP_EOL;

            return;
        }

        $rows = [];
        foreach ($array as $k => $v) {
            ob_start();
            self::dumpCli($v, $level + 1, $seen);
            $rows[] = [
                'Clé' => (string) $k,
                'Valeur' => trim((string) ob_get_clean()),
            ];
        }

        TableRenderer::renderSingleTable($rows, [
            'columns' => ['Clé' => 'Clé', 'Valeur' => 'Valeur'],
            'headerColor' => '1;35',
            'rowColor' => '0;37',
            'borderColor' => '35',
        ]);
    }

    /**
     * @param \SplObjectStorage<object, mixed> $seen
     */
    private static function renderCliObject(object $object, int $level, \SplObjectStorage $seen): void
    {
        if ($seen->contains($object)) {
            echo ConsoleHelper::color('[référence circulaire]', '90').PHP_EOL;

            return;
        }
        $seen->attach($object);

        if ($level >= self::MAX_DEPTH) {
            echo ConsoleHelper::color($object::class.' { … }', '90').PHP_EOL;

            return;
        }

        $ref = new \ReflectionObject($object);
        $props = [];

        foreach ($ref->getProperties() as $prop) {
            $prop->setAccessible(true);
            $vis = implode('|', \Reflection::getModifierNames($prop->getModifiers()));
            ob_start();
            self::dumpCli($prop->getValue($object), $level + 1, $seen);
            $props[] = [
                'Propriété' => $prop->getName(),
                'Visibilité' => $vis,
                'Valeur' => trim((string) ob_get_clean()),
            ];
        }

        ConsoleHelper::title($object::class);
        TableRenderer::renderSingleTable($props, [
            'columns' => ['Propriété' => 'Propriété', 'Visibilité' => 'Visibilité', 'Valeur' => 'Valeur'],
            'headerColor' => '1;35',
            'rowColor' => '0;37',
            'borderColor' => '35',
        ]);
    }
}
