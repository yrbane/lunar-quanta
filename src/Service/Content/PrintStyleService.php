<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service pour les styles d'impression.
 *
 * Génère les CSS optimisés pour l'impression des articles.
 *
 * @example
 * ```php
 * $print = new PrintStyleService();
 *
 * // Générer le CSS d'impression
 * $css = $print->generateCss();
 *
 * // Générer la balise link
 * $link = $print->generateLink();
 * ```
 */
final class PrintStyleService
{
    private string $fontFamily = 'Georgia, serif';
    private string $fontSize = '12pt';
    private string $lineHeight = '1.5';
    private bool $showUrls = true;
    private bool $showPageNumbers = true;
    private bool $avoidBreaksInCode = true;
    /** @var string[] */
    private array $hideSelectors = [
        '.nav', '.navbar', '.navigation',
        '.sidebar', '.aside',
        '.footer', 'footer',
        '.header', 'header:not(.article-header)',
        '.comments', '.comment-form',
        '.social-share', '.share-buttons',
        '.advertisement', '.ad', '.ads',
        '.related-posts', '.related-articles',
        '.cookie-notice', '.cookie-banner',
        'button', '.btn',
        'video', 'iframe', 'audio',
        '.no-print',
    ];

    /**
     * Définit la police.
     */
    public function setFontFamily(string $font): self
    {
        $this->fontFamily = $font;
        return $this;
    }

    /**
     * Définit la taille de police.
     */
    public function setFontSize(string $size): self
    {
        $this->fontSize = $size;
        return $this;
    }

    /**
     * Définit la hauteur de ligne.
     */
    public function setLineHeight(string $height): self
    {
        $this->lineHeight = $height;
        return $this;
    }

    /**
     * Active/désactive l'affichage des URLs.
     */
    public function setShowUrls(bool $show): self
    {
        $this->showUrls = $show;
        return $this;
    }

    /**
     * Active/désactive les numéros de page.
     */
    public function setShowPageNumbers(bool $show): self
    {
        $this->showPageNumbers = $show;
        return $this;
    }

    /**
     * Active/désactive la protection des blocs de code.
     */
    public function setAvoidBreaksInCode(bool $avoid): self
    {
        $this->avoidBreaksInCode = $avoid;
        return $this;
    }

    /**
     * Définit les sélecteurs à masquer.
     *
     * @param string[] $selectors
     */
    public function setHideSelectors(array $selectors): self
    {
        $this->hideSelectors = $selectors;
        return $this;
    }

    /**
     * Ajoute des sélecteurs à masquer.
     *
     * @param string[] $selectors
     */
    public function addHideSelectors(array $selectors): self
    {
        $this->hideSelectors = array_unique(array_merge($this->hideSelectors, $selectors));
        return $this;
    }

    /**
     * Génère le CSS d'impression.
     */
    public function generateCss(): string
    {
        $hideSelectorsStr = implode(",\n", $this->hideSelectors);

        $urlRule = $this->showUrls
            ? <<<CSS
a[href^="http"]:after {
    content: " (" attr(href) ")";
    font-size: 0.8em;
    color: #666;
}
CSS
            : '';

        $pageNumbersRule = $this->showPageNumbers
            ? <<<CSS
@page {
    @bottom-center {
        content: counter(page);
    }
}
CSS
            : '';

        $codeBreakRule = $this->avoidBreaksInCode
            ? <<<CSS
pre, code, blockquote {
    page-break-inside: avoid;
    break-inside: avoid;
}
CSS
            : '';

        return <<<CSS
@media print {
    /* Reset */
    * {
        background: transparent !important;
        color: #000 !important;
        box-shadow: none !important;
        text-shadow: none !important;
    }

    /* Typographie */
    body {
        font-family: {$this->fontFamily};
        font-size: {$this->fontSize};
        line-height: {$this->lineHeight};
        color: #000;
        background: #fff;
        margin: 0;
        padding: 0;
    }

    /* Titres */
    h1, h2, h3, h4, h5, h6 {
        page-break-after: avoid;
        break-after: avoid;
        orphans: 3;
        widows: 3;
    }

    h1 { font-size: 24pt; }
    h2 { font-size: 18pt; }
    h3 { font-size: 14pt; }
    h4, h5, h6 { font-size: 12pt; }

    /* Paragraphes */
    p {
        orphans: 3;
        widows: 3;
    }

    /* Liens */
    a {
        color: #000 !important;
        text-decoration: underline;
    }

    {$urlRule}

    a[href^="#"]:after,
    a[href^="javascript:"]:after {
        content: "";
    }

    /* Images */
    img {
        max-width: 100% !important;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    figure {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    figcaption {
        font-size: 0.9em;
        font-style: italic;
    }

    /* Code */
    pre {
        border: 1px solid #ddd;
        padding: 1em;
        font-size: 0.9em;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    code {
        font-family: 'Courier New', monospace;
    }

    {$codeBreakRule}

    /* Tables */
    table {
        border-collapse: collapse;
        width: 100%;
    }

    th, td {
        border: 1px solid #000;
        padding: 0.5em;
        text-align: left;
    }

    thead {
        display: table-header-group;
    }

    tr {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    /* Listes */
    ul, ol {
        margin-left: 1.5em;
    }

    /* Citations */
    blockquote {
        border-left: 2px solid #000;
        margin: 1em 0;
        padding-left: 1em;
        font-style: italic;
    }

    /* Éléments à masquer */
    {$hideSelectorsStr} {
        display: none !important;
    }

    /* Classe pour forcer l'affichage */
    .print-only {
        display: block !important;
    }

    /* Classe pour masquer à l'impression */
    .no-print {
        display: none !important;
    }

    /* Sauts de page */
    .page-break-before {
        page-break-before: always;
        break-before: page;
    }

    .page-break-after {
        page-break-after: always;
        break-after: page;
    }

    .avoid-break {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    /* Largeur du contenu */
    article, .content, main {
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 0;
    }

    /* Page setup */
    @page {
        margin: 2cm;
        size: A4;
    }

    @page :first {
        margin-top: 3cm;
    }

    {$pageNumbersRule}
}
CSS;
    }

    /**
     * Génère la balise link pour le CSS.
     */
    public function generateLink(string $cssPath = '/css/print.css'): string
    {
        return '<link rel="stylesheet" href="' . htmlspecialchars($cssPath) . '" media="print">';
    }

    /**
     * Génère la balise style inline.
     */
    public function generateInlineStyle(): string
    {
        return '<style media="print">' . $this->generateCss() . '</style>';
    }

    /**
     * Génère le bouton d'impression.
     */
    public function generatePrintButton(string $label = 'Imprimer', string $class = 'print-button'): string
    {
        return <<<HTML
<button type="button" class="{$class} no-print" onclick="window.print()">
    {$label}
</button>
HTML;
    }

    /**
     * Génère la section d'informations pour l'impression.
     */
    public function generatePrintHeader(string $title, string $url, ?string $date = null): string
    {
        $dateHtml = $date ? "<p>Imprimé le : {$date}</p>" : '';

        return <<<HTML
<div class="print-only print-header">
    <h1>{$title}</h1>
    <p>Source : {$url}</p>
    {$dateHtml}
    <hr>
</div>
HTML;
    }
}
