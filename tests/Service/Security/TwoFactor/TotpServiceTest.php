<?php
/**
 * Tests du service TOTP.
 */
declare(strict_types=1);

namespace Tests\Service\Security\TwoFactor;

use Lunar\Service\Security\TwoFactor\TotpService;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService('TestApp');
    }

    // =========================================================================
    // TESTS DE GÉNÉRATION DE SECRET
    // =========================================================================

    public function testGenerateSecretReturnsBase32String(): void
    {
        $secret = $this->totp->generateSecret();

        // Base32 contient uniquement A-Z et 2-7
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGenerateSecretIsUnique(): void
    {
        $secret1 = $this->totp->generateSecret();
        $secret2 = $this->totp->generateSecret();

        $this->assertNotSame($secret1, $secret2);
    }

    public function testGenerateSecretHasCorrectLength(): void
    {
        // 16 octets → 26 caractères Base32 (arrondi)
        $secret = $this->totp->generateSecret(16);

        $this->assertGreaterThanOrEqual(24, strlen($secret));
        $this->assertLessThanOrEqual(32, strlen($secret));
    }

    // =========================================================================
    // TESTS DE GÉNÉRATION DE CODE
    // =========================================================================

    public function testGenerateCodeReturns6Digits(): void
    {
        $secret = $this->totp->generateSecret();
        $code = $this->totp->generateCode($secret);

        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
    }

    public function testGenerateCodeIsDeterministic(): void
    {
        $secret = $this->totp->generateSecret();
        $timestamp = 1699999980;

        $code1 = $this->totp->generateCode($secret, $timestamp);
        $code2 = $this->totp->generateCode($secret, $timestamp);

        $this->assertSame($code1, $code2);
    }

    public function testGenerateCodeChangesWith30Seconds(): void
    {
        $secret = $this->totp->generateSecret();
        $timestamp = 1699999980;

        $code1 = $this->totp->generateCode($secret, $timestamp);
        $code2 = $this->totp->generateCode($secret, $timestamp + 30);

        $this->assertNotSame($code1, $code2);
    }

    public function testGenerateCodeSameWithin30Seconds(): void
    {
        $secret = $this->totp->generateSecret();
        $timestamp = 1699999980;

        $code1 = $this->totp->generateCode($secret, $timestamp);
        $code2 = $this->totp->generateCode($secret, $timestamp + 15);

        $this->assertSame($code1, $code2);
    }

    // =========================================================================
    // TESTS DE VÉRIFICATION
    // =========================================================================

    public function testVerifyCodeWithValidCode(): void
    {
        $secret = $this->totp->generateSecret();
        $code = $this->totp->generateCode($secret);

        $this->assertTrue($this->totp->verifyCode($secret, $code));
    }

    public function testVerifyCodeWithInvalidCode(): void
    {
        $secret = $this->totp->generateSecret();

        $this->assertFalse($this->totp->verifyCode($secret, '000000'));
        $this->assertFalse($this->totp->verifyCode($secret, '999999'));
    }

    public function testVerifyCodeWithWrongSecret(): void
    {
        $secret1 = $this->totp->generateSecret();
        $secret2 = $this->totp->generateSecret();
        $code = $this->totp->generateCode($secret1);

        $this->assertFalse($this->totp->verifyCode($secret2, $code));
    }

    // =========================================================================
    // TESTS OTP AUTH URI
    // =========================================================================

    public function testGetOtpAuthUriFormat(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $uri = $this->totp->getOtpAuthUri($secret, 'user@example.com');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=TestApp', $uri);
        $this->assertStringContainsString('user%40example.com', $uri);
    }

    public function testGetQrCodeUrlReturnsGoogleChartUrl(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $url = $this->totp->getQrCodeUrl($secret, 'user@example.com');

        $this->assertStringStartsWith('https://chart.googleapis.com/chart?', $url);
        $this->assertStringContainsString('cht=qr', $url);
    }

    public function testGetQrCodeSvgReturnsSvg(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $svg = $this->totp->getQrCodeSvg($secret, 'user@example.com');

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringEndsWith('</svg>', $svg);
    }

    // =========================================================================
    // TESTS DES CODES DE RÉCUPÉRATION
    // =========================================================================

    public function testGenerateRecoveryCodesReturnsCorrectCount(): void
    {
        $codes = $this->totp->generateRecoveryCodes(8);

        $this->assertCount(8, $codes);
    }

    public function testGenerateRecoveryCodesFormat(): void
    {
        $codes = $this->totp->generateRecoveryCodes(1);

        // Format: XXXX-XXXX
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{4}-[0-9A-Z]{4}$/', $codes[0]);
    }

    public function testGenerateRecoveryCodesAreUnique(): void
    {
        $codes = $this->totp->generateRecoveryCodes(10);
        $uniqueCodes = array_unique($codes);

        $this->assertCount(10, $uniqueCodes);
    }

    public function testHashRecoveryCodeReturnsSha256(): void
    {
        $code = 'ABCD-1234';
        $hash = $this->totp->hashRecoveryCode($code);

        $this->assertSame(64, strlen($hash)); // SHA-256 = 64 hex chars
    }

    public function testHashRecoveryCodeIsDeterministic(): void
    {
        $code = 'ABCD-1234';
        $hash1 = $this->totp->hashRecoveryCode($code);
        $hash2 = $this->totp->hashRecoveryCode($code);

        $this->assertSame($hash1, $hash2);
    }

    public function testHashRecoveryCodeIgnoresCase(): void
    {
        $hash1 = $this->totp->hashRecoveryCode('ABCD-1234');
        $hash2 = $this->totp->hashRecoveryCode('abcd-1234');

        $this->assertSame($hash1, $hash2);
    }

    public function testHashRecoveryCodeIgnoresDash(): void
    {
        $hash1 = $this->totp->hashRecoveryCode('ABCD-1234');
        $hash2 = $this->totp->hashRecoveryCode('ABCD1234');

        $this->assertSame($hash1, $hash2);
    }

    public function testVerifyRecoveryCodeWithValidCode(): void
    {
        $codes = $this->totp->generateRecoveryCodes(3);
        $hashedCodes = array_map(
            fn($code) => $this->totp->hashRecoveryCode($code),
            $codes
        );

        $result = $this->totp->verifyRecoveryCode($codes[1], $hashedCodes);

        $this->assertSame(1, $result);
    }

    public function testVerifyRecoveryCodeWithInvalidCode(): void
    {
        $codes = $this->totp->generateRecoveryCodes(3);
        $hashedCodes = array_map(
            fn($code) => $this->totp->hashRecoveryCode($code),
            $codes
        );

        $result = $this->totp->verifyRecoveryCode('XXXX-XXXX', $hashedCodes);

        $this->assertFalse($result);
    }

    public function testVerifyRecoveryCodeReturnsIndex(): void
    {
        $codes = ['AAAA-1111', 'BBBB-2222', 'CCCC-3333'];
        $hashedCodes = array_map(
            fn($code) => $this->totp->hashRecoveryCode($code),
            $codes
        );

        $this->assertSame(0, $this->totp->verifyRecoveryCode('AAAA-1111', $hashedCodes));
        $this->assertSame(1, $this->totp->verifyRecoveryCode('BBBB-2222', $hashedCodes));
        $this->assertSame(2, $this->totp->verifyRecoveryCode('CCCC-3333', $hashedCodes));
    }
}
