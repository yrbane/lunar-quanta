<?php
/**
 * Lunar Quanta Framework - Service de Réinitialisation de Mot de Passe.
 *
 * =============================================================================
 * RESPONSABILITÉS
 * =============================================================================
 *
 * Ce service gère le flux complet de récupération de mot de passe :
 *
 * 1. Création de tokens de reset sécurisés
 * 2. Envoi d'emails de récupération (délégué à un MailerInterface)
 * 3. Validation des tokens
 * 4. Réinitialisation du mot de passe
 *
 * =============================================================================
 * SÉCURITÉ
 * =============================================================================
 *
 * Plusieurs mesures de sécurité sont implémentées :
 *
 * ```
 * ┌─────────────────────────────────────────────────────────────────┐
 * │                    MESURES DE SÉCURITÉ                          │
 * ├─────────────────────────────────────────────────────────────────┤
 * │                                                                 │
 * │  1. TIMING CONSTANT                                             │
 * │     ───────────────                                             │
 * │     Même temps de réponse si l'email existe ou non              │
 * │     → Empêche l'énumération des utilisateurs                    │
 * │                                                                 │
 * │  2. TOKEN HASHÉ                                                 │
 * │     ────────────                                                │
 * │     Le token n'est jamais stocké en clair                       │
 * │     → Protection en cas de fuite de données                     │
 * │                                                                 │
 * │  3. EXPIRATION                                                  │
 * │     ──────────                                                  │
 * │     Token valide 1 heure maximum                                │
 * │     → Limite la fenêtre d'attaque                               │
 * │                                                                 │
 * │  4. USAGE UNIQUE                                                │
 * │     ────────────                                                │
 * │     Token invalidé après utilisation                            │
 * │     → Empêche la réutilisation                                  │
 * │                                                                 │
 * │  5. RATE LIMITING (à implémenter)                               │
 * │     ─────────────                                               │
 * │     Limite le nombre de demandes par email/IP                   │
 * │     → Protège contre le spam                                    │
 * │                                                                 │
 * └─────────────────────────────────────────────────────────────────┘
 * ```
 *
 * @package    Lunar\Service\Security\Auth
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

use Lunar\Entity\PasswordResetToken;
use Lunar\Entity\User;
use Lunar\Service\Storage\JsonStorage;

/**
 * Service de réinitialisation de mot de passe.
 *
 * Gère la création, validation et utilisation des tokens de reset.
 */
class PasswordResetService
{
    /**
     * Chemin du dossier de stockage des tokens.
     */
    private const TOKENS_PATH = 'data/password_reset';

    private JsonStorage $storage;

    public function __construct(?JsonStorage $storage = null)
    {
        $this->storage = $storage ?? new JsonStorage();
    }

    // =========================================================================
    // CRÉATION DE TOKEN
    // =========================================================================

    /**
     * Crée un token de réinitialisation pour un email.
     *
     * Cette méthode génère un token sécurisé et le stocke.
     * Le token en clair est retourné pour être envoyé par email.
     *
     * @param string $email L'email de l'utilisateur
     * @param int    $ttl   Durée de validité en secondes
     *
     * @return string Le token en clair (à envoyer par email)
     */
    public function createToken(string $email, int $ttl = PasswordResetToken::DEFAULT_TTL): string
    {
        // Invalide les anciens tokens pour cet email
        $this->invalidateTokensForEmail($email);

        // Génère un nouveau token
        $plainToken = PasswordResetToken::generateSecureToken();
        $token = new PasswordResetToken($email, $plainToken, $ttl);

        // Sauvegarde le token
        $this->saveToken($token);

        return $plainToken;
    }

    /**
     * Crée un token et retourne l'URL de reset complète.
     *
     * @param string $email   L'email de l'utilisateur
     * @param string $baseUrl L'URL de base de l'application
     * @param int    $ttl     Durée de validité en secondes
     *
     * @return string L'URL complète de réinitialisation
     */
    public function createResetUrl(
        string $email,
        string $baseUrl = '',
        int $ttl = PasswordResetToken::DEFAULT_TTL
    ): string {
        $token = $this->createToken($email, $ttl);

        return rtrim($baseUrl, '/') . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email);
    }

    // =========================================================================
    // VALIDATION ET UTILISATION
    // =========================================================================

    /**
     * Vérifie si un token est valide.
     *
     * @param string $email      L'email de l'utilisateur
     * @param string $plainToken Le token en clair
     *
     * @return bool true si le token est valide
     */
    public function isTokenValid(string $email, string $plainToken): bool
    {
        $token = $this->findValidToken($email, $plainToken);

        return $token !== null;
    }

    /**
     * Réinitialise le mot de passe avec un token valide.
     *
     * @param string $email       L'email de l'utilisateur
     * @param string $plainToken  Le token en clair
     * @param string $newPassword Le nouveau mot de passe
     *
     * @return bool true si le reset a réussi
     */
    public function resetPassword(string $email, string $plainToken, string $newPassword): bool
    {
        $token = $this->findValidToken($email, $plainToken);

        if ($token === null) {
            return false;
        }

        // Charge l'utilisateur
        $user = $this->storage->loadUser($email);

        if ($user === null) {
            return false;
        }

        // Met à jour le mot de passe
        $user->setPassword($newPassword);
        $this->storage->saveUser($user);

        // Invalide le token
        $token->markAsUsed();
        $this->saveToken($token);

        // Invalide tous les autres tokens pour cet email
        $this->invalidateTokensForEmail($email);

        return true;
    }

    // =========================================================================
    // GESTION DES TOKENS
    // =========================================================================

    /**
     * Trouve un token valide pour un email.
     *
     * @param string $email      L'email
     * @param string $plainToken Le token en clair
     *
     * @return PasswordResetToken|null Le token si valide, null sinon
     */
    private function findValidToken(string $email, string $plainToken): ?PasswordResetToken
    {
        $tokens = $this->loadTokensForEmail($email);

        foreach ($tokens as $token) {
            if ($token->isValid() && $token->verify($plainToken)) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Invalide tous les tokens d'un email.
     *
     * @param string $email L'email
     */
    public function invalidateTokensForEmail(string $email): void
    {
        $tokens = $this->loadTokensForEmail($email);

        foreach ($tokens as $token) {
            $token->markAsUsed();
            $this->saveToken($token);
        }
    }

    /**
     * Supprime les tokens expirés (nettoyage).
     *
     * @return int Le nombre de tokens supprimés
     */
    public function cleanExpiredTokens(): int
    {
        $deleted = 0;
        $tokensDir = $this->getTokensPath();

        if (!is_dir($tokensDir)) {
            return 0;
        }

        $files = glob($tokensDir . '/*.json');

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if (!is_array($data)) {
                continue;
            }

            $token = PasswordResetToken::fromArray($data);

            if ($token->isExpired()) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    // =========================================================================
    // PERSISTANCE
    // =========================================================================

    /**
     * Sauvegarde un token.
     *
     * @param PasswordResetToken $token Le token à sauvegarder
     */
    private function saveToken(PasswordResetToken $token): void
    {
        $path = $this->getTokenPath($token->getId());
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($token->toArray(), JSON_PRETTY_PRINT));
    }

    /**
     * Charge tous les tokens pour un email.
     *
     * @param string $email L'email
     *
     * @return array<PasswordResetToken> Les tokens
     */
    private function loadTokensForEmail(string $email): array
    {
        $tokens = [];
        $tokensDir = $this->getTokensPath();

        if (!is_dir($tokensDir)) {
            return [];
        }

        $files = glob($tokensDir . '/*.json');

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if (!is_array($data)) {
                continue;
            }

            if (($data['email'] ?? '') === $email) {
                $tokens[] = PasswordResetToken::fromArray($data);
            }
        }

        return $tokens;
    }

    /**
     * Retourne le chemin du dossier de tokens.
     */
    private function getTokensPath(): string
    {
        return getcwd() . '/' . self::TOKENS_PATH;
    }

    /**
     * Retourne le chemin d'un fichier token, avec protection path traversal.
     *
     * Même principe que FileStorage::getPath() : l'ID est nettoyé par
     * whitelist pour empêcher un attaquant de lire/écrire hors du
     * répertoire de tokens via un ID malveillant (ex: "../../etc/passwd").
     *
     * @param string $id L'identifiant du token
     *
     * @return string Le chemin sécurisé du fichier token
     *
     * @see FileStorage::getPath() Pour le même pattern de sanitization
     */
    private function getTokenPath(string $id): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);

        return $this->getTokensPath() . '/' . $safeId . '.json';
    }
}
