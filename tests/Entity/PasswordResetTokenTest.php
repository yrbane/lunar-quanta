<?php
/**
 * Tests du token de réinitialisation de mot de passe.
 */
declare(strict_types=1);

namespace Tests\Entity;

use Lunar\Entity\PasswordResetToken;
use PHPUnit\Framework\TestCase;

class PasswordResetTokenTest extends TestCase
{
    // =========================================================================
    // TESTS DU CONSTRUCTEUR
    // =========================================================================

    public function testConstructorSetsProperties(): void
    {
        $token = new PasswordResetToken('test@example.com', 'mytoken123');

        $this->assertSame('test@example.com', $token->getEmail());
        $this->assertNotEmpty($token->getId());
        $this->assertFalse($token->isUsed());
    }

    public function testConstructorHashesToken(): void
    {
        $plainToken = 'mytoken123';
        $token = new PasswordResetToken('test@example.com', $plainToken);

        // Le hash ne doit pas être le token en clair
        $this->assertNotSame($plainToken, $token->getTokenHash());
        // Mais doit vérifier correctement
        $this->assertTrue($token->verify($plainToken));
    }

    public function testConstructorSetsExpiration(): void
    {
        $ttl = 3600; // 1 heure
        $before = new \DateTimeImmutable();
        $token = new PasswordResetToken('test@example.com', 'token', $ttl);
        $after = new \DateTimeImmutable();

        $expectedMin = $before->modify("+{$ttl} seconds");
        $expectedMax = $after->modify("+{$ttl} seconds");

        $this->assertGreaterThanOrEqual($expectedMin, $token->getExpiresAt());
        $this->assertLessThanOrEqual($expectedMax, $token->getExpiresAt());
    }

    public function testConstructorWithCustomTtl(): void
    {
        $ttl = 7200; // 2 heures
        $token = new PasswordResetToken('test@example.com', 'token', $ttl);

        $expected = (new \DateTimeImmutable())->modify("+{$ttl} seconds");
        $diff = abs($expected->getTimestamp() - $token->getExpiresAt()->getTimestamp());

        $this->assertLessThan(2, $diff); // Moins de 2 secondes de différence
    }

    // =========================================================================
    // TESTS DE VÉRIFICATION
    // =========================================================================

    public function testVerifyWithCorrectToken(): void
    {
        $plainToken = 'correct-token';
        $token = new PasswordResetToken('test@example.com', $plainToken);

        $this->assertTrue($token->verify($plainToken));
    }

    public function testVerifyWithIncorrectToken(): void
    {
        $token = new PasswordResetToken('test@example.com', 'correct-token');

        $this->assertFalse($token->verify('wrong-token'));
    }

    public function testIsExpiredReturnsFalseForNewToken(): void
    {
        $token = new PasswordResetToken('test@example.com', 'token', 3600);

        $this->assertFalse($token->isExpired());
    }

    public function testIsExpiredReturnsTrueForExpiredToken(): void
    {
        // Token avec TTL de 0 secondes = expiré immédiatement
        $token = new PasswordResetToken('test@example.com', 'token', 0);
        usleep(1000); // Attend 1ms

        $this->assertTrue($token->isExpired());
    }

    public function testIsValidForNewToken(): void
    {
        $token = new PasswordResetToken('test@example.com', 'token', 3600);

        $this->assertTrue($token->isValid());
    }

    public function testIsValidReturnsFalseForUsedToken(): void
    {
        $token = new PasswordResetToken('test@example.com', 'token', 3600);
        $token->markAsUsed();

        $this->assertFalse($token->isValid());
    }

    public function testIsValidReturnsFalseForExpiredToken(): void
    {
        $token = new PasswordResetToken('test@example.com', 'token', 0);
        usleep(1000);

        $this->assertFalse($token->isValid());
    }

    // =========================================================================
    // TESTS DE MUTATION
    // =========================================================================

    public function testMarkAsUsed(): void
    {
        $token = new PasswordResetToken('test@example.com', 'token');

        $this->assertFalse($token->isUsed());

        $result = $token->markAsUsed();

        $this->assertTrue($token->isUsed());
        $this->assertSame($token, $result); // Chaînable
    }

    // =========================================================================
    // TESTS DE SÉRIALISATION
    // =========================================================================

    public function testToArrayReturnsCorrectStructure(): void
    {
        $token = new PasswordResetToken('test@example.com', 'mytoken');
        $array = $token->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('tokenHash', $array);
        $this->assertArrayHasKey('expiresAt', $array);
        $this->assertArrayHasKey('used', $array);
        $this->assertArrayHasKey('createdAt', $array);

        $this->assertSame('test@example.com', $array['email']);
        $this->assertFalse($array['used']);
    }

    public function testFromArrayRestoresToken(): void
    {
        $original = new PasswordResetToken('test@example.com', 'mytoken');
        $original->markAsUsed();
        $data = $original->toArray();

        $restored = PasswordResetToken::fromArray($data);

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getEmail(), $restored->getEmail());
        $this->assertSame($original->getTokenHash(), $restored->getTokenHash());
        $this->assertSame($original->isUsed(), $restored->isUsed());
    }

    public function testFromArrayPreservesTokenHash(): void
    {
        $plainToken = 'original-token';
        $original = new PasswordResetToken('test@example.com', $plainToken);
        $data = $original->toArray();

        $restored = PasswordResetToken::fromArray($data);

        $this->assertTrue($restored->verify($plainToken));
    }

    // =========================================================================
    // TESTS DES MÉTHODES STATIQUES
    // =========================================================================

    public function testGenerateSecureTokenReturnsHexString(): void
    {
        $token = PasswordResetToken::generateSecureToken();

        // 32 octets = 64 caractères hex
        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token);
    }

    public function testGenerateSecureTokenIsUnique(): void
    {
        $token1 = PasswordResetToken::generateSecureToken();
        $token2 = PasswordResetToken::generateSecureToken();

        $this->assertNotSame($token1, $token2);
    }

    public function testGenerateSecureTokenWithCustomLength(): void
    {
        $token = PasswordResetToken::generateSecureToken(16);

        // 16 octets = 32 caractères hex
        $this->assertSame(32, strlen($token));
    }

    public function testDefaultTtlConstant(): void
    {
        $this->assertSame(3600, PasswordResetToken::DEFAULT_TTL);
    }
}
