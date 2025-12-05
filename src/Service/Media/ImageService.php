<?php

declare(strict_types=1);

namespace Lunar\Service\Media;

/**
 * Service orchestrateur pour la gestion des images.
 *
 * Coordonne les différents fournisseurs d'images et l'optimisation.
 *
 * @example
 * ```php
 * $service = new ImageService($optimizer);
 * $service->addProvider(new PexelsClient($apiKey));
 * $service->addProvider(new DalleClient($apiKey));
 *
 * // Rechercher des images
 * $images = $service->search('nature landscape');
 *
 * // Générer une image
 * $image = $service->generate('A sunset over mountains', 'dalle');
 *
 * // Télécharger et optimiser
 * $result = $service->download($image);
 * ```
 */
final class ImageService
{
    /** @var ImageProviderInterface[] */
    private array $providers = [];

    public function __construct(
        private readonly ImageOptimizer $optimizer
    ) {
    }

    /**
     * Ajoute un fournisseur d'images.
     */
    public function addProvider(ImageProviderInterface $provider): self
    {
        $this->providers[$provider->getName()] = $provider;
        return $this;
    }

    /**
     * Recherche des images auprès de tous les fournisseurs.
     *
     * @return ImageResult[]
     */
    public function search(string $query, int $limit = 10): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            if (!$provider->supportsGeneration()) {
                $providerResults = $provider->search($query, $limit);
                $results = array_merge($results, $providerResults);
            }
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * Recherche des images auprès d'un fournisseur spécifique.
     *
     * @return ImageResult[]
     */
    public function searchProvider(string $providerName, string $query, int $limit = 10): array
    {
        if (!isset($this->providers[$providerName])) {
            return [];
        }

        return $this->providers[$providerName]->search($query, $limit);
    }

    /**
     * Génère une image avec un fournisseur IA.
     */
    public function generate(string $prompt, string $providerName = 'dalle'): ?ImageResult
    {
        if (!isset($this->providers[$providerName])) {
            return null;
        }

        $provider = $this->providers[$providerName];

        if (!$provider->supportsGeneration()) {
            return null;
        }

        return $provider->generate($prompt);
    }

    /**
     * Télécharge et optimise une image.
     *
     * @return array{original: string, thumb: string, width: int, height: int}|null
     */
    public function download(ImageResult $image): ?array
    {
        $filename = $this->generateFilename($image);
        return $this->optimizer->optimizeFromUrl($image->url, $filename);
    }

    /**
     * Télécharge une image depuis une URL externe.
     *
     * @return array{original: string, thumb: string, width: int, height: int}|null
     */
    public function downloadFromUrl(string $url, string $filename = ''): ?array
    {
        if (empty($filename)) {
            $filename = 'image_' . uniqid() . '.jpg';
        }

        return $this->optimizer->optimizeFromUrl($url, $filename);
    }

    /**
     * Upload et optimise une image depuis des données.
     *
     * @return array{original: string, thumb: string, width: int, height: int}|null
     */
    public function upload(string $imageData, string $filename): ?array
    {
        return $this->optimizer->optimize($imageData, $filename);
    }

    /**
     * Supprime une image.
     */
    public function delete(string $path): bool
    {
        return $this->optimizer->delete($path);
    }

    /**
     * Retourne les fournisseurs disponibles.
     *
     * @return string[]
     */
    public function getProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Retourne les fournisseurs supportant la génération.
     *
     * @return string[]
     */
    public function getGenerativeProviders(): array
    {
        return array_keys(array_filter(
            $this->providers,
            fn($p) => $p->supportsGeneration()
        ));
    }

    /**
     * Génère un nom de fichier à partir d'un résultat d'image.
     */
    private function generateFilename(ImageResult $image): string
    {
        $ext = 'jpg';

        // Essayer de détecter l'extension depuis l'URL
        if (preg_match('/\.(\w{3,4})(\?|$)/', $image->url, $matches)) {
            $ext = strtolower($matches[1]);
        }

        return $image->provider . '_' . $image->id . '.' . $ext;
    }
}
