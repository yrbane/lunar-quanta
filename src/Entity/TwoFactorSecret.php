<?php
/**
 * Lunar Quanta Framework - Configuration 2FA d'un utilisateur.
 *
 * =============================================================================
 * STRUCTURE DES DONNÉES 2FA
 * =============================================================================
 *
 * ```
 * TwoFactorSecret
 * ├── userId          → Identifiant de l'utilisateur
 * ├── secret          → Clé secrète TOTP (Base32)
 * ├── enabled         → 2FA activé ou non
 * ├── recoveryCodes   → Codes de secours (hashés)
 * └── verifiedAt      → Date de première vérification
 * ```
 *
 * @package    Lunar\Entity
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Entity;

/**
 * Configuration 2FA d'un utilisateur.
 */
class TwoFactorSecret
{
    private string $userId;
    private string $secret;
    private bool $enabled = false;

    /** @var array<string> Codes de récupération hashés */
    private array $recoveryCodes = [];

    private ?\DateTimeImmutable $verifiedAt = null;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    /**
     * Crée une nouvelle configuration 2FA.
     *
     * @param string $userId L'identifiant de l'utilisateur
     * @param string $secret Le secret TOTP en Base32
     */
    public function __construct(string $userId, string $secret)
    {
        $this->userId = $userId;
        $this->secret = $secret;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // =========================================================================
    // GETTERS
    // =========================================================================

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<string> Les codes hashés
     */
    public function getRecoveryCodes(): array
    {
        return $this->recoveryCodes;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // =========================================================================
    // ACTIONS
    // =========================================================================

    /**
     * Active le 2FA après vérification.
     *
     * @return self
     */
    public function enable(): self
    {
        $this->enabled = true;
        $this->verifiedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Désactive le 2FA.
     *
     * @return self
     */
    public function disable(): self
    {
        $this->enabled = false;
        $this->verifiedAt = null;
        $this->recoveryCodes = [];
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Définit les codes de récupération (hashés).
     *
     * @param array<string> $hashedCodes Les codes hashés
     *
     * @return self
     */
    public function setRecoveryCodes(array $hashedCodes): self
    {
        $this->recoveryCodes = $hashedCodes;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Invalide un code de récupération utilisé.
     *
     * @param int $index L'index du code à invalider
     *
     * @return self
     */
    public function invalidateRecoveryCode(int $index): self
    {
        if (isset($this->recoveryCodes[$index])) {
            unset($this->recoveryCodes[$index]);
            $this->recoveryCodes = array_values($this->recoveryCodes);
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * Retourne le nombre de codes de récupération restants.
     */
    public function getRemainingRecoveryCodesCount(): int
    {
        return count($this->recoveryCodes);
    }

    // =========================================================================
    // SÉRIALISATION
    // =========================================================================

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'secret' => $this->secret,
            'enabled' => $this->enabled,
            'recoveryCodes' => $this->recoveryCodes,
            'verifiedAt' => $this->verifiedAt?->format('c'),
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $secret = new self($data['userId'], $data['secret']);
        $secret->enabled = $data['enabled'] ?? false;
        $secret->recoveryCodes = $data['recoveryCodes'] ?? [];
        $secret->verifiedAt = isset($data['verifiedAt'])
            ? new \DateTimeImmutable($data['verifiedAt'])
            : null;
        $secret->createdAt = new \DateTimeImmutable($data['createdAt']);
        $secret->updatedAt = new \DateTimeImmutable($data['updatedAt']);

        return $secret;
    }
}
