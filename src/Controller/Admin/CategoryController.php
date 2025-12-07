<?php

declare(strict_types=1);

namespace Lunar\Controller\Admin;

use Lunar\Attribute\Route;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Storage\FileStorage;

/**
 * Contrôleur d'administration des catégories de blog.
 *
 * Gère le CRUD des catégories :
 * - Liste ordonnée par sortOrder avec comptage d'articles
 * - Création et édition avec sélecteur d'icône
 * - Réordonnancement par drag & drop
 * - Suppression avec gestion des dépendances
 */
#[Route('/admin/categories')]
class CategoryController extends BaseController
{
    private CategoryService $categoryService;
    private PostService $postService;

    public function __construct()
    {
        parent::__construct();

        $basePath = dirname(__DIR__, 3);

        $this->categoryService = new CategoryService(
            new FileStorage($basePath . '/data/blog/categories')
        );

        $this->postService = new PostService(
            new FileStorage($basePath . '/data/blog/posts')
        );
    }

    /**
     * Liste des catégories avec statistiques.
     */
    #[Route('', methods: ['GET'], name: 'admin.categories.index')]
    public function index(Request $request): Response
    {
        $categories = $this->categoryService->all();

        // Calculer le nombre d'articles par catégorie
        $posts = $this->postService->all();
        $categoryCounts = [];
        foreach ($posts as $post) {
            $catId = $post->getCategoryId();
            if ($catId !== null) {
                $categoryCounts[$catId] = ($categoryCounts[$catId] ?? 0) + 1;
            }
        }

        $flash = null;
        if (isset($request->getQueryParams()['deleted'])) {
            $flash = ['type' => 'success', 'message' => 'Catégorie supprimée !'];
        }
        if (isset($request->getQueryParams()['created'])) {
            $flash = ['type' => 'success', 'message' => 'Catégorie créée avec succès !'];
        }
        if (isset($request->getQueryParams()['updated'])) {
            $flash = ['type' => 'success', 'message' => 'Catégorie mise à jour !'];
        }

        return $this->renderAdmin('admin/categories/index', [
            'title' => 'Gestion des Catégories',
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'totalCategories' => count($categories),
            'totalPosts' => count($posts),
            'flash' => $flash,
        ]);
    }

    /**
     * Formulaire de création de catégorie.
     */
    #[Route('/create', methods: ['GET', 'POST'], name: 'admin.categories.create')]
    public function create(Request $request): Response
    {
        $errors = [];
        $data = [
            'name' => '',
            'description' => '',
            'color' => '#6b7280',
            'sortOrder' => 0,
        ];

        if ($request->getMethod() === 'POST') {
            $data = $this->getCategoryData($request);
            $errors = $this->validateCategory($data);

            if (empty($errors)) {
                $category = $this->categoryService->create($data['name']);
                $category->setDescription($data['description']);
                $category->setColor($data['color']);
                $category->setSortOrder((int) $data['sortOrder']);
                $this->categoryService->update($category);

                return $this->redirect('/admin/categories/' . $category->getId() . '/edit?created=1');
            }
        }

        return $this->renderAdmin('admin/categories/form', [
            'title' => 'Nouvelle Catégorie',
            'category' => null,
            'data' => $data,
            'errors' => $errors,
            'isNew' => true,
        ]);
    }

    /**
     * Formulaire d'édition de catégorie.
     */
    #[Route('/{id}/edit', methods: ['GET', 'POST'], name: 'admin.categories.edit')]
    public function edit(Request $request, string $id): Response
    {
        $category = $this->categoryService->find($id);

        if ($category === null) {
            return $this->notFound('Catégorie non trouvée');
        }

        $errors = [];
        $data = [
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'color' => $category->getColor(),
            'sortOrder' => $category->getSortOrder(),
        ];

        $flash = null;
        if (isset($request->getQueryParams()['created'])) {
            $flash = ['type' => 'success', 'message' => 'Catégorie créée avec succès !'];
        }

        if ($request->getMethod() === 'POST') {
            $data = $this->getCategoryData($request);
            $errors = $this->validateCategory($data);

            if (empty($errors)) {
                $category->setName($data['name']);
                $category->setDescription($data['description']);
                $category->setColor($data['color']);
                $category->setSortOrder((int) $data['sortOrder']);
                $this->categoryService->update($category);

                $flash = ['type' => 'success', 'message' => 'Catégorie mise à jour !'];
            }
        }

        return $this->renderAdmin('admin/categories/form', [
            'title' => 'Modifier : ' . $category->getName(),
            'category' => $category,
            'data' => $data,
            'errors' => $errors,
            'isNew' => false,
            'flash' => $flash,
        ]);
    }

    /**
     * Supprime une catégorie.
     */
    #[Route('/{id}/delete', methods: ['POST'], name: 'admin.categories.delete')]
    public function delete(Request $request, string $id): Response
    {
        $category = $this->categoryService->find($id);

        if ($category === null) {
            return $this->notFound('Catégorie non trouvée');
        }

        // Compter les articles utilisant cette catégorie
        $posts = $this->postService->all();
        $postsInCategory = array_filter($posts, fn($post) => $post->getCategoryId() === $id);

        // Gérer les articles de cette catégorie
        $newCategoryId = $request->getParsedBody()['new_category_id'] ?? null;
        foreach ($postsInCategory as $post) {
            if ($newCategoryId && $newCategoryId !== 'none') {
                $post->setCategoryId($newCategoryId);
            } else {
                $post->setCategoryId(null);
            }
            $this->postService->update($post);
        }

        $this->categoryService->delete($id);

        return $this->redirect('/admin/categories?deleted=1');
    }

    /**
     * API: Réorganiser les catégories.
     */
    #[Route('/reorder', methods: ['POST'], name: 'admin.categories.reorder')]
    public function reorder(Request $request): Response
    {
        $body = $request->getParsedBody();
        $order = $body['order'] ?? [];

        if (!is_array($order)) {
            return $this->json(['error' => 'Format invalide'], 400);
        }

        foreach ($order as $index => $categoryId) {
            $category = $this->categoryService->find($categoryId);
            if ($category !== null) {
                $category->setSortOrder($index);
                $this->categoryService->update($category);
            }
        }

        return $this->json(['success' => true]);
    }

    /**
     * API: Récupère les données d'une catégorie.
     */
    #[Route('/{id}/json', methods: ['GET'], name: 'admin.categories.json')]
    public function getJson(Request $request, string $id): Response
    {
        $category = $this->categoryService->find($id);

        if ($category === null) {
            return $this->json(['error' => 'Catégorie non trouvée'], 404);
        }

        // Compter les articles
        $posts = $this->postService->all();
        $postCount = count(array_filter($posts, fn($post) => $post->getCategoryId() === $id));

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'description' => $category->getDescription(),
            'color' => $category->getColor(),
            'icon' => $category->getIcon(),
            'sortOrder' => $category->getSortOrder(),
            'postCount' => $postCount,
        ]);
    }

    /**
     * Récupère les données POST.
     *
     * @return array<string, mixed>
     */
    private function getCategoryData(Request $request): array
    {
        $body = $request->getParsedBody();

        return [
            'name' => trim($body['name'] ?? ''),
            'description' => trim($body['description'] ?? ''),
            'color' => trim($body['color'] ?? '#6b7280'),
            'sortOrder' => (int) ($body['sortOrder'] ?? 0),
        ];
    }

    /**
     * Valide les données d'une catégorie.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateCategory(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est obligatoire';
        } elseif (mb_strlen($data['name']) < 2) {
            $errors['name'] = 'Le nom doit faire au moins 2 caractères';
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            $errors['color'] = 'La couleur doit être au format hexadécimal (#RRGGBB)';
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

    /**
     * Réponse JSON.
     */
    private function json(array $data, int $status = 200): Response
    {
        return new Response(
            json_encode($data, JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}
