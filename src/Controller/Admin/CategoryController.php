<?php

declare(strict_types=1);

namespace Lunar\Controller\Admin;

use Lunar\Attribute\Route;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Storage\FileStorage;

/**
 * Contrôleur d'administration des catégories de blog.
 *
 * Gère le CRUD des catégories :
 * - Liste ordonnée par sortOrder
 * - Création et édition
 * - Suppression
 */
#[Route('/admin/categories')]
class CategoryController extends BaseController
{
    private CategoryService $categoryService;

    public function __construct()
    {
        parent::__construct();

        $basePath = dirname(__DIR__, 3);

        $this->categoryService = new CategoryService(
            new FileStorage($basePath . '/data/blog/categories')
        );
    }

    /**
     * Liste des catégories.
     */
    #[Route('', methods: ['GET'], name: 'admin.categories.index')]
    public function index(Request $request): Response
    {
        $categories = $this->categoryService->all();

        $flash = null;
        if (isset($request->getQueryParams()['deleted'])) {
            $flash = ['type' => 'success', 'message' => 'Catégorie supprimée !'];
        }

        return $this->renderAdmin('admin/categories/index', [
            'title' => 'Gestion des Catégories',
            'categories' => $categories,
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
        $this->categoryService->delete($id);

        return $this->redirect('/admin/categories?deleted=1');
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
}
