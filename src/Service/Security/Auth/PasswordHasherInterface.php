<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

/**
 * Interface for password hashing.
 */
interface PasswordHasherInterface
{
    /**
     * Hash a plain-text password.
     *
     * @param string $plainPassword The password to hash
     * @return string The hashed password
     */
    public function hash(string $plainPassword): string;

    /**
     * Verify a plain-text password against a hash.
     *
     * @param string $plainPassword The password to verify
     * @param string $hashedPassword The hash to verify against
     * @return bool True if the password matches
     */
    public function verify(string $plainPassword, string $hashedPassword): bool;

    /**
     * Check if a hash needs to be rehashed.
     *
     * @param string $hashedPassword The current hash
     * @return bool True if rehashing is recommended
     */
    public function needsRehash(string $hashedPassword): bool;
}
