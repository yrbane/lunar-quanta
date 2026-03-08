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
 * Encryption service with AES-256-CBC and HMAC verification.
 *
 * Provides methods to encrypt and decrypt data with integrity verification.
 * Uses encrypt-then-MAC pattern for authenticated encryption.
 */
class EncryptionService implements EncryptionInterface
{
    private string $encryptionKey;
    private string $hmacKey;
    private string $cipher = 'AES-256-CBC';
    private string $hmacAlgo = 'sha256';

    /**
     * Constructor.
     *
     * @param string $key secret key for encryption
     */
    public function __construct(string $key)
    {
        // Derive separate keys for encryption and HMAC
        $derivedKey = hash('sha512', $key, true);
        $this->encryptionKey = substr($derivedKey, 0, 32);
        $this->hmacKey = substr($derivedKey, 32, 32);
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
