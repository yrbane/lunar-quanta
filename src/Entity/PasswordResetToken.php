<?php
/**
 * Lunar Quanta Framework - Token de Réinitialisation de Mot de Passe.
 *
 * =============================================================================
 * FONCTIONNEMENT DU RESET PASSWORD
 * =============================================================================
 *
 * Le processus de récupération de mot de passe suit ce flux :
 *
 * ```
 * ÉTAPE 1 : Demande de reset
 * ─────────────────────────
 *
 *     Utilisateur            Système
 *         │                     │
 *         │  "J'ai oublié"      │
 *         ├────────────────────►│
 *         │                     │
 *         │                     ▼
 *         │            ┌─────────────────┐
 *         │            │ Génère un token │
 *         │            │ aléatoire sûr   │
 *         │            └────────┬────────┘
 *         │                     │
 *         │     Email avec      │
 *         │◄────────────────────┤
 *         │     lien + token    │
 *
 *
 * ÉTAPE 2 : Reset du mot de passe
 * ────────────────────────────────
 *
 *     Utilisateur            Système
 *         │                     │
 *         │  Clique sur lien    │
 *         ├────────────────────►│
 *         │                     │
 *         │                     ▼
 *         │            ┌─────────────────┐
 *         │            │ Vérifie token : │
 *         │            │ - Existe ?      │
 *         │            │ - Non expiré ?  │
 *         │            │ - Non utilisé ? │
 *         │            └────────┬────────┘
 *         │                     │
 *         │   Formulaire reset  │
 *         │◄────────────────────┤
 *         │                     │
 *         │  Nouveau password   │
 *         ├────────────────────►│
 *         │                     │
 *         │                     ▼
 *         │            ┌─────────────────┐
 *         │            │ Invalide token  │
 *         │            │ Change password │
 *         │            └─────────────────┘
 * ```
 *
 * =============================================================================
 * SÉCURITÉ DES TOKENS
 * =============================================================================
 *
 * Les tokens suivent ces principes de sécurité :
 *
 * 1. GÉNÉRATION SÛRE : Utilise random_bytes() (cryptographiquement sûr)
 * 2. HASHAGE : Le token est hashé avant stockage (comme un password)
 * 3. EXPIRATION : Durée de vie limitée (1 heure par défaut)
 * 4. USAGE UNIQUE : Le token est invalidé après utilisation
 *
 * ```
 * Token généré     Token stocké
 * (envoyé)         (en base)
 *     │                │
 *     │                │
 * "abc123..."  →  hash("abc123...")
 *     │                │
 *     └── On compare ──┘
 *         les hashes
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
 * Représente un token de réinitialisation de mot de passe.
 *
 * Ce token est généré quand un utilisateur demande à récupérer son mot de passe.
 * Il est envoyé par email et permet de réinitialiser le mot de passe une seule fois.
 */
class PasswordResetToken
{
    /**
     * Durée de validité par défaut (1 heure).
     */
    public const DEFAULT_TTL = 3600;

    /**
     * Identifiant unique du token.
     */
    private string $id;

    /**
     * Email de l'utilisateur associé.
     */
    private string $email;

    /**
     * Hash du token (le token en clair n'est jamais stocké).
     */
    private string $tokenHash;

    /**
     * Date d'expiration du token.
     */
    private \DateTimeImmutable $expiresAt;

    /**
     * Indique si le token a été utilisé.
     */
    private bool $used = false;

    /**
     * Date de création.
     */
    private \DateTimeImmutable $createdAt;

    /**
     * Crée un nouveau token de reset.
     *
     * @param string $email L'email de l'utilisateur
     * @param string $token Le token en clair (sera hashé)
     * @param int    $ttl   Durée de vie en secondes
     */
    public function __construct(string $email, string $token, int $ttl = self::DEFAULT_TTL)
    {
        $this->id = self::generateId();
        $this->email = $email;
        $this->tokenHash = self::hashToken($token);
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify("+{$ttl} seconds");
    }

    // =========================================================================
    // GETTERS
    // =========================================================================

    /**
     * Retourne l'identifiant du token.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Retourne l'email associé.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Retourne le hash du token.
     */
    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    /**
     * Retourne la date d'expiration.
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Indique si le token a été utilisé.
     */
    public function isUsed(): bool
    {
        return $this->used;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // =========================================================================
    // VÉRIFICATION ET VALIDATION
    // =========================================================================

    /**
     * Vérifie si le token est expiré.
     *
     * @return bool true si expiré
     */
    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }

    /**
     * Vérifie si le token est valide (non expiré et non utilisé).
     *
     * @return bool true si le token peut être utilisé
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->used;
    }

    /**
     * Vérifie si un token en clair correspond au hash stocké.
     *
     * @param string $token Le token en clair à vérifier
     *
     * @return bool true si le token correspond
     */
    public function verify(string $token): bool
    {
        return hash_equals($this->tokenHash, self::hashToken($token));
    }

    /**
     * Marque le token comme utilisé.
     *
     * @return self Pour le chaînage
     */
    public function markAsUsed(): self
    {
        $this->used = true;

        return $this;
    }

    // =========================================================================
    // SÉRIALISATION
    // =========================================================================

    /**
     * Convertit le token en tableau pour la persistance.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'tokenHash' => $this->tokenHash,
            'expiresAt' => $this->expiresAt->format('c'),
            'used' => $this->used,
            'createdAt' => $this->createdAt->format('c'),
        ];
    }

    /**
     * Reconstruit un token depuis un tableau.
     *
     * @param array<string, mixed> $data Les données du token
     *
     * @return self Le token reconstruit
     */
    public static function fromArray(array $data): self
    {
        /** @var string $email */
        $email = $data['email'];
        $token = new self($email, 'placeholder');

        /** @var string $id */
        $id = $data['id'];
        /** @var string $tokenHash */
        $tokenHash = $data['tokenHash'];
        /** @var string $expiresAt */
        $expiresAt = $data['expiresAt'];
        /** @var string $createdAt */
        $createdAt = $data['createdAt'];

        $token->id = $id;
        $token->tokenHash = $tokenHash;
        $token->expiresAt = new \DateTimeImmutable($expiresAt);
        $token->used = isset($data['used']) && $data['used'] === true;
        $token->createdAt = new \DateTimeImmutable($createdAt);

        return $token;
    }

    // =========================================================================
    // MÉTHODES STATIQUES UTILITAIRES
    // =========================================================================

    /**
     * Génère un token aléatoire cryptographiquement sûr.
     *
     * @param int $length Longueur du token en octets (32 par défaut = 64 hex)
     *
     * @return string Le token en hexadécimal
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash un token pour le stockage.
     *
     * @param string $token Le token en clair
     *
     * @return string Le hash SHA-256
     */
    private static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Génère un identifiant unique.
     *
     * @return string L'identifiant
     */
    private static function generateId(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
