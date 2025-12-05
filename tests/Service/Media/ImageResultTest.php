<?php

declare(strict_types=1);

namespace Tests\Service\Media;

use Lunar\Service\Media\ImageResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour ImageResult.
 */
final class ImageResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $result = new ImageResult(
            id: '123',
            url: 'https://example.com/image.jpg',
            thumbnailUrl: 'https://example.com/thumb.jpg',
            provider: 'pexels',
            width: 1920,
            height: 1080,
            alt: 'A beautiful sunset',
            photographer: 'John Doe',
            photographerUrl: 'https://example.com/john'
        );

        $this->assertSame('123', $result->id);
        $this->assertSame('https://example.com/image.jpg', $result->url);
        $this->assertSame('https://example.com/thumb.jpg', $result->thumbnailUrl);
        $this->assertSame('pexels', $result->provider);
        $this->assertSame(1920, $result->width);
        $this->assertSame(1080, $result->height);
        $this->assertSame('A beautiful sunset', $result->alt);
        $this->assertSame('John Doe', $result->photographer);
        $this->assertSame('https://example.com/john', $result->photographerUrl);
    }

    public function testToArray(): void
    {
        $result = new ImageResult(
            id: '456',
            url: 'https://example.com/img.jpg',
            thumbnailUrl: 'https://example.com/img_thumb.jpg',
            provider: 'dalle',
            width: 1024,
            height: 1024,
            alt: 'Generated image'
        );

        $array = $result->toArray();

        $this->assertSame('456', $array['id']);
        $this->assertSame('https://example.com/img.jpg', $array['url']);
        $this->assertSame('https://example.com/img_thumb.jpg', $array['thumbnailUrl']);
        $this->assertSame('dalle', $array['provider']);
        $this->assertSame(1024, $array['width']);
        $this->assertSame(1024, $array['height']);
        $this->assertSame('Generated image', $array['alt']);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => '789',
            'url' => 'https://example.com/photo.jpg',
            'thumbnailUrl' => 'https://example.com/photo_sm.jpg',
            'provider' => 'imagen',
            'width' => 800,
            'height' => 600,
            'alt' => 'Test image',
            'photographer' => 'Jane Doe',
            'photographerUrl' => 'https://example.com/jane',
        ];

        $result = ImageResult::fromArray($data);

        $this->assertSame('789', $result->id);
        $this->assertSame('https://example.com/photo.jpg', $result->url);
        $this->assertSame('imagen', $result->provider);
        $this->assertSame(800, $result->width);
        $this->assertSame(600, $result->height);
        $this->assertSame('Jane Doe', $result->photographer);
    }

    public function testFromArrayWithMissingFields(): void
    {
        $data = [
            'id' => '111',
            'url' => 'https://example.com/image.jpg',
        ];

        $result = ImageResult::fromArray($data);

        $this->assertSame('111', $result->id);
        $this->assertSame('https://example.com/image.jpg', $result->url);
        $this->assertSame('', $result->thumbnailUrl);
        $this->assertSame('', $result->provider);
        $this->assertSame(0, $result->width);
        $this->assertSame(0, $result->height);
        $this->assertSame('', $result->alt);
    }

    public function testToArrayFromArrayRoundtrip(): void
    {
        $original = new ImageResult(
            id: 'test-123',
            url: 'https://example.com/test.jpg',
            thumbnailUrl: 'https://example.com/test_thumb.jpg',
            provider: 'pexels',
            width: 1600,
            height: 900,
            alt: 'Test roundtrip',
            photographer: 'Test User',
            photographerUrl: 'https://example.com/user'
        );

        $array = $original->toArray();
        $restored = ImageResult::fromArray($array);

        $this->assertSame($original->id, $restored->id);
        $this->assertSame($original->url, $restored->url);
        $this->assertSame($original->provider, $restored->provider);
        $this->assertSame($original->width, $restored->width);
        $this->assertSame($original->photographer, $restored->photographer);
    }
}
