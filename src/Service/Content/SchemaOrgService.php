<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service pour les données structurées Schema.org.
 *
 * Génère les balises JSON-LD pour le SEO.
 *
 * @example
 * ```php
 * $schema = new SchemaOrgService();
 *
 * // Article
 * $jsonLd = $schema->article([
 *     'title' => 'Mon Article',
 *     'author' => 'John Doe',
 *     'datePublished' => '2025-01-15',
 * ]);
 *
 * // Breadcrumb
 * $jsonLd = $schema->breadcrumb([
 *     ['name' => 'Accueil', 'url' => '/'],
 *     ['name' => 'Blog', 'url' => '/blog'],
 * ]);
 * ```
 */
final class SchemaOrgService
{
    private string $baseUrl = '';
    private string $siteName = '';
    private string $defaultAuthor = '';
    private ?string $logoUrl = null;

    /**
     * Définit l'URL de base.
     */
    public function setBaseUrl(string $url): self
    {
        $this->baseUrl = rtrim($url, '/');
        return $this;
    }

    /**
     * Définit le nom du site.
     */
    public function setSiteName(string $name): self
    {
        $this->siteName = $name;
        return $this;
    }

    /**
     * Définit l'auteur par défaut.
     */
    public function setDefaultAuthor(string $author): self
    {
        $this->defaultAuthor = $author;
        return $this;
    }

    /**
     * Définit l'URL du logo.
     */
    public function setLogoUrl(?string $url): self
    {
        $this->logoUrl = $url;
        return $this;
    }

    /**
     * Génère le JSON-LD pour un article.
     *
     * @param array<string, mixed> $data
     */
    public function article(array $data): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'datePublished' => $data['datePublished'] ?? '',
            'dateModified' => $data['dateModified'] ?? $data['datePublished'] ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $data['author'] ?? $this->defaultAuthor,
            ],
        ];

        if (!empty($data['url'])) {
            $schema['mainEntityOfPage'] = [
                '@type' => 'WebPage',
                '@id' => $this->normalizeUrl($data['url']),
            ];
        }

        if (!empty($data['image'])) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $this->normalizeUrl($data['image']),
            ];
            if (!empty($data['imageWidth'])) {
                $schema['image']['width'] = $data['imageWidth'];
            }
            if (!empty($data['imageHeight'])) {
                $schema['image']['height'] = $data['imageHeight'];
            }
        }

        if ($this->siteName) {
            $schema['publisher'] = $this->getPublisher();
        }

        if (!empty($data['keywords'])) {
            $schema['keywords'] = is_array($data['keywords'])
                ? implode(', ', $data['keywords'])
                : $data['keywords'];
        }

        if (!empty($data['wordCount'])) {
            $schema['wordCount'] = $data['wordCount'];
        }

        if (!empty($data['articleSection'])) {
            $schema['articleSection'] = $data['articleSection'];
        }

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour un article de blog.
     *
     * @param array<string, mixed> $data
     */
    public function blogPosting(array $data): string
    {
        $schema = json_decode($this->article($data), true);
        $schema['@type'] = 'BlogPosting';

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour le fil d'Ariane.
     *
     * @param array<array<string, string>> $items
     */
    public function breadcrumb(array $items): string
    {
        $listItems = [];
        $position = 1;

        foreach ($items as $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $item['name'],
                'item' => $this->normalizeUrl($item['url']),
            ];
            $position++;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour une organisation.
     *
     * @param array<string, mixed> $data
     */
    public function organization(array $data = []): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $data['name'] ?? $this->siteName,
            'url' => $data['url'] ?? $this->baseUrl,
        ];

        if ($this->logoUrl || !empty($data['logo'])) {
            $schema['logo'] = $this->normalizeUrl($data['logo'] ?? $this->logoUrl);
        }

        if (!empty($data['sameAs'])) {
            $schema['sameAs'] = $data['sameAs'];
        }

        if (!empty($data['email'])) {
            $schema['email'] = $data['email'];
        }

        if (!empty($data['phone'])) {
            $schema['telephone'] = $data['phone'];
        }

        if (!empty($data['address'])) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $data['address']['street'] ?? '',
                'addressLocality' => $data['address']['city'] ?? '',
                'postalCode' => $data['address']['postalCode'] ?? '',
                'addressCountry' => $data['address']['country'] ?? '',
            ];
        }

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour un site web.
     *
     * @param array<string, mixed> $data
     */
    public function website(array $data = []): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $data['name'] ?? $this->siteName,
            'url' => $data['url'] ?? $this->baseUrl,
        ];

        if (!empty($data['searchUrl'])) {
            $schema['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $data['searchUrl'],
                ],
                'query-input' => 'required name=search_term_string',
            ];
        }

        if (!empty($data['description'])) {
            $schema['description'] = $data['description'];
        }

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour une page web.
     *
     * @param array<string, mixed> $data
     */
    public function webPage(array $data): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $data['type'] ?? 'WebPage',
            'name' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
        ];

        if (!empty($data['url'])) {
            $schema['url'] = $this->normalizeUrl($data['url']);
        }

        if (!empty($data['datePublished'])) {
            $schema['datePublished'] = $data['datePublished'];
        }

        if (!empty($data['dateModified'])) {
            $schema['dateModified'] = $data['dateModified'];
        }

        if (!empty($data['breadcrumb'])) {
            $schema['breadcrumb'] = json_decode($this->breadcrumb($data['breadcrumb']), true);
        }

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour une personne.
     *
     * @param array<string, mixed> $data
     */
    public function person(array $data): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $data['name'] ?? '',
        ];

        if (!empty($data['url'])) {
            $schema['url'] = $this->normalizeUrl($data['url']);
        }

        if (!empty($data['image'])) {
            $schema['image'] = $this->normalizeUrl($data['image']);
        }

        if (!empty($data['jobTitle'])) {
            $schema['jobTitle'] = $data['jobTitle'];
        }

        if (!empty($data['email'])) {
            $schema['email'] = $data['email'];
        }

        if (!empty($data['sameAs'])) {
            $schema['sameAs'] = $data['sameAs'];
        }

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour une FAQ.
     *
     * @param array<array<string, string>> $items
     */
    public function faq(array $items): string
    {
        $questions = [];

        foreach ($items as $item) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions,
        ];

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour une liste d'articles.
     *
     * @param array<array<string, mixed>> $items
     */
    public function itemList(array $items, string $listType = 'ItemList'): string
    {
        $listItems = [];
        $position = 1;

        foreach ($items as $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'url' => $this->normalizeUrl($item['url']),
                'name' => $item['name'] ?? '',
            ];
            $position++;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $listType,
            'itemListElement' => $listItems,
        ];

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour une évaluation/notation.
     *
     * @param array<string, mixed> $data
     */
    public function review(array $data): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => $data['itemType'] ?? 'Thing',
                'name' => $data['itemName'] ?? '',
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $data['author'] ?? $this->defaultAuthor,
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $data['rating'] ?? 0,
                'bestRating' => $data['bestRating'] ?? 5,
                'worstRating' => $data['worstRating'] ?? 1,
            ],
        ];

        if (!empty($data['reviewBody'])) {
            $schema['reviewBody'] = $data['reviewBody'];
        }

        if (!empty($data['datePublished'])) {
            $schema['datePublished'] = $data['datePublished'];
        }

        return $this->toJsonLd($schema);
    }

    /**
     * Génère le JSON-LD pour un événement.
     *
     * @param array<string, mixed> $data
     */
    public function event(array $data): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $data['type'] ?? 'Event',
            'name' => $data['name'] ?? '',
            'startDate' => $data['startDate'] ?? '',
        ];

        if (!empty($data['endDate'])) {
            $schema['endDate'] = $data['endDate'];
        }

        if (!empty($data['description'])) {
            $schema['description'] = $data['description'];
        }

        if (!empty($data['location'])) {
            $schema['location'] = [
                '@type' => 'Place',
                'name' => $data['location']['name'] ?? '',
                'address' => $data['location']['address'] ?? '',
            ];
        }

        if (!empty($data['url'])) {
            $schema['url'] = $this->normalizeUrl($data['url']);
        }

        if (!empty($data['image'])) {
            $schema['image'] = $this->normalizeUrl($data['image']);
        }

        return $this->toJsonLd($schema);
    }

    /**
     * Combine plusieurs schemas.
     *
     * @param string[] $schemas
     */
    public function combine(array $schemas): string
    {
        $combined = [];

        foreach ($schemas as $schema) {
            $decoded = json_decode($schema, true);
            if ($decoded) {
                $combined[] = $decoded;
            }
        }

        if (count($combined) === 1) {
            return $this->toJsonLd($combined[0]);
        }

        return $this->toJsonLd([
            '@context' => 'https://schema.org',
            '@graph' => $combined,
        ]);
    }

    /**
     * Génère le script JSON-LD.
     */
    public function toScript(string $jsonLd): string
    {
        return '<script type="application/ld+json">' . $jsonLd . '</script>';
    }

    /**
     * Convertit en JSON-LD.
     *
     * @param array<string, mixed> $schema
     */
    private function toJsonLd(array $schema): string
    {
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Normalise une URL.
     */
    private function normalizeUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return $this->baseUrl . '/' . ltrim($url, '/');
    }

    /**
     * Retourne les données de l'éditeur.
     *
     * @return array<string, mixed>
     */
    private function getPublisher(): array
    {
        $publisher = [
            '@type' => 'Organization',
            'name' => $this->siteName,
        ];

        if ($this->logoUrl) {
            $publisher['logo'] = [
                '@type' => 'ImageObject',
                'url' => $this->normalizeUrl($this->logoUrl),
            ];
        }

        return $publisher;
    }
}
