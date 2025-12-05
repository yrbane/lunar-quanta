<?php

declare(strict_types=1);

namespace Lunar\Entity;

/**
 * Entité représentant une image.
 *
 * Une image peut provenir de différentes sources :
 * - Upload direct
 * - Pexels (stock photos)
 * - DALL-E (génération IA)
 * - Imagen (génération IA)
 *
 * @example
 * ```php
 * $image = new Image('sunset.jpg', ImageSource::PEXELS);
 * $image->setAltText('Beautiful sunset');
 * $image->setSourceId('pexels-12345');
 *
 * echo $image->getUrl('/uploads/'); // /uploads/{id}/sunset.jpg
 * ```
 */
final class Image
{
    private string $id;
    private string $filename;
    private ImageSource $source;
    private string $altText = '';
    private ?string $sourceId = null;
    private ?string $sourceUrl = null;
    private ?string $attribution = null;
    private ?string $prompt = null;
    private ?int $width = null;
    private ?int $height = null;
    private ?int $fileSize = null;
    private ?string $mimeType = null;
    private \DateTimeImmutable $createdAt;

    /** @var array<string, string> */
    private array $optimizedVersions = [];

    public function __construct(string $filename, ImageSource $source)
    {
        $this->id = $this->generateId();
        $this->filename = $filename;
        $this->source = $source;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getSource(): ImageSource
    {
        return $this->source;
    }

    public function getAltText(): string
    {
        return $this->altText;
    }

    public function setAltText(string $altText): self
    {
        $this->altText = $altText;
        return $this;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(string $sourceId): self
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(string $sourceUrl): self
    {
        $this->sourceUrl = $sourceUrl;
        return $this;
    }

    public function getAttribution(): ?string
    {
        return $this->attribution;
    }

    public function setAttribution(string $attribution): self
    {
        $this->attribution = $attribution;
        return $this;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Retourne le chemin relatif de l'image.
     */
    public function getPath(): string
    {
        return sprintf('%s/%s', $this->id, $this->filename);
    }

    /**
     * Retourne l'URL complète de l'image.
     */
    public function getUrl(string $baseUrl = '/uploads/blog/'): string
    {
        return rtrim($baseUrl, '/') . '/' . $this->getPath();
    }

    /**
     * Vérifie si l'image est générée par IA.
     */
    public function isAiGenerated(): bool
    {
        return $this->source->isAiGenerated();
    }

    /**
     * Vérifie si l'image nécessite une attribution.
     */
    public function requiresAttribution(): bool
    {
        return $this->source->requiresAttribution();
    }

    /**
     * Ajoute une version optimisée de l'image.
     */
    public function addOptimizedVersion(string $name, string $filename): self
    {
        $this->optimizedVersions[$name] = $filename;
        return $this;
    }

    /**
     * Vérifie si une version optimisée existe.
     */
    public function hasOptimizedVersion(string $name): bool
    {
        return isset($this->optimizedVersions[$name]);
    }

    /**
     * Retourne une version optimisée ou l'original si non trouvée.
     */
    public function getOptimizedVersion(string $name): string
    {
        return $this->optimizedVersions[$name] ?? $this->filename;
    }

    /**
     * @return array<string, string>
     */
    public function getOptimizedVersions(): array
    {
        return $this->optimizedVersions;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'source' => $this->source->value,
            'altText' => $this->altText,
            'sourceId' => $this->sourceId,
            'sourceUrl' => $this->sourceUrl,
            'attribution' => $this->attribution,
            'prompt' => $this->prompt,
            'width' => $this->width,
            'height' => $this->height,
            'fileSize' => $this->fileSize,
            'mimeType' => $this->mimeType,
            'optimizedVersions' => $this->optimizedVersions,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $image = new self(
            $data['filename'],
            ImageSource::from($data['source'])
        );

        $reflection = new \ReflectionClass($image);

        $idProp = $reflection->getProperty('id');
        $idProp->setValue($image, $data['id']);

        if (isset($data['altText'])) {
            $image->altText = $data['altText'];
        }
        if (isset($data['sourceId'])) {
            $image->sourceId = $data['sourceId'];
        }
        if (isset($data['sourceUrl'])) {
            $image->sourceUrl = $data['sourceUrl'];
        }
        if (isset($data['attribution'])) {
            $image->attribution = $data['attribution'];
        }
        if (isset($data['prompt'])) {
            $image->prompt = $data['prompt'];
        }
        if (isset($data['width'])) {
            $image->width = $data['width'];
        }
        if (isset($data['height'])) {
            $image->height = $data['height'];
        }
        if (isset($data['fileSize'])) {
            $image->fileSize = $data['fileSize'];
        }
        if (isset($data['mimeType'])) {
            $image->mimeType = $data['mimeType'];
        }
        if (isset($data['optimizedVersions'])) {
            $image->optimizedVersions = $data['optimizedVersions'];
        }
        if (isset($data['createdAt'])) {
            $createdAtProp = $reflection->getProperty('createdAt');
            $createdAtProp->setValue($image, new \DateTimeImmutable($data['createdAt']));
        }

        return $image;
    }

    private function generateId(): string
    {
        return sprintf(
            '%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(4))
        );
    }
}
