<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Nettoyeur HTML pour prévenir les attaques XSS.
 *
 * Permet de nettoyer le HTML en :
 * - Supprimant les balises dangereuses (script, style, etc.)
 * - Supprimant les attributs d'événements (onclick, onerror, etc.)
 * - Supprimant les URLs JavaScript
 * - Ajoutant rel="noopener" aux liens externes
 *
 * @example
 * ```php
 * $sanitizer = new HtmlSanitizer();
 * $clean = $sanitizer->sanitize('<p onclick="alert(1)">Hello</p>');
 * // <p>Hello</p>
 *
 * // Avec balises personnalisées
 * $sanitizer = new HtmlSanitizer(['p', 'strong']);
 * ```
 */
final class HtmlSanitizer
{
    /**
     * Balises qui doivent être supprimées avec leur contenu.
     */
    private const DANGEROUS_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'textarea', 'select', 'button', 'link', 'meta', 'base', 'applet',
    ];

    /**
     * Balises HTML autorisées par défaut.
     */
    private const DEFAULT_ALLOWED_TAGS = [
        // Structure
        'p', 'br', 'hr', 'div', 'span',
        // Titres
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        // Formatage
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins', 'mark',
        'sub', 'sup', 'small',
        // Listes
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        // Citations et code
        'blockquote', 'pre', 'code', 'kbd', 'samp', 'var',
        // Tables
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
        // Médias
        'img', 'figure', 'figcaption',
        // Liens
        'a',
        // Autres
        'abbr', 'cite', 'q', 'address', 'time',
    ];

    /**
     * Attributs autorisés par balise.
     */
    private const ALLOWED_ATTRIBUTES = [
        '*' => ['class', 'id', 'title', 'lang', 'dir'],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'td' => ['colspan', 'rowspan'],
        'code' => ['class'], // Pour les langages de code
        'time' => ['datetime'],
        'abbr' => ['title'],
        'q' => ['cite'],
        'blockquote' => ['cite'],
    ];

    /**
     * Protocoles d'URL autorisés.
     */
    private const ALLOWED_PROTOCOLS = ['http', 'https', 'mailto', 'tel', '/'];

    /** @var string[] */
    private array $allowedTags;

    /**
     * @param string[]|null $allowedTags Tags autorisés (null = défaut)
     */
    public function __construct(?array $allowedTags = null)
    {
        $this->allowedTags = $allowedTags ?? self::DEFAULT_ALLOWED_TAGS;
    }

    /**
     * Nettoie le HTML des éléments dangereux.
     */
    public function sanitize(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // Si aucune balise n'est autorisée, juste strip
        if (empty($this->allowedTags)) {
            return self::stripTags($html);
        }

        // Utiliser DOMDocument pour parser le HTML
        $dom = new \DOMDocument();

        // Supprimer les erreurs de parsing HTML
        libxml_use_internal_errors(true);

        // Charger le HTML avec l'encodage UTF-8
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="sanitizer-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        // Nettoyer récursivement
        $root = $dom->getElementById('sanitizer-root');
        if ($root) {
            $this->cleanNode($root);
        }

        // Extraire le HTML nettoyé
        $result = '';
        if ($root) {
            foreach ($root->childNodes as $child) {
                $result .= $dom->saveHTML($child);
            }
        }

        // Nettoyer les entités HTML
        $result = html_entity_decode($result, ENT_QUOTES, 'UTF-8');

        return trim($result);
    }

    /**
     * Supprime toutes les balises HTML.
     */
    public static function stripTags(string $html): string
    {
        return strip_tags($html);
    }

    /**
     * Échappe le HTML pour affichage.
     */
    public static function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Nettoie un nœud DOM et ses enfants.
     */
    private function cleanNode(\DOMNode $node): void
    {
        // Liste des nœuds à supprimer (on ne peut pas modifier pendant l'itération)
        $toRemove = [];

        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $tagName = strtolower($child->tagName);

                // Supprimer complètement les balises dangereuses (contenu compris)
                if (in_array($tagName, self::DANGEROUS_TAGS, true)) {
                    $toRemove[] = ['node' => $child, 'replacement' => null];
                    continue;
                }

                // Supprimer les balises non autorisées mais garder le contenu
                if (!in_array($tagName, $this->allowedTags, true)) {
                    // Conserver le contenu texte des balises supprimées
                    $fragment = $child->ownerDocument->createDocumentFragment();
                    while ($child->firstChild) {
                        $fragment->appendChild($child->firstChild);
                    }
                    $toRemove[] = ['node' => $child, 'replacement' => $fragment];
                    continue;
                }

                // Nettoyer les attributs
                $this->cleanAttributes($child);

                // Récursion sur les enfants
                $this->cleanNode($child);
            }
        }

        // Effectuer les suppressions/remplacements
        foreach ($toRemove as $item) {
            if ($item['replacement'] === null) {
                // Supprimer complètement (balise dangereuse)
                $item['node']->parentNode->removeChild($item['node']);
            } elseif ($item['replacement']->hasChildNodes()) {
                $item['node']->parentNode->replaceChild($item['replacement'], $item['node']);
            } else {
                $item['node']->parentNode->removeChild($item['node']);
            }
        }
    }

    /**
     * Nettoie les attributs d'un élément.
     */
    private function cleanAttributes(\DOMElement $element): void
    {
        $tagName = strtolower($element->tagName);
        $toRemove = [];

        // Collecter les attributs à supprimer
        foreach ($element->attributes as $attr) {
            $attrName = strtolower($attr->name);
            $attrValue = $attr->value;

            // Supprimer les handlers d'événements
            if (str_starts_with($attrName, 'on')) {
                $toRemove[] = $attr->name;
                continue;
            }

            // Supprimer l'attribut style
            if ($attrName === 'style') {
                $toRemove[] = $attr->name;
                continue;
            }

            // Vérifier si l'attribut est autorisé
            $allowedForTag = self::ALLOWED_ATTRIBUTES[$tagName] ?? [];
            $allowedGlobal = self::ALLOWED_ATTRIBUTES['*'] ?? [];
            $allowed = array_merge($allowedGlobal, $allowedForTag);

            if (!in_array($attrName, $allowed, true)) {
                $toRemove[] = $attr->name;
                continue;
            }

            // Vérifier les URLs dangereuses
            if (in_array($attrName, ['href', 'src'], true)) {
                if (!$this->isAllowedUrl($attrValue)) {
                    $toRemove[] = $attr->name;
                    continue;
                }
            }
        }

        // Supprimer les attributs
        foreach ($toRemove as $attrName) {
            $element->removeAttribute($attrName);
        }

        // Ajouter rel="noopener noreferrer" aux liens externes avec target="_blank"
        if ($tagName === 'a' && $element->hasAttribute('target') && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /**
     * Vérifie si une URL est autorisée.
     */
    private function isAllowedUrl(string $url): bool
    {
        // Décoder les entités HTML
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

        // Supprimer les espaces
        $url = trim($url);

        // URLs relatives
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }

        // Vérifier le protocole
        $protocol = parse_url($url, PHP_URL_SCHEME);

        if ($protocol === null) {
            // Pas de protocole, potentiellement relatif
            return true;
        }

        $protocol = strtolower($protocol);

        // Bloquer javascript:, data:, vbscript:, etc.
        if (!in_array($protocol, self::ALLOWED_PROTOCOLS, true)) {
            return false;
        }

        return true;
    }
}
