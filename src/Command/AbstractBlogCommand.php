<?php
/**
 * Lunar Quanta Framework - Classe de base pour les commandes blog.
 *
 * @package    Lunar\Command
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\CommandInterface;
use Lunar\Entity\Post;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Classe de base pour les commandes blog CLI.
 *
 * Centralise la logique commune à toutes les commandes blog :
 * - Instanciation du PostService avec le bon chemin de stockage
 * - Résolution d'un article par ID ou slug (lookup en cascade)
 *
 * Ce pattern "Template Method" élimine la duplication de code
 * entre BlogPublishCommand, BlogUnpublishCommand, BlogArchiveCommand
 * et BlogDeleteCommand qui partagent la même initialisation.
 *
 * @example
 * ```php
 * class BlogPublishCommand extends AbstractBlogCommand
 * {
 *     public function execute(array $args): int
 *     {
 *         $postService = $this->createPostService();
 *         $post = $this->findPostOrFail($postService, $args[0]);
 *         // ...
 *     }
 * }
 * ```
 *
 * @see docs/blog-system.md Pour la documentation complète du système blog
 */
abstract class AbstractBlogCommand implements CommandInterface
{
    /**
     * Crée une instance de PostService avec le stockage par défaut.
     *
     * Méthode protected pour permettre la surcharge dans les tests
     * (injection d'un storage temporaire via sous-classe anonyme).
     *
     * @return PostService Le service configuré avec le chemin data/blog/posts
     */
    protected function createPostService(): PostService
    {
        $basePath = dirname(__DIR__, 2);
        return new PostService(new FileStorage($basePath . '/data/blog/posts'));
    }

    /**
     * Recherche un article par ID, puis par slug en fallback.
     *
     * Ce lookup en cascade permet à l'utilisateur de passer indifféremment
     * un UUID ou un slug comme argument CLI, simplifiant l'usage.
     *
     * @param PostService $postService Le service d'articles
     * @param string      $identifier  UUID ou slug de l'article
     *
     * @return Post|null L'article trouvé, ou null si inexistant
     */
    protected function findPostOrFail(PostService $postService, string $identifier): ?Post
    {
        $post = $postService->find($identifier);
        if ($post === null) {
            $post = $postService->findBySlug($identifier);
        }
        return $post;
    }
}
