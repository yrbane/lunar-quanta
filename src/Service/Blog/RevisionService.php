<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

use Lunar\Entity\Post;
use Lunar\Service\Storage\FileStorage;

/**
 * Service de gestion des révisions d'articles.
 *
 * Permet de sauvegarder et restaurer les versions précédentes des articles.
 *
 * @example
 * ```php
 * $revisionService = new RevisionService(new FileStorage('data/blog/revisions'));
 *
 * // Sauvegarder une révision avant modification
 * $revisionService->save($post, 'Modification du titre');
 *
 * // Lister les révisions
 * $revisions = $revisionService->getRevisions($post->getId());
 *
 * // Restaurer une révision
 * $restoredPost = $revisionService->restore($revisionId);
 * ```
 */
final class RevisionService
{
    private const MAX_REVISIONS_PER_POST = 50;

    public function __construct(
        private readonly FileStorage $storage
    ) {
    }

    /**
     * Sauvegarde une révision d'un article.
     *
     * @param Post $post L'article à sauvegarder
     * @param string $comment Commentaire décrivant les modifications
     * @param string $author Auteur de la modification
     * @return string ID de la révision
     */
    public function save(Post $post, string $comment = '', string $author = ''): string
    {
        $revisionId = $this->generateRevisionId();

        $revision = [
            'id' => $revisionId,
            'postId' => $post->getId(),
            'postSlug' => $post->getSlug(),
            'postTitle' => $post->getTitle(),
            'data' => $post->toArray(),
            'comment' => $comment,
            'author' => $author ?: $post->getAuthor(),
            'createdAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $this->storage->save($revisionId, $revision);

        // Limiter le nombre de révisions par article
        $this->pruneOldRevisions($post->getId());

        return $revisionId;
    }

    /**
     * Récupère une révision par son ID.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $revisionId): ?array
    {
        return $this->storage->find($revisionId);
    }

    /**
     * Restaure un article depuis une révision.
     *
     * @param string $revisionId ID de la révision
     * @return Post|null L'article restauré ou null si la révision n'existe pas
     */
    public function restore(string $revisionId): ?Post
    {
        $revision = $this->find($revisionId);

        if ($revision === null || !isset($revision['data'])) {
            return null;
        }

        return Post::fromArray($revision['data']);
    }

    /**
     * Récupère toutes les révisions d'un article.
     *
     * @return array<int, array{id: string, postId: string, postTitle: string, comment: string, author: string, createdAt: string}>
     */
    public function getRevisions(string $postId): array
    {
        $all = $this->storage->all();

        $revisions = array_filter($all, fn($r) => ($r['postId'] ?? '') === $postId);

        // Trier par date décroissante
        usort($revisions, fn($a, $b) => ($b['createdAt'] ?? '') <=> ($a['createdAt'] ?? ''));

        // Retourner les métadonnées seulement (sans le contenu complet)
        return array_map(fn($r) => [
            'id' => $r['id'],
            'postId' => $r['postId'],
            'postTitle' => $r['postTitle'] ?? '',
            'comment' => $r['comment'] ?? '',
            'author' => $r['author'] ?? '',
            'createdAt' => $r['createdAt'] ?? '',
        ], array_values($revisions));
    }

    /**
     * Compte le nombre de révisions d'un article.
     */
    public function countRevisions(string $postId): int
    {
        return count($this->getRevisions($postId));
    }

    /**
     * Supprime une révision.
     */
    public function delete(string $revisionId): void
    {
        $this->storage->delete($revisionId);
    }

    /**
     * Supprime toutes les révisions d'un article.
     */
    public function deleteAllForPost(string $postId): int
    {
        $revisions = $this->getRevisions($postId);
        $count = 0;

        foreach ($revisions as $revision) {
            $this->delete($revision['id']);
            $count++;
        }

        return $count;
    }

    /**
     * Compare deux révisions et retourne les différences.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function diff(string $revisionId1, string $revisionId2): array
    {
        $rev1 = $this->find($revisionId1);
        $rev2 = $this->find($revisionId2);

        if ($rev1 === null || $rev2 === null) {
            return [];
        }

        $data1 = $rev1['data'] ?? [];
        $data2 = $rev2['data'] ?? [];

        $diff = [];
        $fieldsToCompare = ['title', 'content', 'excerpt', 'author', 'tags', 'categoryId', 'status'];

        foreach ($fieldsToCompare as $field) {
            $old = $data1[$field] ?? null;
            $new = $data2[$field] ?? null;

            if ($old !== $new) {
                $diff[$field] = ['old' => $old, 'new' => $new];
            }
        }

        return $diff;
    }

    /**
     * Supprime les anciennes révisions si le quota est dépassé.
     */
    private function pruneOldRevisions(string $postId): void
    {
        $revisions = $this->getRevisions($postId);

        if (count($revisions) <= self::MAX_REVISIONS_PER_POST) {
            return;
        }

        // Supprimer les révisions les plus anciennes
        $toDelete = array_slice($revisions, self::MAX_REVISIONS_PER_POST);
        foreach ($toDelete as $revision) {
            $this->delete($revision['id']);
        }
    }

    /**
     * Génère un ID unique pour une révision.
     */
    private function generateRevisionId(): string
    {
        return sprintf(
            'rev-%s-%s',
            date('Ymd-His'),
            bin2hex(random_bytes(4))
        );
    }
}
