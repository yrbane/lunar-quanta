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
}
