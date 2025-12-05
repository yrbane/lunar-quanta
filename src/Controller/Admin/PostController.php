<?php

declare(strict_types=1);

namespace Lunar\Controller\Admin;

use Lunar\Attribute\Route;
use Lunar\Entity\Post;
use Lunar\Service\Blog\BlogException;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\StaticSite\StaticGenerator;
use Lunar\Service\Storage\FileStorage;

/**
 * Contrôleur d'administration des articles de blog.
 *
 * Gère le CRUD complet des articles :
 * - Liste avec filtres et statistiques
 * - Création et édition avec prévisualisation Markdown
 * - Publication et génération HTML statique
 * - Archivage et suppression
 */
#[Route('/admin/blog')]
class PostController extends BaseController
{
    private PostService $postService;
    private StaticGenerator $staticGenerator;
    private MarkdownParser $markdownParser;

    public function __construct()
    {
        parent::__construct();

        $basePath = dirname(__DIR__, 3);

        $this->postService = new PostService(
            new FileStorage($basePath . '/data/blog/posts')
        );

        $this->markdownParser = new MarkdownParser();

        $this->staticGenerator = new StaticGenerator(
            $this->postService,
            $this->markdownParser,
            $basePath . '/public/blog',
            $basePath . '/template/blog'
        );
    }

    /**
     * Liste des articles avec statistiques.
     */
    #[Route('', methods: ['GET'], name: 'admin.blog.index')]
    public function index(Request $request): Response
    {
        $filter = $request->getQueryParams()['filter'] ?? 'all';

        $posts = match ($filter) {
            'published' => $this->postService->findPublished(),
            'drafts' => $this->postService->findDrafts(),
            default => $this->postService->all(),
        };

        // Trier par date de mise à jour décroissante
        usort($posts, fn($a, $b) => $b->getUpdatedAt() <=> $a->getUpdatedAt());

        $stats = [
            'total' => $this->postService->count(),
            'published' => count($this->postService->findPublished()),
            'drafts' => count($this->postService->findDrafts()),
        ];

        return $this->renderAdmin('admin/blog/index', [
            'title' => 'Gestion des Articles',
            'posts' => $posts,
            'stats' => $stats,
            'filter' => $filter,
        ]);
    }

    /**
     * Formulaire de création d'article.
     */
    #[Route('/create', methods: ['GET', 'POST'], name: 'admin.blog.create')]
    public function create(Request $request): Response
    {
        $errors = [];
        $data = [
            'title' => '',
            'content' => '',
            'excerpt' => '',
            'author' => '',
        ];

        if ($request->getMethod() === 'POST') {
            $data = $this->getPostData($request);
            $errors = $this->validatePost($data);

            if (empty($errors)) {
                $post = $this->postService->create($data['title'], $data['content']);
                $post->setExcerpt($data['excerpt']);
                $post->setAuthor($data['author']);
                $this->postService->update($post);

                return $this->redirect('/admin/blog/' . $post->getId() . '/edit?created=1');
            }
        }

        return $this->renderAdmin('admin/blog/form', [
            'title' => 'Nouvel Article',
            'post' => null,
            'data' => $data,
            'errors' => $errors,
            'isNew' => true,
        ]);
    }

    /**
     * Formulaire d'édition d'article.
     */
    #[Route('/{id}/edit', methods: ['GET', 'POST'], name: 'admin.blog.edit')]
    public function edit(Request $request, string $id): Response
    {
        $post = $this->postService->find($id);

        if ($post === null) {
            return $this->notFound('Article non trouvé');
        }

        $errors = [];
        $data = [
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'excerpt' => $post->getExcerpt(),
            'author' => $post->getAuthor(),
        ];

        $flash = null;
        if (isset($request->getQueryParams()['created'])) {
            $flash = ['type' => 'success', 'message' => 'Article créé avec succès !'];
        }
        if (isset($request->getQueryParams()['published'])) {
            $flash = ['type' => 'success', 'message' => 'Article publié et HTML généré !'];
        }

        if ($request->getMethod() === 'POST') {
            $data = $this->getPostData($request);
            $errors = $this->validatePost($data);

            if (empty($errors)) {
                $post->setTitle($data['title']);
                $post->setContent($data['content']);
                $post->setExcerpt($data['excerpt']);
                $post->setAuthor($data['author']);
                $this->postService->update($post);

                $flash = ['type' => 'success', 'message' => 'Article mis à jour !'];
            }
        }

        return $this->renderAdmin('admin/blog/form', [
            'title' => 'Modifier : ' . $post->getTitle(),
            'post' => $post,
            'data' => $data,
            'errors' => $errors,
            'isNew' => false,
            'flash' => $flash,
        ]);
    }

    /**
     * Publie un article et génère le HTML statique.
     */
    #[Route('/{id}/publish', methods: ['POST'], name: 'admin.blog.publish')]
    public function publish(Request $request, string $id): Response
    {
        try {
            $post = $this->postService->publish($id);
            $this->staticGenerator->generatePost($post);
            $this->staticGenerator->generateIndex();

            return $this->redirect('/admin/blog/' . $id . '/edit?published=1');
        } catch (BlogException $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Dépublie un article.
     */
    #[Route('/{id}/unpublish', methods: ['POST'], name: 'admin.blog.unpublish')]
    public function unpublish(Request $request, string $id): Response
    {
        try {
            $this->postService->unpublish($id);
            $this->staticGenerator->regenerate();

            return $this->redirect('/admin/blog/' . $id . '/edit');
        } catch (BlogException $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Archive un article.
     */
    #[Route('/{id}/archive', methods: ['POST'], name: 'admin.blog.archive')]
    public function archive(Request $request, string $id): Response
    {
        try {
            $this->postService->archive($id);
            $this->staticGenerator->regenerate();

            return $this->redirect('/admin/blog');
        } catch (BlogException $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Supprime un article.
     */
    #[Route('/{id}/delete', methods: ['POST'], name: 'admin.blog.delete')]
    public function delete(Request $request, string $id): Response
    {
        $this->postService->delete($id);
        $this->staticGenerator->regenerate();

        return $this->redirect('/admin/blog');
    }

    /**
     * Prévisualisation Markdown en AJAX.
     */
    #[Route('/preview', methods: ['POST'], name: 'admin.blog.preview')]
    public function preview(Request $request): Response
    {
        $content = $request->getParsedBody()['content'] ?? '';
        $html = $this->markdownParser->parse($content);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Régénère tout le site statique.
     */
    #[Route('/regenerate', methods: ['POST'], name: 'admin.blog.regenerate')]
    public function regenerate(Request $request): Response
    {
        $result = $this->staticGenerator->regenerate();

        return $this->redirect('/admin/blog?regenerated=' . $result['posts']);
    }

    /**
     * Récupère les données POST.
     */
    private function getPostData(Request $request): array
    {
        $body = $request->getParsedBody();

        return [
            'title' => trim($body['title'] ?? ''),
            'content' => $body['content'] ?? '',
            'excerpt' => trim($body['excerpt'] ?? ''),
            'author' => trim($body['author'] ?? ''),
        ];
    }

    /**
     * Valide les données d'un article.
     */
    private function validatePost(array $data): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors['title'] = 'Le titre est obligatoire';
        } elseif (mb_strlen($data['title']) < 3) {
            $errors['title'] = 'Le titre doit faire au moins 3 caractères';
        }

        if (empty($data['content'])) {
            $errors['content'] = 'Le contenu est obligatoire';
        }

        return $errors;
    }

    /**
     * Rendu avec le layout admin.
     */
    private function renderAdmin(string $template, array $data = []): Response
    {
        $html = $this->render($template, $data);
        return new Response($html);
    }

    /**
     * Redirection.
     */
    private function redirect(string $url): Response
    {
        return new Response('', 302, ['Location' => $url]);
    }

    /**
     * Page 404.
     */
    private function notFound(string $message): Response
    {
        $html = $this->render('error', [
            'title' => 'Non trouvé',
            'content' => $message,
        ]);
        return new Response($html, 404);
    }
}
