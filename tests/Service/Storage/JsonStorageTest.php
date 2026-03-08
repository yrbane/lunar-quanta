<?php

declare(strict_types=1);

namespace Tests\Service\Storage;

use Lunar\Entity\User;
use Lunar\Service\Storage\JsonStorage;
use PHPUnit\Framework\TestCase;

class JsonStorageTest extends TestCase
{
    private string $tempDataPath;
    private string $originalDataPath;
    private string $originalAppKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDataPath = sys_get_temp_dir() . '/json_storage_test_' . uniqid();
        mkdir($this->tempDataPath, 0777, true);

        $this->originalDataPath = getenv('DATA_PATH') ?: '';
        $this->originalAppKey = getenv('APP_KEY') ?: '';

        putenv('DATA_PATH=' . $this->tempDataPath);
        putenv('APP_KEY=***SECRET-RETIRE***');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteDirectory($this->tempDataPath);

        if ($this->originalDataPath) {
            putenv('DATA_PATH=' . $this->originalDataPath);
        } else {
            putenv('DATA_PATH');
        }

        if ($this->originalAppKey) {
            putenv('APP_KEY=' . $this->originalAppKey);
        } else {
            putenv('APP_KEY');
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testSaveUserCreatesFile(): void
    {
        $storage = new JsonStorage();
        $user = new User('test@example.com', 'Test User', 'password123');

        $storage->saveUser($user);

        $hash = $user->getHash();
        $subDir = substr($hash, 0, 3);
        $filePath = $this->tempDataPath . '/user/' . $subDir . '/' . $hash . '.json';

        $this->assertFileExists($filePath);
    }

    public function testSaveUserCreatesDirectoryStructure(): void
    {
        $storage = new JsonStorage();
        $user = new User('newuser@example.com', 'New User', 'secret');

        $storage->saveUser($user);

        $hash = $user->getHash();
        $subDir = substr($hash, 0, 3);
        $dir = $this->tempDataPath . '/user/' . $subDir;

        $this->assertDirectoryExists($dir);
    }

    public function testSaveUserEncryptsData(): void
    {
        $storage = new JsonStorage();
        $user = new User('encrypt@example.com', 'Encrypted User', 'mypassword');

        $storage->saveUser($user);

        $hash = $user->getHash();
        $subDir = substr($hash, 0, 3);
        $filePath = $this->tempDataPath . '/user/' . $subDir . '/' . $hash . '.json';

        $content = file_get_contents($filePath);

        $this->assertStringNotContainsString('encrypt@example.com', $content);
        $this->assertStringNotContainsString('Encrypted User', $content);
    }

    public function testLoadUserReturnsNullForNonexistentUser(): void
    {
        $storage = new JsonStorage();

        $user = $storage->loadUser('nonexistent@example.com');

        $this->assertNull($user);
    }

    public function testLoadUserReturnsUserAfterSave(): void
    {
        $storage = new JsonStorage();
        $originalUser = new User('load@example.com', 'Load Test', 'password');

        $storage->saveUser($originalUser);

        $loadedUser = $storage->loadUser('load@example.com');

        $this->assertInstanceOf(User::class, $loadedUser);
        $this->assertSame('load@example.com', $loadedUser->getEmail());
        $this->assertSame('Load Test', $loadedUser->getName());
    }

    public function testLoadUserReturnsCorrectEmail(): void
    {
        $storage = new JsonStorage();
        $user = new User('unique@example.com', 'Unique User', 'pass');

        $storage->saveUser($user);

        $loaded = $storage->loadUser('unique@example.com');

        $this->assertNotNull($loaded);
        $this->assertSame('unique@example.com', $loaded->getEmail());
    }

    public function testMultipleUsersSavedIndependently(): void
    {
        $storage = new JsonStorage();

        $user1 = new User('user1@example.com', 'User One', 'pass1');
        $user2 = new User('user2@example.com', 'User Two', 'pass2');

        $storage->saveUser($user1);
        $storage->saveUser($user2);

        $loaded1 = $storage->loadUser('user1@example.com');
        $loaded2 = $storage->loadUser('user2@example.com');

        $this->assertSame('User One', $loaded1->getName());
        $this->assertSame('User Two', $loaded2->getName());
    }

    public function testJsonStorageImplementsStorageInterface(): void
    {
        $storage = new JsonStorage();

        $this->assertInstanceOf(\Lunar\Service\Storage\StorageInterface::class, $storage);
    }

    public function testSaveAndLoadPreservesEmail(): void
    {
        $storage = new JsonStorage();
        $email = 'preserve@example.com';
        $user = new User($email, 'Preserve User', 'testpass');

        $storage->saveUser($user);
        $loaded = $storage->loadUser($email);

        $this->assertNotNull($loaded);
        $this->assertSame($email, $loaded->getEmail());
    }

    public function testSaveUserOverwritesExistingFile(): void
    {
        $storage = new JsonStorage();
        $email = 'overwrite@example.com';

        $user1 = new User($email, 'First Name', 'pass1');
        $storage->saveUser($user1);

        $user2 = new User($email, 'Updated Name', 'pass2');
        $storage->saveUser($user2);

        $loaded = $storage->loadUser($email);
        $this->assertNotNull($loaded);
        $this->assertSame('Updated Name', $loaded->getName());
    }

    public function testLoadUserWithDifferentEmails(): void
    {
        $storage = new JsonStorage();

        $user = new User('test@example.com', 'Test', 'pass');
        $storage->saveUser($user);

        // Should not find user with different email
        $wrongUser = $storage->loadUser('wrong@example.com');
        $this->assertNull($wrongUser);
    }

    public function testSaveAndLoadPreservesName(): void
    {
        $storage = new JsonStorage();
        $user = new User('name@example.com', 'Special Name With Spaces', 'pass');

        $storage->saveUser($user);
        $loaded = $storage->loadUser('name@example.com');

        $this->assertNotNull($loaded);
        $this->assertSame('Special Name With Spaces', $loaded->getName());
    }

    public function testFilePathUsesHashPrefix(): void
    {
        $storage = new JsonStorage();
        $user = new User('hashtest@example.com', 'Hash Test', 'pass');

        $storage->saveUser($user);

        $hash = $user->getHash();
        $expectedPrefix = substr($hash, 0, 3);
        $expectedDir = $this->tempDataPath . '/user/' . $expectedPrefix;

        $this->assertDirectoryExists($expectedDir);
    }

    public function testInstantiationWithoutAppKeyThrowsException(): void
    {
        // Store current values
        $currentDataPath = getenv('DATA_PATH');
        $currentAppKey = getenv('APP_KEY');

        // Clear env vars
        putenv('DATA_PATH=' . $this->tempDataPath);
        putenv('APP_KEY');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('APP_KEY');

        try {
            new JsonStorage();
        } finally {
            // Restore
            if ($currentDataPath) {
                putenv('DATA_PATH=' . $currentDataPath);
            }
            if ($currentAppKey) {
                putenv('APP_KEY=' . $currentAppKey);
            }
        }
    }

    public function testMultipleSavesToSameDirectory(): void
    {
        $storage = new JsonStorage();

        // Create multiple users that might share same subdirectory prefix
        for ($i = 0; $i < 5; $i++) {
            $user = new User("user{$i}@example.com", "User {$i}", "pass{$i}");
            $storage->saveUser($user);
        }

        // Verify all can be loaded
        for ($i = 0; $i < 5; $i++) {
            $loaded = $storage->loadUser("user{$i}@example.com");
            $this->assertNotNull($loaded);
            $this->assertSame("User {$i}", $loaded->getName());
        }
    }

    public function testSaveUserWithSpecialCharactersInName(): void
    {
        $storage = new JsonStorage();
        $user = new User('special@example.com', 'Naïve Café Résumé', 'pass');

        $storage->saveUser($user);
        $loaded = $storage->loadUser('special@example.com');

        $this->assertNotNull($loaded);
        $this->assertSame('Naïve Café Résumé', $loaded->getName());
    }

    public function testLoadUserIsCaseSensitive(): void
    {
        $storage = new JsonStorage();
        $user = new User('CaseSensitive@Example.Com', 'Case Test', 'pass');

        $storage->saveUser($user);

        // Exact email should work
        $loaded = $storage->loadUser('CaseSensitive@Example.Com');
        $this->assertNotNull($loaded);

        // Different case should not find the user
        $notFound = $storage->loadUser('casesensitive@example.com');
        $this->assertNull($notFound);
    }
}
