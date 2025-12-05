<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

use Lunar\Entity\Post;
use Lunar\Entity\PostStatus;
use Lunar\Service\Storage\FileStorage;

/**
 * Service de gestion des articles.
 *
 * @example
 * ```php
 * $service = new PostService(new JsonStorage('data/blog/posts'));
 *
 * // Créer un article
 * $post = $service->create('Mon Article', '# Contenu');
 *
 * // Publier
 * $service->publish($post->getId());
 *
 * // Lister les publiés
 * $posts = $service->findPublished();
 * ```
 */
final class PostService
{
    public function __construct(
        private readonly FileStorage $storage
    ) {
    }

    /**
     * Crée un nouvel article.
     */
    public function create(string $title, string $content): Post
    {
        $post = new Post($title, $content);

        // Garantir l'unicité du slug
        $existingSlugs = array_map(
            fn($data) => $data['slug'],
            $this->storage->all()
        );

        if (in_array($post->getSlug(), $existingSlugs, true)) {
            $uniqueSlug = (new SlugGenerator())->generateUnique($title, $existingSlugs);
            $post->setSlug($uniqueSlug);
        }

        $this->storage->save($post->getId(), $post->toArray());

        return $post;
    }

    /**
     * Trouve un article par ID.
     */
    public function find(string $id): ?Post
    {
        $data = $this->storage->find($id);

        return $data ? Post::fromArray($data) : null;
    }

    /**
     * Trouve un article par slug.
     */
    public function findBySlug(string $slug): ?Post
    {
        foreach ($this->storage->all() as $data) {
            if ($data['slug'] === $slug) {
                return Post::fromArray($data);
            }
        }

        return null;
    }

    /**
     * Met à jour un article.
     */
    public function update(Post $post): void
    {
        $this->storage->save($post->getId(), $post->toArray());
    }

    /**
     * Supprime un article.
     */
    public function delete(string $id): void
    {
        $this->storage->delete($id);
    }

    /**
     * Retourne tous les articles.
     *
     * @return Post[]
     */
    public function all(): array
    {
        return array_map(
            fn($data) => Post::fromArray($data),
            $this->storage->all()
        );
    }

    /**
     * Publie un article.
     *
     * @throws BlogException si l'article n'existe pas
     */
    public function publish(string $id): Post
    {
        $post = $this->find($id);

        if ($post === null) {
            throw BlogException::postNotFound($id);
        }

        $post->publish();
        $this->update($post);

        return $post;
    }

    /**
     * Dépublie un article.
     *
     * @throws BlogException si l'article n'existe pas
     */
    public function unpublish(string $id): Post
    {
        $post = $this->find($id);

        if ($post === null) {
            throw BlogException::postNotFound($id);
        }

        $post->unpublish();
        $this->update($post);

        return $post;
    }

    /**
     * Archive un article.
     *
     * @throws BlogException si l'article n'existe pas
     */
    public function archive(string $id): Post
    {
        $post = $this->find($id);

        if ($post === null) {
            throw BlogException::postNotFound($id);
        }

        $post->archive();
        $this->update($post);

        return $post;
    }

    /**
     * Retourne les articles publiés.
     *
     * @return Post[]
     */
    public function findPublished(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn($post) => $post->isPublished()
        ));
    }

    /**
     * Retourne les brouillons.
     *
     * @return Post[]
     */
    public function findDrafts(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn($post) => $post->isDraft()
        ));
    }

    /**
     * Retourne les articles par tag.
     *
     * @return Post[]
     */
    public function findByTag(string $tagId): array
    {
        return array_values(array_filter(
            $this->all(),
            fn($post) => $post->hasTag($tagId)
        ));
    }

    /**
     * Retourne les articles par catégorie.
     *
     * @return Post[]
     */
    public function findByCategory(string $categoryId): array
    {
        return array_values(array_filter(
            $this->all(),
            fn($post) => $post->getCategoryId() === $categoryId
        ));
    }

    /**
     * Retourne les articles récents.
     *
     * @return Post[]
     */
    public function findRecent(int $limit = 10): array
    {
        $published = $this->findPublished();

        // Trier par date de publication décroissante
        usort($published, function ($a, $b) {
            return $b->getPublishedAt() <=> $a->getPublishedAt();
        });

        return array_slice($published, 0, $limit);
    }

    /**
     * Compte le nombre total d'articles.
     */
    public function count(): int
    {
        return count($this->storage->all());
    }

    /**
     * Compte les articles par statut.
     */
    public function countByStatus(PostStatus $status): int
    {
        return count(array_filter(
            $this->all(),
            fn($post) => $post->getStatus() === $status
        ));
    }
}
