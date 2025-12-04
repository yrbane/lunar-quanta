<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Csrf;

/**
 * Interface for CSRF token management.
 */
interface CsrfTokenManagerInterface
{
    /**
     * Generate a new CSRF token for a given token ID.
     *
     * @param string $tokenId Unique identifier for the token (e.g., 'form_login')
     * @return string The generated token value
     */
    public function generate(string $tokenId): string;

    /**
     * Validate a CSRF token.
     *
     * @param string $tokenId The token identifier
     * @param string $token The token value to validate
     * @return bool True if valid, false otherwise
     */
    public function isValid(string $tokenId, string $token): bool;

    /**
     * Remove a token from storage.
     *
     * @param string $tokenId The token identifier to remove
     */
    public function remove(string $tokenId): void;
}
