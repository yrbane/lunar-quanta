<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

use Lunar\Service\Session\SessionInterface;

/**
 * Main authentication service.
 *
 * Handles user authentication, session management, and user retrieval.
 */
class Authenticator
{
    private const SESSION_USER_KEY = '_auth_user_id';

    private UserProviderInterface $userProvider;
    private PasswordHasherInterface $passwordHasher;
    private SessionInterface $session;

    public function __construct(
        UserProviderInterface $userProvider,
        PasswordHasherInterface $passwordHasher,
        SessionInterface $session
    ) {
        $this->userProvider = $userProvider;
        $this->passwordHasher = $passwordHasher;
        $this->session = $session;
    }

    /**
     * Attempt to authenticate a user with credentials.
     *
     * @param string $identifier Username or email
     * @param string $password Plain-text password
     * @return UserInterface|null The authenticated user or null on failure
     */
    public function attempt(string $identifier, string $password): ?UserInterface
    {
        $user = $this->userProvider->loadByIdentifier($identifier);

        if (null === $user) {
            return null;
        }

        if (!$this->passwordHasher->verify($password, $user->getPassword())) {
            return null;
        }

        // Check if password needs rehashing
        if ($this->passwordHasher->needsRehash($user->getPassword())) {
            // Note: Actual rehashing would need to be handled by the application
            // as it requires updating the user in the data store
        }

        $this->login($user);

        return $user;
    }

    /**
     * Log in a user (store in session).
     */
    public function login(UserInterface $user): void
    {
        $this->session->regenerate();
        $this->session->set(self::SESSION_USER_KEY, $user->getId());
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        $this->session->remove(self::SESSION_USER_KEY);
        $this->session->regenerate();
    }

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?UserInterface
    {
        $userId = $this->session->get(self::SESSION_USER_KEY);

        if (null === $userId) {
            return null;
        }

        if (!is_string($userId) && !is_int($userId)) {
            return null;
        }

        return $this->userProvider->loadById($userId);
    }

    /**
     * Check if a user is currently authenticated.
     */
    public function check(): bool
    {
        return null !== $this->user();
    }

    /**
     * Check if no user is authenticated (guest).
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get the authenticated user's ID.
     *
     * @return string|int|null
     */
    public function id(): string|int|null
    {
        $userId = $this->session->get(self::SESSION_USER_KEY);

        if (!is_string($userId) && !is_int($userId)) {
            return null;
        }

        return $userId;
    }

    /**
     * Validate credentials without logging in.
     */
    public function validate(string $identifier, string $password): bool
    {
        $user = $this->userProvider->loadByIdentifier($identifier);

        if (null === $user) {
            return false;
        }

        return $this->passwordHasher->verify($password, $user->getPassword());
    }
}
