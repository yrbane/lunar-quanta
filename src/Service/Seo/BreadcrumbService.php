<?php

declare(strict_types=1);

namespace Lunar\Service\Seo;

use Lunar\Entity\Post;

/**
 * Service de génération de fil d'Ariane (breadcrumb).
 *
 * Génère des breadcrumbs pour la navigation et le SEO avec support JSON-LD.
 *
 * @example
 * ```php
 * $service = new BreadcrumbService('https://example.com');
 * $breadcrumb = $service->forPost($post, $categoryName);
 * echo $breadcrumb->renderHtml();
 * echo $breadcrumb->renderJsonLd();
 * ```
 */
final class BreadcrumbService
{
    private string $siteUrl;
    private string $homeName = 'Accueil';
    private string $blogName = 'Blog';

    public function __construct(string $siteUrl)
    {
        $this->siteUrl = rtrim($siteUrl, '/');
    }

    /**
     * Définit le nom de la page d'accueil.
     */
    public function setHomeName(string $name): self
    {
        $this->homeName = $name;
        return $this;
    }

    /**
     * Définit le nom de la section blog.
     */
    public function setBlogName(string $name): self
    {
        $this->blogName = $name;
        return $this;
    }

    /**
     * Génère le breadcrumb pour un article.
     */
    public function forPost(Post $post, ?string $categoryName = null, ?string $categorySlug = null): Breadcrumb
    {
        $breadcrumb = new Breadcrumb();

        // Accueil
        $breadcrumb->add($this->homeName, $this->siteUrl . '/');

        // Blog
        $breadcrumb->add($this->blogName, $this->siteUrl . '/blog/');

        // Catégorie (optionnel)
        if ($categoryName !== null && $categorySlug !== null) {
            $breadcrumb->add($categoryName, $this->siteUrl . '/blog/category/' . $categorySlug . '/');
        }

        // Article courant
        $breadcrumb->add($post->getTitle());

        return $breadcrumb;
    }

    /**
     * Génère le breadcrumb pour une page de catégorie.
     */
    public function forCategory(string $categoryName): Breadcrumb
    {
        $breadcrumb = new Breadcrumb();

        $breadcrumb->add($this->homeName, $this->siteUrl . '/');
        $breadcrumb->add($this->blogName, $this->siteUrl . '/blog/');
        $breadcrumb->add($categoryName);

        return $breadcrumb;
    }

    /**
     * Génère le breadcrumb pour une page de tag.
     */
    public function forTag(string $tagName): Breadcrumb
    {
        $breadcrumb = new Breadcrumb();

        $breadcrumb->add($this->homeName, $this->siteUrl . '/');
        $breadcrumb->add($this->blogName, $this->siteUrl . '/blog/');
        $breadcrumb->add('Tag: ' . $tagName);

        return $breadcrumb;
    }

    /**
     * Génère le breadcrumb pour la page d'index du blog.
     */
    public function forBlogIndex(): Breadcrumb
    {
        $breadcrumb = new Breadcrumb();

        $breadcrumb->add($this->homeName, $this->siteUrl . '/');
        $breadcrumb->add($this->blogName);

        return $breadcrumb;
    }

    /**
     * Génère le breadcrumb pour une page de recherche.
     */
    public function forSearch(string $query): Breadcrumb
    {
        $breadcrumb = new Breadcrumb();

        $breadcrumb->add($this->homeName, $this->siteUrl . '/');
        $breadcrumb->add($this->blogName, $this->siteUrl . '/blog/');
        $breadcrumb->add('Recherche: ' . $query);

        return $breadcrumb;
    }
}

/**
 * Représente un fil d'Ariane.
 */
final class Breadcrumb
{
    /** @var array<array{name: string, url: ?string}> */
    private array $items = [];

    /**
     * Ajoute un élément au breadcrumb.
     */
    public function add(string $name, ?string $url = null): self
    {
        $this->items[] = [
            'name' => $name,
            'url' => $url,
        ];
        return $this;
    }

    /**
     * Retourne tous les éléments.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Génère le HTML du breadcrumb.
     */
    public function renderHtml(string $separator = ' › ', string $class = 'breadcrumb'): string
    {
        if (empty($this->items)) {
            return '';
        }

        $html = '<nav aria-label="Fil d\'Ariane" class="' . $this->escape($class) . '">';
        $html .= '<ol itemscope itemtype="https://schema.org/BreadcrumbList">';

        $count = count($this->items);
        foreach ($this->items as $i => $item) {
            $isLast = ($i === $count - 1);
            $position = $i + 1;

            $html .= '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';

            if ($item['url'] !== null && !$isLast) {
                $html .= '<a itemprop="item" href="' . $this->escape($item['url']) . '">';
                $html .= '<span itemprop="name">' . $this->escape($item['name']) . '</span>';
                $html .= '</a>';
            } else {
                $html .= '<span itemprop="name">' . $this->escape($item['name']) . '</span>';
            }

            $html .= '<meta itemprop="position" content="' . $position . '">';
            $html .= '</li>';

            if (!$isLast) {
                $html .= '<li class="separator" aria-hidden="true">' . $separator . '</li>';
            }
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Génère le JSON-LD pour le breadcrumb.
     */
    public function renderJsonLd(): string
    {
        if (empty($this->items)) {
            return '';
        }

        $itemList = [];
        foreach ($this->items as $i => $item) {
            $listItem = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
            ];

            if ($item['url'] !== null) {
                $listItem['item'] = $item['url'];
            }

            $itemList[] = $listItem;
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList,
        ];

        $json = json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return '<script type="application/ld+json">' . $json . '</script>';
    }

    /**
     * Génère un breadcrumb simple (texte uniquement).
     */
    public function renderSimple(string $separator = ' › '): string
    {
        $names = array_column($this->items, 'name');
        return implode($separator, array_map([$this, 'escape'], $names));
    }

    /**
     * Échappe le contenu HTML.
     */
    private function escape(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
