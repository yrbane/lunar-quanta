<?php
/**
 * Lunar Quanta Framework - Token OAuth.
 *
 * Représente un token d'accès OAuth avec ses métadonnées.
 *
 * @package    Lunar\Service\Security\OAuth
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Security\OAuth;

/**
 * Token OAuth avec access token et refresh token.
 */
class OAuthToken
{
    private string $accessToken;
    private ?string $refreshToken;
    private ?int $expiresIn;
    private \DateTimeImmutable $createdAt;
    private string $tokenType;

    /** @var array<string> Les scopes accordés */
    private array $scopes;

    /**
     * @param string        $accessToken  Le token d'accès
     * @param string|null   $refreshToken Le token de rafraîchissement
     * @param int|null      $expiresIn    Durée de vie en secondes
     * @param string        $tokenType    Type de token (Bearer)
     * @param array<string> $scopes       Les scopes accordés
     */
    public function __construct(
        string $accessToken,
        ?string $refreshToken = null,
        ?int $expiresIn = null,
        string $tokenType = 'Bearer',
        array $scopes = []
    ) {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresIn = $expiresIn;
        $this->tokenType = $tokenType;
        $this->scopes = $scopes;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * @return array<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Vérifie si le token est expiré.
     */
    public function isExpired(): bool
    {
        if ($this->expiresIn === null) {
            return false;
        }

        $expiresAt = $this->createdAt->modify("+{$this->expiresIn} seconds");

        return new \DateTimeImmutable() > $expiresAt;
    }

    /**
     * Retourne le header Authorization.
     */
    public function getAuthorizationHeader(): string
    {
        return $this->tokenType . ' ' . $this->accessToken;
    }

    /**
     * Crée un token depuis une réponse OAuth.
     *
     * @param array<string, mixed> $data La réponse JSON du provider
     */
    public static function fromResponse(array $data): self
    {
        return new self(
            $data['access_token'],
            $data['refresh_token'] ?? null,
            isset($data['expires_in']) ? (int) $data['expires_in'] : null,
            $data['token_type'] ?? 'Bearer',
            isset($data['scope']) ? explode(' ', $data['scope']) : []
        );
    }
}
