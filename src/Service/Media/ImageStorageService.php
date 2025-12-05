<?php

declare(strict_types=1);

namespace Lunar\Service\Media;

use Lunar\Entity\Image;
use Lunar\Entity\ImageSource;
use Lunar\Service\Storage\FileStorage;

/**
 * Service de stockage des métadonnées d'images.
 *
 * Gère les métadonnées des images (pas les fichiers eux-mêmes).
 * Les fichiers sont stockés dans public/uploads/blog/.
 *
 * @example
 * ```php
 * $service = new ImageStorageService(
 *     new JsonStorage('data/blog/images'),
 *     'public/uploads/blog'
 * );
 *
 * // Enregistrer une image uploadée
 * $image = $service->createFromUpload($uploadedFile);
 *
 * // Trouver une image
 * $image = $service->find($imageId);
 * ```
 */
final class ImageStorageService
{
    public function __construct(
        private readonly FileStorage $storage,
        private readonly string $uploadPath
    ) {
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Crée et sauvegarde une nouvelle image.
     */
    public function create(Image $image): Image
    {
        $this->storage->save($image->getId(), $image->toArray());

        return $image;
    }

    /**
     * Trouve une image par ID.
     */
    public function find(string $id): ?Image
    {
        $data = $this->storage->find($id);

        return $data ? Image::fromArray($data) : null;
    }

    /**
     * Met à jour une image.
     */
    public function update(Image $image): void
    {
        $this->storage->save($image->getId(), $image->toArray());
    }

    /**
     * Supprime une image (métadonnées et fichier).
     */
    public function delete(string $id): void
    {
        $image = $this->find($id);

        if ($image !== null) {
            // Supprimer le fichier
            $filePath = $this->getFilePath($image);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Supprimer le dossier s'il est vide
            $dir = dirname($filePath);
            if (is_dir($dir) && count(scandir($dir)) === 2) {
                rmdir($dir);
            }
        }

        $this->storage->delete($id);
    }

    /**
     * Retourne toutes les images.
     *
     * @return Image[]
     */
    public function all(): array
    {
        return array_map(
            fn($data) => Image::fromArray($data),
            $this->storage->all()
        );
    }

    /**
     * Retourne les images par source.
     *
     * @return Image[]
     */
    public function findBySource(ImageSource $source): array
    {
        return array_filter(
            $this->all(),
            fn($image) => $image->getSource() === $source
        );
    }

    /**
     * Retourne le chemin complet du fichier image.
     */
    public function getFilePath(Image $image): string
    {
        return $this->uploadPath . '/' . $image->getPath();
    }

    /**
     * Retourne l'URL publique de l'image.
     */
    public function getPublicUrl(Image $image): string
    {
        return '/uploads/blog/' . $image->getPath();
    }

    /**
     * Crée le répertoire pour une image.
     */
    public function ensureImageDirectory(Image $image): string
    {
        $dir = $this->uploadPath . '/' . $image->getId();

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}
