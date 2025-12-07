<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service de génération de boutons de partage social.
 *
 * Génère des boutons de partage pour différents réseaux sociaux.
 *
 * @example
 * ```php
 * $social = new SocialShareService();
 *
 * echo $social->generateButtons(
 *     'https://example.com/article',
 *     'Mon Article',
 *     'Description'
 * );
 * ```
 */
final class SocialShareService
{
    /** @var string[] */
    private array $networks = ['twitter', 'facebook', 'linkedin'];
    private string $buttonClass = 'la-btn outline sm';
    private string $iconClass = 'la-icon sm';
    private bool $openInNewTab = true;
    private bool $includeText = false;

    /**
     * Définit les réseaux à afficher.
     *
     * @param string[] $networks
     */
    public function setNetworks(array $networks): self
    {
        $this->networks = $networks;
        return $this;
    }

    /**
     * Définit la classe CSS des boutons.
     */
    public function setButtonClass(string $class): self
    {
        $this->buttonClass = $class;
        return $this;
    }

    /**
     * Définit la classe CSS des icônes.
     */
    public function setIconClass(string $class): self
    {
        $this->iconClass = $class;
        return $this;
    }

    /**
     * Active/désactive l'ouverture dans un nouvel onglet.
     */
    public function setOpenInNewTab(bool $open): self
    {
        $this->openInNewTab = $open;
        return $this;
    }

    /**
     * Active/désactive l'affichage du texte avec l'icône.
     */
    public function setIncludeText(bool $include): self
    {
        $this->includeText = $include;
        return $this;
    }

    /**
     * Génère tous les boutons de partage.
     */
    public function generateButtons(string $url, string $title, string $description = ''): string
    {
        $buttons = [];

        foreach ($this->networks as $network) {
            $button = $this->generateButton($network, $url, $title, $description);
            if ($button !== null) {
                $buttons[] = $button;
            }
        }

        return implode("\n", $buttons);
    }

    /**
     * Génère un bouton de partage pour un réseau.
     */
    public function generateButton(string $network, string $url, string $title, string $description = ''): ?string
    {
        $shareUrl = $this->getShareUrl($network, $url, $title, $description);
        if ($shareUrl === null) {
            return null;
        }

        $icon = $this->getIcon($network);
        $label = $this->getLabel($network);
        $target = $this->openInNewTab ? ' target="_blank" rel="noopener"' : '';
        $text = $this->includeText ? ' <span>' . htmlspecialchars($label) . '</span>' : '';

        return sprintf(
            '<a href="%s" class="%s" title="Partager sur %s"%s><span class="%s">%s</span>%s</a>',
            htmlspecialchars($shareUrl),
            htmlspecialchars($this->buttonClass),
            htmlspecialchars($label),
            $target,
            htmlspecialchars($this->iconClass),
            $icon,
            $text
        );
    }

    /**
     * Obtient l'URL de partage pour un réseau.
     */
    public function getShareUrl(string $network, string $url, string $title, string $description = ''): ?string
    {
        $encodedUrl = urlencode($url);
        $encodedTitle = urlencode($title);
        $encodedDesc = urlencode($description);

        return match (strtolower($network)) {
            'twitter', 'x' => "https://twitter.com/intent/tweet?url={$encodedUrl}&text={$encodedTitle}",
            'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}",
            'linkedin' => "https://www.linkedin.com/shareArticle?mini=true&url={$encodedUrl}&title={$encodedTitle}&summary={$encodedDesc}",
            'pinterest' => "https://pinterest.com/pin/create/button/?url={$encodedUrl}&description={$encodedTitle}",
            'reddit' => "https://reddit.com/submit?url={$encodedUrl}&title={$encodedTitle}",
            'hackernews', 'hn' => "https://news.ycombinator.com/submitlink?u={$encodedUrl}&t={$encodedTitle}",
            'whatsapp' => "https://wa.me/?text={$encodedTitle}%20{$encodedUrl}",
            'telegram' => "https://t.me/share/url?url={$encodedUrl}&text={$encodedTitle}",
            'email' => "mailto:?subject={$encodedTitle}&body={$encodedDesc}%0A%0A{$encodedUrl}",
            default => null,
        };
    }

    /**
     * Obtient l'icône pour un réseau (Material Icons name).
     */
    private function getIcon(string $network): string
    {
        return match (strtolower($network)) {
            'twitter', 'x' => 'share',
            'facebook' => 'facebook',
            'linkedin' => 'work',
            'pinterest' => 'push_pin',
            'reddit' => 'forum',
            'hackernews', 'hn' => 'newspaper',
            'whatsapp' => 'chat',
            'telegram' => 'send',
            'email' => 'mail',
            default => 'share',
        };
    }

    /**
     * Obtient le label pour un réseau.
     */
    private function getLabel(string $network): string
    {
        return match (strtolower($network)) {
            'twitter' => 'Twitter',
            'x' => 'X',
            'facebook' => 'Facebook',
            'linkedin' => 'LinkedIn',
            'pinterest' => 'Pinterest',
            'reddit' => 'Reddit',
            'hackernews', 'hn' => 'Hacker News',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'email' => 'Email',
            default => ucfirst($network),
        };
    }

    /**
     * Génère le JavaScript pour ouvrir en popup.
     */
    public function generatePopupScript(): string
    {
        return <<<'JS'
document.querySelectorAll('.social-share-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.href;
        const w = 600, h = 400;
        const left = (screen.width - w) / 2;
        const top = (screen.height - h) / 2;
        window.open(url, 'share', `width=${w},height=${h},left=${left},top=${top}`);
    });
});
JS;
    }

    /**
     * Génère le CSS pour les boutons.
     */
    public function generateCss(): string
    {
        return <<<'CSS'
.social-share-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    text-decoration: none;
    transition: opacity 0.2s;
}
.social-share-btn:hover {
    opacity: 0.8;
}
CSS;
    }
}
