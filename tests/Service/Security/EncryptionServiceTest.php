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
}
