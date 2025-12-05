<?php

declare(strict_types=1);

namespace Lunar\Service\Media;

/**
 * Client pour l'API Pexels.
 *
 * Pexels fournit des photos libres de droits.
 *
 * @example
 * ```php
 * $client = new PexelsClient('your-api-key');
 * $images = $client->search('nature', 10);
 * ```
 *
 * @see https://www.pexels.com/api/documentation/
 */
final class PexelsClient implements ImageProviderInterface
{
    private const API_URL = 'https://api.pexels.com/v1';

    public function __construct(
        private readonly string $apiKey
    ) {
    }

    public function search(string $query, int $limit = 10): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        $url = self::API_URL . '/search?' . http_build_query([
            'query' => $query,
            'per_page' => min($limit, 80),
            'locale' => 'fr-FR',
        ]);

        $response = $this->request($url);

        if ($response === null || !isset($response['photos'])) {
            return [];
        }

        return array_map(
            fn(array $photo) => $this->mapPhoto($photo),
            $response['photos']
        );
    }

    public function generate(string $prompt): ?ImageResult
    {
        // Pexels ne supporte pas la génération d'images
        return null;
    }

    public function getName(): string
    {
        return 'pexels';
    }

    public function supportsGeneration(): bool
    {
        return false;
    }

    /**
     * Récupère des images populaires.
     *
     * @return ImageResult[]
     */
    public function curated(int $limit = 10): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        $url = self::API_URL . '/curated?' . http_build_query([
            'per_page' => min($limit, 80),
        ]);

        $response = $this->request($url);

        if ($response === null || !isset($response['photos'])) {
            return [];
        }

        return array_map(
            fn(array $photo) => $this->mapPhoto($photo),
            $response['photos']
        );
    }

    /**
     * Effectue une requête HTTP.
     *
     * @return array<string, mixed>|null
     */
    private function request(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Authorization: ' . $this->apiKey,
                    'Accept: application/json',
                ],
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Convertit une photo Pexels en ImageResult.
     *
     * @param array<string, mixed> $photo
     */
    private function mapPhoto(array $photo): ImageResult
    {
        return new ImageResult(
            id: (string) $photo['id'],
            url: $photo['src']['original'] ?? $photo['src']['large'] ?? '',
            thumbnailUrl: $photo['src']['medium'] ?? $photo['src']['small'] ?? '',
            provider: 'pexels',
            width: (int) ($photo['width'] ?? 0),
            height: (int) ($photo['height'] ?? 0),
            alt: $photo['alt'] ?? '',
            photographer: $photo['photographer'] ?? '',
            photographerUrl: $photo['photographer_url'] ?? '',
        );
    }
}
