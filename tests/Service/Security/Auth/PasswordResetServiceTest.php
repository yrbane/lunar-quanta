<?php
/**
 * Tests du service de réinitialisation de mot de passe.
 */
declare(strict_types=1);

namespace Tests\Service\Security\Auth;

use Lunar\Entity\User;
use Lunar\Service\Security\Auth\PasswordResetService;
use Lunar\Service\Storage\JsonStorage;
use PHPUnit\Framework\TestCase;

class PasswordResetServiceTest extends TestCase
{
    private PasswordResetService $service;
    private JsonStorage $storage;
    private string $tokensPath;
    private string $originalAppKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalAppKey = getenv('APP_KEY') ?: '';
        if (!getenv('APP_KEY')) {
            putenv('APP_KEY=test_key_for_password_reset_tests');
        }

        $this->storage = new JsonStorage();
        $this->service = new PasswordResetService($this->storage);
        $this->tokensPath = getcwd() . '/data/password_reset';

        // Nettoie le dossier de tokens avant chaque test
        $this->cleanTokensDirectory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanTokensDirectory();

        if ($this->originalAppKey) {
            putenv('APP_KEY=' . $this->originalAppKey);
        } else {
            putenv('APP_KEY');
        }
    }

    private function cleanTokensDirectory(): void
    {
        if (is_dir($this->tokensPath)) {
            $files = glob($this->tokensPath . '/*.json');
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }
    }

    // =========================================================================
    // TESTS DE CRÉATION DE TOKEN
    // =========================================================================

    public function testCreateTokenReturnsString(): void
    {
        $token = $this->service->createToken('test@example.com');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testCreateTokenGeneratesUniqueTokens(): void
    {
        $token1 = $this->service->createToken('test1@example.com');
        $token2 = $this->service->createToken('test2@example.com');

        $this->assertNotSame($token1, $token2);
    }

    public function testCreateTokenStoresTokenFile(): void
    {
        $this->service->createToken('test@example.com');

        $files = glob($this->tokensPath . '/*.json');
        $this->assertNotFalse($files);
        $this->assertCount(1, $files);
    }

    public function testCreateResetUrlReturnsValidUrl(): void
    {
        $url = $this->service->createResetUrl('test@example.com', 'https://example.com');

        $this->assertStringStartsWith('https://example.com/reset-password?', $url);
        $this->assertStringContainsString('token=', $url);
        $this->assertStringContainsString('email=', $url);
    }

    public function testCreateResetUrlHandlesTrailingSlash(): void
    {
        $url1 = $this->service->createResetUrl('test@example.com', 'https://example.com/');
        $url2 = $this->service->createResetUrl('test@example.com', 'https://example.com');

        // Les deux doivent commencer par le même chemin
        $this->assertStringStartsWith('https://example.com/reset-password?', $url1);
        $this->assertStringStartsWith('https://example.com/reset-password?', $url2);
    }

    // =========================================================================
    // TESTS DE VALIDATION
    // =========================================================================

    public function testIsTokenValidWithValidToken(): void
    {
        $email = 'test@example.com';
        $plainToken = $this->service->createToken($email);

        $this->assertTrue($this->service->isTokenValid($email, $plainToken));
    }

    public function testIsTokenValidWithWrongToken(): void
    {
        $email = 'test@example.com';
        $this->service->createToken($email);

        $this->assertFalse($this->service->isTokenValid($email, 'wrong-token'));
    }

    public function testIsTokenValidWithWrongEmail(): void
    {
        $plainToken = $this->service->createToken('correct@example.com');

        $this->assertFalse($this->service->isTokenValid('wrong@example.com', $plainToken));
    }

    public function testIsTokenValidWithNoToken(): void
    {
        $this->assertFalse($this->service->isTokenValid('test@example.com', 'nonexistent'));
    }

    public function testIsTokenValidWithExpiredToken(): void
    {
        $email = 'test@example.com';
        $plainToken = $this->service->createToken($email, 0); // Expire immédiatement
        usleep(1000);

        $this->assertFalse($this->service->isTokenValid($email, $plainToken));
    }

    // =========================================================================
    // TESTS DE RESET PASSWORD
    // =========================================================================

    public function testResetPasswordWithValidToken(): void
    {
        $email = 'reset@example.com';
        $oldPassword = 'oldpassword123';
        $newPassword = 'newpassword456';

        // Crée un utilisateur
        $user = new User($email, 'Test User', $oldPassword);
        $this->storage->saveUser($user);

        // Crée un token et reset
        $plainToken = $this->service->createToken($email);
        $result = $this->service->resetPassword($email, $plainToken, $newPassword);

        $this->assertTrue($result);

        // Vérifie que le mot de passe a changé
        $updatedUser = $this->storage->loadUser($email);
        $this->assertNotNull($updatedUser);
        $this->assertTrue(password_verify($newPassword, $updatedUser->getPassword()));
        $this->assertFalse(password_verify($oldPassword, $updatedUser->getPassword()));
    }

    public function testResetPasswordInvalidatesToken(): void
    {
        $email = 'reset@example.com';

        // Crée un utilisateur
        $user = new User($email, 'Test User', 'password');
        $this->storage->saveUser($user);

        // Crée un token et reset
        $plainToken = $this->service->createToken($email);
        $this->service->resetPassword($email, $plainToken, 'newpassword');

        // Le token ne doit plus être valide
        $this->assertFalse($this->service->isTokenValid($email, $plainToken));
    }

    public function testResetPasswordWithInvalidToken(): void
    {
        $email = 'reset@example.com';
        $user = new User($email, 'Test User', 'password');
        $this->storage->saveUser($user);

        $result = $this->service->resetPassword($email, 'invalid-token', 'newpassword');

        $this->assertFalse($result);
    }

    public function testResetPasswordWithNonExistentUser(): void
    {
        $email = 'nonexistent@example.com';
        $plainToken = $this->service->createToken($email);

        $result = $this->service->resetPassword($email, $plainToken, 'newpassword');

        $this->assertFalse($result);
    }

    // =========================================================================
    // TESTS D'INVALIDATION
    // =========================================================================

    public function testInvalidateTokensForEmail(): void
    {
        $email = 'test@example.com';

        // Crée plusieurs tokens
        $token1 = $this->service->createToken($email, 3600);
        // Note: createToken invalide déjà les anciens tokens

        $this->service->invalidateTokensForEmail($email);

        // Aucun token ne doit être valide
        $this->assertFalse($this->service->isTokenValid($email, $token1));
    }

    public function testCreateTokenInvalidatesOldTokens(): void
    {
        $email = 'test@example.com';

        $oldToken = $this->service->createToken($email);
        $newToken = $this->service->createToken($email);

        // L'ancien token doit être invalidé
        $this->assertFalse($this->service->isTokenValid($email, $oldToken));
        // Le nouveau doit être valide
        $this->assertTrue($this->service->isTokenValid($email, $newToken));
    }

    // =========================================================================
    // TESTS DE NETTOYAGE
    // =========================================================================

    public function testCleanExpiredTokens(): void
    {
        // Crée des tokens expirés
        $this->service->createToken('expired1@example.com', 0);
        $this->service->createToken('expired2@example.com', 0);
        usleep(1000);

        // Crée un token valide
        $this->service->createToken('valid@example.com', 3600);

        $deleted = $this->service->cleanExpiredTokens();

        $this->assertSame(2, $deleted);

        // Vérifie qu'il reste 1 fichier (le token valide)
        $files = glob($this->tokensPath . '/*.json');
        $this->assertNotFalse($files);
        $this->assertCount(1, $files);
    }

    public function testCleanExpiredTokensWithNoTokens(): void
    {
        $deleted = $this->service->cleanExpiredTokens();

        $this->assertSame(0, $deleted);
    }

    public function testGetTokenPathSanitizesId(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass(PasswordResetService::class);
        $method = $reflection->getMethod('getTokenPath');
        $method->setAccessible(true);

        $path = $method->invoke($this->service, '../../etc/passwd');

        $this->assertStringNotContainsString('..', $path);
        $this->assertStringNotContainsString('etc/passwd', $path);
        $this->assertStringEndsWith('.json', $path);
    }
}
