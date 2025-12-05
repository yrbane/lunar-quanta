<?php

declare(strict_types=1);

namespace Lunar\Service\Media;

/**
 * Représente une image retournée par un fournisseur.
 */
final class ImageResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly string $thumbnailUrl,
        public readonly string $provider,
        public readonly int $width,
        public readonly int $height,
        public readonly string $alt = '',
        public readonly string $photographer = '',
        public readonly string $photographerUrl = '',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'thumbnailUrl' => $this->thumbnailUrl,
            'provider' => $this->provider,
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->alt,
            'photographer' => $this->photographer,
            'photographerUrl' => $this->photographerUrl,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            thumbnailUrl: (string) ($data['thumbnailUrl'] ?? ''),
            provider: (string) ($data['provider'] ?? ''),
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 0),
            alt: (string) ($data['alt'] ?? ''),
            photographer: (string) ($data['photographer'] ?? ''),
            photographerUrl: (string) ($data['photographerUrl'] ?? ''),
        );
    }
}
