<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

/**
 * Interface for authenticated users.
 *
 * Any class representing a user in the authentication system
 * should implement this interface.
 */
interface UserInterface
{
    /**
     * Get the unique identifier of the user.
     */
    public function getId(): string|int;

    /**
     * Get the username or email used for authentication.
     */
    public function getIdentifier(): string;

    /**
     * Get the hashed password.
     */
    public function getPassword(): string;

    /**
     * Get the roles assigned to this user.
     *
     * @return array<string>
     */
    public function getRoles(): array;
}
