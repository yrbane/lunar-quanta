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
    /** @var Post[]|null */
    private ?array $cachedAll = null;

    public function __construct(
        private readonly FileStorage $storage
    ) {
    }

    private function invalidateCache(): void
    {
        $this->cachedAll = null;
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
        $this->invalidateCache();

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
        $this->invalidateCache();
    }

    /**
     * Supprime un article.
     */
    public function delete(string $id): void
    {
        $this->storage->delete($id);
        $this->invalidateCache();
    }

    /**
     * Retourne tous les articles.
     *
     * @return Post[]
     */
    public function all(): array
    {
        if ($this->cachedAll !== null) {
            return $this->cachedAll;
        }

        $this->cachedAll = array_map(
            fn($data) => Post::fromArray($data),
            $this->storage->all()
        );

        return $this->cachedAll;
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

    /**
     * Retourne les articles paginés.
     *
     * @return array{items: Post[], total: int, page: int, perPage: int, totalPages: int, hasNext: bool, hasPrev: bool}
     */
    public function paginate(int $page = 1, int $perPage = 10, ?PostStatus $status = null): array
    {
        $posts = $status !== null
            ? array_filter($this->all(), fn($post) => $post->getStatus() === $status)
            : $this->all();

        // Trier par date de création décroissante
        usort($posts, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        $total = count($posts);
        $totalPages = (int) ceil($total / $perPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($posts, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1,
        ];
    }

    /**
     * Retourne les articles publiés paginés.
     *
     * @return array{items: Post[], total: int, page: int, perPage: int, totalPages: int, hasNext: bool, hasPrev: bool}
     */
    public function paginatePublished(int $page = 1, int $perPage = 10): array
    {
        $posts = $this->findPublished();

        // Trier par date de publication décroissante
        usort($posts, fn($a, $b) => $b->getPublishedAt() <=> $a->getPublishedAt());

        $total = count($posts);
        $totalPages = (int) ceil($total / $perPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($posts, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1,
        ];
    }

    /**
     * Retourne les articles par catégorie paginés.
     *
     * @return array{items: Post[], total: int, page: int, perPage: int, totalPages: int, hasNext: bool, hasPrev: bool}
     */
    public function paginateByCategory(string $categoryId, int $page = 1, int $perPage = 10): array
    {
        $posts = array_filter(
            $this->findPublished(),
            fn($post) => $post->getCategoryId() === $categoryId
        );

        // Trier par date de publication décroissante
        usort($posts, fn($a, $b) => $b->getPublishedAt() <=> $a->getPublishedAt());

        $total = count($posts);
        $totalPages = (int) ceil($total / $perPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($posts, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1,
        ];
    }

    /**
     * Retourne les articles par tag paginés.
     *
     * @return array{items: Post[], total: int, page: int, perPage: int, totalPages: int, hasNext: bool, hasPrev: bool}
     */
    public function paginateByTag(string $tagId, int $page = 1, int $perPage = 10): array
    {
        $posts = array_filter(
            $this->findPublished(),
            fn($post) => $post->hasTag($tagId)
        );

        // Trier par date de publication décroissante
        usort($posts, fn($a, $b) => $b->getPublishedAt() <=> $a->getPublishedAt());

        $total = count($posts);
        $totalPages = (int) ceil($total / $perPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($posts, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1,
        ];
    }

    /**
     * Recherche full-text dans les articles.
     *
     * Recherche dans le titre, contenu, excerpt, auteur et tags.
     *
     * @param string $query Terme de recherche
     * @param bool $publishedOnly Ne rechercher que dans les articles publiés
     * @return Post[] Articles correspondants triés par pertinence
     */
    public function search(string $query, bool $publishedOnly = true): array
    {
        $query = mb_strtolower(trim($query));
        if ($query === '') {
            return [];
        }

        $posts = $publishedOnly ? $this->findPublished() : $this->all();
        $results = [];

        // Tokeniser la requête en mots
        $terms = array_filter(preg_split('/\s+/', $query));

        foreach ($posts as $post) {
            $score = $this->calculateSearchScore($post, $terms);
            if ($score > 0) {
                $results[] = ['post' => $post, 'score' => $score];
            }
        }

        // Trier par score décroissant
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn($r) => $r['post'], $results);
    }

    /**
     * Recherche paginée.
     *
     * @return array{items: Post[], total: int, page: int, perPage: int, totalPages: int, hasNext: bool, hasPrev: bool, query: string}
     */
    public function searchPaginated(string $query, int $page = 1, int $perPage = 10, bool $publishedOnly = true): array
    {
        $posts = $this->search($query, $publishedOnly);

        $total = count($posts);
        $totalPages = (int) ceil($total / $perPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($posts, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1,
            'query' => $query,
        ];
    }

    /**
     * Retourne les articles featured/épinglés.
     *
     * @return Post[]
     */
    public function findFeatured(): array
    {
        $posts = array_filter(
            $this->findPublished(),
            fn($post) => $post->isFeatured()
        );

        // Trier par pinOrder croissant
        usort($posts, fn($a, $b) => $a->getPinOrder() <=> $b->getPinOrder());

        return $posts;
    }

    /**
     * Définit un article comme featured.
     */
    public function setFeatured(string $id, bool $featured = true, int $pinOrder = 0): Post
    {
        $post = $this->find($id);

        if ($post === null) {
            throw BlogException::postNotFound($id);
        }

        $post->setFeatured($featured);
        $post->setPinOrder($pinOrder);
        $this->update($post);

        return $post;
    }

    /**
     * Retourne les articles programmés pour publication.
     *
     * @return Post[]
     */
    public function findScheduled(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn($post) => $post->isScheduled()
        ));
    }

    /**
     * Retourne les articles dont la publication programmée est échue.
     *
     * @return Post[]
     */
    public function findReadyToPublish(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn($post) => $post->shouldAutoPublish()
        ));
    }

    /**
     * Publie automatiquement les articles dont la date programmée est passée.
     *
     * @return int Nombre d'articles publiés
     */
    public function publishScheduled(): int
    {
        $posts = $this->findReadyToPublish();
        $count = 0;

        foreach ($posts as $post) {
            $post->publish();
            $this->update($post);
            $count++;
        }

        return $count;
    }

    /**
     * Programme la publication d'un article.
     */
    public function schedulePublication(string $id, \DateTimeImmutable $date): Post
    {
        $post = $this->find($id);

        if ($post === null) {
            throw BlogException::postNotFound($id);
        }

        $post->schedulePublication($date);
        $this->update($post);

        return $post;
    }

    /**
     * Calcule le score de pertinence d'un article pour une recherche.
     *
     * @param Post $post
     * @param string[] $terms
     * @return int Score (0 si aucun match)
     */
    private function calculateSearchScore(Post $post, array $terms): int
    {
        $score = 0;
        $title = mb_strtolower($post->getTitle());
        $content = mb_strtolower($post->getContent());
        $excerpt = mb_strtolower($post->getExcerpt() ?? '');
        $author = mb_strtolower($post->getAuthor());
        $tags = array_map('mb_strtolower', $post->getTags());

        foreach ($terms as $term) {
            // Match exact dans le titre (poids élevé)
            if (str_contains($title, $term)) {
                $score += 100;
                // Bonus si le titre commence par le terme
                if (str_starts_with($title, $term)) {
                    $score += 50;
                }
            }

            // Match dans les tags (poids moyen-élevé)
            foreach ($tags as $tag) {
                if (str_contains($tag, $term)) {
                    $score += 80;
                }
            }

            // Match dans l'excerpt (poids moyen)
            if (str_contains($excerpt, $term)) {
                $score += 40;
            }

            // Match dans l'auteur (poids moyen)
            if (str_contains($author, $term)) {
                $score += 30;
            }

            // Match dans le contenu (poids faible mais compte le nombre d'occurrences)
            $contentMatches = substr_count($content, $term);
            if ($contentMatches > 0) {
                $score += min(10 + ($contentMatches * 2), 50); // Max 50 points pour le contenu
            }
        }

        return $score;
    }
}
