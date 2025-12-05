<?php
/**
 * Lunar Quanta Framework - Provider OAuth GitHub.
 *
 * =============================================================================
 * CONFIGURATION
 * =============================================================================
 *
 * 1. Allez sur https://github.com/settings/developers
 * 2. Créez une nouvelle OAuth App
 * 3. Configurez l'Authorization callback URL
 *
 * ```php
 * $github = new GitHubProvider(
 *     clientId: 'votre-client-id',
 *     clientSecret: 'votre-client-secret',
 *     redirectUri: 'https://votre-site.com/oauth/github/callback'
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
 * Provider OAuth pour GitHub.
 */
class GitHubProvider extends AbstractOAuthProvider
{
    public function getName(): string
    {
        return 'github';
    }

    protected function getAuthorizationEndpoint(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function getTokenEndpoint(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function getUserInfoEndpoint(): string
    {
        return 'https://api.github.com/user';
    }

    /**
     * @return array<string>
     */
    protected function getDefaultScopes(): array
    {
        return [
            'user:email',
            'read:user',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function parseUserInfo(array $data): OAuthUser
    {
        // GitHub peut avoir l'email null si privé, on le récupère séparément
        $email = $data['email'] ?? $this->fetchPrimaryEmail($data);

        $user = new OAuthUser(
            provider: 'github',
            providerId: (string) $data['id'],
            email: $email ?? '',
            name: $data['name'] ?? $data['login'] ?? null,
            avatar: $data['avatar_url'] ?? null,
            rawData: $data
        );

        $user->setProfileUrl($data['html_url'] ?? null);

        return $user;
    }

    /**
     * Récupère l'email primaire si non disponible dans le profil.
     *
     * @param array<string, mixed> $userData Les données utilisateur
     *
     * @return string|null L'email primaire
     */
    private function fetchPrimaryEmail(array $userData): ?string
    {
        // Cette méthode nécessiterait un token valide
        // En production, faites un appel à /user/emails
        return null;
    }

    /**
     * Récupère les emails de l'utilisateur (nécessite le scope user:email).
     *
     * @param OAuthToken $token Le token d'accès
     *
     * @return array<array{email: string, primary: bool, verified: bool}>
     */
    public function getUserEmails(OAuthToken $token): array
    {
        $response = $this->httpGet(
            'https://api.github.com/user/emails',
            $token->getAuthorizationHeader()
        );

        // Filtre pour ne garder que les emails vérifiés
        return array_filter($response, fn($email) => $email['verified'] ?? false);
    }

    /**
     * @inheritDoc
     */
    protected function httpGet(string $url, string $authorization): array
    {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    "Authorization: {$authorization}",
                    'Accept: application/json',
                    'User-Agent: Lunar-Quanta-OAuth', // GitHub exige un User-Agent
                ],
            ],
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new OAuthException("Failed to fetch from GitHub API");
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new OAuthException("Invalid JSON response from GitHub");
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function httpPost(string $url, array $params): array
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                    'User-Agent: Lunar-Quanta-OAuth',
                ],
                'content' => http_build_query($params),
            ],
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new OAuthException("Failed to connect to GitHub OAuth");
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new OAuthException("Invalid JSON response from GitHub");
        }

        return $data;
    }
}
