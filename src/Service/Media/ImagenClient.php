<?php

declare(strict_types=1);

namespace Lunar\Service\Media;

/**
 * Client pour l'API Google Imagen.
 *
 * Imagen génère des images à partir de descriptions textuelles.
 *
 * @example
 * ```php
 * $client = new ImagenClient('your-api-key', 'your-project-id');
 * $image = $client->generate('A peaceful garden');
 * ```
 *
 * @see https://cloud.google.com/vertex-ai/docs/generative-ai/image/generate-images
 */
final class ImagenClient implements ImageProviderInterface
{
    private const API_URL = 'https://us-central1-aiplatform.googleapis.com/v1';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $projectId,
        private readonly string $location = 'us-central1',
        private readonly string $model = 'imagegeneration@006'
    ) {
    }

    public function search(string $query, int $limit = 10): array
    {
        // Imagen ne supporte pas la recherche, mais on peut générer des images
        $result = $this->generate($query);
        return $result !== null ? [$result] : [];
    }

    public function generate(string $prompt): ?ImageResult
    {
        if (empty($this->apiKey) || empty($this->projectId)) {
            return null;
        }

        $url = sprintf(
            '%s/projects/%s/locations/%s/publishers/google/models/%s:predict',
            self::API_URL,
            $this->projectId,
            $this->location,
            $this->model
        );

        $payload = [
            'instances' => [
                ['prompt' => $prompt],
            ],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => '1:1',
                'safetySetting' => 'block_some',
            ],
        ];

        $response = $this->request($url, $payload);

        if ($response === null || !isset($response['predictions'][0])) {
            return null;
        }

        $prediction = $response['predictions'][0];

        // Imagen retourne l'image en base64
        $imageData = $prediction['bytesBase64Encoded'] ?? '';

        if (empty($imageData)) {
            return null;
        }

        return new ImageResult(
            id: 'imagen-' . uniqid(),
            url: 'data:image/png;base64,' . $imageData,
            thumbnailUrl: 'data:image/png;base64,' . $imageData,
            provider: 'imagen',
            width: 1024,
            height: 1024,
            alt: $prompt,
        );
    }

    public function getName(): string
    {
        return 'imagen';
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
    private function request(string $url, array $payload): ?array
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

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        return is_array($data) ? $data : null;
    }
}
