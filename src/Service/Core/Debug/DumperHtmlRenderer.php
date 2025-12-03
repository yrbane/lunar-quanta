<?php
/**
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Core\Debug;

use Lunar\Config\Config;

/**
 * Renderer HTML pour le Dumper.
 * Génère le HTML à partir de templates.
 */
final class DumperHtmlRenderer
{
    private const MAX_DEPTH = 4;

    private string $templatePath;

    /** @var array<string, string> */
    private array $templates = [];

    public function __construct()
    {
        $this->templatePath = Config::resolvePath('template/debug');
        $this->loadTemplates();
    }

    /**
     * Rend une variable en HTML.
     */
    public function render(mixed $var, string $file, int $line): string
    {
        $content = $this->export($var, 0, new \SplObjectStorage());

        return $this->renderTemplate('dump', [
            'file' => htmlspecialchars($file),
            'line' => $line,
            'type' => get_debug_type($var),
            'content' => $content,
        ]);
    }

    /**
     * Charge les templates depuis le dossier.
     */
    private function loadTemplates(): void
    {
        $files = glob($this->templatePath . '/*.html.tpl');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $name = basename($file, '.html.tpl');
            $this->templates[$name] = file_get_contents($file) ?: '';
        }
    }

    /**
     * Rend un template avec les variables.
     *
     * @param array<string, mixed> $vars
     */
    private function renderTemplate(string $name, array $vars): string
    {
        if (!isset($this->templates[$name])) {
            return '';
        }

        $html = $this->templates[$name];

        foreach ($vars as $key => $value) {
            $html = str_replace("[% {$key} %]", (string) $value, $html);
        }

        return $html;
    }

    /**
     * Exporte une variable en HTML.
     *
     * @param \SplObjectStorage<object, mixed> $seen
     */
    private function export(mixed $var, int $level, \SplObjectStorage $seen): string
    {
        return match (gettype($var)) {
            'boolean' => $this->renderBool($var),
            'integer', 'double' => $this->renderNumber($var),
            'string' => $this->renderString($var),
            'NULL' => $this->renderNull(),
            'array' => $this->renderArray($var, $level, $seen),
            'object' => $this->renderObject($var, $level, $seen),
            default => $this->renderResource($var),
        };
    }

    private function renderBool(bool $var): string
    {
        $value = $var ? 'true' : 'false';

        return "<span class=\"bool\">{$value}</span>";
    }

    private function renderNumber(int|float $var): string
    {
        return '<span class="number">' . $var . '</span>';
    }

    private function renderString(string $var): string
    {
        $escaped = htmlspecialchars($var, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<span class="string">&quot;' . $escaped . '&quot;</span>';
    }

    private function renderNull(): string
    {
        return '<span class="null">null</span>';
    }

    /**
     * @param array<int|string, mixed> $var
     * @param \SplObjectStorage<object, mixed> $seen
     */
    private function renderArray(array $var, int $level, \SplObjectStorage $seen): string
    {
        if ($level >= self::MAX_DEPTH) {
            return '<span class="null">[…]</span>';
        }

        if ($var === []) {
            return '<span class="array">[]</span>';
        }

        $indent = $this->indent($level);
        $innerIndent = $this->indent($level + 1);
        $html = '<span class="array">[</span>' . "\n";

        foreach ($var as $key => $value) {
            $keyHtml = '<span class="key">' . htmlspecialchars((string) $key) . '</span>';
            $valueHtml = $this->export($value, $level + 1, $seen);
            $html .= $innerIndent . $keyHtml . ' =&gt; ' . $valueHtml . ",\n";
        }

        $html .= $indent . '<span class="array">]</span>';

        return $html;
    }

    /**
     * @param \SplObjectStorage<object, mixed> $seen
     */
    private function renderObject(object $var, int $level, \SplObjectStorage $seen): string
    {
        if ($seen->contains($var)) {
            return '<span class="object">' . $var::class . ' { référence circulaire }</span>';
        }

        $seen->attach($var);

        if ($level >= self::MAX_DEPTH) {
            return '<span class="null">' . $var::class . ' { … }</span>';
        }

        $ref = new \ReflectionObject($var);
        $indent = $this->indent($level);
        $innerIndent = $this->indent($level + 1);

        $html = '<span class="object">' . $ref->getName() . '</span> {' . "\n";

        foreach ($ref->getProperties() as $prop) {
            $prop->setAccessible(true);
            $visibility = implode('|', \Reflection::getModifierNames($prop->getModifiers()));
            $valueHtml = $this->export($prop->getValue($var), $level + 1, $seen);

            $html .= $innerIndent
                . '<span class="key">' . $prop->getName() . '</span>'
                . ' <span class="visibility">(' . $visibility . ')</span>'
                . ' =&gt; ' . $valueHtml . ",\n";
        }

        $html .= $indent . '}';

        return $html;
    }

    private function renderResource(mixed $var): string
    {
        $type = is_resource($var) ? get_resource_type($var) : 'unknown';

        return '<span class="resource">resource(' . htmlspecialchars($type) . ')</span>';
    }

    /**
     * Génère l'indentation.
     */
    private function indent(int $level): string
    {
        return str_repeat('  ', $level);
    }
}
