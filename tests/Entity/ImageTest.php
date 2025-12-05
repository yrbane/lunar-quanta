<?php

declare(strict_types=1);

namespace Tests\Entity;

use Lunar\Entity\Image;
use Lunar\Entity\ImageSource;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour l'entité Image.
 *
 * Une image peut provenir de différentes sources :
 * upload, Pexels, DALL-E ou Imagen.
 */
final class ImageTest extends TestCase
{
    public function testConstructorGeneratesUniqueId(): void
    {
        $image1 = new Image('test.jpg', ImageSource::UPLOAD);
        $image2 = new Image('test.jpg', ImageSource::UPLOAD);

        $this->assertNotEmpty($image1->getId());
        $this->assertNotSame($image1->getId(), $image2->getId());
    }

    public function testConstructorSetsFilename(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);

        $this->assertSame('photo.jpg', $image->getFilename());
    }

    public function testConstructorSetsSource(): void
    {
        $image = new Image('photo.jpg', ImageSource::PEXELS);

        $this->assertSame(ImageSource::PEXELS, $image->getSource());
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $image = new Image('test.jpg', ImageSource::UPLOAD);
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $image->getCreatedAt());
        $this->assertLessThanOrEqual($after, $image->getCreatedAt());
    }

    public function testSetAltText(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);
        $image->setAltText('A beautiful sunset');

        $this->assertSame('A beautiful sunset', $image->getAltText());
    }

    public function testAltTextDefaultsToEmpty(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);

        $this->assertSame('', $image->getAltText());
    }

    public function testSetSourceId(): void
    {
        $image = new Image('photo.jpg', ImageSource::PEXELS);
        $image->setSourceId('pexels-12345');

        $this->assertSame('pexels-12345', $image->getSourceId());
    }

    public function testSetSourceUrl(): void
    {
        $image = new Image('photo.jpg', ImageSource::PEXELS);
        $image->setSourceUrl('https://pexels.com/photo/12345');

        $this->assertSame('https://pexels.com/photo/12345', $image->getSourceUrl());
    }

    public function testSetAttribution(): void
    {
        $image = new Image('photo.jpg', ImageSource::PEXELS);
        $image->setAttribution('Photo by John Doe on Pexels');

        $this->assertSame('Photo by John Doe on Pexels', $image->getAttribution());
    }

    public function testSetPrompt(): void
    {
        $image = new Image('generated.jpg', ImageSource::DALLE);
        $image->setPrompt('A cat wearing a hat');

        $this->assertSame('A cat wearing a hat', $image->getPrompt());
    }

    public function testSetDimensions(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);
        $image->setWidth(1920);
        $image->setHeight(1080);

        $this->assertSame(1920, $image->getWidth());
        $this->assertSame(1080, $image->getHeight());
    }

    public function testSetFileSize(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);
        $image->setFileSize(1024000);

        $this->assertSame(1024000, $image->getFileSize());
    }

    public function testSetMimeType(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);
        $image->setMimeType('image/jpeg');

        $this->assertSame('image/jpeg', $image->getMimeType());
    }

    public function testGetPath(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);

        // Le path est basé sur l'ID et le filename
        $this->assertStringContainsString('photo.jpg', $image->getPath());
    }

    public function testGetUrl(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);

        $url = $image->getUrl('/uploads/blog/');

        $this->assertStringStartsWith('/uploads/blog/', $url);
        $this->assertStringContainsString('photo.jpg', $url);
    }

    public function testIsAiGenerated(): void
    {
        $upload = new Image('test.jpg', ImageSource::UPLOAD);
        $pexels = new Image('test.jpg', ImageSource::PEXELS);
        $dalle = new Image('test.jpg', ImageSource::DALLE);
        $imagen = new Image('test.jpg', ImageSource::IMAGEN);

        $this->assertFalse($upload->isAiGenerated());
        $this->assertFalse($pexels->isAiGenerated());
        $this->assertTrue($dalle->isAiGenerated());
        $this->assertTrue($imagen->isAiGenerated());
    }

    public function testRequiresAttribution(): void
    {
        $upload = new Image('test.jpg', ImageSource::UPLOAD);
        $pexels = new Image('test.jpg', ImageSource::PEXELS);

        $this->assertFalse($upload->requiresAttribution());
        $this->assertTrue($pexels->requiresAttribution());
    }

    public function testToArray(): void
    {
        $image = new Image('photo.jpg', ImageSource::PEXELS);
        $image->setAltText('Sunset');
        $image->setSourceId('12345');
        $image->setWidth(1920);
        $image->setHeight(1080);

        $array = $image->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('filename', $array);
        $this->assertArrayHasKey('source', $array);
        $this->assertArrayHasKey('altText', $array);
        $this->assertArrayHasKey('sourceId', $array);
        $this->assertArrayHasKey('width', $array);
        $this->assertArrayHasKey('height', $array);
        $this->assertArrayHasKey('createdAt', $array);

        $this->assertSame('photo.jpg', $array['filename']);
        $this->assertSame('pexels', $array['source']);
        $this->assertSame('Sunset', $array['altText']);
    }

    public function testFromArray(): void
    {
        $original = new Image('photo.jpg', ImageSource::DALLE);
        $original->setAltText('AI Art');
        $original->setPrompt('A futuristic city');
        $original->setWidth(1024);
        $original->setHeight(1024);

        $data = $original->toArray();
        $restored = Image::fromArray($data);

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getFilename(), $restored->getFilename());
        $this->assertSame($original->getSource(), $restored->getSource());
        $this->assertSame($original->getAltText(), $restored->getAltText());
        $this->assertSame($original->getPrompt(), $restored->getPrompt());
        $this->assertSame($original->getWidth(), $restored->getWidth());
    }

    public function testOptimizedVersions(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);

        $image->addOptimizedVersion('thumb', 'photo_thumb.webp');
        $image->addOptimizedVersion('medium', 'photo_medium.webp');

        $this->assertTrue($image->hasOptimizedVersion('thumb'));
        $this->assertTrue($image->hasOptimizedVersion('medium'));
        $this->assertFalse($image->hasOptimizedVersion('large'));

        $this->assertSame('photo_thumb.webp', $image->getOptimizedVersion('thumb'));
    }

    public function testGetOptimizedVersionReturnsOriginalIfNotFound(): void
    {
        $image = new Image('photo.jpg', ImageSource::UPLOAD);

        $this->assertSame('photo.jpg', $image->getOptimizedVersion('nonexistent'));
    }
}
