<?php

declare(strict_types=1);

namespace Lunar\Entity;

use Lunar\Service\Blog\SlugGenerator;

/**
 * Entité représentant un tag (mot-clé).
 *
 * Les tags sont des mots-clés simples sans hiérarchie,
 * contrairement aux catégories. Ils permettent un classement
 * transversal des articles.
 *
 * @example
 * ```php
 * $tag = new Tag('PHP');
 * echo $tag->getSlug(); // "php"
 *
 * // Persistance
 * $data = $tag->toArray();
 * $restored = Tag::fromArray($data);
 * ```
 */
final class Tag
{
    private string $id;
    private string $name;
    private string $slug;
    private \DateTimeImmutable $createdAt;
    private int $articleCount = 0;

    public function __construct(string $name)
    {
        $this->id = $this->generateId();
        $this->name = $name;
        $this->slug = SlugGenerator::slugify($name);
        $this->createdAt = new \DateTimeImmutable();
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
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getArticleCount(): int
    {
        return $this->articleCount;
    }

    public function setArticleCount(int $count): self
    {
        $this->articleCount = max(0, $count);
        return $this;
    }

    public function incrementArticleCount(): self
    {
        $this->articleCount++;
        return $this;
    }

    public function decrementArticleCount(): self
    {
        $this->articleCount = max(0, $this->articleCount - 1);
        return $this;
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
            'articleCount' => $this->articleCount,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $tag = new self($data['name']);

        // Restaurer les propriétés
        $reflection = new \ReflectionClass($tag);

        $idProp = $reflection->getProperty('id');
        $idProp->setValue($tag, $data['id']);

        if (isset($data['slug'])) {
            $tag->slug = $data['slug'];
        }

        if (isset($data['articleCount'])) {
            $tag->articleCount = $data['articleCount'];
        }

        if (isset($data['createdAt'])) {
            $createdAtProp = $reflection->getProperty('createdAt');
            $createdAtProp->setValue($tag, new \DateTimeImmutable($data['createdAt']));
        }

        return $tag;
    }

    private function generateId(): string
    {
        return sprintf(
            '%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(4))
        );
    }
}
