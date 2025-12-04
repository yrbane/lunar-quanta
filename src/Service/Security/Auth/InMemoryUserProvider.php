<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

/**
 * In-memory user provider for testing and simple applications.
 */
class InMemoryUserProvider implements UserProviderInterface
{
    /** @var array<string|int, InMemoryUser> */
    private array $users = [];

    public function loadByIdentifier(string $identifier): ?UserInterface
    {
        foreach ($this->users as $user) {
            if ($user->getIdentifier() === $identifier) {
                return $user;
            }
        }
        return null;
    }

    public function loadById(string|int $id): ?UserInterface
    {
        return $this->users[$id] ?? null;
    }

    /**
     * Add a user to the provider.
     */
    public function addUser(InMemoryUser $user): self
    {
        $this->users[$user->getId()] = $user;
        return $this;
    }

    /**
     * Create a user with hashed password and add to the provider.
     *
     * @param string|int $id The user ID
     * @param string $identifier Username or email
     * @param string $plainPassword Plain-text password to hash
     * @param array<string> $roles User roles
     */
    public function createUser(
        string|int $id,
        string $identifier,
        string $plainPassword,
        PasswordHasherInterface $hasher,
        array $roles = ['ROLE_USER']
    ): self {
        $user = new InMemoryUser(
            $id,
            $identifier,
            $hasher->hash($plainPassword),
            $roles
        );
        return $this->addUser($user);
    }
}

/**
 * Simple user implementation for in-memory storage.
 */
class InMemoryUser implements UserInterface
{
    /**
     * @param string|int $id
     * @param string $identifier
     * @param string $hashedPassword
     * @param array<string> $roles
     */
    public function __construct(
        private readonly string|int $id,
        private readonly string $identifier,
        private readonly string $hashedPassword,
        private readonly array $roles = ['ROLE_USER']
    ) {
    }

    public function getId(): string|int
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPassword(): string
    {
        return $this->hashedPassword;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
