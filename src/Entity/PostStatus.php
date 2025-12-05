<?php

declare(strict_types=1);

namespace Lunar\Entity;

/**
 * Énumération des états possibles d'un article.
 *
 * Le cycle de vie d'un article suit ces transitions :
 * - DRAFT -> PUBLISHED (publication)
 * - PUBLISHED -> DRAFT (dépublication)
 * - PUBLISHED -> ARCHIVED (archivage)
 * - DRAFT -> ARCHIVED (archivage sans publication)
 *
 * @example
 * ```php
 * $post = new Post();
 * $post->setStatus(PostStatus::DRAFT);
 *
 * if ($post->getStatus() === PostStatus::PUBLISHED) {
 *     // L'article est visible publiquement
 * }
 * ```
 */
enum PostStatus: string
{
    /**
     * Brouillon - Article en cours de rédaction, non visible publiquement.
     */
    case DRAFT = 'draft';

    /**
     * Publié - Article visible publiquement, HTML statique généré.
     */
    case PUBLISHED = 'published';

    /**
     * Archivé - Article retiré mais conservé pour référence.
     */
    case ARCHIVED = 'archived';

    /**
     * Vérifie si l'article peut être publié depuis cet état.
     */
    public function canPublish(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Vérifie si l'article peut être dépublié depuis cet état.
     */
    public function canUnpublish(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Vérifie si l'article peut être archivé depuis cet état.
     */
    public function canArchive(): bool
    {
        return $this === self::DRAFT || $this === self::PUBLISHED;
    }

    /**
     * Vérifie si l'article est visible publiquement.
     */
    public function isPublic(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Retourne le libellé localisé de l'état.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'Publié',
            self::ARCHIVED => 'Archivé',
        };
    }

    /**
     * Retourne la couleur associée à l'état (pour l'UI).
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
            self::ARCHIVED => 'orange',
        };
    }
}
