<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\CommandInterface;
use Lunar\Entity\Post;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Classe de base pour les commandes blog.
 *
 * Fournit les helpers communs : création du PostService,
 * résolution d'un post par ID ou slug.
 */
abstract class AbstractBlogCommand implements CommandInterface
{
    protected function createPostService(): PostService
    {
        $basePath = dirname(__DIR__, 2);
        return new PostService(new FileStorage($basePath . '/data/blog/posts'));
    }

    protected function findPostOrFail(PostService $postService, string $identifier): ?Post
    {
        $post = $postService->find($identifier);
        if ($post === null) {
            $post = $postService->findBySlug($identifier);
        }
        return $post;
    }
}
