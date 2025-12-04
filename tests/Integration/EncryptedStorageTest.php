<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lunar\Entity\User;
use Lunar\Service\Security\EncryptionService;
use Lunar\Service\Storage\JsonStorage;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for encrypted data storage.
 *
 * Tests the complete flow of encrypting, storing, loading, and decrypting data.
 */
class EncryptedStorageTest extends TestCase
{
    private string $testDataPath;
    private EncryptionService $encryption;

    protected function setUp(): void
    {
        $this->testDataPath = sys_get_temp_dir() . '/lunar_test_' . uniqid();
        mkdir($this->testDataPath, 0755, true);

        // Set environment for testing
        putenv('DATA_PATH=' . $this->testDataPath);
        putenv('APP_KEY=test_encryption_key_12345');

        $this->encryption = new EncryptionService('test_encryption_key_12345');
    }

    protected function tearDown(): void
    {
        // Clean up test data directory
        $this->removeDirectory($this->testDataPath);

        putenv('DATA_PATH=');
        putenv('APP_KEY=');
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    public function testEncryptionServiceEncryptsData(): void
    {
        $plaintext = 'Sensitive data';
        $encrypted = $this->encryption->encrypt($plaintext);

        $this->assertNotEquals($plaintext, $encrypted);
        $this->assertNotEmpty($encrypted);
    }

    public function testEncryptionServiceDecryptsData(): void
    {
        $plaintext = 'Sensitive data';
        $encrypted = $this->encryption->encrypt($plaintext);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptedDataIsDifferentEachTime(): void
    {
        $plaintext = 'Same data';
        $encrypted1 = $this->encryption->encrypt($plaintext);
        $encrypted2 = $this->encryption->encrypt($plaintext);

        // Due to random IV, encryptions should differ
        $this->assertNotEquals($encrypted1, $encrypted2);
    }

    public function testJsonStorageSavesUser(): void
    {
        $storage = new JsonStorage();

        $user = new User('test@example.com', 'Test User', 'password123');
        $storage->saveUser($user);

        // File should exist
        $hash = hash('sha256', 'test@example.com');
        $subDir = substr($hash, 0, 3);
        $filePath = $this->testDataPath . '/user/' . $subDir . '/' . $hash . '.json';

        $this->assertFileExists($filePath);
    }

    public function testJsonStorageSavesEncryptedData(): void
    {
        $storage = new JsonStorage();

        $user = new User('encrypted@example.com', 'Encrypted User', 'secret');
        $storage->saveUser($user);

        $hash = hash('sha256', 'encrypted@example.com');
        $subDir = substr($hash, 0, 3);
        $filePath = $this->testDataPath . '/user/' . $subDir . '/' . $hash . '.json';

        // Read raw file content
        $rawContent = file_get_contents($filePath);

        // Content should NOT contain plaintext email (it's encrypted)
        $this->assertStringNotContainsString('encrypted@example.com', $rawContent);
        $this->assertStringNotContainsString('Encrypted User', $rawContent);
    }

    public function testJsonStorageLoadsUser(): void
    {
        $storage = new JsonStorage();

        $originalUser = new User('load@example.com', 'Load User', 'password');
        $storage->saveUser($originalUser);

        $loadedUser = $storage->loadUser('load@example.com');

        $this->assertInstanceOf(User::class, $loadedUser);
        $this->assertEquals('load@example.com', $loadedUser->getEmail());
        $this->assertEquals('Load User', $loadedUser->getName());
    }

    public function testJsonStorageReturnsNullForNonexistentUser(): void
    {
        $storage = new JsonStorage();

        $user = $storage->loadUser('nonexistent@example.com');

        $this->assertNull($user);
    }

    public function testHmacVerificationDetectsTampering(): void
    {
        $this->expectException(\Lunar\Exception\SecurityException::class);
        $this->expectExceptionMessage('HMAC verification failed');

        $plaintext = 'Sensitive data';
        $encrypted = $this->encryption->encrypt($plaintext);

        // Tamper with the encrypted data
        $decoded = base64_decode($encrypted);
        $tampered = substr($decoded, 0, 20) . 'X' . substr($decoded, 21);
        $tamperedEncrypted = base64_encode($tampered);

        $this->encryption->decrypt($tamperedEncrypted);
    }

    public function testCompleteStorageCycle(): void
    {
        $storage = new JsonStorage();

        // Create user
        $user = new User('cycle@example.com', 'Cycle User', 'cyclepass');

        // Save
        $storage->saveUser($user);

        // Load
        $loaded = $storage->loadUser('cycle@example.com');

        // Verify
        $this->assertNotNull($loaded);
        $this->assertEquals('cycle@example.com', $loaded->getEmail());
        $this->assertEquals('Cycle User', $loaded->getName());
    }
}
