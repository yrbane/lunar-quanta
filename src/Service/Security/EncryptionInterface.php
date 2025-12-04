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
 * Interface for encryption services.
 *
 * Defines the contract for data encryption and decryption.
 */
interface EncryptionInterface
{
    /**
     * Encrypt data.
     *
     * @throws SecurityException
     */
    public function encrypt(string $plaintext): string;

    /**
     * Decrypt data.
     *
     * @throws SecurityException
     */
    public function decrypt(string $ciphertext): string;
}
