<?php

declare(strict_types=1);

namespace Lunar\Entity;

use Lunar\Service\Blog\SlugGenerator;

/**
 * Entité représentant une catégorie d'articles.
 *
 * Les catégories permettent de classer les articles par thème.
 * Contrairement aux tags, un article appartient à une seule catégorie.
 *
 * @example
 * ```php
 * $category = new Category('Développement Web');
 * $category->setDescription('Articles sur le développement web');
 * $category->setColor('#3b82f6');
 *
 * echo $category->getUrl(); // /blog/categories/developpement-web.html
 * ```
 */
final class Category
{
    private string $id;
    private string $name;
    private string $slug;
    private string $description = '';
    private string $color = '#6b7280';
    private int $sortOrder = 0;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $name)
    {
        $this->id = $this->generateId();
        $this->name = $name;
        $this->slug = SlugGenerator::slugify($name);
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->touch();
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        $this->touch();
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        $this->touch();
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        $this->touch();
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        $this->touch();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Retourne l'URL de la page catégorie.
     */
    public function getUrl(): string
    {
        return '/blog/categories/' . $this->slug . '.html';
    }

    /**
     * Met à jour le timestamp de modification.
     */
    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'sortOrder' => $this->sortOrder,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var string $name */
        $name = $data['name'];
        $category = new self($name);

        $reflection = new \ReflectionClass($category);

        /** @var string $id */
        $id = $data['id'];
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($category, $id);

        if (isset($data['slug']) && is_string($data['slug'])) {
            $category->slug = $data['slug'];
        }
        if (isset($data['description']) && is_string($data['description'])) {
            $category->description = $data['description'];
        }
        if (isset($data['color']) && is_string($data['color'])) {
            $category->color = $data['color'];
        }
        if (isset($data['sortOrder']) && is_int($data['sortOrder'])) {
            $category->sortOrder = $data['sortOrder'];
        }
        if (isset($data['createdAt']) && is_string($data['createdAt'])) {
            $createdAtProp = $reflection->getProperty('createdAt');
            $createdAtProp->setValue($category, new \DateTimeImmutable($data['createdAt']));
        }
        if (isset($data['updatedAt']) && is_string($data['updatedAt'])) {
            $updatedAtProp = $reflection->getProperty('updatedAt');
            $updatedAtProp->setValue($category, new \DateTimeImmutable($data['updatedAt']));
        }

        return $category;
    }

    private function generateId(): string
    {
        return sprintf(
            '%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2))
        );
    }
}
