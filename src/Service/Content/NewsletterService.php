<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service pour gérer les formulaires de newsletter.
 *
 * Génère les formulaires d'inscription et gère
 * le stockage des abonnés en JSON.
 *
 * @example
 * ```php
 * $newsletter = new NewsletterService('/data/subscribers.json');
 * $newsletter->subscribe('email@example.com');
 *
 * echo $newsletter->generateForm();
 * ```
 */
final class NewsletterService
{
    private string $storagePath;
    private string $formClass = 'la-newsletter-form';
    private string $successMessage = 'Merci ! Vous êtes maintenant inscrit.';
    private string $errorMessage = 'Une erreur est survenue. Veuillez réessayer.';
    private string $duplicateMessage = 'Cette adresse email est déjà inscrite.';
    private bool $doubleOptIn = false;

    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;
    }

    /**
     * Définit la classe CSS du formulaire.
     */
    public function setFormClass(string $class): self
    {
        $this->formClass = $class;
        return $this;
    }

    /**
     * Définit le message de succès.
     */
    public function setSuccessMessage(string $message): self
    {
        $this->successMessage = $message;
        return $this;
    }

    /**
     * Définit le message d'erreur.
     */
    public function setErrorMessage(string $message): self
    {
        $this->errorMessage = $message;
        return $this;
    }

    /**
     * Active/désactive le double opt-in.
     */
    public function setDoubleOptIn(bool $enabled): self
    {
        $this->doubleOptIn = $enabled;
        return $this;
    }

    /**
     * Inscrit un nouvel abonné.
     *
     * @return array{success: bool, message: string}
     */
    public function subscribe(string $email, string $name = '', array $tags = []): array
    {
        $email = mb_strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Adresse email invalide.'];
        }

        $subscribers = $this->loadSubscribers();

        // Vérifier si déjà inscrit
        if ($this->isSubscribed($email)) {
            return ['success' => false, 'message' => $this->duplicateMessage];
        }

        $subscriber = [
            'id' => $this->generateId(),
            'email' => $email,
            'name' => trim($name),
            'tags' => $tags,
            'status' => $this->doubleOptIn ? 'pending' : 'active',
            'subscribed_at' => (new \DateTimeImmutable())->format('c'),
            'confirmed_at' => $this->doubleOptIn ? null : (new \DateTimeImmutable())->format('c'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];

        if ($this->doubleOptIn) {
            $subscriber['confirmation_token'] = bin2hex(random_bytes(32));
        }

        $subscribers[] = $subscriber;
        $this->saveSubscribers($subscribers);

        return [
            'success' => true,
            'message' => $this->successMessage,
            'subscriber' => $subscriber,
        ];
    }

    /**
     * Confirme une inscription (double opt-in).
     */
    public function confirm(string $token): bool
    {
        $subscribers = $this->loadSubscribers();

        foreach ($subscribers as &$subscriber) {
            if (($subscriber['confirmation_token'] ?? '') === $token) {
                $subscriber['status'] = 'active';
                $subscriber['confirmed_at'] = (new \DateTimeImmutable())->format('c');
                unset($subscriber['confirmation_token']);

                $this->saveSubscribers($subscribers);
                return true;
            }
        }

        return false;
    }

    /**
     * Désinscrit un abonné.
     */
    public function unsubscribe(string $email): bool
    {
        $email = mb_strtolower(trim($email));
        $subscribers = $this->loadSubscribers();
        $found = false;

        foreach ($subscribers as &$subscriber) {
            if ($subscriber['email'] === $email) {
                $subscriber['status'] = 'unsubscribed';
                $subscriber['unsubscribed_at'] = (new \DateTimeImmutable())->format('c');
                $found = true;
                break;
            }
        }

        if ($found) {
            $this->saveSubscribers($subscribers);
        }

        return $found;
    }

    /**
     * Vérifie si un email est inscrit.
     */
    public function isSubscribed(string $email): bool
    {
        $email = mb_strtolower(trim($email));
        $subscribers = $this->loadSubscribers();

        foreach ($subscribers as $subscriber) {
            if ($subscriber['email'] === $email && $subscriber['status'] !== 'unsubscribed') {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne le nombre d'abonnés actifs.
     */
    public function getActiveCount(): int
    {
        $subscribers = $this->loadSubscribers();
        return count(array_filter($subscribers, fn($s) => $s['status'] === 'active'));
    }

    /**
     * Retourne tous les abonnés actifs.
     *
     * @return array<array{email: string, name: string}>
     */
    public function getActiveSubscribers(): array
    {
        $subscribers = $this->loadSubscribers();
        return array_filter($subscribers, fn($s) => $s['status'] === 'active');
    }

    /**
     * Exporte les abonnés au format CSV.
     */
    public function exportCsv(): string
    {
        $subscribers = $this->getActiveSubscribers();
        $lines = ['email,name,subscribed_at'];

        foreach ($subscribers as $subscriber) {
            $lines[] = sprintf(
                '"%s","%s","%s"',
                $subscriber['email'],
                $subscriber['name'] ?? '',
                $subscriber['subscribed_at'] ?? ''
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Charge les abonnés depuis le fichier.
     */
    private function loadSubscribers(): array
    {
        if (!file_exists($this->storagePath)) {
            return [];
        }

        $content = file_get_contents($this->storagePath);
        return json_decode($content, true) ?? [];
    }

    /**
     * Sauvegarde les abonnés dans le fichier.
     */
    private function saveSubscribers(array $subscribers): void
    {
        $dir = dirname($this->storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->storagePath,
            json_encode($subscribers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Génère un ID unique.
     */
    private function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Génère le formulaire d'inscription HTML.
     */
    public function generateForm(string $action = '', string $title = 'Newsletter'): string
    {
        $formClass = htmlspecialchars($this->formClass);
        $escapedTitle = htmlspecialchars($title);

        return <<<HTML
<form class="{$formClass}" method="post" action="{$action}">
    <div class="la-newsletter-header">
        <span class="la-icon">mail</span>
        <h4>{$escapedTitle}</h4>
    </div>
    <p class="la-newsletter-description">
        Recevez nos derniers articles directement dans votre boîte mail.
    </p>
    <div class="la-newsletter-fields">
        <input type="email" name="email" placeholder="Votre email" required class="la-input">
        <button type="submit" class="la-btn primary">
            <span class="la-icon sm">send</span>
            S'inscrire
        </button>
    </div>
    <p class="la-newsletter-privacy">
        <small>Nous respectons votre vie privée. Désinscription possible à tout moment.</small>
    </p>
</form>
HTML;
    }

    /**
     * Génère le formulaire compact.
     */
    public function generateCompactForm(string $action = ''): string
    {
        return <<<HTML
<form class="la-newsletter-compact" method="post" action="{$action}">
    <input type="email" name="email" placeholder="Votre email" required class="la-input">
    <button type="submit" class="la-btn primary sm">
        <span class="la-icon sm">send</span>
    </button>
</form>
HTML;
    }

    /**
     * Génère le CSS pour les formulaires.
     */
    public function generateCss(): string
    {
        return <<<CSS
.{$this->formClass} {
    padding: 2rem;
    background: var(--la-surface, #f9fafb);
    border-radius: 0.75rem;
    text-align: center;
}

.la-newsletter-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.la-newsletter-header h4 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.la-newsletter-description {
    color: var(--la-text-muted, #6b7280);
    margin-bottom: 1.5rem;
}

.la-newsletter-fields {
    display: flex;
    gap: 0.5rem;
    max-width: 400px;
    margin: 0 auto;
}

.la-newsletter-fields .la-input {
    flex: 1;
}

.la-newsletter-privacy {
    margin-top: 1rem;
    color: var(--la-text-muted, #9ca3af);
}

.la-newsletter-compact {
    display: flex;
    gap: 0.5rem;
}

.la-newsletter-compact .la-input {
    flex: 1;
}

.la-newsletter-success {
    padding: 1rem;
    background: var(--la-success-bg, #d1fae5);
    color: var(--la-success, #059669);
    border-radius: 0.5rem;
    text-align: center;
}

.la-newsletter-error {
    padding: 1rem;
    background: var(--la-error-bg, #fee2e2);
    color: var(--la-error, #dc2626);
    border-radius: 0.5rem;
    text-align: center;
}

@media (max-width: 640px) {
    .la-newsletter-fields {
        flex-direction: column;
    }
}
CSS;
    }

    /**
     * Génère le JavaScript pour soumission AJAX.
     */
    public function generateScript(string $endpoint): string
    {
        $successMessage = addslashes($this->successMessage);
        $errorMessage = addslashes($this->errorMessage);

        return <<<JS
document.querySelectorAll('.{$this->formClass}, .la-newsletter-compact').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = this.querySelector('input[name="email"]').value;
        const button = this.querySelector('button[type="submit"]');
        const originalContent = button.innerHTML;

        button.disabled = true;
        button.innerHTML = '<span class="la-icon sm la-spin">refresh</span>';

        try {
            const response = await fetch('{$endpoint}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });

            const result = await response.json();

            if (result.success) {
                this.innerHTML = '<div class="la-newsletter-success">{$successMessage}</div>';
            } else {
                const error = document.createElement('div');
                error.className = 'la-newsletter-error';
                error.textContent = result.message || '{$errorMessage}';
                this.appendChild(error);
                setTimeout(() => error.remove(), 5000);
            }
        } catch (err) {
            const error = document.createElement('div');
            error.className = 'la-newsletter-error';
            error.textContent = '{$errorMessage}';
            this.appendChild(error);
            setTimeout(() => error.remove(), 5000);
        } finally {
            button.disabled = false;
            button.innerHTML = originalContent;
        }
    });
});
JS;
    }
}
