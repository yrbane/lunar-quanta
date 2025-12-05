<?php

declare(strict_types=1);

namespace Lunar\Service\Media;

/**
 * Optimiseur d'images.
 *
 * Redimensionne et optimise les images pour le web.
 *
 * @example
 * ```php
 * $optimizer = new ImageOptimizer('/path/to/uploads');
 * $result = $optimizer->optimize($imageData, 'featured.jpg');
 * // ['original' => '/uploads/featured.jpg', 'thumb' => '/uploads/featured_thumb.jpg']
 * ```
 */
final class ImageOptimizer
{
    public function __construct(
        private readonly string $uploadPath,
        private readonly string $publicPath = '/uploads',
        private readonly int $maxWidth = 1200,
        private readonly int $thumbWidth = 300,
        private readonly int $quality = 85
    ) {
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Optimise une image depuis des données binaires.
     *
     * @return array{original: string, thumb: string, width: int, height: int}|null
     */
    public function optimize(string $imageData, string $filename): ?array
    {
        $image = @imagecreatefromstring($imageData);

        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Générer un nom de fichier unique
        $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: 'jpg';
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $uniqueName = $baseName . '_' . uniqid() . '.' . $ext;

        // Optimiser l'image principale
        $optimized = $this->resize($image, $width, $height, $this->maxWidth);
        $originalPath = $this->uploadPath . '/' . $uniqueName;
        $this->save($optimized, $originalPath, $ext);

        // Créer la miniature
        $thumb = $this->resize($image, $width, $height, $this->thumbWidth);
        $thumbName = $baseName . '_' . uniqid() . '_thumb.' . $ext;
        $thumbPath = $this->uploadPath . '/' . $thumbName;
        $this->save($thumb, $thumbPath, $ext);

        // Libérer la mémoire
        imagedestroy($image);
        imagedestroy($optimized);
        imagedestroy($thumb);

        $newWidth = imagesx(@imagecreatefromstring(file_get_contents($originalPath)));
        $newHeight = imagesy(@imagecreatefromstring(file_get_contents($originalPath)));

        return [
            'original' => $this->publicPath . '/' . $uniqueName,
            'thumb' => $this->publicPath . '/' . $thumbName,
            'width' => $newWidth ?: $width,
            'height' => $newHeight ?: $height,
        ];
    }

    /**
     * Optimise une image depuis une URL.
     *
     * @return array{original: string, thumb: string, width: int, height: int}|null
     */
    public function optimizeFromUrl(string $url, string $filename): ?array
    {
        $imageData = @file_get_contents($url);

        if ($imageData === false) {
            return null;
        }

        return $this->optimize($imageData, $filename);
    }

    /**
     * Supprime une image et sa miniature.
     */
    public function delete(string $path): bool
    {
        // Convertir le chemin public en chemin système
        $fullPath = str_replace($this->publicPath, $this->uploadPath, $path);

        if (file_exists($fullPath)) {
            unlink($fullPath);

            // Essayer de supprimer la miniature aussi
            $thumbPath = preg_replace('/\.(\w+)$/', '_thumb.$1', $fullPath);
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }

            return true;
        }

        return false;
    }

    /**
     * Redimensionne une image.
     *
     * @param \GdImage $image
     * @return \GdImage
     */
    private function resize(\GdImage $image, int $width, int $height, int $maxWidth): \GdImage
    {
        if ($width <= $maxWidth) {
            // Créer une copie si pas besoin de redimensionner
            $copy = imagecreatetruecolor($width, $height);
            imagecopy($copy, $image, 0, 0, 0, 0, $width, $height);
            return $copy;
        }

        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = (int) ($height * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        return $resized;
    }

    /**
     * Sauvegarde une image.
     *
     * @param \GdImage $image
     */
    private function save(\GdImage $image, string $path, string $extension): void
    {
        $ext = strtolower($extension);

        match ($ext) {
            'png' => imagepng($image, $path, (int) (9 - ($this->quality / 11))),
            'gif' => imagegif($image, $path),
            'webp' => imagewebp($image, $path, $this->quality),
            default => imagejpeg($image, $path, $this->quality),
        };
    }
}
