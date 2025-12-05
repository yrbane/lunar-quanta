<?php

declare(strict_types=1);

namespace Lunar\Entity;

use Lunar\Service\Blog\SlugGenerator;

/**
 * Entité représentant un article de blog.
 *
 * Un article a un cycle de vie :
 * - DRAFT : brouillon, non visible
 * - PUBLISHED : publié, HTML statique généré
 * - ARCHIVED : archivé, retiré mais conservé
 *
 * @example
 * ```php
 * $post = new Post('Mon Article', '# Contenu Markdown');
 * $post->setExcerpt('Description courte');
 * $post->addTag('php');
 * $post->publish();
 *
 * echo $post->getUrl(); // /blog/posts/mon-article.html
 * ```
 */
final class Post
{
    private string $id;
    private string $title;
    private string $slug;
    private string $content;
    private string $excerpt = '';
    private string $author = '';
    private ?string $categoryId = null;
    private ?string $featuredImage = null;
    private PostStatus $status;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $publishedAt = null;

    /** @var string[] */
    private array $tags = [];

    public function __construct(string $title, string $content)
    {
        $this->id = $this->generateId();
        $this->title = $title;
        $this->content = $content;
        $this->slug = SlugGenerator::slugify($title);
        $this->status = PostStatus::DRAFT;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        $this->touch();
        return $this;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function setExcerpt(string $excerpt): self
    {
        $this->excerpt = $excerpt;
        $this->touch();
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        $this->touch();
        return $this;
    }

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function setCategoryId(?string $categoryId): self
    {
        $this->categoryId = $categoryId;
        $this->touch();
        return $this;
    }

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?string $featuredImage): self
    {
        $this->featuredImage = $featuredImage;
        $this->touch();
        return $this;
    }

    public function getStatus(): PostStatus
    {
        return $this->status;
    }

    public function setStatus(PostStatus $status): self
    {
        $this->status = $status;
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

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function addTag(string $tagId): self
    {
        if (!in_array($tagId, $this->tags, true)) {
            $this->tags[] = $tagId;
            $this->touch();
        }
        return $this;
    }

    public function removeTag(string $tagId): self
    {
        $this->tags = array_values(array_filter(
            $this->tags,
            fn($t) => $t !== $tagId
        ));
        $this->touch();
        return $this;
    }

    public function hasTag(string $tagId): bool
    {
        return in_array($tagId, $this->tags, true);
    }

    /**
     * Publie l'article.
     */
    public function publish(): self
    {
        $this->status = PostStatus::PUBLISHED;
        if ($this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        $this->touch();
        return $this;
    }

    /**
     * Dépublie l'article (retour en brouillon).
     */
    public function unpublish(): self
    {
        $this->status = PostStatus::DRAFT;
        $this->touch();
        return $this;
    }

    /**
     * Archive l'article.
     */
    public function archive(): self
    {
        $this->status = PostStatus::ARCHIVED;
        $this->touch();
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === PostStatus::DRAFT;
    }

    public function isArchived(): bool
    {
        return $this->status === PostStatus::ARCHIVED;
    }

    /**
     * Retourne l'URL de l'article.
     */
    public function getUrl(): string
    {
        return '/blog/posts/' . $this->slug . '.html';
    }

    /**
     * Compte le nombre de mots dans le contenu.
     */
    public function getWordCount(): int
    {
        $text = strip_tags($this->content);
        $text = preg_replace('/\s+/', ' ', $text);
        return str_word_count($text);
    }

    /**
     * Estime le temps de lecture en minutes.
     */
    public function getReadingTime(): int
    {
        $wordsPerMinute = 200;
        return max(1, (int) ceil($this->getWordCount() / $wordsPerMinute));
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
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'author' => $this->author,
            'categoryId' => $this->categoryId,
            'featuredImage' => $this->featuredImage,
            'status' => $this->status->value,
            'tags' => $this->tags,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
            'publishedAt' => $this->publishedAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $post = new self($data['title'], $data['content']);

        $reflection = new \ReflectionClass($post);

        $idProp = $reflection->getProperty('id');
        $idProp->setValue($post, $data['id']);

        if (isset($data['slug'])) {
            $post->slug = $data['slug'];
        }
        if (isset($data['excerpt'])) {
            $post->excerpt = $data['excerpt'];
        }
        if (isset($data['author'])) {
            $post->author = $data['author'];
        }
        if (isset($data['categoryId'])) {
            $post->categoryId = $data['categoryId'];
        }
        if (isset($data['featuredImage'])) {
            $post->featuredImage = $data['featuredImage'];
        }
        if (isset($data['status'])) {
            $post->status = PostStatus::from($data['status']);
        }
        if (isset($data['tags'])) {
            $post->tags = $data['tags'];
        }
        if (isset($data['createdAt'])) {
            $createdAtProp = $reflection->getProperty('createdAt');
            $createdAtProp->setValue($post, new \DateTimeImmutable($data['createdAt']));
        }
        if (isset($data['updatedAt'])) {
            $updatedAtProp = $reflection->getProperty('updatedAt');
            $updatedAtProp->setValue($post, new \DateTimeImmutable($data['updatedAt']));
        }
        if (isset($data['publishedAt']) && $data['publishedAt'] !== null) {
            $post->publishedAt = new \DateTimeImmutable($data['publishedAt']);
        }

        return $post;
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
