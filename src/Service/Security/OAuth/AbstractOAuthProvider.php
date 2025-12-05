<?php
/**
 * Lunar Quanta Framework - Provider OAuth Abstrait.
 *
 * Classe de base pour les providers OAuth avec la logique commune.
 *
 * @package    Lunar\Service\Security\OAuth
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Security\OAuth;

/**
 * Classe abstraite pour les providers OAuth.
 */
abstract class AbstractOAuthProvider implements OAuthProviderInterface
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    /** @var array<string> Les scopes demandés */
    protected array $scopes = [];

    /**
     * @param string        $clientId     L'ID client OAuth
     * @param string        $clientSecret Le secret client OAuth
     * @param string        $redirectUri  L'URL de callback
     * @param array<string> $scopes       Les scopes à demander
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUri,
        array $scopes = []
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->scopes = array_merge($this->getDefaultScopes(), $scopes);
    }

    /**
     * Retourne l'URL d'autorisation du provider.
     */
    abstract protected function getAuthorizationEndpoint(): string;

    /**
     * Retourne l'URL du token endpoint.
     */
    abstract protected function getTokenEndpoint(): string;

    /**
     * Retourne l'URL de l'API userinfo.
     */
    abstract protected function getUserInfoEndpoint(): string;

    /**
     * Retourne les scopes par défaut.
     *
     * @return array<string>
     */
    abstract protected function getDefaultScopes(): array;

    /**
     * Parse la réponse userinfo en OAuthUser.
     *
     * @param array<string, mixed> $data Les données de l'API
     */
    abstract protected function parseUserInfo(array $data): OAuthUser;

    // =========================================================================
    // IMPLÉMENTATION DE L'INTERFACE
    // =========================================================================

    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
        ];

        return $this->getAuthorizationEndpoint() . '?' . http_build_query($params);
    }

    public function getAccessToken(string $code): OAuthToken
    {
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $response = $this->httpPost($this->getTokenEndpoint(), $params);

        if (isset($response['error'])) {
            throw OAuthException::fromResponse($response);
        }

        return OAuthToken::fromResponse($response);
    }

    public function getUser(OAuthToken $token): OAuthUser
    {
        $response = $this->httpGet(
            $this->getUserInfoEndpoint(),
            $token->getAuthorizationHeader()
        );

        if (isset($response['error'])) {
            throw OAuthException::fromResponse($response);
        }

        return $this->parseUserInfo($response);
    }

    public function refreshToken(string $refreshToken): OAuthToken
    {
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $response = $this->httpPost($this->getTokenEndpoint(), $params);

        if (isset($response['error'])) {
            throw OAuthException::fromResponse($response);
        }

        return OAuthToken::fromResponse($response);
    }

    // =========================================================================
    // REQUÊTES HTTP
    // =========================================================================

    /**
     * Effectue une requête POST.
     *
     * @param string               $url    L'URL
     * @param array<string, mixed> $params Les paramètres
     *
     * @return array<string, mixed> La réponse JSON
     */
    protected function httpPost(string $url, array $params): array
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                ],
                'content' => http_build_query($params),
            ],
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new OAuthException("Failed to connect to OAuth provider: {$url}");
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new OAuthException("Invalid JSON response from OAuth provider");
        }

        return $data;
    }

    /**
     * Effectue une requête GET.
     *
     * @param string $url           L'URL
     * @param string $authorization Le header Authorization
     *
     * @return array<string, mixed> La réponse JSON
     */
    protected function httpGet(string $url, string $authorization): array
    {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    "Authorization: {$authorization}",
                    'Accept: application/json',
                ],
            ],
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new OAuthException("Failed to fetch user info from OAuth provider");
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new OAuthException("Invalid JSON response from OAuth provider");
        }

        return $data;
    }
}
