<?php
/**
 * Lunar Quanta Framework - Service de Gestion des Avatars.
 *
 * =============================================================================
 * FONCTIONNALITÉS
 * =============================================================================
 *
 * Ce service gère le cycle de vie complet des avatars utilisateur :
 *
 * ```
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │                        PIPELINE DE TRAITEMENT                           │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │                                                                         │
 * │   Upload         Validation        Redimensionnement       Stockage     │
 * │      │               │                    │                    │        │
 * │      ▼               ▼                    ▼                    ▼        │
 * │  ┌───────┐      ┌─────────┐          ┌─────────┐          ┌─────────┐  │
 * │  │ Image │ ───► │ Type ?  │ ───►     │ Resize  │ ───►     │  Save   │  │
 * │  │ brute │      │ Taille ?│          │ 256x256 │          │ WebP/PNG│  │
 * │  └───────┘      └─────────┘          └─────────┘          └─────────┘  │
 * │                                                                         │
 * └─────────────────────────────────────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * FORMATS SUPPORTÉS
 * =============================================================================
 *
 * - JPEG/JPG : Photos, images avec beaucoup de couleurs
 * - PNG : Images avec transparence
 * - GIF : Images animées (première frame uniquement)
 * - WebP : Format moderne (meilleure compression)
 *
 * @package    Lunar\Service\Media
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Media;

/**
 * Service de gestion des avatars utilisateur.
 */
class AvatarService
{
    /**
     * Taille par défaut des avatars (carré).
     */
    private const DEFAULT_SIZE = 256;

    /**
     * Taille maximale du fichier uploadé (2 Mo).
     */
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;

    /**
     * Types MIME autorisés.
     */
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private string $storagePath;
    private string $publicPath;
    private int $avatarSize;

    /**
     * @param string $storagePath Chemin de stockage (ex: 'public/uploads/avatars')
     * @param string $publicPath  Chemin public pour les URLs (ex: '/uploads/avatars')
     * @param int    $avatarSize  Taille des avatars en pixels
     */
    public function __construct(
        string $storagePath = 'public/uploads/avatars',
        string $publicPath = '/uploads/avatars',
        int $avatarSize = self::DEFAULT_SIZE
    ) {
        $this->storagePath = rtrim($storagePath, '/');
        $this->publicPath = rtrim($publicPath, '/');
        $this->avatarSize = $avatarSize;

        $this->ensureDirectoryExists();
    }

    // =========================================================================
    // UPLOAD ET TRAITEMENT
    // =========================================================================

    /**
     * Upload et traite un avatar.
     *
     * @param array<string, mixed> $file   Le fichier uploadé ($_FILES['avatar'])
     * @param string               $userId L'ID de l'utilisateur
     *
     * @return string Le chemin public de l'avatar
     *
     * @throws AvatarException Si l'upload échoue
     */
    public function upload(array $file, string $userId): string
    {
        // Validation
        $this->validateUpload($file);

        // Génère un nom unique
        $extension = $this->getExtension($file['type']);
        $filename = $this->generateFilename($userId, $extension);

        // Traite l'image
        $sourcePath = $file['tmp_name'];
        $destinationPath = $this->storagePath . '/' . $filename;

        $this->processImage($sourcePath, $destinationPath, $file['type']);

        // Supprime l'ancien avatar
        $this->deleteOldAvatars($userId);

        return $this->publicPath . '/' . $filename;
    }

    /**
     * Upload depuis une URL (pour OAuth).
     *
     * @param string $url    L'URL de l'avatar
     * @param string $userId L'ID de l'utilisateur
     *
     * @return string|null Le chemin public ou null si échec
     */
    public function uploadFromUrl(string $url, string $userId): ?string
    {
        // Télécharge l'image
        $content = @file_get_contents($url);
        if ($content === false) {
            return null;
        }

        // Crée un fichier temporaire
        $tempFile = tempnam(sys_get_temp_dir(), 'avatar_');
        if ($tempFile === false) {
            return null;
        }

        file_put_contents($tempFile, $content);

        // Détecte le type MIME
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tempFile);

        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            unlink($tempFile);

            return null;
        }

        // Génère le nom et traite l'image
        $extension = self::ALLOWED_TYPES[$mimeType];
        $filename = $this->generateFilename($userId, $extension);
        $destinationPath = $this->storagePath . '/' . $filename;

        try {
            $this->processImage($tempFile, $destinationPath, $mimeType);
        } finally {
            unlink($tempFile);
        }

        $this->deleteOldAvatars($userId);

        return $this->publicPath . '/' . $filename;
    }

    /**
     * Supprime l'avatar d'un utilisateur.
     *
     * @param string $userId L'ID de l'utilisateur
     *
     * @return bool true si un avatar a été supprimé
     */
    public function delete(string $userId): bool
    {
        return $this->deleteOldAvatars($userId) > 0;
    }

    /**
     * Retourne l'URL de l'avatar d'un utilisateur.
     *
     * @param string $userId L'ID de l'utilisateur
     *
     * @return string|null L'URL ou null si pas d'avatar
     */
    public function getAvatarUrl(string $userId): ?string
    {
        $hash = $this->hashUserId($userId);
        $pattern = $this->storagePath . '/' . $hash . '_*';
        $files = glob($pattern);

        if ($files === false || empty($files)) {
            return null;
        }

        // Retourne le plus récent
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return $this->publicPath . '/' . basename($files[0]);
    }

    /**
     * Génère un avatar par défaut (initiales).
     *
     * @param string $name  Le nom de l'utilisateur
     * @param string $color Couleur de fond (hex)
     *
     * @return string Le SVG de l'avatar
     */
    public function generateDefaultAvatar(string $name, string $color = '#6366f1'): string
    {
        $initials = $this->getInitials($name);
        $size = $this->avatarSize;

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
            <rect width="{$size}" height="{$size}" fill="{$color}"/>
            <text x="50%" y="50%" dominant-baseline="central" text-anchor="middle"
                  fill="white" font-family="sans-serif" font-size="{$this->getFontSize()}" font-weight="600">
                {$initials}
            </text>
        </svg>
        SVG;
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    /**
     * Valide un fichier uploadé.
     *
     * @param array<string, mixed> $file Le fichier
     *
     * @throws AvatarException Si la validation échoue
     */
    private function validateUpload(array $file): void
    {
        // Vérifie les erreurs d'upload
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new AvatarException($this->getUploadErrorMessage($file['error'] ?? -1));
        }

        // Vérifie que le fichier existe
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new AvatarException('Fichier invalide.');
        }

        // Vérifie la taille
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $maxMb = self::MAX_FILE_SIZE / 1024 / 1024;
            throw new AvatarException("Le fichier est trop volumineux. Maximum : {$maxMb} Mo.");
        }

        // Vérifie le type MIME
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            throw new AvatarException('Type de fichier non supporté. Utilisez JPG, PNG, GIF ou WebP.');
        }
    }

    // =========================================================================
    // TRAITEMENT D'IMAGE
    // =========================================================================

    /**
     * Traite et redimensionne l'image.
     *
     * @param string $sourcePath      Chemin source
     * @param string $destinationPath Chemin destination
     * @param string $mimeType        Type MIME
     *
     * @throws AvatarException Si le traitement échoue
     */
    private function processImage(string $sourcePath, string $destinationPath, string $mimeType): void
    {
        // Charge l'image source
        $source = $this->loadImage($sourcePath, $mimeType);
        if ($source === false) {
            throw new AvatarException('Impossible de charger l\'image.');
        }

        // Dimensions source
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        // Calcule le crop carré centré
        $cropSize = min($sourceWidth, $sourceHeight);
        $cropX = (int) (($sourceWidth - $cropSize) / 2);
        $cropY = (int) (($sourceHeight - $cropSize) / 2);

        // Crée l'image destination
        $destination = imagecreatetruecolor($this->avatarSize, $this->avatarSize);
        if ($destination === false) {
            imagedestroy($source);
            throw new AvatarException('Erreur lors de la création de l\'image.');
        }

        // Préserve la transparence pour PNG/WebP
        imagealphablending($destination, false);
        imagesavealpha($destination, true);

        // Redimensionne avec crop centré
        imagecopyresampled(
            $destination,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            $this->avatarSize,
            $this->avatarSize,
            $cropSize,
            $cropSize
        );

        // Sauvegarde
        $this->saveImage($destination, $destinationPath, $mimeType);

        // Libère la mémoire
        imagedestroy($source);
        imagedestroy($destination);
    }

    /**
     * Charge une image depuis un fichier.
     *
     * @return \GdImage|false
     */
    private function loadImage(string $path, string $mimeType): \GdImage|false
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * Sauvegarde une image.
     *
     * @param \GdImage $image L'image
     * @param string   $path  Le chemin
     * @param string   $mimeType Le type MIME
     */
    private function saveImage(\GdImage $image, string $path, string $mimeType): void
    {
        match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $path, 90),
            'image/png' => imagepng($image, $path, 9),
            'image/gif' => imagegif($image, $path),
            'image/webp' => imagewebp($image, $path, 90),
            default => throw new AvatarException('Type MIME non supporté pour la sauvegarde.'),
        };
    }

    // =========================================================================
    // UTILITAIRES
    // =========================================================================

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    private function generateFilename(string $userId, string $extension): string
    {
        $hash = $this->hashUserId($userId);
        $timestamp = time();

        return "{$hash}_{$timestamp}.{$extension}";
    }

    private function hashUserId(string $userId): string
    {
        return substr(hash('sha256', $userId), 0, 16);
    }

    private function getExtension(string $mimeType): string
    {
        return self::ALLOWED_TYPES[$mimeType] ?? 'jpg';
    }

    private function deleteOldAvatars(string $userId): int
    {
        $hash = $this->hashUserId($userId);
        $pattern = $this->storagePath . '/' . $hash . '_*';
        $files = glob($pattern);
        $count = 0;

        if ($files !== false) {
            foreach ($files as $file) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function getInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        if ($parts === false || empty($parts)) {
            return '?';
        }

        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials ?: '?';
    }

    private function getFontSize(): int
    {
        return (int) ($this->avatarSize * 0.4);
    }

    private function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier est trop volumineux.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier.',
            default => 'Erreur lors de l\'upload.',
        };
    }
}
