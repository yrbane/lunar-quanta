<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

/**
 * Interface for user providers.
 *
 * User providers are responsible for loading users from a data source
 * (database, file, memory, etc.).
 */
interface UserProviderInterface
{
    /**
     * Load a user by their unique identifier (username or email).
     *
     * @param string $identifier The user identifier
     * @return UserInterface|null The user or null if not found
     */
    public function loadByIdentifier(string $identifier): ?UserInterface;

    /**
     * Load a user by their ID.
     *
     * @param string|int $id The user ID
     * @return UserInterface|null The user or null if not found
     */
    public function loadById(string|int $id): ?UserInterface;
}
