<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

namespace Lunar\Service\Security;

use Lunar\Exception\SecurityException;

/**
 * Service de chiffrement AES-256-CBC avec vérification HMAC.
 *
 * Utilise le pattern Encrypt-then-MAC (EtM), reconnu comme le plus sûr
 * des trois patterns d'authenticated encryption :
 * - Encrypt-then-MAC (EtM) ✓ : HMAC calculé sur le ciphertext
 * - Encrypt-and-MAC (E&M)   : HMAC calculé sur le plaintext
 * - MAC-then-Encrypt (MtE)  : vulnérable aux padding oracle attacks
 *
 * ```
 * Flux de chiffrement :
 *
 *   plaintext → [AES-256-CBC + IV aléatoire] → ciphertext
 *                                                    ↓
 *   IV + ciphertext → [HMAC-SHA256] → tag d'intégrité
 *                                          ↓
 *   Sortie : base64(IV || ciphertext || HMAC)
 * ```
 *
 * Choix de sécurité :
 * - IV généré par random_bytes() (CSPRNG du système, pas openssl)
 * - Clés dérivées : SHA-512 de la master key, split en 2 clés de 32 octets
 * - Vérification HMAC avec hash_equals() (constant-time, anti timing-attack)
 *
 * @see docs/security.md Pour l'architecture de sécurité complète
 */
class EncryptionService implements EncryptionInterface
{
    private string $encryptionKey;
    private string $hmacKey;
    private string $cipher = 'AES-256-CBC';
    private string $hmacAlgo = 'sha256';

    /**
     * Dérive deux clés distinctes depuis la master key.
     *
     * Utiliser la même clé pour le chiffrement et le HMAC serait une
     * mauvaise pratique : si l'une est compromise, l'autre le serait aussi.
     * SHA-512 produit 64 octets, divisés en 2 clés de 32 octets chacune.
     *
     * @param string $key La clé maîtresse (APP_KEY)
     */
    public function __construct(string $key)
    {
        // Dérivation : 1 master key → 2 clés indépendantes
        $derivedKey = hash('sha512', $key, true);
        $this->encryptionKey = substr($derivedKey, 0, 32);  // Clé AES
        $this->hmacKey = substr($derivedKey, 32, 32);        // Clé HMAC
    }

    /**
     * Encrypt data with HMAC verification.
     *
     * Format: base64(IV + ciphertext + HMAC)
     *
     * @param string $plaintext data to encrypt
     *
     * @return string encrypted data encoded in base64
     *
     * @throws SecurityException if encryption fails
     */
    public function encrypt(string $plaintext): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        if (false === $ivLength) {
            throw new SecurityException("Cipher {$this->cipher} not supported");
        }

        $iv = random_bytes($ivLength);
        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $this->encryptionKey, OPENSSL_RAW_DATA, $iv);

        if (false === $ciphertext) {
            throw new SecurityException('Encryption failed');
        }

        // Compute HMAC over IV + ciphertext (encrypt-then-MAC)
        $hmac = hash_hmac($this->hmacAlgo, $iv.$ciphertext, $this->hmacKey, true);

        return base64_encode($iv.$ciphertext.$hmac);
    }

    /**
     * Decrypt data with HMAC verification.
     *
     * @param string $ciphertext encrypted data in base64
     *
     * @return string decrypted data
     *
     * @throws SecurityException if decryption or HMAC verification fails
     */
    public function decrypt(string $ciphertext): string
    {
        $data = base64_decode($ciphertext, true);
        if (false === $data) {
            throw new SecurityException('Invalid base64 encoding');
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        if (false === $ivLength) {
            throw new SecurityException("Cipher {$this->cipher} not supported");
        }

        $hmacLength = 32; // SHA-256 produces 32 bytes

        // Minimum length: IV + at least 0 bytes ciphertext + HMAC
        if (strlen($data) < $ivLength + $hmacLength) {
            throw new SecurityException('Invalid encrypted data format');
        }

        // Extract components
        $iv = substr($data, 0, $ivLength);
        $hmac = substr($data, -$hmacLength);
        $encryptedData = substr($data, $ivLength, -$hmacLength);

        // Verify HMAC first (constant-time comparison)
        $expectedHmac = hash_hmac($this->hmacAlgo, $iv.$encryptedData, $this->hmacKey, true);
        if (!hash_equals($expectedHmac, $hmac)) {
            throw new SecurityException('HMAC verification failed: data may have been tampered with');
        }

        $decrypted = openssl_decrypt($encryptedData, $this->cipher, $this->encryptionKey, OPENSSL_RAW_DATA, $iv);
        if (false === $decrypted) {
            throw new SecurityException('Decryption failed');
        }

        return $decrypted;
    }
}
