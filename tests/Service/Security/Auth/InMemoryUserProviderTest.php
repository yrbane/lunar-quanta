<?php
/**
 * Tests unitaires pour InMemoryUserProvider et InMemoryUser.
 *
 * =============================================================================
 * QU'EST-CE QU'UN USER PROVIDER ?
 * =============================================================================
 *
 * Un UserProvider est une source de données utilisateurs. Il peut être :
 * - En mémoire (InMemoryUserProvider) : pour les tests
 * - Base de données : pour la production
 * - API externe : pour l'authentification tierce
 *
 * ```
 * AUTHENTICATOR                    USER PROVIDER
 *      │                                │
 *      │ "Donne-moi l'utilisateur       │
 *      │  avec cet email"               │
 *      │ ─────────────────────────────► │
 *      │                                │
 *      │ ◄───────────────────────────── │
 *      │   UserInterface ou null        │
 *      │                                │
 * ```
 *
 * =============================================================================
 * POURQUOI UN PROVIDER EN MÉMOIRE ?
 * =============================================================================
 *
 * 1. TESTS : Pas besoin de base de données pour tester l'authentification
 * 2. PROTOTYPAGE : Développer rapidement sans infrastructure
 * 3. SIMPLICITÉ : Configuration directe dans le code
 *
 * ```php
 * // Création d'utilisateurs pour les tests
 * $provider = new InMemoryUserProvider();
 * $provider->createUser(1, 'admin@test.com', 'secret', $hasher);
 * $provider->createUser(2, 'user@test.com', 'password', $hasher);
 *
 * // Utilisation avec l'authentificateur
 * $auth = new Authenticator($provider, $session, $hasher);
 * ```
 *
 * @package Tests\Service\Security\Auth
 */
declare(strict_types=1);

namespace Tests\Service\Security\Auth;

use Lunar\Service\Security\Auth\InMemoryUser;
use Lunar\Service\Security\Auth\InMemoryUserProvider;
use Lunar\Service\Security\Auth\PasswordHasher;
use Lunar\Service\Security\Auth\UserInterface;
use PHPUnit\Framework\TestCase;

class InMemoryUserProviderTest extends TestCase
{
    private InMemoryUserProvider $provider;
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->provider = new InMemoryUserProvider();
        $this->hasher = new PasswordHasher();
    }

    // =========================================================================
    // TESTS DE CRÉATION D'UTILISATEURS
    // =========================================================================

    public function testAddUserStoresUser(): void
    {
        $user = new InMemoryUser(1, 'test@example.com', 'hashed_password');

        $result = $this->provider->addUser($user);

        $this->assertSame($this->provider, $result); // Fluent interface
        $this->assertSame($user, $this->provider->loadById(1));
    }

    public function testCreateUserHashesPassword(): void
    {
        $this->provider->createUser(1, 'test@example.com', 'plaintext', $this->hasher);

        $user = $this->provider->loadById(1);

        $this->assertNotNull($user);
        $this->assertNotSame('plaintext', $user->getPassword());
        $this->assertTrue($this->hasher->verify('plaintext', $user->getPassword()));
    }

    public function testCreateUserWithCustomRoles(): void
    {
        $roles = ['ROLE_ADMIN', 'ROLE_MODERATOR'];
        $this->provider->createUser(1, 'admin@example.com', 'pass', $this->hasher, $roles);

        $user = $this->provider->loadById(1);

        $this->assertNotNull($user);
        $this->assertSame($roles, $user->getRoles());
    }

    public function testCreateUserWithDefaultRoles(): void
    {
        $this->provider->createUser(1, 'user@example.com', 'pass', $this->hasher);

        $user = $this->provider->loadById(1);

        $this->assertNotNull($user);
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testCreateUserReturnsFluentInterface(): void
    {
        $result = $this->provider->createUser(1, 'test@example.com', 'pass', $this->hasher);

        $this->assertSame($this->provider, $result);
    }

    // =========================================================================
    // TESTS DE CHARGEMENT PAR IDENTIFIANT
    // =========================================================================

    public function testLoadByIdentifierFindsUser(): void
    {
        $this->provider->createUser(1, 'find@example.com', 'pass', $this->hasher);

        $user = $this->provider->loadByIdentifier('find@example.com');

        $this->assertNotNull($user);
        $this->assertSame('find@example.com', $user->getIdentifier());
    }

    public function testLoadByIdentifierReturnsNullForUnknown(): void
    {
        $user = $this->provider->loadByIdentifier('unknown@example.com');

        $this->assertNull($user);
    }

    public function testLoadByIdentifierWithMultipleUsers(): void
    {
        $this->provider->createUser(1, 'first@example.com', 'pass1', $this->hasher);
        $this->provider->createUser(2, 'second@example.com', 'pass2', $this->hasher);
        $this->provider->createUser(3, 'third@example.com', 'pass3', $this->hasher);

        $user = $this->provider->loadByIdentifier('second@example.com');

        $this->assertNotNull($user);
        $this->assertSame(2, $user->getId());
    }

    // =========================================================================
    // TESTS DE CHARGEMENT PAR ID
    // =========================================================================

    public function testLoadByIdFindsUser(): void
    {
        $this->provider->createUser(42, 'user42@example.com', 'pass', $this->hasher);

        $user = $this->provider->loadById(42);

        $this->assertNotNull($user);
        $this->assertSame(42, $user->getId());
    }

    public function testLoadByIdReturnsNullForUnknown(): void
    {
        $user = $this->provider->loadById(999);

        $this->assertNull($user);
    }

    public function testLoadByIdWithStringId(): void
    {
        $this->provider->createUser('uuid-123', 'uuid@example.com', 'pass', $this->hasher);

        $user = $this->provider->loadById('uuid-123');

        $this->assertNotNull($user);
        $this->assertSame('uuid-123', $user->getId());
    }

    // =========================================================================
    // TESTS DE CAS LIMITES
    // =========================================================================

    public function testMultipleUsersWithDifferentTypes(): void
    {
        $this->provider->createUser(1, 'int@example.com', 'pass', $this->hasher);
        $this->provider->createUser('abc', 'string@example.com', 'pass', $this->hasher);

        $this->assertNotNull($this->provider->loadById(1));
        $this->assertNotNull($this->provider->loadById('abc'));
    }

    public function testOverwriteUserWithSameId(): void
    {
        $this->provider->createUser(1, 'original@example.com', 'pass', $this->hasher);
        $this->provider->createUser(1, 'updated@example.com', 'newpass', $this->hasher);

        $user = $this->provider->loadById(1);

        $this->assertNotNull($user);
        $this->assertSame('updated@example.com', $user->getIdentifier());
    }

    public function testEmptyProviderReturnsNull(): void
    {
        $emptyProvider = new InMemoryUserProvider();

        $this->assertNull($emptyProvider->loadByIdentifier('any@example.com'));
        $this->assertNull($emptyProvider->loadById(1));
    }

    // =========================================================================
    // TESTS DE CHAÎNAGE FLUENT
    // =========================================================================

    public function testFluentChaining(): void
    {
        $this->provider
            ->createUser(1, 'first@example.com', 'pass1', $this->hasher)
            ->createUser(2, 'second@example.com', 'pass2', $this->hasher)
            ->addUser(new InMemoryUser(3, 'third@example.com', 'hash3'));

        $this->assertNotNull($this->provider->loadById(1));
        $this->assertNotNull($this->provider->loadById(2));
        $this->assertNotNull($this->provider->loadById(3));
    }
}

/**
 * Tests unitaires pour InMemoryUser.
 *
 * =============================================================================
 * QU'EST-CE QU'UN USER ?
 * =============================================================================
 *
 * Un User est un objet qui représente un utilisateur authentifiable.
 * Il implémente UserInterface qui définit les méthodes obligatoires :
 *
 * ```
 * UserInterface
 *      │
 *      ├── getId()         → Identifiant unique (int ou string)
 *      ├── getIdentifier() → Nom d'utilisateur ou email
 *      ├── getPassword()   → Mot de passe hashé
 *      └── getRoles()      → Tableau de rôles ['ROLE_USER', ...]
 * ```
 */
class InMemoryUserTest extends TestCase
{
    // =========================================================================
    // TESTS DU CONSTRUCTEUR
    // =========================================================================

    public function testConstructorWithIntId(): void
    {
        $user = new InMemoryUser(42, 'user@example.com', 'hashed_password');

        $this->assertSame(42, $user->getId());
        $this->assertSame('user@example.com', $user->getIdentifier());
        $this->assertSame('hashed_password', $user->getPassword());
    }

    public function testConstructorWithStringId(): void
    {
        $user = new InMemoryUser('uuid-abc-123', 'user@example.com', 'hashed_password');

        $this->assertSame('uuid-abc-123', $user->getId());
    }

    public function testConstructorWithDefaultRoles(): void
    {
        $user = new InMemoryUser(1, 'user@example.com', 'hash');

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testConstructorWithCustomRoles(): void
    {
        $roles = ['ROLE_ADMIN', 'ROLE_EDITOR'];
        $user = new InMemoryUser(1, 'admin@example.com', 'hash', $roles);

        $this->assertSame($roles, $user->getRoles());
    }

    // =========================================================================
    // TESTS DE L'INTERFACE UserInterface
    // =========================================================================

    public function testImplementsUserInterface(): void
    {
        $user = new InMemoryUser(1, 'user@example.com', 'hash');

        $this->assertInstanceOf(UserInterface::class, $user);
    }

    public function testGetIdReturnsCorrectValue(): void
    {
        $user = new InMemoryUser(123, 'user@example.com', 'hash');

        $this->assertSame(123, $user->getId());
    }

    public function testGetIdentifierReturnsCorrectValue(): void
    {
        $user = new InMemoryUser(1, 'identifier@example.com', 'hash');

        $this->assertSame('identifier@example.com', $user->getIdentifier());
    }

    public function testGetPasswordReturnsHashedValue(): void
    {
        $hashedPassword = '$2y$10$somehash';
        $user = new InMemoryUser(1, 'user@example.com', $hashedPassword);

        $this->assertSame($hashedPassword, $user->getPassword());
    }

    public function testGetRolesReturnsArray(): void
    {
        $user = new InMemoryUser(1, 'user@example.com', 'hash', ['ROLE_A', 'ROLE_B']);

        $this->assertIsArray($user->getRoles());
        $this->assertCount(2, $user->getRoles());
    }

    // =========================================================================
    // TESTS DE CAS LIMITES
    // =========================================================================

    public function testEmptyRolesArray(): void
    {
        $user = new InMemoryUser(1, 'user@example.com', 'hash', []);

        $this->assertSame([], $user->getRoles());
    }

    public function testSpecialCharactersInIdentifier(): void
    {
        $identifier = "user+tag@example.com";
        $user = new InMemoryUser(1, $identifier, 'hash');

        $this->assertSame($identifier, $user->getIdentifier());
    }

    public function testUnicodeInIdentifier(): void
    {
        $identifier = "utilisateur@例え.com";
        $user = new InMemoryUser(1, $identifier, 'hash');

        $this->assertSame($identifier, $user->getIdentifier());
    }
}
