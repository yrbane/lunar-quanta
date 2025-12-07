<?php

declare(strict_types=1);

namespace Lunar\Service\Social;

use Lunar\Entity\Post;

/**
 * Service de génération de liens de partage social.
 *
 * Génère des URLs de partage pour les principaux réseaux sociaux.
 *
 * @example
 * ```php
 * $service = new SocialShareService('https://example.com');
 * $links = $service->getShareLinks($post);
 * echo $links->renderButtons();
 * ```
 */
final class SocialShareService
{
    private string $siteUrl;
    private ?string $twitterVia = null;

    /** @var array<string, bool> */
    private array $enabledNetworks = [
        'twitter' => true,
        'facebook' => true,
        'linkedin' => true,
        'reddit' => true,
        'email' => true,
        'whatsapp' => true,
        'telegram' => true,
        'pinterest' => true,
        'copy' => true,
    ];

    public function __construct(string $siteUrl)
    {
        $this->siteUrl = rtrim($siteUrl, '/');
    }

    /**
     * Définit le compte Twitter pour les attributions.
     */
    public function setTwitterVia(string $handle): self
    {
        $this->twitterVia = ltrim($handle, '@');
        return $this;
    }

    /**
     * Active/désactive un réseau social.
     */
    public function setNetworkEnabled(string $network, bool $enabled): self
    {
        if (isset($this->enabledNetworks[$network])) {
            $this->enabledNetworks[$network] = $enabled;
        }
        return $this;
    }

    /**
     * Génère les liens de partage pour un article.
     */
    public function getShareLinks(Post $post): ShareLinks
    {
        $url = $this->siteUrl . $post->getUrl();
        $title = $post->getTitle();
        $description = $post->getExcerpt() ?? '';
        $image = $post->getFeaturedImage();

        if ($image !== null && !str_starts_with($image, 'http')) {
            $image = $this->siteUrl . $image;
        }

        return $this->generateLinks($url, $title, $description, $image);
    }

    /**
     * Génère les liens de partage pour une URL personnalisée.
     */
    public function generateLinks(
        string $url,
        string $title,
        string $description = '',
        ?string $image = null
    ): ShareLinks {
        $links = new ShareLinks($url, $title);

        if ($this->enabledNetworks['twitter']) {
            $links->add('twitter', $this->buildTwitterUrl($url, $title));
        }

        if ($this->enabledNetworks['facebook']) {
            $links->add('facebook', $this->buildFacebookUrl($url));
        }

        if ($this->enabledNetworks['linkedin']) {
            $links->add('linkedin', $this->buildLinkedInUrl($url, $title, $description));
        }

        if ($this->enabledNetworks['reddit']) {
            $links->add('reddit', $this->buildRedditUrl($url, $title));
        }

        if ($this->enabledNetworks['email']) {
            $links->add('email', $this->buildEmailUrl($url, $title, $description));
        }

        if ($this->enabledNetworks['whatsapp']) {
            $links->add('whatsapp', $this->buildWhatsAppUrl($url, $title));
        }

        if ($this->enabledNetworks['telegram']) {
            $links->add('telegram', $this->buildTelegramUrl($url, $title));
        }

        if ($this->enabledNetworks['pinterest'] && $image !== null) {
            $links->add('pinterest', $this->buildPinterestUrl($url, $description, $image));
        }

        if ($this->enabledNetworks['copy']) {
            $links->setCopyUrl($url);
        }

        return $links;
    }

    private function buildTwitterUrl(string $url, string $title): string
    {
        $params = [
            'url' => $url,
            'text' => $title,
        ];

        if ($this->twitterVia !== null) {
            $params['via'] = $this->twitterVia;
        }

        return 'https://twitter.com/intent/tweet?' . http_build_query($params);
    }

    private function buildFacebookUrl(string $url): string
    {
        return 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url);
    }

    private function buildLinkedInUrl(string $url, string $title, string $description): string
    {
        $params = [
            'url' => $url,
            'title' => $title,
            'summary' => $description,
        ];

        return 'https://www.linkedin.com/sharing/share-offsite/?' . http_build_query($params);
    }

    private function buildRedditUrl(string $url, string $title): string
    {
        $params = [
            'url' => $url,
            'title' => $title,
        ];

        return 'https://reddit.com/submit?' . http_build_query($params);
    }

    private function buildEmailUrl(string $url, string $title, string $description): string
    {
        $body = $description . "\n\n" . $url;

        return 'mailto:?subject=' . rawurlencode($title) . '&body=' . rawurlencode($body);
    }

    private function buildWhatsAppUrl(string $url, string $title): string
    {
        $text = $title . ' ' . $url;

        return 'https://api.whatsapp.com/send?text=' . urlencode($text);
    }

    private function buildTelegramUrl(string $url, string $title): string
    {
        $params = [
            'url' => $url,
            'text' => $title,
        ];

        return 'https://t.me/share/url?' . http_build_query($params);
    }

    private function buildPinterestUrl(string $url, string $description, string $image): string
    {
        $params = [
            'url' => $url,
            'media' => $image,
            'description' => $description,
        ];

        return 'https://pinterest.com/pin/create/button/?' . http_build_query($params);
    }
}

/**
 * Collection de liens de partage social.
 */
final class ShareLinks
{
    private string $url;
    private string $title;

    /** @var array<string, string> */
    private array $links = [];

    private ?string $copyUrl = null;

    public function __construct(string $url, string $title)
    {
        $this->url = $url;
        $this->title = $title;
    }

    /**
     * Ajoute un lien de partage.
     */
    public function add(string $network, string $shareUrl): self
    {
        $this->links[$network] = $shareUrl;
        return $this;
    }

    /**
     * Définit l'URL à copier.
     */
    public function setCopyUrl(string $url): self
    {
        $this->copyUrl = $url;
        return $this;
    }

    /**
     * Retourne tous les liens.
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Retourne un lien spécifique.
     */
    public function getLink(string $network): ?string
    {
        return $this->links[$network] ?? null;
    }

    /**
     * Retourne l'URL à copier.
     */
    public function getCopyUrl(): ?string
    {
        return $this->copyUrl;
    }

    /**
     * Génère le HTML des boutons de partage.
     */
    public function renderButtons(string $class = 'social-share'): string
    {
        $html = '<div class="' . $this->escape($class) . '">';

        $labels = [
            'twitter' => 'Twitter',
            'facebook' => 'Facebook',
            'linkedin' => 'LinkedIn',
            'reddit' => 'Reddit',
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'pinterest' => 'Pinterest',
        ];

        foreach ($this->links as $network => $url) {
            $label = $labels[$network] ?? ucfirst($network);
            $html .= '<a href="' . $this->escape($url) . '" ';
            $html .= 'class="share-button share-' . $this->escape($network) . '" ';
            $html .= 'target="_blank" rel="noopener noreferrer" ';
            $html .= 'title="Partager sur ' . $this->escape($label) . '">';
            $html .= $this->escape($label);
            $html .= '</a>';
        }

        if ($this->copyUrl !== null) {
            $html .= '<button type="button" class="share-button share-copy" ';
            $html .= 'data-url="' . $this->escape($this->copyUrl) . '" ';
            $html .= 'title="Copier le lien">Copier</button>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Génère le HTML des icônes de partage (nécessite CSS/SVG).
     */
    public function renderIcons(string $class = 'social-icons'): string
    {
        $html = '<div class="' . $this->escape($class) . '">';

        foreach ($this->links as $network => $url) {
            $html .= '<a href="' . $this->escape($url) . '" ';
            $html .= 'class="share-icon share-' . $this->escape($network) . '" ';
            $html .= 'target="_blank" rel="noopener noreferrer" ';
            $html .= 'aria-label="Partager sur ' . $this->escape(ucfirst($network)) . '">';
            $html .= '<span class="icon icon-' . $this->escape($network) . '"></span>';
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Retourne les données sous forme de tableau.
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'title' => $this->title,
            'links' => $this->links,
            'copyUrl' => $this->copyUrl,
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
