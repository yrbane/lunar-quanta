<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Highlight de code pour les blocs de code dans le contenu.
 *
 * Ajoute des classes CSS pour la coloration syntaxique sans dépendances externes.
 *
 * @example
 * ```php
 * $highlighter = new CodeHighlighter();
 *
 * // Highlighter un bloc de code
 * $html = $highlighter->highlight($code, 'php');
 *
 * // Traiter tout le contenu HTML
 * $html = $highlighter->processContent($htmlWithCodeBlocks);
 * ```
 */
final class CodeHighlighter
{
    /** @var array<string, array<string, string>> */
    private const PATTERNS = [
        'php' => [
            'comment_single' => '/\/\/.*$/m',
            'comment_multi' => '/\/\*[\s\S]*?\*\//',
            'string_double' => '/"(?:[^"\\\\]|\\\\.)*"/',
            'string_single' => "/\'(?:[^\'\\\\]|\\\\.)*\'/",
            'keyword' => '/\b(abstract|and|array|as|break|callable|case|catch|class|clone|const|continue|declare|default|die|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|eval|exit|extends|final|finally|fn|for|foreach|function|global|goto|if|implements|include|include_once|instanceof|insteadof|interface|isset|list|match|namespace|new|or|print|private|protected|public|readonly|require|require_once|return|static|switch|throw|trait|try|unset|use|var|while|xor|yield)\b/',
            'type' => '/\b(array|bool|callable|float|int|iterable|mixed|never|null|numeric|object|resource|self|static|string|void|true|false)\b/',
            'variable' => '/\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*/',
            'number' => '/\b\d+\.?\d*\b/',
            'function' => '/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/',
        ],
        'javascript' => [
            'comment_single' => '/\/\/.*$/m',
            'comment_multi' => '/\/\*[\s\S]*?\*\//',
            'string_double' => '/"(?:[^"\\\\]|\\\\.)*"/',
            'string_single' => "/\'(?:[^\'\\\\]|\\\\.)*\'/",
            'string_template' => '/`(?:[^`\\\\]|\\\\.)*`/',
            'keyword' => '/\b(async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|export|extends|finally|for|from|function|if|import|in|instanceof|let|new|of|return|static|super|switch|this|throw|try|typeof|var|void|while|with|yield)\b/',
            'boolean' => '/\b(true|false|null|undefined|NaN|Infinity)\b/',
            'number' => '/\b\d+\.?\d*\b/',
            'function' => '/\b([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(/',
        ],
        'css' => [
            'comment' => '/\/\*[\s\S]*?\*\//',
            'selector' => '/[^{}]+(?=\s*\{)/',
            'property' => '/([a-z-]+)\s*:/i',
            'value' => '/:\s*([^;{}]+)/i',
            'unit' => '/\b\d+\.?\d*(px|em|rem|%|vh|vw|vmin|vmax|ch|ex|cm|mm|in|pt|pc|deg|rad|grad|turn|s|ms|Hz|kHz)\b/i',
            'color' => '/#[a-fA-F0-9]{3,8}\b|rgb\([^)]+\)|hsl\([^)]+\)/',
        ],
        'html' => [
            'comment' => '/<!--[\s\S]*?-->/',
            'doctype' => '/<!DOCTYPE[^>]*>/i',
            'tag' => '/<\/?[a-zA-Z][a-zA-Z0-9-]*/',
            'attribute' => '/\s([a-zA-Z-]+)(?==)/',
            'string' => '/"[^"]*"|\'[^\']*\'/',
        ],
        'sql' => [
            'comment_single' => '/--.*$/m',
            'comment_multi' => '/\/\*[\s\S]*?\*\//',
            'keyword' => '/\b(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|AND|OR|NOT|IN|LIKE|ORDER|BY|GROUP|HAVING|LIMIT|OFFSET|JOIN|LEFT|RIGHT|INNER|OUTER|ON|AS|CREATE|TABLE|INDEX|DROP|ALTER|ADD|COLUMN|SET|VALUES|INTO|NULL|IS|DISTINCT|UNION|ALL|BETWEEN|EXISTS|CASE|WHEN|THEN|ELSE|END|COUNT|SUM|AVG|MIN|MAX|PRIMARY|KEY|FOREIGN|REFERENCES|CONSTRAINT|DEFAULT|UNIQUE)\b/i',
            'string' => "/\'(?:[^\'\\\\]|\\\\.)*\'/",
            'number' => '/\b\d+\.?\d*\b/',
        ],
        'json' => [
            'string' => '/"(?:[^"\\\\]|\\\\.)*"/',
            'number' => '/-?\b\d+\.?\d*([eE][+-]?\d+)?\b/',
            'boolean' => '/\b(true|false|null)\b/',
        ],
        'bash' => [
            'comment' => '/#.*$/m',
            'string_double' => '/"(?:[^"\\\\]|\\\\.)*"/',
            'string_single' => "/\'[^\']*\'/",
            'variable' => '/\$\{?[a-zA-Z_][a-zA-Z0-9_]*\}?/',
            'keyword' => '/\b(if|then|else|elif|fi|for|while|do|done|case|esac|function|in|select|until|return|exit|break|continue|export|source)\b/',
            'command' => '/\b(echo|cd|ls|pwd|mkdir|rm|cp|mv|cat|grep|find|sed|awk|chmod|chown|sudo|apt|yum|npm|yarn|git|docker|curl|wget)\b/',
        ],
    ];

    /**
     * Highlight un bloc de code.
     */
    public function highlight(string $code, string $language): string
    {
        $language = strtolower($language);

        // Alias de langages
        $aliases = [
            'js' => 'javascript',
            'ts' => 'javascript',
            'typescript' => 'javascript',
            'sh' => 'bash',
            'shell' => 'bash',
            'zsh' => 'bash',
            'htm' => 'html',
            'xml' => 'html',
            'mysql' => 'sql',
            'pgsql' => 'sql',
            'postgresql' => 'sql',
        ];

        $language = $aliases[$language] ?? $language;

        if (!isset(self::PATTERNS[$language])) {
            // Pas de patterns pour ce langage, échapper seulement
            return '<pre><code class="language-' . $this->escape($language) . '">'
                . $this->escape($code)
                . '</code></pre>';
        }

        $highlighted = $this->applyPatterns($code, self::PATTERNS[$language]);

        return '<pre><code class="language-' . $this->escape($language) . '">'
            . $highlighted
            . '</code></pre>';
    }

    /**
     * Traite le contenu HTML pour highlighter les blocs de code.
     */
    public function processContent(string $html): string
    {
        // Traiter les blocs <pre><code class="language-xxx">
        $html = preg_replace_callback(
            '/<pre><code class="language-([^"]+)">([\s\S]*?)<\/code><\/pre>/i',
            function ($matches) {
                $language = $matches[1];
                $code = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $this->highlight($code, $language);
            },
            $html
        );

        // Traiter les blocs Markdown fenced code (```language)
        $html = preg_replace_callback(
            '/```(\w+)?\n([\s\S]*?)```/',
            function ($matches) {
                $language = $matches[1] ?: 'text';
                $code = $matches[2];
                return $this->highlight(rtrim($code), $language);
            },
            $html
        );

        return $html;
    }

    /**
     * Applique les patterns de highlighting.
     */
    private function applyPatterns(string $code, array $patterns): string
    {
        // Échapper le HTML d'abord
        $code = $this->escape($code);

        // Créer des placeholders pour éviter les conflits
        $placeholders = [];
        $placeholderIndex = 0;

        foreach ($patterns as $type => $pattern) {
            $code = preg_replace_callback($pattern, function ($matches) use (&$placeholders, &$placeholderIndex, $type) {
                $match = $matches[1] ?? $matches[0];
                $placeholder = "<<PLACEHOLDER_{$placeholderIndex}>>";
                $placeholders[$placeholder] = '<span class="hl-' . $type . '">' . $match . '</span>';
                $placeholderIndex++;
                return $placeholder;
            }, $code);
        }

        // Restaurer les placeholders
        foreach ($placeholders as $placeholder => $replacement) {
            $code = str_replace($placeholder, $replacement, $code);
        }

        return $code;
    }

    /**
     * Génère le CSS pour le highlighting.
     */
    public function generateCss(string $theme = 'dark'): string
    {
        if ($theme === 'dark') {
            return <<<'CSS'
pre code { background: #1e1e1e; color: #d4d4d4; display: block; padding: 1em; overflow-x: auto; }
.hl-comment_single, .hl-comment_multi, .hl-comment { color: #6a9955; font-style: italic; }
.hl-string_single, .hl-string_double, .hl-string, .hl-string_template { color: #ce9178; }
.hl-keyword { color: #569cd6; font-weight: bold; }
.hl-type, .hl-boolean { color: #4ec9b0; }
.hl-variable { color: #9cdcfe; }
.hl-number, .hl-unit { color: #b5cea8; }
.hl-function, .hl-command { color: #dcdcaa; }
.hl-selector { color: #d7ba7d; }
.hl-property { color: #9cdcfe; }
.hl-value { color: #ce9178; }
.hl-color { color: #ce9178; }
.hl-tag { color: #569cd6; }
.hl-attribute { color: #9cdcfe; }
.hl-doctype { color: #808080; }
CSS;
        }

        return <<<'CSS'
pre code { background: #f5f5f5; color: #333; display: block; padding: 1em; overflow-x: auto; }
.hl-comment_single, .hl-comment_multi, .hl-comment { color: #6a737d; font-style: italic; }
.hl-string_single, .hl-string_double, .hl-string, .hl-string_template { color: #032f62; }
.hl-keyword { color: #d73a49; font-weight: bold; }
.hl-type, .hl-boolean { color: #005cc5; }
.hl-variable { color: #e36209; }
.hl-number, .hl-unit { color: #005cc5; }
.hl-function, .hl-command { color: #6f42c1; }
.hl-selector { color: #22863a; }
.hl-property { color: #005cc5; }
.hl-value { color: #032f62; }
.hl-color { color: #032f62; }
.hl-tag { color: #22863a; }
.hl-attribute { color: #6f42c1; }
.hl-doctype { color: #6a737d; }
CSS;
    }

    /**
     * Échappe le contenu HTML.
     */
    private function escape(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
