<?php
/**
 * Lunar Quanta Framework - Utilisateur OAuth.
 *
 * Représente les informations d'un utilisateur obtenues via OAuth.
 *
 * @package    Lunar\Service\Security\OAuth
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Security\OAuth;

/**
 * Informations utilisateur OAuth normalisées.
 *
 * Les différents providers retournent des formats différents.
 * Cette classe normalise les données.
 */
class OAuthUser
{
    private string $provider;
    private string $providerId;
    private string $email;
    private ?string $name;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $avatar;
    private ?string $profileUrl;

    /** @var array<string, mixed> Données brutes du provider */
    private array $rawData;

    /**
     * @param string               $provider   Le nom du provider (google, github)
     * @param string               $providerId L'ID unique chez le provider
     * @param string               $email      L'email de l'utilisateur
     * @param string|null          $name       Le nom complet
     * @param string|null          $avatar     L'URL de l'avatar
     * @param array<string, mixed> $rawData    Les données brutes
     */
    public function __construct(
        string $provider,
        string $providerId,
        string $email,
        ?string $name = null,
        ?string $avatar = null,
        array $rawData = []
    ) {
        $this->provider = $provider;
        $this->providerId = $providerId;
        $this->email = $email;
        $this->name = $name;
        $this->avatar = $avatar;
        $this->rawData = $rawData;

        // Tente d'extraire prénom/nom
        if ($name !== null) {
            $parts = explode(' ', $name, 2);
            $this->firstName = $parts[0];
            $this->lastName = $parts[1] ?? null;
        } else {
            $this->firstName = null;
            $this->lastName = null;
        }

        $this->profileUrl = null;
    }

    // =========================================================================
    // GETTERS
    // =========================================================================

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getProviderId(): string
    {
        return $this->providerId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getProfileUrl(): ?string
    {
        return $this->profileUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    // =========================================================================
    // SETTERS
    // =========================================================================

    public function setProfileUrl(?string $url): self
    {
        $this->profileUrl = $url;

        return $this;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    // =========================================================================
    // UTILITAIRES
    // =========================================================================

    /**
     * Génère une clé unique pour cet utilisateur OAuth.
     *
     * Format : provider:providerId
     */
    public function getUniqueKey(): string
    {
        return $this->provider . ':' . $this->providerId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'providerId' => $this->providerId,
            'email' => $this->email,
            'name' => $this->name,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'avatar' => $this->avatar,
            'profileUrl' => $this->profileUrl,
        ];
    }
}
