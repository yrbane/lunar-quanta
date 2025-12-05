<?php
/**
 * Tests de l'entité User.
 *
 * Ces tests vérifient :
 * - La création et l'initialisation d'un utilisateur
 * - Le hashage du mot de passe
 * - La gestion des rôles
 * - L'implémentation de UserInterface
 * - La sérialisation/désérialisation (toArray/fromArray)
 */
declare(strict_types=1);

namespace Tests\Entity;

use Lunar\Entity\User;
use Lunar\Service\Security\Auth\UserInterface;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    // =========================================================================
    // TESTS DU CONSTRUCTEUR
    // =========================================================================

    public function testConstructorSetsProperties(): void
    {
        $user = new User('test@example.com', 'John Doe', 'secret123');

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('John Doe', $user->getName());
    }

    public function testConstructorGeneratesUniqueId(): void
    {
        $user1 = new User('user1@example.com', 'User1', 'pass1');
        $user2 = new User('user2@example.com', 'User2', 'pass2');

        $this->assertNotEmpty($user1->getId());
        $this->assertNotEmpty($user2->getId());
        $this->assertNotSame($user1->getId(), $user2->getId());
    }

    public function testConstructorIdIsValidUuid(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $uuid = $user->getId();

        // Format UUID v4 : xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testConstructorSetsDefaultRoles(): void
    {
        $user = new User('test@example.com', 'John', 'pass');

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testConstructorAcceptsCustomRoles(): void
    {
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $user = new User('test@example.com', 'John', 'pass', $roles);

        $this->assertSame($roles, $user->getRoles());
    }

    public function testPasswordIsHashed(): void
    {
        $password = 'secret123';
        $user = new User('test@example.com', 'John', $password);

        $this->assertNotSame($password, $user->getPassword());
        $this->assertTrue(password_verify($password, $user->getPassword()));
    }

    // =========================================================================
    // TESTS DE UserInterface
    // =========================================================================

    public function testImplementsUserInterface(): void
    {
        $user = new User('test@example.com', 'John', 'pass');

        $this->assertInstanceOf(UserInterface::class, $user);
    }

    public function testGetIdentifierReturnsEmail(): void
    {
        $email = 'john@example.com';
        $user = new User($email, 'John', 'pass');

        $this->assertSame($email, $user->getIdentifier());
    }

    public function testCreatedAtIsSet(): void
    {
        $before = new \DateTimeImmutable();
        $user = new User('test@example.com', 'John', 'pass');
        $after = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $user->getCreatedAt());
        $this->assertLessThanOrEqual($after, $user->getCreatedAt());
    }

    public function testUpdatedAtIsSet(): void
    {
        $user = new User('test@example.com', 'John', 'pass');

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
    }

    public function testUpdateTimestampChangesUpdatedAt(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $originalUpdatedAt = $user->getUpdatedAt();

        usleep(1000);
        $user->updateTimestamp();

        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function testGetHashReturnsSha256OfEmail(): void
    {
        $email = 'test@example.com';
        $user = new User($email, 'John', 'pass');

        $expectedHash = hash('sha256', $email);
        $this->assertSame($expectedHash, $user->getHash());
    }

    public function testGetHashIsDeterministic(): void
    {
        $email = 'same@email.com';
        $user1 = new User($email, 'User1', 'pass1');
        $user2 = new User($email, 'User2', 'pass2');

        $this->assertSame($user1->getHash(), $user2->getHash());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $user = new User('test@example.com', 'John Doe', 'password');
        $array = $user->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('password', $array);
        $this->assertArrayHasKey('roles', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);

        $this->assertSame('test@example.com', $array['email']);
        $this->assertSame('John Doe', $array['name']);
        $this->assertSame(['ROLE_USER'], $array['roles']);
    }

    public function testToArrayDatesAreIso8601(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $array = $user->toArray();

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $array['createdAt']
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $array['updatedAt']
        );
    }

    public function testDifferentEmailsHaveDifferentHashes(): void
    {
        $user1 = new User('user1@example.com', 'User1', 'pass');
        $user2 = new User('user2@example.com', 'User2', 'pass');

        $this->assertNotSame($user1->getHash(), $user2->getHash());
    }

    // =========================================================================
    // TESTS DES SETTERS
    // =========================================================================

    public function testSetName(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $result = $user->setName('Jane');

        $this->assertSame('Jane', $user->getName());
        $this->assertSame($user, $result); // Chaînable
    }

    public function testSetEmail(): void
    {
        $user = new User('old@example.com', 'John', 'pass');
        $result = $user->setEmail('new@example.com');

        $this->assertSame('new@example.com', $user->getEmail());
        $this->assertSame('new@example.com', $user->getIdentifier());
        $this->assertSame($user, $result); // Chaînable
    }

    public function testSetPassword(): void
    {
        $user = new User('test@example.com', 'John', 'oldpass');
        $oldHash = $user->getPassword();

        $user->setPassword('newpassword');

        $this->assertNotSame($oldHash, $user->getPassword());
        $this->assertTrue(password_verify('newpassword', $user->getPassword()));
        $this->assertFalse(password_verify('oldpass', $user->getPassword()));
    }

    public function testSetPasswordIsChainable(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $result = $user->setPassword('newpass');

        $this->assertSame($user, $result);
    }

    public function testSettersUpdateTimestamp(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $original = $user->getUpdatedAt();

        usleep(1000);
        $user->setName('Jane');

        $this->assertGreaterThan($original, $user->getUpdatedAt());
    }

    // =========================================================================
    // TESTS DE LA GESTION DES RÔLES
    // =========================================================================

    public function testAddRole(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $user->addRole('ROLE_ADMIN');

        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testAddRoleIsChainable(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $result = $user->addRole('ROLE_ADMIN');

        $this->assertSame($user, $result);
    }

    public function testAddRoleDoesNotDuplicate(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $user->addRole('ROLE_USER'); // Déjà présent
        $user->addRole('ROLE_USER'); // Doublon

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testRemoveRole(): void
    {
        $user = new User('test@example.com', 'John', 'pass', ['ROLE_USER', 'ROLE_ADMIN']);
        $user->removeRole('ROLE_ADMIN');

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testRemoveRoleIsChainable(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $result = $user->removeRole('ROLE_NONEXISTENT');

        $this->assertSame($user, $result);
    }

    public function testRemoveRoleNonExistent(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $user->removeRole('ROLE_NONEXISTENT');

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testRemoveRoleReindexesArray(): void
    {
        $user = new User('test@example.com', 'John', 'pass', ['ROLE_A', 'ROLE_B', 'ROLE_C']);
        $user->removeRole('ROLE_B');

        $roles = $user->getRoles();

        // Doit être réindexé (0, 1, 2, ...)
        $this->assertSame([0, 1], array_keys($roles));
        $this->assertSame(['ROLE_A', 'ROLE_C'], $roles);
    }

    public function testHasRole(): void
    {
        $user = new User('test@example.com', 'John', 'pass', ['ROLE_USER', 'ROLE_ADMIN']);

        $this->assertTrue($user->hasRole('ROLE_USER'));
        $this->assertTrue($user->hasRole('ROLE_ADMIN'));
        $this->assertFalse($user->hasRole('ROLE_SUPER_ADMIN'));
    }

    // =========================================================================
    // TESTS DE SÉRIALISATION (fromArray)
    // =========================================================================

    public function testFromArrayRestoresUser(): void
    {
        $original = new User('test@example.com', 'John Doe', 'secret', ['ROLE_USER', 'ROLE_ADMIN']);
        $data = $original->toArray();

        $restored = User::fromArray($data);

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getEmail(), $restored->getEmail());
        $this->assertSame($original->getName(), $restored->getName());
        $this->assertSame($original->getPassword(), $restored->getPassword());
        $this->assertSame($original->getRoles(), $restored->getRoles());
    }

    public function testFromArrayPreservesPasswordHash(): void
    {
        $user = new User('test@example.com', 'John', 'mypassword');
        $data = $user->toArray();

        $restored = User::fromArray($data);

        // Le mot de passe doit être le même hash, pas re-hashé
        $this->assertSame($user->getPassword(), $restored->getPassword());
        $this->assertTrue(password_verify('mypassword', $restored->getPassword()));
    }

    public function testFromArrayRestoresDates(): void
    {
        $user = new User('test@example.com', 'John', 'pass');
        $data = $user->toArray();

        $restored = User::fromArray($data);

        $this->assertEquals(
            $user->getCreatedAt()->format('c'),
            $restored->getCreatedAt()->format('c')
        );
        $this->assertEquals(
            $user->getUpdatedAt()->format('c'),
            $restored->getUpdatedAt()->format('c')
        );
    }

    public function testFromArrayWithMissingRolesUsesDefault(): void
    {
        $data = [
            'id' => 'test-uuid',
            'email' => 'test@example.com',
            'name' => 'John',
            'password' => '$2y$10$hashedpassword',
            'createdAt' => '2024-01-01T00:00:00+00:00',
            'updatedAt' => '2024-01-01T00:00:00+00:00',
        ];

        $user = User::fromArray($data);

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }
}
