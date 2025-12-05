<?php
/**
 * Lunar Quanta Framework - Provider OAuth Google.
 *
 * =============================================================================
 * CONFIGURATION
 * =============================================================================
 *
 * 1. Créez un projet sur https://console.cloud.google.com
 * 2. Activez l'API Google+ ou People API
 * 3. Créez des identifiants OAuth 2.0
 * 4. Configurez l'URI de redirection autorisée
 *
 * ```php
 * $google = new GoogleProvider(
 *     clientId: 'votre-client-id.apps.googleusercontent.com',
 *     clientSecret: 'votre-client-secret',
 *     redirectUri: 'https://votre-site.com/oauth/google/callback'
 * );
 * ```
 *
 * @package    Lunar\Service\Security\OAuth
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Security\OAuth;

/**
 * Provider OAuth pour Google.
 */
class GoogleProvider extends AbstractOAuthProvider
{
    public function getName(): string
    {
        return 'google';
    }

    protected function getAuthorizationEndpoint(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function getTokenEndpoint(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function getUserInfoEndpoint(): string
    {
        return 'https://www.googleapis.com/oauth2/v3/userinfo';
    }

    /**
     * @return array<string>
     */
    protected function getDefaultScopes(): array
    {
        return [
            'openid',
            'email',
            'profile',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function parseUserInfo(array $data): OAuthUser
    {
        $user = new OAuthUser(
            provider: 'google',
            providerId: (string) $data['sub'],
            email: $data['email'],
            name: $data['name'] ?? null,
            avatar: $data['picture'] ?? null,
            rawData: $data
        );

        if (isset($data['given_name'])) {
            $user->setFirstName($data['given_name']);
        }

        if (isset($data['family_name'])) {
            $user->setLastName($data['family_name']);
        }

        return $user;
    }

    /**
     * Génère l'URL d'autorisation avec options supplémentaires.
     *
     * @param string $state       Token CSRF
     * @param bool   $forcePrompt Force la demande de permission
     * @param string $loginHint   Email suggéré pour la connexion
     */
    public function getAuthorizationUrlWithOptions(
        string $state,
        bool $forcePrompt = false,
        string $loginHint = ''
    ): string {
        $url = $this->getAuthorizationUrl($state);

        if ($forcePrompt) {
            $url .= '&prompt=consent';
        }

        if (!empty($loginHint)) {
            $url .= '&login_hint=' . urlencode($loginHint);
        }

        // Google spécifique : accès hors ligne pour refresh token
        $url .= '&access_type=offline';

        return $url;
    }
}
