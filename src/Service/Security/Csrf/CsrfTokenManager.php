<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Csrf;

use Lunar\Service\Session\SessionInterface;

/**
 * CSRF token manager using session storage.
 *
 * Generates and validates CSRF tokens to protect against
 * Cross-Site Request Forgery attacks.
 */
class CsrfTokenManager implements CsrfTokenManagerInterface
{
    private const SESSION_KEY = '_csrf_tokens';
    private const TOKEN_LENGTH = 32;

    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function generate(string $tokenId): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $this->storeToken($tokenId, $token);
        return $token;
    }

    public function isValid(string $tokenId, string $token): bool
    {
        if ('' === $token) {
            return false;
        }

        $storedToken = $this->getStoredToken($tokenId);
        if (null === $storedToken) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    public function remove(string $tokenId): void
    {
        /** @var array<string, string> $tokens */
        $tokens = $this->session->get(self::SESSION_KEY, []);
        unset($tokens[$tokenId]);
        $this->session->set(self::SESSION_KEY, $tokens);
    }

    /**
     * Store a token in the session.
     */
    private function storeToken(string $tokenId, string $token): void
    {
        /** @var array<string, string> $tokens */
        $tokens = $this->session->get(self::SESSION_KEY, []);
        $tokens[$tokenId] = $token;
        $this->session->set(self::SESSION_KEY, $tokens);
    }

    /**
     * Get a stored token from the session.
     */
    private function getStoredToken(string $tokenId): ?string
    {
        /** @var array<string, string> $tokens */
        $tokens = $this->session->get(self::SESSION_KEY, []);
        return $tokens[$tokenId] ?? null;
    }
}
