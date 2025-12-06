<?php

declare(strict_types=1);

namespace Lunar\Controller\Admin;

use Lunar\Attribute\Route;
use Lunar\Entity\Tag;
use Lunar\Service\Blog\TagService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Storage\FileStorage;

/**
 * Contrôleur d'administration des tags.
 *
 * Gère le CRUD complet des tags :
 * - Liste avec comptage d'articles
 * - Création et édition
 * - Suppression avec vérification des dépendances
 */
#[Route('/admin/tags')]
class TagController extends BaseController
{
    private TagService $tagService;
    private PostService $postService;

    public function __construct()
    {
        parent::__construct();

        $basePath = dirname(__DIR__, 3);

        $this->tagService = new TagService(
            new FileStorage($basePath . '/data/blog/tags')
        );

        $this->postService = new PostService(
            new FileStorage($basePath . '/data/blog/posts')
        );
    }

    /**
     * Liste des tags avec statistiques.
     */
    #[Route('', methods: ['GET'], name: 'admin.tags.index')]
    public function index(Request $request): Response
    {
        $tags = $this->tagService->all();

        // Calculer le nombre d'articles par tag
        $posts = $this->postService->all();
        $tagCounts = [];
        foreach ($posts as $post) {
            foreach ($post->getTags() as $tagName) {
                $tagCounts[$tagName] = ($tagCounts[$tagName] ?? 0) + 1;
            }
        }

        // Trier par nom alphabétique
        usort($tags, fn($a, $b) => strcasecmp($a->getName(), $b->getName()));

        return $this->renderAdmin('admin/tags/index', [
            'title' => 'Gestion des Tags',
            'tags' => $tags,
            'tagCounts' => $tagCounts,
            'totalTags' => count($tags),
            'totalPosts' => count($posts),
        ]);
    }

    /**
     * Formulaire de création de tag.
     */
    #[Route('/create', methods: ['GET', 'POST'], name: 'admin.tags.create')]
    public function create(Request $request): Response
    {
        $errors = [];
        $data = ['name' => '', 'description' => '', 'color' => '#6366f1'];

        if ($request->getMethod() === 'POST') {
            $data = $this->getTagData($request);
            $errors = $this->validateTag($data);

            if (empty($errors)) {
                // Vérifier si le tag existe déjà
                $existingTag = $this->tagService->findBySlug($this->slugify($data['name']));
                if ($existingTag !== null) {
                    $errors['name'] = 'Un tag avec ce nom existe déjà.';
                } else {
                    $tag = $this->tagService->create($data['name']);

                    if (!empty($data['description'])) {
                        $tag->setDescription($data['description']);
                    }
                    if (!empty($data['color'])) {
                        $tag->setColor($data['color']);
                    }

                    $this->tagService->update($tag);

                    return $this->redirect('/admin/tags?created=1');
                }
            }
        }

        return $this->renderAdmin('admin/tags/form', [
            'title' => 'Créer un Tag',
            'tag' => null,
            'data' => $data,
            'errors' => $errors,
            'isEdit' => false,
        ]);
    }

    /**
     * Formulaire d'édition de tag.
     */
    #[Route('/{id}/edit', methods: ['GET', 'POST'], name: 'admin.tags.edit')]
    public function edit(Request $request, string $id): Response
    {
        $tag = $this->tagService->find($id);

        if ($tag === null) {
            return $this->notFound('Tag non trouvé');
        }

        $errors = [];
        $data = [
            'name' => $tag->getName(),
            'description' => $tag->getDescription(),
            'color' => $tag->getColor(),
        ];

        if ($request->getMethod() === 'POST') {
            $data = $this->getTagData($request);
            $errors = $this->validateTag($data);

            if (empty($errors)) {
                // Vérifier si un autre tag a le même slug
                $newSlug = $this->slugify($data['name']);
                $existingTag = $this->tagService->findBySlug($newSlug);
                if ($existingTag !== null && $existingTag->getId() !== $id) {
                    $errors['name'] = 'Un autre tag avec ce nom existe déjà.';
                } else {
                    $tag->setName($data['name']);
                    $tag->setSlug($newSlug);
                    $tag->setDescription($data['description']);
                    $tag->setColor($data['color']);

                    $this->tagService->update($tag);

                    return $this->redirect('/admin/tags?updated=1');
                }
            }
        }

        return $this->renderAdmin('admin/tags/form', [
            'title' => 'Modifier le Tag',
            'tag' => $tag,
            'data' => $data,
            'errors' => $errors,
            'isEdit' => true,
        ]);
    }

    /**
     * Suppression d'un tag.
     */
    #[Route('/{id}/delete', methods: ['POST'], name: 'admin.tags.delete')]
    public function delete(Request $request, string $id): Response
    {
        $tag = $this->tagService->find($id);

        if ($tag === null) {
            return $this->notFound('Tag non trouvé');
        }

        // Compter les articles utilisant ce tag
        $posts = $this->postService->all();
        $postsWithTag = array_filter($posts, fn($post) => in_array($tag->getName(), $post->getTags(), true));
        $postCount = count($postsWithTag);

        // Supprimer le tag des articles si demandé
        $removeFromPosts = $request->getParsedBody()['remove_from_posts'] ?? false;
        if ($removeFromPosts && $postCount > 0) {
            foreach ($postsWithTag as $post) {
                $post->removeTag($tag->getName());
                $this->postService->update($post);
            }
        }

        $this->tagService->delete($id);

        return $this->redirect('/admin/tags?deleted=1');
    }

    /**
     * API: Récupère les données d'un tag.
     */
    #[Route('/{id}/json', methods: ['GET'], name: 'admin.tags.json')]
    public function getJson(Request $request, string $id): Response
    {
        $tag = $this->tagService->find($id);

        if ($tag === null) {
            return $this->json(['error' => 'Tag non trouvé'], 404);
        }

        // Compter les articles
        $posts = $this->postService->all();
        $postCount = count(array_filter($posts, fn($post) => in_array($tag->getName(), $post->getTags(), true)));

        return $this->json([
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'slug' => $tag->getSlug(),
            'description' => $tag->getDescription(),
            'color' => $tag->getColor(),
            'postCount' => $postCount,
        ]);
    }

    /**
     * Récupère les données du formulaire.
     *
     * @return array<string, string>
     */
    private function getTagData(Request $request): array
    {
        $body = $request->getParsedBody();

        return [
            'name' => trim($body['name'] ?? ''),
            'description' => trim($body['description'] ?? ''),
            'color' => trim($body['color'] ?? '#6366f1'),
        ];
    }

    /**
     * Valide les données du tag.
     *
     * @return array<string, string>
     */
    private function validateTag(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est requis.';
        } elseif (mb_strlen($data['name']) < 2) {
            $errors['name'] = 'Le nom doit contenir au moins 2 caractères.';
        } elseif (mb_strlen($data['name']) > 50) {
            $errors['name'] = 'Le nom ne peut pas dépasser 50 caractères.';
        }

        if (!empty($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            $errors['color'] = 'La couleur doit être au format hexadécimal (#RRGGBB).';
        }

        return $errors;
    }

    /**
     * Génère un slug à partir d'un texte.
     */
    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
