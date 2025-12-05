<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

/**
 * Exception de base pour le système de blog.
 *
 * Toutes les exceptions spécifiques au blog étendent cette classe,
 * permettant de les attraper de manière groupée si nécessaire.
 *
 * @example
 * ```php
 * try {
 *     $postService->publish($post);
 * } catch (BlogException $e) {
 *     // Gère toutes les erreurs liées au blog
 *     $logger->error($e->getMessage());
 * }
 * ```
 */
class BlogException extends \RuntimeException
{
    /**
     * Crée une exception pour un article non trouvé.
     */
    public static function postNotFound(string $id): self
    {
        return new self(sprintf('Post not found: %s', $id));
    }

    /**
     * Crée une exception pour une catégorie non trouvée.
     */
    public static function categoryNotFound(string $id): self
    {
        return new self(sprintf('Category not found: %s', $id));
    }

    /**
     * Crée une exception pour un tag non trouvé.
     */
    public static function tagNotFound(string $id): self
    {
        return new self(sprintf('Tag not found: %s', $id));
    }

    /**
     * Crée une exception pour une image non trouvée.
     */
    public static function imageNotFound(string $id): self
    {
        return new self(sprintf('Image not found: %s', $id));
    }

    /**
     * Crée une exception pour un slug déjà utilisé.
     */
    public static function slugAlreadyExists(string $slug): self
    {
        return new self(sprintf('Slug already exists: %s', $slug));
    }

    /**
     * Crée une exception pour une référence circulaire de catégories.
     */
    public static function circularCategoryReference(string $categoryId, string $parentId): self
    {
        return new self(sprintf(
            'Circular category reference detected: %s cannot be child of %s',
            $categoryId,
            $parentId
        ));
    }

    /**
     * Crée une exception pour une erreur de génération statique.
     */
    public static function staticGenerationFailed(string $reason): self
    {
        return new self(sprintf('Static generation failed: %s', $reason));
    }

    /**
     * Crée une exception pour une erreur d'upload d'image.
     */
    public static function imageUploadFailed(string $reason): self
    {
        return new self(sprintf('Image upload failed: %s', $reason));
    }

    /**
     * Crée une exception pour un état de publication invalide.
     */
    public static function invalidPublishState(string $currentState, string $action): self
    {
        return new self(sprintf(
            'Cannot %s post in state: %s',
            $action,
            $currentState
        ));
    }
}
