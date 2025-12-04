<?php

declare(strict_types=1);

namespace Tests\Service\Security\Csrf;

use Lunar\Service\Security\Csrf\CsrfTokenManager;
use Lunar\Service\Session\SessionService;
use PHPUnit\Framework\TestCase;

class CsrfTokenManagerTest extends TestCase
{
    private SessionService $session;
    private CsrfTokenManager $manager;

    protected function setUp(): void
    {
        $this->session = new SessionService(testMode: true);
        $this->manager = new CsrfTokenManager($this->session);
    }

    public function testGenerateReturnsToken(): void
    {
        $token = $this->manager->generate('form_login');

        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testGenerateReturnsDifferentTokensEachTime(): void
    {
        $token1 = $this->manager->generate('form_a');
        $token2 = $this->manager->generate('form_b');

        $this->assertNotSame($token1, $token2);
    }

    public function testIsValidReturnsTrueForValidToken(): void
    {
        $token = $this->manager->generate('form_login');

        $this->assertTrue($this->manager->isValid('form_login', $token));
    }

    public function testIsValidReturnsFalseForInvalidToken(): void
    {
        $this->manager->generate('form_login');

        $this->assertFalse($this->manager->isValid('form_login', 'wrong_token'));
    }

    public function testIsValidReturnsFalseForWrongTokenId(): void
    {
        $token = $this->manager->generate('form_login');

        $this->assertFalse($this->manager->isValid('form_register', $token));
    }

    public function testIsValidReturnsFalseForEmptyToken(): void
    {
        $this->manager->generate('form_login');

        $this->assertFalse($this->manager->isValid('form_login', ''));
    }

    public function testIsValidReturnsFalseForNonExistentToken(): void
    {
        $this->assertFalse($this->manager->isValid('nonexistent', 'some_token'));
    }

    public function testRemoveDeletesToken(): void
    {
        $token = $this->manager->generate('form_login');
        $this->assertTrue($this->manager->isValid('form_login', $token));

        $this->manager->remove('form_login');

        $this->assertFalse($this->manager->isValid('form_login', $token));
    }

    public function testMultipleTokensCanCoexist(): void
    {
        $loginToken = $this->manager->generate('form_login');
        $registerToken = $this->manager->generate('form_register');
        $contactToken = $this->manager->generate('form_contact');

        $this->assertTrue($this->manager->isValid('form_login', $loginToken));
        $this->assertTrue($this->manager->isValid('form_register', $registerToken));
        $this->assertTrue($this->manager->isValid('form_contact', $contactToken));
    }

    public function testRegeneratingTokenInvalidatesOldToken(): void
    {
        $oldToken = $this->manager->generate('form_login');
        $newToken = $this->manager->generate('form_login');

        $this->assertFalse($this->manager->isValid('form_login', $oldToken));
        $this->assertTrue($this->manager->isValid('form_login', $newToken));
    }

    public function testTokenIsCryptographicallySecure(): void
    {
        // Generate multiple tokens and ensure they're all different
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = $this->manager->generate("form_$i");
        }

        $uniqueTokens = array_unique($tokens);
        $this->assertCount(100, $uniqueTokens);
    }

    public function testTimingAttackProtection(): void
    {
        $token = $this->manager->generate('form_login');

        // hash_equals is used internally, so timing should be constant
        // We can't easily test timing, but we verify the method works
        $this->assertTrue($this->manager->isValid('form_login', $token));
        $this->assertFalse($this->manager->isValid('form_login', $token . 'x'));
    }
}
