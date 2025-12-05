<?php

declare(strict_types=1);

namespace Tests\Service\Security;

use Lunar\Exception\SecurityException;
use Lunar\Service\Security\EncryptionService;
use PHPUnit\Framework\TestCase;

class EncryptionServiceTest extends TestCase
{
    private EncryptionService $service;

    protected function setUp(): void
    {
        $this->service = new EncryptionService('test_secret_key');
    }

    public function testEncryptReturnsBase64String(): void
    {
        $data = 'Hello, World!';
        $encrypted = $this->service->encrypt($data);

        $this->assertIsString($encrypted);
        $this->assertNotSame($data, $encrypted);
        $this->assertNotFalse(base64_decode($encrypted, true));
    }

    public function testDecryptReturnsOriginalData(): void
    {
        $data = 'Hello, World!';
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testEncryptionWithEmptyString(): void
    {
        $data = '';
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testEncryptionWithUnicodeCharacters(): void
    {
        $data = 'Héllo, Wörld! 日本語 🎉';
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testEncryptionWithLongString(): void
    {
        $data = str_repeat('Lorem ipsum dolor sit amet. ', 1000);
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testDifferentKeysProduceDifferentResults(): void
    {
        $service1 = new EncryptionService('key1');
        $service2 = new EncryptionService('key2');

        $data = 'Secret data';
        $encrypted1 = $service1->encrypt($data);
        $encrypted2 = $service2->encrypt($data);

        $this->assertNotSame($encrypted1, $encrypted2);
    }

    public function testEncryptProducesDifferentOutputsForSameInput(): void
    {
        $data = 'Same input';
        $encrypted1 = $this->service->encrypt($data);
        $encrypted2 = $this->service->encrypt($data);

        $this->assertNotSame($encrypted1, $encrypted2);
        $this->assertSame($data, $this->service->decrypt($encrypted1));
        $this->assertSame($data, $this->service->decrypt($encrypted2));
    }

    public function testEncryptionWithJsonData(): void
    {
        $data = json_encode(['email' => 'test@example.com', 'name' => 'Test User']);
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testHmacVerificationFailsOnTamperedData(): void
    {
        $data = 'Secret data';
        $encrypted = $this->service->encrypt($data);

        // Tamper with the encrypted data (change a character in the middle)
        $decoded = base64_decode($encrypted);
        $tampered = substr($decoded, 0, 20) . 'X' . substr($decoded, 21);
        $tamperedEncrypted = base64_encode($tampered);

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('HMAC verification failed');

        $this->service->decrypt($tamperedEncrypted);
    }

    public function testHmacVerificationFailsOnTruncatedData(): void
    {
        $data = 'Secret data';
        $encrypted = $this->service->encrypt($data);

        // Truncate the encrypted data
        $decoded = base64_decode($encrypted);
        $truncated = substr($decoded, 0, 10);
        $truncatedEncrypted = base64_encode($truncated);

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Invalid encrypted data format');

        $this->service->decrypt($truncatedEncrypted);
    }

    public function testDecryptFailsWithInvalidBase64(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Invalid base64 encoding');

        $this->service->decrypt('not-valid-base64!!!');
    }

    public function testDecryptWithDifferentKeyFails(): void
    {
        $data = 'Secret data';
        $encrypted = $this->service->encrypt($data);

        $differentService = new EncryptionService('different_key');

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('HMAC verification failed');

        $differentService->decrypt($encrypted);
    }

    public function testEncryptionImplementsInterface(): void
    {
        $this->assertInstanceOf(\Lunar\Service\Security\EncryptionInterface::class, $this->service);
    }

    public function testEncryptWithNumericString(): void
    {
        $data = '1234567890';
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testEncryptWithNewlines(): void
    {
        $data = "Line 1\nLine 2\nLine 3";
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testEncryptWithTabs(): void
    {
        $data = "Column1\tColumn2\tColumn3";
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testEncryptWithBinaryData(): void
    {
        $data = "\x00\x01\x02\x03\x04\x05";
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testEncryptedDataIsBase64(): void
    {
        $data = 'Test data';
        $encrypted = $this->service->encrypt($data);

        // Should be valid base64
        $decoded = base64_decode($encrypted, true);
        $this->assertNotFalse($decoded);
        $this->assertSame($encrypted, base64_encode($decoded));
    }

    public function testEncryptedDataLengthIsConsistent(): void
    {
        // Same length input should produce similar output lengths
        $data1 = str_repeat('a', 100);
        $data2 = str_repeat('b', 100);

        $encrypted1 = $this->service->encrypt($data1);
        $encrypted2 = $this->service->encrypt($data2);

        // Lengths should be approximately the same
        $this->assertSame(strlen($encrypted1), strlen($encrypted2));
    }

    public function testDecryptWithEmptyStringThrows(): void
    {
        $this->expectException(SecurityException::class);

        $this->service->decrypt('');
    }

    public function testMultipleEncryptDecryptCycles(): void
    {
        $data = 'Original data';

        for ($i = 0; $i < 10; $i++) {
            $encrypted = $this->service->encrypt($data);
            $decrypted = $this->service->decrypt($encrypted);
            $this->assertSame($data, $decrypted);
        }
    }

    public function testEncryptWithWhitespaceOnly(): void
    {
        $data = '   ';
        $encrypted = $this->service->encrypt($data);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testSameKeyProducesSameDecryption(): void
    {
        $service1 = new EncryptionService('same_key');
        $service2 = new EncryptionService('same_key');

        $data = 'Secret data';
        $encrypted = $service1->encrypt($data);
        $decrypted = $service2->decrypt($encrypted);

        $this->assertSame($data, $decrypted);
    }

    public function testDecryptWithModifiedHmacFails(): void
    {
        $data = 'Secret data';
        $encrypted = $this->service->encrypt($data);

        // Modify the last byte (part of HMAC)
        $decoded = base64_decode($encrypted);
        $modified = substr($decoded, 0, -1) . chr(ord(substr($decoded, -1)) ^ 0xFF);
        $modifiedEncrypted = base64_encode($modified);

        $this->expectException(SecurityException::class);
        $this->service->decrypt($modifiedEncrypted);
    }

    public function testDecryptWithSwappedBytes(): void
    {
        $data = 'Secret data';
        $encrypted = $this->service->encrypt($data);

        // Swap two bytes in the middle
        $decoded = base64_decode($encrypted);
        if (strlen($decoded) > 30) {
            $swapped = substr($decoded, 0, 20) .
                       $decoded[21] .
                       $decoded[20] .
                       substr($decoded, 22);
            $swappedEncrypted = base64_encode($swapped);

            $this->expectException(SecurityException::class);
            $this->service->decrypt($swappedEncrypted);
        } else {
            $this->markTestSkipped('Encrypted data too short for swap test');
        }
    }

    public function testKeyDerivationIsConsistent(): void
    {
        // Same key should always derive the same encryption keys
        $service1 = new EncryptionService('consistent_key');
        $service2 = new EncryptionService('consistent_key');

        $data = 'Test data';
        $encrypted1 = $service1->encrypt($data);

        // Both services should be able to decrypt each other's data
        $this->assertSame($data, $service2->decrypt($encrypted1));
    }
}
