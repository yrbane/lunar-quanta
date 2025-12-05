<?php
/**
 * Tests de l'entité TwoFactorSecret.
 */
declare(strict_types=1);

namespace Tests\Entity;

use Lunar\Entity\TwoFactorSecret;
use PHPUnit\Framework\TestCase;

class TwoFactorSecretTest extends TestCase
{
    // =========================================================================
    // TESTS DU CONSTRUCTEUR
    // =========================================================================

    public function testConstructorSetsProperties(): void
    {
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');

        $this->assertSame('user-123', $secret->getUserId());
        $this->assertSame('ABCDEFGH', $secret->getSecret());
        $this->assertFalse($secret->isEnabled());
    }

    public function testConstructorSetsTimestamps(): void
    {
        $before = new \DateTimeImmutable();
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $secret->getCreatedAt());
        $this->assertLessThanOrEqual($after, $secret->getCreatedAt());
    }

    public function testConstructorSetsEmptyRecoveryCodes(): void
    {
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');

        $this->assertSame([], $secret->getRecoveryCodes());
        $this->assertSame(0, $secret->getRemainingRecoveryCodesCount());
    }

    // =========================================================================
    // TESTS D'ACTIVATION / DÉSACTIVATION
    // =========================================================================

    public function testEnable(): void
    {
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');

        $this->assertFalse($secret->isEnabled());
        $this->assertNull($secret->getVerifiedAt());

        $result = $secret->enable();

        $this->assertTrue($secret->isEnabled());
        $this->assertNotNull($secret->getVerifiedAt());
        $this->assertSame($secret, $result); // Chaînable
    }

    public function testDisable(): void
    {
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');
        $secret->enable();
        $secret->setRecoveryCodes(['hash1', 'hash2']);

        $result = $secret->disable();

        $this->assertFalse($secret->isEnabled());
        $this->assertNull($secret->getVerifiedAt());
        $this->assertSame([], $secret->getRecoveryCodes());
        $this->assertSame($secret, $result); // Chaînable
    }

    // =========================================================================
    // TESTS DES CODES DE RÉCUPÉRATION
    // =========================================================================

    public function testSetRecoveryCodes(): void
    {
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');
        $codes = ['hash1', 'hash2', 'hash3'];

        $result = $secret->setRecoveryCodes($codes);

        $this->assertSame($codes, $secret->getRecoveryCodes());
        $this->assertSame(3, $secret->getRemainingRecoveryCodesCount());
        $this->assertSame($secret, $result); // Chaînable
    }

    public function testInvalidateRecoveryCode(): void
    {
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');
        $secret->setRecoveryCodes(['hash0', 'hash1', 'hash2']);

        $result = $secret->invalidateRecoveryCode(1);

        $this->assertSame(['hash0', 'hash2'], $secret->getRecoveryCodes());
        $this->assertSame(2, $secret->getRemainingRecoveryCodesCount());
        $this->assertSame($secret, $result); // Chaînable
    }

    public function testInvalidateRecoveryCodeReindexes(): void
    {
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');
        $secret->setRecoveryCodes(['a', 'b', 'c', 'd']);

        $secret->invalidateRecoveryCode(1);

        $codes = $secret->getRecoveryCodes();
        $this->assertSame([0, 1, 2], array_keys($codes));
        $this->assertSame(['a', 'c', 'd'], $codes);
    }

    public function testInvalidateRecoveryCodeWithInvalidIndex(): void
    {
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');
        $secret->setRecoveryCodes(['hash0', 'hash1']);

        $secret->invalidateRecoveryCode(99);

        $this->assertSame(['hash0', 'hash1'], $secret->getRecoveryCodes());
    }

    // =========================================================================
    // TESTS DE SÉRIALISATION
    // =========================================================================

    public function testToArrayReturnsCorrectStructure(): void
    {
        $secret = new TwoFactorSecret('user-123', 'ABCDEFGH');
        $secret->enable();
        $secret->setRecoveryCodes(['hash1', 'hash2']);

        $array = $secret->toArray();

        $this->assertArrayHasKey('userId', $array);
        $this->assertArrayHasKey('secret', $array);
        $this->assertArrayHasKey('enabled', $array);
        $this->assertArrayHasKey('recoveryCodes', $array);
        $this->assertArrayHasKey('verifiedAt', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);

        $this->assertSame('user-123', $array['userId']);
        $this->assertSame('ABCDEFGH', $array['secret']);
        $this->assertTrue($array['enabled']);
        $this->assertSame(['hash1', 'hash2'], $array['recoveryCodes']);
    }

    public function testFromArrayRestoresSecret(): void
    {
        $original = new TwoFactorSecret('user-123', 'ABCDEFGH');
        $original->enable();
        $original->setRecoveryCodes(['hash1', 'hash2']);

        $data = $original->toArray();
        $restored = TwoFactorSecret::fromArray($data);

        $this->assertSame($original->getUserId(), $restored->getUserId());
        $this->assertSame($original->getSecret(), $restored->getSecret());
        $this->assertSame($original->isEnabled(), $restored->isEnabled());
        $this->assertSame($original->getRecoveryCodes(), $restored->getRecoveryCodes());
    }

    public function testFromArrayWithDisabledSecret(): void
    {
        $data = [
            'userId' => 'user-456',
            'secret' => 'ZYXWVUTS',
            'enabled' => false,
            'recoveryCodes' => [],
            'verifiedAt' => null,
            'createdAt' => '2024-01-01T00:00:00+00:00',
            'updatedAt' => '2024-01-01T00:00:00+00:00',
        ];

        $secret = TwoFactorSecret::fromArray($data);

        $this->assertSame('user-456', $secret->getUserId());
        $this->assertFalse($secret->isEnabled());
        $this->assertNull($secret->getVerifiedAt());
    }

    public function testFromArrayPreservesDates(): void
    {
        $original = new TwoFactorSecret('user-123', 'ABCDEFGH');
        $data = $original->toArray();

        $restored = TwoFactorSecret::fromArray($data);

        $this->assertEquals(
            $original->getCreatedAt()->format('c'),
            $restored->getCreatedAt()->format('c')
        );
    }
}
