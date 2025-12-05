<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

use Lunar\Entity\Category;
use Lunar\Service\Storage\FileStorage;

/**
 * Service de gestion des catégories.
 *
 * Fournit les opérations CRUD pour les catégories d'articles.
 *
 * @example
 * ```php
 * $service = new CategoryService($storage);
 *
 * // Créer une catégorie
 * $category = $service->create('Développement Web');
 * $category->setDescription('Articles sur le dev web');
 * $service->update($category);
 *
 * // Récupérer toutes les catégories
 * $categories = $service->all();
 *
 * // Trouver par slug
 * $category = $service->findBySlug('developpement-web');
 * ```
 */
final class CategoryService
{
    public function __construct(
        private readonly FileStorage $storage
    ) {
    }

    /**
     * Crée une nouvelle catégorie.
     */
    public function create(string $name): Category
    {
        $category = new Category($name);

        // Générer un slug unique
        $baseSlug = $category->getSlug();
        $slug = $baseSlug;
        $counter = 1;

        while ($this->findBySlug($slug) !== null) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        if ($slug !== $baseSlug) {
            $category->setSlug($slug);
        }

        $this->storage->save($category->getId(), $category->toArray());

        return $category;
    }

    /**
     * Met à jour une catégorie existante.
     */
    public function update(Category $category): Category
    {
        $this->storage->save($category->getId(), $category->toArray());
        return $category;
    }

    /**
     * Supprime une catégorie.
     */
    public function delete(string $id): bool
    {
        if (!$this->storage->exists($id)) {
            return false;
        }
        $this->storage->delete($id);
        return true;
    }

    /**
     * Récupère une catégorie par son ID.
     */
    public function find(string $id): ?Category
    {
        $data = $this->storage->find($id);

        if ($data === null) {
            return null;
        }

        return Category::fromArray($data);
    }

    /**
     * Récupère une catégorie par son slug.
     */
    public function findBySlug(string $slug): ?Category
    {
        $all = $this->storage->all();

        foreach ($all as $data) {
            if (isset($data['slug']) && $data['slug'] === $slug) {
                return Category::fromArray($data);
            }
        }

        return null;
    }

    /**
     * Récupère toutes les catégories.
     *
     * @return Category[]
     */
    public function all(): array
    {
        $all = $this->storage->all();

        $categories = array_map(
            fn(array $data) => Category::fromArray($data),
            $all
        );

        // Trier par sortOrder puis par nom
        usort($categories, function (Category $a, Category $b) {
            if ($a->getSortOrder() !== $b->getSortOrder()) {
                return $a->getSortOrder() <=> $b->getSortOrder();
            }
            return $a->getName() <=> $b->getName();
        });

        return $categories;
    }

    /**
     * Compte le nombre de catégories.
     */
    public function count(): int
    {
        return count($this->storage->all());
    }

    /**
     * Vérifie si une catégorie existe.
     */
    public function exists(string $id): bool
    {
        return $this->find($id) !== null;
    }
}
