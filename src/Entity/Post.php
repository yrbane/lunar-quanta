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

    /**
     * Sources/references for the article.
     * Each source contains: title, url, domain, description (optional)
     *
     * @var array<int, array{title: string, url: string, domain: string, description?: string}>
     */
    private array $sources = [];

    /**
     * Author bio/description.
     */
    private string $authorBio = '';

    /**
     * Author avatar URL.
     */
    private string $authorAvatar = '';

    /**
     * Whether the article is locked (cannot be modified by AI).
     * Used for Creative Commons imported articles.
     */
    private bool $locked = false;

    /**
     * License information (e.g., "CC BY-ND 4.0").
     */
    private string $license = '';

    /**
     * Original source URL (for imported articles).
     */
    private string $originalUrl = '';

    /**
     * Original source name (e.g., "The Conversation").
     */
    private string $originalSource = '';

    /**
     * Author institution/affiliation.
     */
    private string $authorInstitution = '';

    /**
     * Comments on the article.
     *
     * @var array<int, array{id: string, author: string, content: string, date: string, approved: bool}>
     */
    private array $comments = [];

    /**
     * Rating criteria (1-5 stars each):
     * - relevance: How well the article addresses its topic
     * - depth: How thoroughly the subject is covered
     * - clarity: How clear and well-structured the article is
     * - freshness: How up-to-date the information is
     * - usefulness: How practical/useful for the reader
     *
     * @var array<string, int>
     */
    private array $ratings = [
        'relevance' => 0,
        'depth' => 0,
        'clarity' => 0,
        'freshness' => 0,
        'usefulness' => 0,
    ];

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

    // =========================================================================
    // SOURCES METHODS
    // =========================================================================

    /**
     * Get all sources/references.
     *
     * @return array<int, array{title: string, url: string, domain: string, description?: string}>
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * Add a source/reference.
     *
     * @param string $title Source title
     * @param string $url Source URL
     * @param string|null $description Optional description
     */
    public function addSource(string $title, string $url, ?string $description = null): self
    {
        $domain = parse_url($url, PHP_URL_HOST) ?? 'unknown';
        $domain = preg_replace('/^www\./', '', $domain);

        $source = [
            'title' => $title,
            'url' => $url,
            'domain' => $domain,
        ];

        if ($description !== null) {
            $source['description'] = $description;
        }

        $this->sources[] = $source;
        $this->touch();
        return $this;
    }

    /**
     * Set all sources at once.
     *
     * @param array<int, array{title: string, url: string, domain?: string, description?: string}> $sources
     */
    public function setSources(array $sources): self
    {
        $this->sources = [];
        foreach ($sources as $source) {
            if (isset($source['title'], $source['url'])) {
                $this->addSource(
                    $source['title'],
                    $source['url'],
                    $source['description'] ?? null
                );
            }
        }
        return $this;
    }

    /**
     * Clear all sources.
     */
    public function clearSources(): self
    {
        $this->sources = [];
        $this->touch();
        return $this;
    }

    /**
     * Get unique domains from sources.
     *
     * @return string[]
     */
    public function getSourceDomains(): array
    {
        return array_unique(array_column($this->sources, 'domain'));
    }

    /**
     * Check if article has minimum required sources from different domains.
     */
    public function hasValidSources(int $minSources = 2): bool
    {
        return count($this->getSourceDomains()) >= $minSources;
    }

    // =========================================================================
    // AUTHOR METHODS
    // =========================================================================

    public function getAuthorBio(): string
    {
        return $this->authorBio;
    }

    public function setAuthorBio(string $bio): self
    {
        $this->authorBio = $bio;
        $this->touch();
        return $this;
    }

    public function getAuthorAvatar(): string
    {
        return $this->authorAvatar;
    }

    public function setAuthorAvatar(string $avatarUrl): self
    {
        $this->authorAvatar = $avatarUrl;
        $this->touch();
        return $this;
    }

    public function getAuthorInstitution(): string
    {
        return $this->authorInstitution;
    }

    public function setAuthorInstitution(string $institution): self
    {
        $this->authorInstitution = $institution;
        $this->touch();
        return $this;
    }

    // =========================================================================
    // LOCK & LICENSE METHODS (for Creative Commons articles)
    // =========================================================================

    /**
     * Check if article is locked (cannot be modified by AI).
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Lock the article (prevent AI modifications).
     */
    public function lock(): self
    {
        $this->locked = true;
        $this->touch();
        return $this;
    }

    /**
     * Unlock the article.
     */
    public function unlock(): self
    {
        $this->locked = false;
        $this->touch();
        return $this;
    }

    public function getLicense(): string
    {
        return $this->license;
    }

    public function setLicense(string $license): self
    {
        $this->license = $license;
        $this->touch();
        return $this;
    }

    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    public function setOriginalUrl(string $url): self
    {
        $this->originalUrl = $url;
        $this->touch();
        return $this;
    }

    public function getOriginalSource(): string
    {
        return $this->originalSource;
    }

    public function setOriginalSource(string $source): self
    {
        $this->originalSource = $source;
        $this->touch();
        return $this;
    }

    /**
     * Check if this is an imported article from external source.
     */
    public function isImported(): bool
    {
        return !empty($this->originalUrl);
    }

    // =========================================================================
    // COMMENTS METHODS
    // =========================================================================

    /**
     * Get all comments.
     *
     * @return array<int, array{id: string, author: string, content: string, date: string, approved: bool}>
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    /**
     * Get approved comments only.
     *
     * @return array<int, array{id: string, author: string, content: string, date: string, approved: bool}>
     */
    public function getApprovedComments(): array
    {
        return array_values(array_filter($this->comments, fn($c) => $c['approved']));
    }

    /**
     * Add a comment.
     */
    public function addComment(string $author, string $content, bool $approved = false): self
    {
        $this->comments[] = [
            'id' => bin2hex(random_bytes(8)),
            'author' => $author,
            'content' => $content,
            'date' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'approved' => $approved,
        ];
        $this->touch();
        return $this;
    }

    /**
     * Approve a comment by ID.
     */
    public function approveComment(string $commentId): self
    {
        foreach ($this->comments as $key => $comment) {
            if ($comment['id'] === $commentId) {
                $this->comments[$key]['approved'] = true;
                $this->touch();
                break;
            }
        }
        return $this;
    }

    /**
     * Delete a comment by ID.
     */
    public function deleteComment(string $commentId): self
    {
        $this->comments = array_values(array_filter(
            $this->comments,
            fn($c) => $c['id'] !== $commentId
        ));
        $this->touch();
        return $this;
    }

    /**
     * Get comments count.
     */
    public function getCommentsCount(): int
    {
        return count($this->getApprovedComments());
    }

    // =========================================================================
    // RATING METHODS
    // =========================================================================

    /**
     * Available rating criteria.
     */
    public const RATING_CRITERIA = [
        'relevance' => 'Pertinence',
        'depth' => 'Profondeur',
        'clarity' => 'Clarté',
        'freshness' => 'Actualité',
        'usefulness' => 'Utilité',
    ];

    /**
     * @return array<string, int>
     */
    public function getRatings(): array
    {
        return $this->ratings;
    }

    /**
     * Set a rating for a specific criterion.
     *
     * @param string $criterion One of: relevance, depth, clarity, freshness, usefulness
     * @param int $value Rating from 1 to 5
     */
    public function setRating(string $criterion, int $value): self
    {
        if (!array_key_exists($criterion, self::RATING_CRITERIA)) {
            throw new \InvalidArgumentException("Invalid rating criterion: {$criterion}");
        }
        if ($value < 0 || $value > 5) {
            throw new \InvalidArgumentException("Rating must be between 0 and 5");
        }
        $this->ratings[$criterion] = $value;
        $this->touch();
        return $this;
    }

    /**
     * Get a specific rating value.
     */
    public function getRating(string $criterion): int
    {
        return $this->ratings[$criterion] ?? 0;
    }

    /**
     * Set all ratings at once.
     *
     * @param array<string, int> $ratings
     */
    public function setRatings(array $ratings): self
    {
        foreach ($ratings as $criterion => $value) {
            $this->setRating($criterion, $value);
        }
        return $this;
    }

    /**
     * Calculate the average rating (0-5).
     */
    public function getAverageRating(): float
    {
        $rated = array_filter($this->ratings, fn($v) => $v > 0);
        if (empty($rated)) {
            return 0.0;
        }
        return round(array_sum($rated) / count($rated), 1);
    }

    /**
     * Check if the article has been rated.
     */
    public function isRated(): bool
    {
        return array_sum($this->ratings) > 0;
    }

    /**
     * Get the number of criteria that have been rated.
     */
    public function getRatedCriteriaCount(): int
    {
        return count(array_filter($this->ratings, fn($v) => $v > 0));
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
            'authorBio' => $this->authorBio,
            'authorAvatar' => $this->authorAvatar,
            'authorInstitution' => $this->authorInstitution,
            'categoryId' => $this->categoryId,
            'featuredImage' => $this->featuredImage,
            'status' => $this->status->value,
            'tags' => $this->tags,
            'sources' => $this->sources,
            'ratings' => $this->ratings,
            'comments' => $this->comments,
            'locked' => $this->locked,
            'license' => $this->license,
            'originalUrl' => $this->originalUrl,
            'originalSource' => $this->originalSource,
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
        if (isset($data['authorBio'])) {
            $post->authorBio = $data['authorBio'];
        }
        if (isset($data['authorAvatar'])) {
            $post->authorAvatar = $data['authorAvatar'];
        }
        if (isset($data['authorInstitution'])) {
            $post->authorInstitution = $data['authorInstitution'];
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
        if (isset($data['sources']) && is_array($data['sources'])) {
            $post->sources = $data['sources'];
        }
        if (isset($data['ratings']) && is_array($data['ratings'])) {
            $post->ratings = array_merge($post->ratings, $data['ratings']);
        }
        if (isset($data['comments']) && is_array($data['comments'])) {
            $post->comments = $data['comments'];
        }
        if (isset($data['locked'])) {
            $post->locked = (bool) $data['locked'];
        }
        if (isset($data['license'])) {
            $post->license = $data['license'];
        }
        if (isset($data['originalUrl'])) {
            $post->originalUrl = $data['originalUrl'];
        }
        if (isset($data['originalSource'])) {
            $post->originalSource = $data['originalSource'];
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
