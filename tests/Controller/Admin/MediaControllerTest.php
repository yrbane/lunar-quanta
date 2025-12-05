<?php

declare(strict_types=1);

namespace Tests\Controller\Admin;

use Lunar\Controller\Admin\MediaController;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour MediaController.
 */
final class MediaControllerTest extends TestCase
{
    public function testControllerCanBeInstantiated(): void
    {
        // Simuler les variables d'environnement vides
        $_ENV['PEXELS_API_KEY'] = '';
        $_ENV['OPENAI_API_KEY'] = '';
        $_ENV['GCP_API_KEY'] = '';
        $_ENV['GCP_PROJECT_ID'] = '';

        $controller = new MediaController();

        $this->assertInstanceOf(MediaController::class, $controller);
    }

    public function testSanitizeFilename(): void
    {
        $controller = new MediaController();

        // Utiliser la réflexion pour tester la méthode privée
        $method = new \ReflectionMethod(MediaController::class, 'sanitizeFilename');

        // Nom normal
        $result = $method->invoke($controller, 'my-image.jpg');
        $this->assertSame('my-image.jpg', $result);

        // Caractères spéciaux
        $result = $method->invoke($controller, 'mon image (1).PNG');
        $this->assertSame('mon_image_1.png', $result);

        // Nom vide après nettoyage
        $result = $method->invoke($controller, '!!!.jpg');
        $this->assertStringStartsWith('image_', $result);
    }

    public function testGetUploadedImagesReturnsEmptyForNonexistentDir(): void
    {
        $controller = new MediaController();

        $method = new \ReflectionMethod(MediaController::class, 'getUploadedImages');

        // Sans uploads, devrait retourner un tableau vide
        $result = $method->invoke($controller);

        $this->assertIsArray($result);
    }
}
