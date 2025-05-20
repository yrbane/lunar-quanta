<?php

declare(strict_types=1);

namespace App\Service\Core\Debug;

use App\Service\Command\ConsoleHelper;
use App\Service\Command\TableRenderer;

/**
 * Classe utilitaire de débogage permettant d’afficher joliment
 * le contenu de n’importe quelle variable en CLI ou en HTML.
 *
 * @since   0.0.1
 * @link    https://nethttp.net
 * @author  seb@
 */
final class Dumper
{
    /** Profondeur maximale avant affichage compact “…”. */
    private const MAX_DEPTH = 4;

    /** @var list<string> Sortie HTML déjà rendue, prête à être envoyée */
    private static array $htmlBuffer = [];
    private static bool  $shutdownRegistered = false;

    /**
     * Dump une ou plusieurs variables.
     *
     * @param mixed ...$vars
     */
    public static function dump(mixed ...$vars): void
    {
        $trace      = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $file       = $trace['file'] ?? 'n/a';
        $line       = $trace['line'] ?? 0;

        if (\PHP_SAPI === 'cli') {
            foreach ($vars as $var) {
                self::cliHeader($file, $line, $var);
                self::dumpCli($var);
            }
        } 
        /* ----------- HTML : on capture tout de suite le rendu ---------- */
        ob_start();
        echo '<div class="dump">';
        self::htmlHeader($file, $line, $var); // écrit dans le buffer de sortie
        foreach ($vars as $var) {
            echo '<div class="type">'.get_debug_type($var).'</div>';
            self::dumpHtml($var);   
        }
        echo '</div>';              // idem
        self::$htmlBuffer[] = ob_get_clean(); // on stocke la chaîne finale

        if (! self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function([self::class, 'flush']);
        }
    }

    /**
     * Force l’émission de tous les dumps HTML.
     * À appeler manuellement si besoin (`dump_flush();`).
     */
    public static function flush(): void
    {
        if (\PHP_SAPI === 'cli' || self::$htmlBuffer === []) {
            return;
        }

        echo implode('', self::$htmlBuffer);
        self::$htmlBuffer = [];
    }

    /* -----------------------------------------------------------------
     *  ENTÊTES
     * ----------------------------------------------------------------*/
    private static function cliHeader(string $file, int $line, mixed $var): void
    {
        $msg = sprintf('%s:%d  |  %s', $file, $line, get_debug_type($var));
        ConsoleHelper::subtitle($msg);
    }

    private static function htmlHeader(string $file, int $line, mixed $var): void
    {
        printf(
            '<pre class="header">%s:&nbsp;<span class="line">%d</span>&nbsp;</pre>',
            htmlspecialchars($file),
            $line
        );
    }



    /* ---------------------------------------------------------------------
     *  CLI
     * ------------------------------------------------------------------ */

    /**
     * Affichage en terminal : scalaires colorés, tableaux/objets via TableRenderer.
     */
    private static function dumpCli(mixed $var, int $level = 0, ?\SplObjectStorage $seen = null): void
    {
        $seen ??= new \SplObjectStorage();

        switch (gettype($var)) {
            case 'boolean':
                echo ConsoleHelper::color($var ? 'true' : 'false', $var ? '32' : '31').\PHP_EOL;
                break;

            case 'integer':
            case 'double':
                echo ConsoleHelper::color((string) $var, '33').\PHP_EOL;
                break;

            case 'string':
                echo ConsoleHelper::color('"'.$var.'"', '36').\PHP_EOL;
                break;

            case 'NULL':
                echo ConsoleHelper::color('null', '35').\PHP_EOL;
                break;

            case 'array':
                self::renderCliArray($var, $level, $seen);
                break;

            case 'object':
                self::renderCliObject($var, $level, $seen);
                break;

            default:
                echo ConsoleHelper::color('resource('.get_resource_type($var).')', '90').\PHP_EOL;
        }
    }

    /**
     * Rendu d’un tableau pour le terminal.
     */
    private static function renderCliArray(array $array, int $level, ?\SplObjectStorage $seen=null): void
    {
        if ($level >= self::MAX_DEPTH) {
            echo ConsoleHelper::color('[…]', '90').\PHP_EOL;

            return;
        }

        if ($array === []) {
            echo ConsoleHelper::color('[]', '90').\PHP_EOL;

            return;
        }

        $rows = [];
        foreach ($array as $k => $v) {
            ob_start();
            self::dumpCli($v, $level + 1, $seen);
            $rows[] = [
                'Clé'    => (string) $k,
                'Valeur' => trim(ob_get_clean()),
            ];
        }

        TableRenderer::renderSingleTable(
            $rows,
            [
                'columns'      => ['Clé' => 'Clé', 'Valeur' => 'Valeur'],
                'headerColor'  => '1;35',
                'rowColor'     => '0;37',
                'borderColor'  => '35',
            ],
        );
    }

    /**
     * Rendu d’un objet pour le terminal.
     */
    private static function renderCliObject(object $object, int $level, \SplObjectStorage $seen): void
    {
        if ($seen->contains($object)) {
            echo ConsoleHelper::color('[référence circulaire]', '90').\PHP_EOL;

            return;
        }
        $seen->attach($object);

        if ($level >= self::MAX_DEPTH) {
            echo ConsoleHelper::color($object::class.' { … }', '90').\PHP_EOL;

            return;
        }

        $ref   = new \ReflectionObject($object);
        $props = [];

        foreach ($ref->getProperties() as $prop) {
            $prop->setAccessible(true);
            $vis   = implode('|', \Reflection::getModifierNames($prop->getModifiers()));
            ob_start();
            self::dumpCli($prop->getValue($object), $level + 1, $seen);
            $props[] = [
                'Propriété'  => $prop->getName(),
                'Visibilité' => $vis,
                'Valeur'     => trim(ob_get_clean()),
            ];
        }

        ConsoleHelper::title($object::class);
        TableRenderer::renderSingleTable(
            $props,
            [
                'columns'      => ['Propriété' => 'Propriété', 'Visibilité' => 'Visibilité', 'Valeur' => 'Valeur'],
                'headerColor'  => '1;35',
                'rowColor'     => '0;37',
                'borderColor'  => '35',
            ],
        );
    }

    /* ---------------------------------------------------------------------
     *  HTML
     * ------------------------------------------------------------------ */

    /**
     * Affichage dans un navigateur : <pre> avec classes CSS.
     */
    private static function dumpHtml(mixed $var, int $level = 0, ?\SplObjectStorage $seen = null): void
    {
        $seen ??= new \SplObjectStorage();

        echo '<pre style="background:var(--bg-dark);color:var(--text-light);padding:var(--spacing);border-radius:var(--radius);overflow:auto;">';
        self::exportHtml($var, $level, $seen);
        echo '</pre>';
    }

    /**
     * Transformation récursive en HTML.
     */
    private static function exportHtml(mixed $var, int $level, ?\SplObjectStorage $seen): void
    {
        switch (gettype($var)) {
            case 'boolean':
                echo '<span class="bool">'.($var ? 'true' : 'false').'</span>';
                break;

            case 'integer':
            case 'double':
                echo '<span class="number">'.$var.'</span>';
                break;

            case 'string':
                echo '<span class="string">&quot;'.htmlspecialchars($var, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8').'&quot;</span>';
                break;

            case 'NULL':
                echo '<span class="null">null</span>';
                break;

            case 'array':
                $thisLevelIndent = str_repeat('&nbsp;&nbsp;', $level);
                echo '<span class="array">[</span><br>';
                foreach ($var as $k => $v) {
                    echo $thisLevelIndent.'&nbsp;&nbsp;<span class="key">'.htmlspecialchars((string) $k).'</span> =&gt; ';
                    self::exportHtml($v, $level + 1, $seen);
                    echo ',<br>';
                }
                echo $thisLevelIndent.'<span class="array">]</span>';
                break;

            case 'object':
                if ($seen->contains($var)) {
                    echo '<span class="object">'.$var::class.' {&nbsp;référence circulaire&nbsp;}</span>';
                    break;
                }
                $seen->attach($var);
                $ref = new \ReflectionObject($var);

                echo '<span class="object">'.$ref->getName().'</span> {<br>';
                foreach ($ref->getProperties() as $prop) {
                    $prop->setAccessible(true);
                    $vis = implode('|', \Reflection::getModifierNames($prop->getModifiers()));
                    $indent = str_repeat('&nbsp;&nbsp;', $level + 1);
                    echo $indent.'<span class="key">'.$prop->getName().'</span> (<span class="visibility">'.$vis.'</span>) =&gt; ';
                    self::exportHtml($prop->getValue($var), $level + 2, $seen);
                    echo ',<br>';
                }
                echo str_repeat('&nbsp;&nbsp;', $level).'}';
                break;

            default:
                echo '<span class="resource">resource('.htmlspecialchars((string) get_resource_type($var)).')</span>';
        }
    }
}
