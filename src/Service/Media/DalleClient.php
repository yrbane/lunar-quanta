<?php

declare(strict_types=1);

namespace Lunar\Service\Media;

/**
 * Client pour l'API DALL-E (OpenAI).
 *
 * DALL-E génère des images à partir de descriptions textuelles.
 *
 * @example
 * ```php
 * $client = new DalleClient('your-api-key');
 * $image = $client->generate('A sunset over mountains');
 * ```
 *
 * @see https://platform.openai.com/docs/api-reference/images
 */
final class DalleClient implements ImageProviderInterface
{
    private const API_URL = 'https://api.openai.com/v1/images/generations';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'dall-e-3',
        private readonly string $size = '1024x1024',
        private readonly string $quality = 'standard'
    ) {
    }

    public function search(string $query, int $limit = 10): array
    {
        // DALL-E ne supporte pas la recherche, mais on peut générer des images
        $result = $this->generate($query);
        return $result !== null ? [$result] : [];
    }

    public function generate(string $prompt): ?ImageResult
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => $this->size,
            'quality' => $this->quality,
        ];

        $response = $this->request($payload);

        if ($response === null || !isset($response['data'][0])) {
            return null;
        }

        $image = $response['data'][0];

        return new ImageResult(
            id: 'dalle-' . uniqid(),
            url: $image['url'] ?? '',
            thumbnailUrl: $image['url'] ?? '',
            provider: 'dalle',
            width: $this->getWidthFromSize(),
            height: $this->getHeightFromSize(),
            alt: $prompt,
        );
    }

    public function getName(): string
    {
        return 'dalle';
    }

    public function supportsGeneration(): bool
    {
        return true;
    }

    /**
     * Effectue une requête HTTP POST.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    private function request(array $payload): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json',
                ],
                'content' => json_encode($payload),
                'timeout' => 60,
            ],
        ]);

        $response = @file_get_contents(self::API_URL, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        return is_array($data) ? $data : null;
    }

    private function getWidthFromSize(): int
    {
        return match ($this->size) {
            '256x256' => 256,
            '512x512' => 512,
            '1024x1024' => 1024,
            '1024x1792' => 1024,
            '1792x1024' => 1792,
            default => 1024,
        };
    }

    private function getHeightFromSize(): int
    {
        return match ($this->size) {
            '256x256' => 256,
            '512x512' => 512,
            '1024x1024' => 1024,
            '1024x1792' => 1792,
            '1792x1024' => 1024,
            default => 1024,
        };
    }
}
