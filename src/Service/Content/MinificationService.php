<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service de minification pour HTML, CSS et JavaScript.
 *
 * Minifie le code sans dépendances externes.
 *
 * @example
 * ```php
 * $minify = new MinificationService();
 *
 * // Minifier du HTML
 * $html = $minify->html($htmlContent);
 *
 * // Minifier du CSS
 * $css = $minify->css($cssContent);
 *
 * // Minifier du JavaScript
 * $js = $minify->js($jsContent);
 * ```
 */
final class MinificationService
{
    private bool $removeComments = true;
    private bool $removeWhitespace = true;
    private bool $preserveLineBreaks = false;
    private bool $collapseInlineStyles = false;

    /**
     * Active/désactive la suppression des commentaires.
     */
    public function setRemoveComments(bool $remove): self
    {
        $this->removeComments = $remove;
        return $this;
    }

    /**
     * Active/désactive la suppression des espaces.
     */
    public function setRemoveWhitespace(bool $remove): self
    {
        $this->removeWhitespace = $remove;
        return $this;
    }

    /**
     * Préserve les sauts de ligne.
     */
    public function setPreserveLineBreaks(bool $preserve): self
    {
        $this->preserveLineBreaks = $preserve;
        return $this;
    }

    /**
     * Collapse les styles inline.
     */
    public function setCollapseInlineStyles(bool $collapse): self
    {
        $this->collapseInlineStyles = $collapse;
        return $this;
    }

    /**
     * Minifie du HTML.
     */
    public function html(string $html): string
    {
        // Préserver les éléments pre et code
        $preserved = [];
        $html = preg_replace_callback(
            '/<(pre|code|script|style|textarea)([^>]*)>(.*?)<\/\1>/is',
            function ($matches) use (&$preserved) {
                $key = '___PRESERVED_' . count($preserved) . '___';
                $preserved[$key] = $matches[0];
                return $key;
            },
            $html
        );

        // Supprimer les commentaires HTML (sauf conditionnels IE)
        if ($this->removeComments) {
            $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
        }

        // Supprimer les espaces entre les balises
        if ($this->removeWhitespace) {
            $html = preg_replace('/>\s+</', '><', $html);
            $html = preg_replace('/\s+/', ' ', $html);
        }

        // Supprimer les espaces autour des attributs
        $html = preg_replace('/\s*=\s*/', '=', $html);

        // Supprimer les guillemets des attributs simples
        $html = preg_replace('/="([^"\s>]+)"(?=\s|>)/', '=$1', $html);

        // Supprimer les attributs booléens redondants
        $html = preg_replace('/\s+(disabled|checked|selected|readonly|required|autofocus|autoplay|muted|loop|controls|multiple)=["\'](?:disabled|checked|selected|readonly|required|autofocus|autoplay|muted|loop|controls|multiple)["\']/', ' $1', $html);

        // Minifier les styles inline
        if ($this->collapseInlineStyles) {
            $html = preg_replace_callback(
                '/style="([^"]+)"/i',
                fn ($m) => 'style="' . $this->css($m[1]) . '"',
                $html
            );
        }

        // Restaurer les éléments préservés
        foreach ($preserved as $key => $value) {
            $html = str_replace($key, $value, $html);
        }

        return trim($html);
    }

    /**
     * Minifie du CSS.
     */
    public function css(string $css): string
    {
        // Supprimer les commentaires
        if ($this->removeComments) {
            $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        }

        // Supprimer les espaces inutiles
        if ($this->removeWhitespace) {
            // Normaliser les espaces
            $css = preg_replace('/\s+/', ' ', $css);

            // Supprimer les espaces autour des caractères spéciaux
            $css = preg_replace('/\s*([{};:,>+~])\s*/', '$1', $css);

            // Supprimer les espaces après les parenthèses ouvrantes et avant les fermantes
            $css = preg_replace('/\(\s+/', '(', $css);
            $css = preg_replace('/\s+\)/', ')', $css);
        }

        // Supprimer le point-virgule avant l'accolade fermante
        $css = str_replace(';}', '}', $css);

        // Supprimer les zéros inutiles
        $css = preg_replace('/(:|\s)0px/', '${1}0', $css);
        $css = preg_replace('/(:|\s)0\.(\d+)/', '${1}.${2}', $css);

        // Raccourcir les codes couleur hex
        $css = preg_replace('/#([0-9a-fA-F])\1([0-9a-fA-F])\2([0-9a-fA-F])\3(?=[;\s}])/', '#$1$2$3', $css);

        // Supprimer les unités pour la valeur 0
        $css = preg_replace('/(:|\s)0(em|rem|px|%|vh|vw|vmin|vmax|ch|ex|cm|mm|in|pt|pc)/', '${1}0', $css);

        return trim($css);
    }

    /**
     * Minifie du JavaScript (basique).
     */
    public function js(string $js): string
    {
        // Préserver les chaînes de caractères et les regex
        $preserved = [];
        $js = preg_replace_callback(
            '/("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'|`(?:[^`\\\\]|\\\\.)*`|\/(?![*\/])(?:[^\/\\\\]|\\\\.)*\/[gimsuy]*)/',
            function ($matches) use (&$preserved) {
                $key = '___STRING_' . count($preserved) . '___';
                $preserved[$key] = $matches[0];
                return $key;
            },
            $js
        );

        // Supprimer les commentaires de ligne
        if ($this->removeComments) {
            $js = preg_replace('/\/\/.*$/m', '', $js);
            // Supprimer les commentaires multi-lignes
            $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        }

        // Supprimer les espaces inutiles
        if ($this->removeWhitespace) {
            // Normaliser les espaces
            $js = preg_replace('/\s+/', ' ', $js);

            // Supprimer les espaces autour des opérateurs
            $js = preg_replace('/\s*([{}()\[\];,=+\-*\/<>!&|?:])\s*/', '$1', $js);

            // Restaurer l'espace après les mots-clés
            $keywords = ['return', 'var', 'let', 'const', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'break', 'continue', 'function', 'class', 'new', 'typeof', 'instanceof', 'in', 'of', 'throw', 'try', 'catch', 'finally', 'import', 'export', 'from', 'async', 'await'];
            foreach ($keywords as $keyword) {
                $js = preg_replace('/\b(' . $keyword . ')([^a-zA-Z0-9_$])/', '$1 $2', $js);
            }
        }

        // Restaurer les chaînes préservées
        foreach ($preserved as $key => $value) {
            $js = str_replace($key, $value, $js);
        }

        return trim($js);
    }

    /**
     * Minifie du JSON.
     */
    public function json(string $json): string
    {
        $decoded = json_decode($json);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return $json;
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Minifie un fichier selon son extension.
     */
    public function file(string $content, string $type): string
    {
        return match (strtolower($type)) {
            'html', 'htm' => $this->html($content),
            'css' => $this->css($content),
            'js', 'javascript' => $this->js($content),
            'json' => $this->json($content),
            default => $content,
        };
    }

    /**
     * Calcule le taux de compression.
     */
    public function getCompressionRatio(string $original, string $minified): float
    {
        $originalSize = strlen($original);
        if ($originalSize === 0) {
            return 0.0;
        }

        $minifiedSize = strlen($minified);
        return round(100 - ($minifiedSize / $originalSize * 100), 2);
    }

    /**
     * Minifie et retourne les statistiques.
     *
     * @return array{minified: string, original_size: int, minified_size: int, savings: float}
     */
    public function minifyWithStats(string $content, string $type): array
    {
        $originalSize = strlen($content);
        $minified = $this->file($content, $type);
        $minifiedSize = strlen($minified);

        return [
            'minified' => $minified,
            'original_size' => $originalSize,
            'minified_size' => $minifiedSize,
            'savings' => $this->getCompressionRatio($content, $minified),
        ];
    }

    /**
     * Traite un fichier et retourne le contenu minifié.
     */
    public function minifyFile(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return $this->file($content, $extension);
    }

    /**
     * Sauvegarde un fichier minifié.
     */
    public function minifyAndSave(string $sourcePath, ?string $destPath = null): bool
    {
        $minified = $this->minifyFile($sourcePath);
        if ($minified === null) {
            return false;
        }

        if ($destPath === null) {
            $pathInfo = pathinfo($sourcePath);
            $destPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.min.' . $pathInfo['extension'];
        }

        return file_put_contents($destPath, $minified) !== false;
    }
}
