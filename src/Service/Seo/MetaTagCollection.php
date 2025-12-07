<?php

declare(strict_types=1);

namespace Lunar\Service\Seo;

/**
 * Collection de meta tags pour le SEO.
 *
 * Stocke et génère les meta tags HTML, Open Graph et Twitter Cards.
 */
final class MetaTagCollection
{
    /** @var array<string, string> */
    private array $meta = [];

    /** @var array<string, array<string>> */
    private array $openGraph = [];

    /** @var array<string, string> */
    private array $twitter = [];

    private ?string $canonical = null;
    private ?array $jsonLd = null;

    /**
     * Définit un meta tag.
     */
    public function set(string $name, string $content): self
    {
        $this->meta[$name] = $content;
        return $this;
    }

    /**
     * Obtient un meta tag.
     */
    public function get(string $name): ?string
    {
        return $this->meta[$name] ?? null;
    }

    /**
     * Définit l'URL canonique.
     */
    public function setCanonical(string $url): self
    {
        $this->canonical = $url;
        return $this;
    }

    /**
     * Définit un meta tag Open Graph.
     */
    public function setOpenGraph(string $property, string $content): self
    {
        $this->openGraph[$property] = [$content];
        return $this;
    }

    /**
     * Ajoute une valeur Open Graph (pour les propriétés multiples comme article:tag).
     */
    public function addOpenGraph(string $property, string $content): self
    {
        if (!isset($this->openGraph[$property])) {
            $this->openGraph[$property] = [];
        }
        $this->openGraph[$property][] = $content;
        return $this;
    }

    /**
     * Définit un meta tag Twitter Card.
     */
    public function setTwitter(string $name, string $content): self
    {
        $this->twitter[$name] = $content;
        return $this;
    }

    /**
     * Définit les données JSON-LD.
     */
    public function setJsonLd(array $data): self
    {
        $this->jsonLd = $data;
        return $this;
    }

    /**
     * Génère le HTML des meta tags.
     */
    public function render(): string
    {
        $html = [];

        // Meta tags de base
        foreach ($this->meta as $name => $content) {
            if ($name === 'title') {
                $html[] = '<title>' . $this->escape($content) . '</title>';
            } else {
                $html[] = '<meta name="' . $this->escape($name) . '" content="' . $this->escape($content) . '">';
            }
        }

        // Canonical
        if ($this->canonical !== null) {
            $html[] = '<link rel="canonical" href="' . $this->escape($this->canonical) . '">';
        }

        // Open Graph
        foreach ($this->openGraph as $property => $values) {
            foreach ($values as $content) {
                $html[] = '<meta property="' . $this->escape($property) . '" content="' . $this->escape($content) . '">';
            }
        }

        // Twitter Cards
        foreach ($this->twitter as $name => $content) {
            $html[] = '<meta name="' . $this->escape($name) . '" content="' . $this->escape($content) . '">';
        }

        // JSON-LD
        if ($this->jsonLd !== null) {
            $json = json_encode($this->jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $html[] = '<script type="application/ld+json">' . $json . '</script>';
        }

        return implode("\n    ", $html);
    }

    /**
     * Retourne les meta tags sous forme de tableau.
     */
    public function toArray(): array
    {
        return [
            'meta' => $this->meta,
            'canonical' => $this->canonical,
            'openGraph' => $this->openGraph,
            'twitter' => $this->twitter,
            'jsonLd' => $this->jsonLd,
        ];
    }

    /**
     * Échappe le contenu HTML.
     */
    private function escape(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
