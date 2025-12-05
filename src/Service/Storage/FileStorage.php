<?php

declare(strict_types=1);

namespace Lunar\Service\Storage;

/**
 * Stockage générique basé sur fichiers JSON.
 *
 * Chaque entité est stockée dans un fichier JSON séparé.
 * Pas de chiffrement pour les données publiques du blog.
 *
 * @example
 * ```php
 * $storage = new FileStorage('data/blog/posts');
 *
 * // Sauvegarder
 * $storage->save('post-123', ['title' => 'Hello']);
 *
 * // Charger
 * $data = $storage->find('post-123');
 *
 * // Lister tous
 * $all = $storage->all();
 * ```
 */
final class FileStorage
{
    public function __construct(
        private readonly string $basePath
    ) {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    /**
     * Sauvegarde des données.
     *
     * @param array<string, mixed> $data
     */
    public function save(string $id, array $data): void
    {
        $path = $this->getPath($id);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON');
        }

        file_put_contents($path, $json);
    }

    /**
     * Charge des données par ID.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        $path = $this->getPath($id);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Vérifie si un ID existe.
     */
    public function exists(string $id): bool
    {
        return file_exists($this->getPath($id));
    }

    /**
     * Supprime des données.
     */
    public function delete(string $id): void
    {
        $path = $this->getPath($id);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Retourne toutes les données.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $files = glob($this->basePath . '/*.json');
        $result = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if (is_array($data) && isset($data['id'])) {
                $result[$data['id']] = $data;
            }
        }

        return $result;
    }

    /**
     * Compte le nombre d'éléments.
     */
    public function count(): int
    {
        return count(glob($this->basePath . '/*.json'));
    }

    /**
     * Vide le stockage.
     */
    public function clear(): void
    {
        $files = glob($this->basePath . '/*.json');

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Retourne le chemin du fichier pour un ID.
     */
    private function getPath(string $id): string
    {
        // Nettoyer l'ID pour éviter les traversées de répertoire
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);

        return $this->basePath . '/' . $safeId . '.json';
    }
}
