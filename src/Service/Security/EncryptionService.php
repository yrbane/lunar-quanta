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

namespace App\Service\Security;

/**
 * Class EncryptionService.
 *
 * Fournit des méthodes pour chiffrer et déchiffrer les données.
 */
class EncryptionService
{
    private string $key;
    private string $cipher = 'AES-256-CBC';

    /**
     * Constructeur.
     *
     * @param string $key clé secrète pour le chiffrement
     */
    public function __construct(string $key)
    {
        $this->key = substr(hash('sha256', $key), 0, 32);
    }

    /**
     * Chiffre une chaîne de caractères.
     *
     * @param string $data données à chiffrer
     *
     * @return string données chiffrées encodées en base64
     */
    public function encrypt(string $data): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv.$encrypted);
    }

    /**
     * Déchiffre une chaîne de caractères.
     *
     * @param string $encrypted données chiffrées en base64
     *
     * @return string données déchiffrées
     */
    public function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $cipherText = substr($data, $ivLength);

        return openssl_decrypt($cipherText, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
    }
}
