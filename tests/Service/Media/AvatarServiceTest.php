<?php
/**
 * Tests pour AvatarService.
 */
declare(strict_types=1);

namespace Tests\Service\Media;

use Lunar\Service\Media\AvatarException;
use Lunar\Service\Media\AvatarService;
use PHPUnit\Framework\TestCase;

class AvatarServiceTest extends TestCase
{
    private string $testStoragePath;
    private AvatarService $service;

    protected function setUp(): void
    {
        $this->testStoragePath = sys_get_temp_dir() . '/avatar_test_' . uniqid();
        $this->service = new AvatarService(
            $this->testStoragePath,
            '/uploads/avatars',
            128
        );
    }

    protected function tearDown(): void
    {
        // Nettoie les fichiers de test
        if (is_dir($this->testStoragePath)) {
            $files = glob($this->testStoragePath . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            rmdir($this->testStoragePath);
        }
    }

    // =========================================================================
    // TESTS CONSTRUCTEUR
    // =========================================================================

    public function testConstructorCreatesDirectory(): void
    {
        $path = sys_get_temp_dir() . '/avatar_new_' . uniqid();
        $this->assertDirectoryDoesNotExist($path);

        new AvatarService($path);

        $this->assertDirectoryExists($path);

        // Cleanup
        rmdir($path);
    }

    // =========================================================================
    // TESTS GÉNÉRATION AVATAR PAR DÉFAUT
    // =========================================================================

    public function testGenerateDefaultAvatarReturnsSvg(): void
    {
        $svg = $this->service->generateDefaultAvatar('John Doe');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('JD', $svg); // Initiales
    }

    public function testGenerateDefaultAvatarWithCustomColor(): void
    {
        $svg = $this->service->generateDefaultAvatar('Test User', '#ff0000');

        $this->assertStringContainsString('fill="#ff0000"', $svg);
    }

    public function testGenerateDefaultAvatarSingleName(): void
    {
        $svg = $this->service->generateDefaultAvatar('Madonna');

        // Le SVG contient les initiales (avec whitespace possible)
        $this->assertMatchesRegularExpression('/>\s*M\s*</', $svg);
    }

    public function testGenerateDefaultAvatarEmptyName(): void
    {
        $svg = $this->service->generateDefaultAvatar('');

        // Le SVG contient ? pour un nom vide
        $this->assertMatchesRegularExpression('/>\s*\?\s*</', $svg);
    }

    public function testGenerateDefaultAvatarTripleName(): void
    {
        $svg = $this->service->generateDefaultAvatar('Jean Pierre Martin');

        // Prend les deux premiers mots
        $this->assertStringContainsString('JP', $svg);
    }

    // =========================================================================
    // TESTS RÉCUPÉRATION URL
    // =========================================================================

    public function testGetAvatarUrlReturnsNullWhenNoAvatar(): void
    {
        $url = $this->service->getAvatarUrl('nonexistent-user');

        $this->assertNull($url);
    }

    public function testGetAvatarUrlReturnsPathWhenExists(): void
    {
        // Crée un fichier avatar manuellement
        $userId = 'test-user-123';
        $hash = substr(hash('sha256', $userId), 0, 16);
        $filename = "{$hash}_" . time() . ".jpg";
        touch($this->testStoragePath . '/' . $filename);

        $url = $this->service->getAvatarUrl($userId);

        $this->assertNotNull($url);
        $this->assertStringContainsString('/uploads/avatars/', $url);
        $this->assertStringContainsString($hash, $url);
    }

    // =========================================================================
    // TESTS SUPPRESSION
    // =========================================================================

    public function testDeleteReturnsFalseWhenNoAvatar(): void
    {
        $result = $this->service->delete('no-avatar-user');

        $this->assertFalse($result);
    }

    public function testDeleteRemovesExistingAvatar(): void
    {
        // Crée un fichier avatar
        $userId = 'user-to-delete';
        $hash = substr(hash('sha256', $userId), 0, 16);
        $filename = "{$hash}_" . time() . ".png";
        $filepath = $this->testStoragePath . '/' . $filename;
        touch($filepath);

        $this->assertFileExists($filepath);

        $result = $this->service->delete($userId);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($filepath);
    }

    public function testDeleteRemovesMultipleOldAvatars(): void
    {
        // Crée plusieurs fichiers avatars pour le même utilisateur
        $userId = 'multi-avatar-user';
        $hash = substr(hash('sha256', $userId), 0, 16);

        $files = [];
        for ($i = 0; $i < 3; $i++) {
            $filename = "{$hash}_" . (time() - $i) . ".jpg";
            $filepath = $this->testStoragePath . '/' . $filename;
            touch($filepath);
            $files[] = $filepath;
        }

        foreach ($files as $file) {
            $this->assertFileExists($file);
        }

        $result = $this->service->delete($userId);

        $this->assertTrue($result);
        foreach ($files as $file) {
            $this->assertFileDoesNotExist($file);
        }
    }

    // =========================================================================
    // TESTS UPLOAD FROM URL
    // =========================================================================

    public function testUploadFromUrlReturnsNullOnFailedDownload(): void
    {
        // URL invalide
        $result = $this->service->uploadFromUrl('http://invalid-domain-that-does-not-exist.test/avatar.jpg', 'user1');

        $this->assertNull($result);
    }

    // =========================================================================
    // TESTS VALIDATION (via réflexion pour méthodes privées)
    // =========================================================================

    public function testValidatesFileSizeLimitViaPhpError(): void
    {
        $this->expectException(AvatarException::class);
        $this->expectExceptionMessage('trop volumineux');

        // Simule l'erreur PHP quand le fichier dépasse upload_max_filesize
        $file = [
            'error' => UPLOAD_ERR_INI_SIZE,
            'tmp_name' => '',
            'size' => 0,
            'type' => '',
        ];

        $this->service->upload($file, 'test-user');
    }

    public function testValidatesUploadError(): void
    {
        $this->expectException(AvatarException::class);

        $file = [
            'error' => UPLOAD_ERR_NO_FILE,
            'tmp_name' => '',
            'size' => 0,
            'type' => '',
        ];

        $this->service->upload($file, 'test-user');
    }

    public function testValidatesInvalidFile(): void
    {
        $this->expectException(AvatarException::class);
        $this->expectExceptionMessage('Fichier invalide');

        $file = [
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => '/nonexistent/path/file.jpg',
            'size' => 1024,
            'type' => 'image/jpeg',
        ];

        $this->service->upload($file, 'test-user');
    }
}
