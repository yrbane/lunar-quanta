<?php
/**
 * Lunar Quanta Framework - Exception Avatar.
 *
 * @package    Lunar\Service\Media
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Media;

/**
 * Exception pour les erreurs liées aux avatars.
 */
class AvatarException extends \RuntimeException
{
    /**
     * Crée une exception pour un fichier trop volumineux.
     *
     * @param int $maxSize Taille max en octets
     */
    public static function fileTooLarge(int $maxSize): self
    {
        $maxMb = $maxSize / 1024 / 1024;

        return new self("Le fichier est trop volumineux. Maximum : {$maxMb} Mo.");
    }

    /**
     * Crée une exception pour un type de fichier non supporté.
     */
    public static function unsupportedType(): self
    {
        return new self('Type de fichier non supporté. Utilisez JPG, PNG, GIF ou WebP.');
    }

    /**
     * Crée une exception pour un fichier invalide.
     */
    public static function invalidFile(): self
    {
        return new self('Fichier invalide.');
    }

    /**
     * Crée une exception pour une erreur de traitement.
     */
    public static function processingError(string $message): self
    {
        return new self("Erreur de traitement : {$message}");
    }
}
