<?php

declare(strict_types=1);

namespace Tests\Entity;

use Lunar\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $user = new User('test@example.com', 'John Doe', 'secret123');

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('John Doe', $user->getName());
    }

    public function testPasswordIsHashed(): void
    {
        $password = 'secret123';
        $user = new User('test@example.com', 'John', $password);

        $this->assertNotSame($password, $user->getPassword());
        $this->assertTrue(password_verify($password, $user->getPassword()));
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

        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('password', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);

        $this->assertSame('test@example.com', $array['email']);
        $this->assertSame('John Doe', $array['name']);
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
}
