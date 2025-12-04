<?php

declare(strict_types=1);

namespace Tests\Service\Security\Auth;

use Lunar\Service\Security\Auth\Authenticator;
use Lunar\Service\Security\Auth\PasswordHasher;
use Lunar\Service\Security\Auth\UserInterface;
use Lunar\Service\Security\Auth\UserProviderInterface;
use Lunar\Service\Session\SessionService;
use PHPUnit\Framework\TestCase;

class AuthenticatorTest extends TestCase
{
    private SessionService $session;
    private PasswordHasher $hasher;
    private TestUserProvider $userProvider;
    private Authenticator $auth;

    protected function setUp(): void
    {
        $this->session = new SessionService(testMode: true);
        $this->hasher = new PasswordHasher();
        $this->userProvider = new TestUserProvider($this->hasher);
        $this->auth = new Authenticator($this->userProvider, $this->hasher, $this->session);
    }

    public function testAttemptReturnsUserOnSuccess(): void
    {
        $user = $this->auth->attempt('john@example.com', 'password123');

        $this->assertNotNull($user);
        $this->assertSame('john@example.com', $user->getIdentifier());
    }

    public function testAttemptReturnsNullOnInvalidPassword(): void
    {
        $user = $this->auth->attempt('john@example.com', 'wrongpassword');

        $this->assertNull($user);
    }

    public function testAttemptReturnsNullOnInvalidIdentifier(): void
    {
        $user = $this->auth->attempt('unknown@example.com', 'password123');

        $this->assertNull($user);
    }

    public function testLoginStoresUserInSession(): void
    {
        $user = $this->userProvider->loadByIdentifier('john@example.com');
        $this->assertNotNull($user);

        $this->auth->login($user);

        $this->assertTrue($this->auth->check());
        $this->assertSame($user->getId(), $this->auth->id());
    }

    public function testLogoutRemovesUserFromSession(): void
    {
        $this->auth->attempt('john@example.com', 'password123');
        $this->assertTrue($this->auth->check());

        $this->auth->logout();

        $this->assertFalse($this->auth->check());
        $this->assertNull($this->auth->id());
    }

    public function testUserReturnsAuthenticatedUser(): void
    {
        $this->auth->attempt('john@example.com', 'password123');

        $user = $this->auth->user();

        $this->assertNotNull($user);
        $this->assertSame('john@example.com', $user->getIdentifier());
    }

    public function testUserReturnsNullWhenNotAuthenticated(): void
    {
        $this->assertNull($this->auth->user());
    }

    public function testCheckReturnsTrueWhenAuthenticated(): void
    {
        $this->auth->attempt('john@example.com', 'password123');

        $this->assertTrue($this->auth->check());
    }

    public function testCheckReturnsFalseWhenNotAuthenticated(): void
    {
        $this->assertFalse($this->auth->check());
    }

    public function testGuestReturnsTrueWhenNotAuthenticated(): void
    {
        $this->assertTrue($this->auth->guest());
    }

    public function testGuestReturnsFalseWhenAuthenticated(): void
    {
        $this->auth->attempt('john@example.com', 'password123');

        $this->assertFalse($this->auth->guest());
    }

    public function testIdReturnsUserIdWhenAuthenticated(): void
    {
        $this->auth->attempt('john@example.com', 'password123');

        $this->assertSame(1, $this->auth->id());
    }

    public function testIdReturnsNullWhenNotAuthenticated(): void
    {
        $this->assertNull($this->auth->id());
    }

    public function testValidateReturnsTrueForValidCredentials(): void
    {
        $this->assertTrue($this->auth->validate('john@example.com', 'password123'));
    }

    public function testValidateReturnsFalseForInvalidCredentials(): void
    {
        $this->assertFalse($this->auth->validate('john@example.com', 'wrongpassword'));
    }

    public function testValidateDoesNotLogInUser(): void
    {
        $this->auth->validate('john@example.com', 'password123');

        $this->assertTrue($this->auth->guest());
    }

    public function testMultipleUsersCanAuthenticate(): void
    {
        $user = $this->auth->attempt('jane@example.com', 'secret456');

        $this->assertNotNull($user);
        $this->assertSame('jane@example.com', $user->getIdentifier());
        $this->assertSame(2, $this->auth->id());
    }
}

/**
 * Test implementation of UserInterface.
 */
class TestUser implements UserInterface
{
    /** @param array<string> $roles */
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly string $password,
        private readonly array $roles = ['ROLE_USER']
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}

/**
 * Test implementation of UserProviderInterface.
 */
class TestUserProvider implements UserProviderInterface
{
    /** @var array<int, TestUser> */
    private array $users = [];

    public function __construct(PasswordHasher $hasher)
    {
        $this->users = [
            1 => new TestUser(1, 'john@example.com', $hasher->hash('password123'), ['ROLE_USER']),
            2 => new TestUser(2, 'jane@example.com', $hasher->hash('secret456'), ['ROLE_USER', 'ROLE_ADMIN']),
        ];
    }

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
}
