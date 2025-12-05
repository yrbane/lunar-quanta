<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

use Lunar\Entity\Tag;
use Lunar\Service\Storage\FileStorage;

/**
 * Service de gestion des tags.
 *
 * @example
 * ```php
 * $service = new TagService(new JsonStorage('data/blog/tags'));
 *
 * // Créer un tag
 * $tag = $service->create('PHP');
 *
 * // Trouver ou créer
 * $tag = $service->findOrCreate('PHP');
 *
 * // Chercher par slug
 * $tag = $service->findBySlug('php');
 * ```
 */
final class TagService
{
    public function __construct(
        private readonly FileStorage $storage
    ) {
    }

    /**
     * Crée un nouveau tag.
     */
    public function create(string $name): Tag
    {
        $tag = new Tag($name);
        $this->storage->save($tag->getId(), $tag->toArray());

        return $tag;
    }

    /**
     * Trouve un tag par ID.
     */
    public function find(string $id): ?Tag
    {
        $data = $this->storage->find($id);

        return $data ? Tag::fromArray($data) : null;
    }

    /**
     * Trouve un tag par slug.
     */
    public function findBySlug(string $slug): ?Tag
    {
        foreach ($this->storage->all() as $data) {
            if ($data['slug'] === $slug) {
                return Tag::fromArray($data);
            }
        }

        return null;
    }

    /**
     * Trouve ou crée un tag par son nom.
     */
    public function findOrCreate(string $name): Tag
    {
        $slug = SlugGenerator::slugify($name);
        $existing = $this->findBySlug($slug);

        if ($existing !== null) {
            return $existing;
        }

        return $this->create($name);
    }

    /**
     * Met à jour un tag.
     */
    public function update(Tag $tag): void
    {
        $this->storage->save($tag->getId(), $tag->toArray());
    }

    /**
     * Supprime un tag.
     */
    public function delete(string $id): void
    {
        $this->storage->delete($id);
    }

    /**
     * Retourne tous les tags.
     *
     * @return Tag[]
     */
    public function all(): array
    {
        return array_map(
            fn($data) => Tag::fromArray($data),
            $this->storage->all()
        );
    }

    /**
     * Retourne les tags les plus utilisés.
     *
     * @return Tag[]
     */
    public function popular(int $limit = 10): array
    {
        $tags = $this->all();

        usort($tags, fn($a, $b) => $b->getArticleCount() <=> $a->getArticleCount());

        return array_slice($tags, 0, $limit);
    }
}
