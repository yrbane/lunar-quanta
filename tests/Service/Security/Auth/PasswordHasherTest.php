<?php

declare(strict_types=1);

namespace Tests\Service\Security\Auth;

use Lunar\Service\Security\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

class PasswordHasherTest extends TestCase
{
    public function testHashReturnsHashedPassword(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('password123');

        $this->assertNotSame('password123', $hash);
        $this->assertNotEmpty($hash);
    }

    public function testHashReturnsDifferentHashesForSamePassword(): void
    {
        $hasher = new PasswordHasher();
        $hash1 = $hasher->hash('password123');
        $hash2 = $hasher->hash('password123');

        $this->assertNotSame($hash1, $hash2);
    }

    public function testVerifyReturnsTrueForCorrectPassword(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('password123');

        $this->assertTrue($hasher->verify('password123', $hash));
    }

    public function testVerifyReturnsFalseForIncorrectPassword(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('password123');

        $this->assertFalse($hasher->verify('wrongpassword', $hash));
    }

    public function testVerifyReturnsFalseForEmptyPassword(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('password123');

        $this->assertFalse($hasher->verify('', $hash));
    }

    public function testVerifyReturnsFalseForEmptyHash(): void
    {
        $hasher = new PasswordHasher();

        $this->assertFalse($hasher->verify('password123', ''));
    }

    public function testHashThrowsExceptionForEmptyPassword(): void
    {
        $hasher = new PasswordHasher();

        $this->expectException(\InvalidArgumentException::class);
        $hasher->hash('');
    }

    public function testNeedsRehashReturnsFalseForFreshHash(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('password123');

        $this->assertFalse($hasher->needsRehash($hash));
    }

    public function testBcryptFactoryMethod(): void
    {
        $hasher = PasswordHasher::bcrypt(10);
        $hash = $hasher->hash('password123');

        $this->assertTrue($hasher->verify('password123', $hash));
        $this->assertStringStartsWith('$2y$10$', $hash);
    }

    public function testArgon2idFactoryMethod(): void
    {
        if (!defined('PASSWORD_ARGON2ID')) {
            $this->markTestSkipped('Argon2id not available');
        }

        $hasher = PasswordHasher::argon2id();
        $hash = $hasher->hash('password123');

        $this->assertTrue($hasher->verify('password123', $hash));
        $this->assertStringStartsWith('$argon2id$', $hash);
    }

    public function testHashIsDeterministicallySafe(): void
    {
        $hasher = new PasswordHasher();

        // Hash multiple passwords and verify each
        $passwords = ['pass1', 'pass2', 'pass3'];
        foreach ($passwords as $password) {
            $hash = $hasher->hash($password);
            $this->assertTrue($hasher->verify($password, $hash));
        }
    }

    public function testBcryptDefaultCost(): void
    {
        $hasher = PasswordHasher::bcrypt();
        $hash = $hasher->hash('password');

        // Default cost depends on PHP version (10 in PHP <8.4, 12 in PHP 8.4+)
        $defaultCost = PASSWORD_BCRYPT_DEFAULT_COST;
        $this->assertStringStartsWith('$2y$' . $defaultCost . '$', $hash);
    }

    public function testBcryptCustomCost(): void
    {
        $hasher = PasswordHasher::bcrypt(11);
        $hash = $hasher->hash('password');

        $this->assertStringStartsWith('$2y$11$', $hash);
    }

    public function testNeedsRehashReturnsTrueForOldHash(): void
    {
        // Create hash with cost 10
        $oldHasher = PasswordHasher::bcrypt(10);
        $hash = $oldHasher->hash('password');

        // New hasher with higher cost
        $newHasher = PasswordHasher::bcrypt(12);

        $this->assertTrue($newHasher->needsRehash($hash));
    }

    public function testNeedsRehashReturnsFalseForSameCost(): void
    {
        $hasher = PasswordHasher::bcrypt(10);
        $hash = $hasher->hash('password');

        $this->assertFalse($hasher->needsRehash($hash));
    }

    public function testHashWithUnicodePassword(): void
    {
        $hasher = new PasswordHasher();
        $password = 'Pässwörd123日本語';
        $hash = $hasher->hash($password);

        $this->assertTrue($hasher->verify($password, $hash));
    }

    public function testHashWithLongPassword(): void
    {
        $hasher = new PasswordHasher();
        // bcrypt truncates at 72 bytes, but should still work
        $password = str_repeat('a', 100);
        $hash = $hasher->hash($password);

        $this->assertTrue($hasher->verify($password, $hash));
    }

    public function testHashWithSpecialCharacters(): void
    {
        $hasher = new PasswordHasher();
        $password = '!@#$%^&*()_+-=[]{}|;:\'",.<>?/\\`~';
        $hash = $hasher->hash($password);

        $this->assertTrue($hasher->verify($password, $hash));
    }

    public function testVerifyWithInvalidHash(): void
    {
        $hasher = new PasswordHasher();

        // Invalid hash format
        $this->assertFalse($hasher->verify('password', 'not-a-valid-hash'));
    }

    public function testVerifyWithWrongHashFormat(): void
    {
        $hasher = new PasswordHasher();

        // Random string that looks like a hash but isn't
        $this->assertFalse($hasher->verify('password', '$2y$10$invalidhashvalue'));
    }

    public function testHashImplementsInterface(): void
    {
        $hasher = new PasswordHasher();

        $this->assertInstanceOf(\Lunar\Service\Security\Auth\PasswordHasherInterface::class, $hasher);
    }

    public function testBcryptFactoryReturnsNewInstance(): void
    {
        $hasher1 = PasswordHasher::bcrypt(10);
        $hasher2 = PasswordHasher::bcrypt(10);

        $this->assertNotSame($hasher1, $hasher2);
    }

    public function testArgon2idFactoryReturnsNewInstance(): void
    {
        if (!defined('PASSWORD_ARGON2ID')) {
            $this->markTestSkipped('Argon2id not available');
        }

        $hasher1 = PasswordHasher::argon2id();
        $hasher2 = PasswordHasher::argon2id();

        $this->assertNotSame($hasher1, $hasher2);
    }

    public function testHashProducesConsistentVerification(): void
    {
        $hasher = new PasswordHasher();
        $password = 'testPassword123';
        $hash = $hasher->hash($password);

        // Verify multiple times - should always return true
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($hasher->verify($password, $hash));
        }
    }

    public function testVerifyWithWhitespacePassword(): void
    {
        $hasher = new PasswordHasher();
        $password = '   spaces   ';
        $hash = $hasher->hash($password);

        $this->assertTrue($hasher->verify($password, $hash));
        $this->assertFalse($hasher->verify('spaces', $hash)); // Without spaces
    }
}
