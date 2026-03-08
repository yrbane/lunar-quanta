<?php

declare(strict_types=1);

namespace Lunar\Service\StaticSite;

use Lunar\Entity\Category;
use Lunar\Entity\Post;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Template\AdvancedTemplateEngine;

/**
 * Générateur de site statique pour le blog.
 *
 * Transforme les articles Markdown en pages HTML statiques.
 * Le HTML généré peut être servi directement par Nginx sans PHP.
 *
 * @example
 * ```php
 * $generator = new StaticGenerator(
 *     $postService,
 *     new MarkdownParser(),
 *     'public/blog',
 *     'template/blog'
 * );
 *
 * // Générer tout le site
 * $result = $generator->generateAll();
 * echo "Generated {$result['posts']} posts";
 *
 * // Générer un seul article
 * $generator->generatePost($post);
 * ```
 */
final class StaticGenerator
{
    /** @var callable[] */
    private array $publishCallbacks = [];

    /** @var callable|null Callback de progression (current, total, type, item) */
    private $progressCallback = null;

    private ?RssGenerator $rssGenerator = null;
    private ?SitemapGenerator $sitemapGenerator = null;
    private ?CategoryService $categoryService = null;
    private ?AdvancedTemplateEngine $templateEngine = null;

    /** @var array<string, Category> Pre-loaded category cache */
    private array $categoryCache = [];

    public function __construct(
        private readonly PostService $postService,
        private readonly MarkdownParser $markdownParser,
        private readonly string $outputPath,
        private readonly string $templatePath,
        private readonly string $siteUrl = ''
    ) {
        $this->ensureDirectories();

        // Initialiser le moteur de template lunar-template
        $cachePath = dirname($this->outputPath) . '/cache/templates';
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        $this->templateEngine = new AdvancedTemplateEngine($this->templatePath, $cachePath);

        if ($this->siteUrl !== '') {
            $this->rssGenerator = new RssGenerator(
                $this->postService,
                $this->siteUrl,
                'Blog',
                'Articles du blog'
            );
            $this->sitemapGenerator = new SitemapGenerator(
                $this->postService,
                $this->siteUrl
            );
        }
    }

    /**
     * Configure le service de catégories.
     */
    public function setCategoryService(CategoryService $categoryService): void
    {
        $this->categoryService = $categoryService;
        $this->warmCategoryCache();
    }

    private function warmCategoryCache(): void
    {
        $this->categoryCache = [];
        if ($this->categoryService !== null) {
            foreach ($this->categoryService->all() as $category) {
                $this->categoryCache[$category->getId()] = $category;
            }
        }
    }

    private function getCachedCategory(?string $categoryId): ?Category
    {
        if ($categoryId === null) {
            return null;
        }
        return $this->categoryCache[$categoryId] ?? null;
    }

    /**
     * Génère le fichier HTML d'un article.
     */
    public function generatePost(Post $post): void
    {
        $htmlContent = $this->markdownParser->parse($post->getContent());

        // Récupérer la catégorie si disponible
        $categoryName = '';
        $categorySlug = '';
        $category = $this->getCachedCategory($post->getCategoryId());
        if ($category !== null) {
            $categoryName = $category->getName();
            $categorySlug = $this->slugify($categoryName);
        }

        // Récupérer les articles similaires (même catégorie ou tags communs)
        $relatedPosts = $this->findRelatedPosts($post, 4);

        // Calculer la note moyenne
        $averageRating = $post->getAverageRating();

        // Récupérer les sources
        $sources = $post->getSources();

        // Utiliser le moteur de template lunar-template
        $html = $this->templateEngine->render('post.html', [
            'title' => $post->getTitle(),
            'content' => $htmlContent,
            'excerpt' => $post->getExcerpt() ?? '',
            'author' => $post->getAuthor() ?? '',
            'author_bio' => $post->getAuthorBio() ?? '',
            'author_avatar' => $post->getAuthorAvatar() ?? '',
            'author_institution' => $post->getAuthorInstitution() ?? '',
            'published_at' => $post->getPublishedAt()?->format('d/m/Y') ?? '',
            'reading_time' => $post->getReadingTime(),
            'url' => $post->getUrl(),
            'year' => date('Y'),
            'featured_image' => $post->getFeaturedImage() ?? '',
            'category' => $categoryName,
            'category_slug' => $categorySlug,
            'average_rating' => $averageRating > 0 ? number_format($averageRating, 1) : '0',
            'license' => $post->getLicense() ?? '',
            'original_url' => $post->getOriginalUrl() ?? '',
            'original_source' => $post->getOriginalSource() ?? '',
            'tags' => $post->getTags(),
            'sources' => $sources,
            'related_posts' => $relatedPosts,
            // JSON data for JavaScript
            'tags_json' => json_encode($post->getTags()),
            'sources_json' => json_encode($sources),
            'related_json' => json_encode($relatedPosts),
            // Boolean flags for JavaScript (as JSON strings)
            'has_avatar' => $post->getAuthorAvatar() !== '' ? 'true' : 'false',
            'has_institution' => $post->getAuthorInstitution() !== '' ? 'true' : 'false',
            'has_bio' => $post->getAuthorBio() !== '' ? 'true' : 'false',
            'has_license' => $post->getLicense() !== null && $post->getLicense() !== '' ? 'true' : 'false',
            'is_locked' => $post->isLocked() ? 'true' : 'false',
            'has_original_source' => $post->getOriginalSource() !== null && $post->getOriginalSource() !== '' ? 'true' : 'false',
            'has_featured_image' => $post->getFeaturedImage() !== null && $post->getFeaturedImage() !== '' ? 'true' : 'false',
        ]);

        $this->writeFile('posts/' . $post->getSlug() . '.html', $html);

        // Déclencher les callbacks
        foreach ($this->publishCallbacks as $callback) {
            $callback($post);
        }
    }

    /** @var array<string, array<string, true>> Tag index: tag => [postId => true] */
    private array $tagIndex = [];

    /** @var array<string, Post> Post index: id => Post */
    private array $postIndex = [];

    private bool $relatedIndexBuilt = false;

    private function buildRelatedIndex(): void
    {
        if ($this->relatedIndexBuilt) {
            return;
        }

        $allPosts = $this->postService->findPublished();
        foreach ($allPosts as $post) {
            $this->postIndex[$post->getId()] = $post;
            foreach ($post->getTags() as $tag) {
                $this->tagIndex[$tag][$post->getId()] = true;
            }
        }
        $this->relatedIndexBuilt = true;
    }

    /**
     * Trouve les articles similaires basés sur la catégorie et les tags.
     * Utilise un index de tags pour des lookups O(1) au lieu de O(n).
     *
     * @return array<int, array{title: string, url: string, excerpt: string}>
     */
    private function findRelatedPosts(Post $currentPost, int $limit = 4): array
    {
        $this->buildRelatedIndex();

        $currentId = $currentPost->getId();
        $currentTags = $currentPost->getTags();
        $currentCategoryId = $currentPost->getCategoryId();

        // Collect candidate post IDs from tag index
        $scores = [];
        foreach ($currentTags as $tag) {
            if (isset($this->tagIndex[$tag])) {
                foreach ($this->tagIndex[$tag] as $postId => $_) {
                    if ($postId === $currentId) {
                        continue;
                    }
                    $scores[$postId] = ($scores[$postId] ?? 0) + 5;
                }
            }
        }

        // Add category bonus
        if ($currentCategoryId !== null) {
            foreach ($this->postIndex as $postId => $post) {
                if ($postId !== $currentId && $post->getCategoryId() === $currentCategoryId) {
                    $scores[$postId] = ($scores[$postId] ?? 0) + 10;
                }
            }
        }

        // Sort by score descending
        arsort($scores);

        // Take top results and format
        $related = [];
        $count = 0;
        foreach ($scores as $postId => $score) {
            if ($count >= $limit) {
                break;
            }
            $post = $this->postIndex[$postId];
            $related[] = [
                'title' => $post->getTitle(),
                'url' => $post->getUrl(),
                'excerpt' => $post->getExcerpt(),
            ];
            $count++;
        }

        return $related;
    }

    /**
     * Génère la page d'index du blog.
     */
    public function generateIndex(): void
    {
        $posts = $this->postService->findPublished();

        // Trier par date décroissante
        usort($posts, fn($a, $b) => $b->getPublishedAt() <=> $a->getPublishedAt());

        // Collecter tous les tags uniques
        $allTags = [];
        foreach ($posts as $post) {
            foreach ($post->getTags() as $tag) {
                if (!isset($allTags[$tag])) {
                    $allTags[$tag] = 0;
                }
                $allTags[$tag]++;
            }
        }
        arsort($allTags); // Trier par popularité

        // Collecter toutes les catégories uniques
        $allCategories = [];
        foreach ($posts as $post) {
            $catId = $post->getCategoryId();
            if ($catId !== null && !isset($allCategories[$catId])) {
                $allCategories[$catId] = true;
            }
        }

        // Générer le HTML des tags
        $tagsHtml = '';
        foreach ($allTags as $tag => $count) {
            $tagStr = (string) $tag;
            $slug = $this->slugify($tagStr);
            $tagsHtml .= sprintf(
                '<a href="/blog/tags/%s.html" class="tag-pill">%s</a>',
                htmlspecialchars($slug),
                htmlspecialchars($tagStr)
            );
        }

        // Générer le slider des 10 derniers articles (pleine largeur)
        $sliderHtml = '';
        $latestPosts = array_slice($posts, 0, 10);
        foreach ($latestPosts as $post) {
            $categoryName = '';
            $sliderCategory = $this->getCachedCategory($post->getCategoryId());
            if ($sliderCategory !== null) {
                $categoryName = $sliderCategory->getName();
            }
            $sliderHtml .= sprintf(
                '<article class="la-hero-slide">
                    <div class="la-hero-slide-image">
                        <img src="%s" alt="%s">
                    </div>
                    <div class="la-hero-slide-content">
                        <span class="la-hero-slide-category">%s</span>
                        <h2 class="la-hero-slide-title">%s</h2>
                        <p class="la-hero-slide-excerpt">%s</p>
                        <div class="la-hero-slide-meta">
                            <span>%s</span>
                            <span>•</span>
                            <span>%d min de lecture</span>
                        </div>
                        <a href="%s" class="la-hero-slide-link">
                            Lire l\'article <span class="la-icon">arrow_forward</span>
                        </a>
                    </div>
                </article>',
                htmlspecialchars($post->getFeaturedImage() ?? ''),
                htmlspecialchars($post->getTitle()),
                htmlspecialchars($categoryName),
                htmlspecialchars($post->getTitle()),
                htmlspecialchars($post->getExcerpt() ?? ''),
                $post->getPublishedAt()?->format('d M Y') ?? '',
                $post->getReadingTime() ?? 5,
                htmlspecialchars($post->getUrl())
            );
        }

        // Préparer les données des posts pour le template
        $postsData = array_map(function($post) {
            $categoryName = '';
            $categorySlug = '';
            $postCategory = $this->getCachedCategory($post->getCategoryId());
            if ($postCategory !== null) {
                $categoryName = $postCategory->getName();
                $categorySlug = $this->slugify($categoryName);
            }

            // Generate rating stars HTML
            $avgRating = $post->getAverageRating();
            $ratingStars = $this->generateRatingStarsHtml($avgRating);

            return [
                'title' => $post->getTitle(),
                'url' => $post->getUrl(),
                'excerpt' => $post->getExcerpt() ?? '',
                'author' => $post->getAuthor() ?? '',
                'published_at' => $post->getPublishedAt()?->format('d/m/Y') ?? '',
                'reading_time' => $post->getReadingTime(),
                'featured_image' => $post->getFeaturedImage() ?? '',
                'category' => $categoryName,
                'category_slug' => $categorySlug,
                'slug' => $post->getSlug(),
                'tags_string' => implode(', ', $post->getTags()),
                'average_rating' => $avgRating > 0 ? number_format($avgRating, 1) : '',
                'rating_stars' => $ratingStars,
            ];
        }, $posts);

        // Utiliser le moteur de template lunar-template
        $html = $this->templateEngine->render('index.html', [
            'posts' => $postsData,
            'year' => date('Y'),
            'article_count' => count($posts),
            'categories_count' => count($allCategories),
            'tags_count' => count($allTags),
            'tags_list' => $tagsHtml,
            'slider_items' => $sliderHtml,
            'schema_org' => '',
            'head_injections' => '',
            'body_end_injections' => '',
        ]);

        $this->writeFile('index.html', $html);
    }

    /**
     * Génère tous les fichiers statiques.
     *
     * @return array{posts: int, index: bool, rss: bool, sitemap: bool, tags: int, categories: int}
     */
    public function generateAll(): array
    {
        $posts = $this->postService->findPublished();
        $totalPosts = count($posts);
        $count = 0;

        foreach ($posts as $post) {
            $count++;
            $this->reportProgress($count, $totalPosts, 'post', $post->getTitle());
            $this->generatePost($post);
        }

        $this->reportProgress(1, 1, 'index', 'index.html');
        $this->generateIndex();

        $tagsCount = $this->generateTagPagesWithProgress();
        $categoriesCount = $this->generateCategoryPagesWithProgress();

        $this->reportProgress(1, 1, 'rss', 'feed.xml');
        $rss = $this->generateRss();

        $this->reportProgress(1, 1, 'sitemap', 'sitemap.xml');
        $sitemap = $this->generateSitemap();

        return [
            'posts' => $count,
            'index' => true,
            'rss' => $rss,
            'sitemap' => $sitemap,
            'tags' => $tagsCount,
            'categories' => $categoriesCount,
        ];
    }

    /**
     * Génère les pages de tags.
     *
     * @return int Nombre de pages générées
     */
    public function generateTagPages(): int
    {
        return $this->generateTagPagesWithProgress(false);
    }

    /**
     * Génère les pages de tags avec progression.
     *
     * @param bool $reportProgress Signaler la progression
     * @return int Nombre de pages générées
     */
    private function generateTagPagesWithProgress(bool $reportProgress = true): int
    {
        // Vérifier si le template existe (avec ou sans .tpl)
        $templateExists = file_exists($this->templatePath . '/tag.html.tpl')
            || file_exists($this->templatePath . '/tag.html');
        if (!$templateExists) {
            return 0;
        }

        $posts = $this->postService->findPublished();

        // Collecter tous les tags
        $taggedPosts = [];
        foreach ($posts as $post) {
            foreach ($post->getTags() as $tag) {
                if (!isset($taggedPosts[$tag])) {
                    $taggedPosts[$tag] = [];
                }
                $taggedPosts[$tag][] = $post;
            }
        }

        // Générer une page par tag
        $total = count($taggedPosts);
        $count = 0;
        foreach ($taggedPosts as $tag => $tagPosts) {
            $count++;
            if ($reportProgress) {
                $this->reportProgress($count, $total, 'tag', (string) $tag);
            }
            $this->generateTagPage((string) $tag, $tagPosts);
        }

        return $count;
    }

    /**
     * Génère une page de tag.
     *
     * @param Post[] $posts
     */
    private function generateTagPage(string $tag, array $posts): void
    {
        $postsData = array_map(function($post) {
            $avgRating = $post->getAverageRating();
            $ratingStars = $this->generateRatingStarsHtml($avgRating);

            // Get category info from cache
            $categoryName = '';
            $categorySlug = '';
            $tagCategory = $this->getCachedCategory($post->getCategoryId());
            if ($tagCategory !== null) {
                $categoryName = $tagCategory->getName();
                $categorySlug = $tagCategory->getSlug();
            }

            return [
                'title' => $post->getTitle(),
                'url' => $post->getUrl(),
                'excerpt' => $post->getExcerpt() ?? '',
                'author' => $post->getAuthor() ?? '',
                'published_at' => $post->getPublishedAt()?->format('d/m/Y') ?? '',
                'featured_image' => $post->getFeaturedImage() ?? '',
                'reading_time' => $post->getReadingTime(),
                'category' => $categoryName,
                'category_slug' => $categorySlug,
                'average_rating' => $avgRating > 0 ? number_format($avgRating, 1) : '',
                'rating_stars' => $ratingStars,
            ];
        }, $posts);

        $html = $this->templateEngine->render('tag.html', [
            'tag' => $tag,
            'posts' => $postsData,
            'count' => count($posts),
            'year' => date('Y'),
        ]);

        $this->writeFile('tag/' . $this->slugify($tag) . '.html', $html);
    }

    /**
     * Génère les pages de catégories.
     *
     * @return int Nombre de pages générées
     */
    public function generateCategoryPages(): int
    {
        return $this->generateCategoryPagesWithProgress(false);
    }

    /**
     * Génère les pages de catégories avec progression.
     *
     * @param bool $reportProgress Signaler la progression
     * @return int Nombre de pages générées
     */
    private function generateCategoryPagesWithProgress(bool $reportProgress = true): int
    {
        if ($this->categoryService === null) {
            return 0;
        }

        // Vérifier si le template existe (avec ou sans .tpl)
        $templateExists = file_exists($this->templatePath . '/category.html.tpl')
            || file_exists($this->templatePath . '/category.html');
        if (!$templateExists) {
            return 0;
        }

        $categories = $this->categoryService->all();
        $posts = $this->postService->findPublished();

        $total = count($categories);
        $count = 0;
        foreach ($categories as $category) {
            $count++;
            if ($reportProgress) {
                $this->reportProgress($count, $total, 'category', $category->getName());
            }

            $categoryPosts = array_filter(
                $posts,
                fn(Post $post) => $post->getCategoryId() === $category->getId()
            );

            $this->generateCategoryPage($category, array_values($categoryPosts));
        }

        return $count;
    }

    /**
     * Génère une page de catégorie.
     *
     * @param Post[] $posts
     */
    private function generateCategoryPage(Category $category, array $posts): void
    {
        $postsData = array_map(function($post) {
            $avgRating = $post->getAverageRating();
            $ratingStars = $this->generateRatingStarsHtml($avgRating);

            return [
                'title' => $post->getTitle(),
                'url' => $post->getUrl(),
                'excerpt' => $post->getExcerpt() ?? '',
                'author' => $post->getAuthor() ?? '',
                'published_at' => $post->getPublishedAt()?->format('d/m/Y') ?? '',
                'featured_image' => $post->getFeaturedImage() ?? '',
                'reading_time' => $post->getReadingTime(),
                'average_rating' => $avgRating > 0 ? number_format($avgRating, 1) : '',
                'rating_stars' => $ratingStars,
            ];
        }, $posts);

        $html = $this->templateEngine->render('category.html', [
            'category_name' => $category->getName(),
            'category_description' => $category->getDescription(),
            'category_color' => $category->getColor(),
            'posts' => $postsData,
            'count' => count($posts),
            'year' => date('Y'),
        ]);

        $this->writeFile('category/' . $category->getSlug() . '.html', $html);
    }

    /**
     * Convertit une chaîne en slug.
     */
    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Génère le flux RSS.
     */
    public function generateRss(): bool
    {
        if ($this->rssGenerator === null) {
            return false;
        }

        $rss = $this->rssGenerator->generate();
        $this->writeFile('feed.xml', $rss);

        return true;
    }

    /**
     * Génère le sitemap.
     */
    public function generateSitemap(): bool
    {
        if ($this->sitemapGenerator === null) {
            return false;
        }

        $sitemap = $this->sitemapGenerator->generate();
        // Sitemap à la racine du site, pas dans /blog/
        file_put_contents(dirname($this->outputPath) . '/sitemap.xml', $sitemap);

        return true;
    }

    /**
     * Supprime tous les fichiers générés.
     */
    public function clean(): void
    {
        $this->removeDirectory($this->outputPath . '/posts');
        $this->removeFile($this->outputPath . '/index.html');
    }

    /**
     * Nettoie et régénère tout.
     *
     * @return array{posts: int, index: bool, rss: bool, sitemap: bool, tags: int, categories: int}
     */
    public function regenerate(): array
    {
        $this->clean();
        $this->ensureDirectories();
        return $this->generateAll();
    }

    /**
     * Enregistre un callback appelé après la génération d'un article.
     */
    public function onPublish(callable $callback): void
    {
        $this->publishCallbacks[] = $callback;
    }

    /**
     * Définit un callback de progression.
     *
     * Le callback reçoit: (int $current, int $total, string $type, string $item)
     * - $current: numéro de l'élément en cours
     * - $total: nombre total d'éléments
     * - $type: type d'élément ('post', 'tag', 'category', 'index', 'rss', 'sitemap')
     * - $item: nom/titre de l'élément en cours
     */
    public function onProgress(callable $callback): void
    {
        $this->progressCallback = $callback;
    }

    /**
     * Appelle le callback de progression s'il est défini.
     */
    private function reportProgress(int $current, int $total, string $type, string $item): void
    {
        if ($this->progressCallback !== null) {
            ($this->progressCallback)($current, $total, $type, $item);
        }
    }

    /**
     * Charge un template.
     */
    private function loadTemplate(string $name): string
    {
        $path = $this->templatePath . '/' . $name;

        // Support pour la nouvelle convention .tpl
        if (!file_exists($path) && file_exists($path . '.tpl')) {
            $path = $path . '.tpl';
        }

        if (!file_exists($path)) {
            throw new \RuntimeException("Template not found: $name");
        }

        return file_get_contents($path);
    }

    /**
     * Rendu simple avec remplacement de variables.
     *
     * @param array<string, mixed> $variables
     */
    private function render(string $template, array $variables): string
    {
        $html = $template;

        // Gérer les conditions {% if var %} ... {% endif %}
        $html = $this->processConditions($html, $variables);

        foreach ($variables as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $html = str_replace('{{ ' . $key . ' }}', (string) $value, $html);
            }
        }

        // Nettoyer les variables non remplacées
        $html = preg_replace('/\{\{\s*\w+\s*\}\}/', '', $html);

        return $html;
    }

    /**
     * Traite les conditions {% if var %} ... {% endif %}.
     *
     * @param array<string, mixed> $variables
     */
    private function processConditions(string $html, array $variables): string
    {
        // Pattern pour {% if var %} ... {% endif %}
        $pattern = '/\{%\s*if\s+(\w+)\s*%\}(.*?)\{%\s*endif\s*%\}/s';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $var = $matches[1];
            $content = $matches[2];

            // Si la variable existe et n'est pas vide, afficher le contenu
            if (isset($variables[$var]) && !empty($variables[$var])) {
                return $content;
            }

            return '';
        }, $html);
    }

    /**
     * Traite {% if var %} ... {% else %} ... {% endif %} pour une variable simple.
     */
    private function processSimpleCondition(string $html, string $var, bool $hasValue): string
    {
        // Pattern avec {% else %} - utilise un pattern non-greedy plus précis
        $patternWithElse = '/\{%\s*if\s+' . preg_quote($var, '/') . '\s*%\}((?:(?!\{%\s*(?:if|endif|else)\s*%\}).)*?)\{%\s*else\s*%\}((?:(?!\{%\s*(?:if|endif)\s*%\}).)*?)\{%\s*endif\s*%\}/s';

        $html = preg_replace_callback($patternWithElse, function($matches) use ($hasValue) {
            return $hasValue ? $matches[1] : $matches[2];
        }, $html);

        // Pattern sans {% else %}
        $patternNoElse = '/\{%\s*if\s+' . preg_quote($var, '/') . '\s*%\}((?:(?!\{%\s*(?:if|endif)\s*%\}).)*?)\{%\s*endif\s*%\}/s';

        $html = preg_replace_callback($patternNoElse, function($matches) use ($hasValue) {
            return $hasValue ? $matches[1] : '';
        }, $html);

        return $html;
    }

    /**
     * Traite {% if var|length > 0 %} ... {% else %} ... {% endif %}.
     *
     * Gère correctement les blocs {% if %} imbriqués en comptant les niveaux.
     */
    private function processLengthCondition(string $html, string $var, bool $hasItems): string
    {
        // Trouver le début du bloc
        $startPattern = '/\{%\s*if\s+' . preg_quote($var, '/') . '\|length\s*>\s*0\s*%\}/';
        if (!preg_match($startPattern, $html, $startMatch, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $startPos = $startMatch[0][1];
        $startLen = strlen($startMatch[0][0]);
        $content = substr($html, $startPos + $startLen);

        // Trouver le {% else %} et {% endif %} correspondants (en comptant les niveaux)
        $elsePos = null;
        $endPos = null;
        $endLen = 0;
        $level = 1;
        $pos = 0;

        // Pattern qui capture: if, else, ou endif (mais pas endfor, for, etc.)
        // Le pattern .*? est non-greedy et s'arrête au premier %}
        while ($level > 0 && preg_match('/\{%\s*(if|else|endif)\b[^}]*%\}/', $content, $match, PREG_OFFSET_CAPTURE, $pos)) {
            $tag = $match[1][0];
            $tagPos = $match[0][1];
            $tagLen = strlen($match[0][0]);

            if ($tag === 'if') {
                $level++;
            } elseif ($tag === 'else' && $level === 1) {
                // Seulement capturer le {% else %} au niveau 1 (bloc principal)
                $elsePos = $tagPos;
                $elseLen = $tagLen;
            } elseif ($tag === 'endif') {
                $level--;
                if ($level === 0) {
                    $endPos = $tagPos;
                    $endLen = $tagLen;
                }
            }

            $pos = $tagPos + $tagLen;
        }

        if ($endPos === null) {
            return $html;
        }

        // Extraire les parties
        if ($elsePos !== null) {
            $ifContent = substr($content, 0, $elsePos);
            $elseContent = substr($content, $elsePos + $elseLen, $endPos - $elsePos - $elseLen);
        } else {
            $ifContent = substr($content, 0, $endPos);
            $elseContent = '';
        }

        // Reconstruire le HTML
        $before = substr($html, 0, $startPos);
        $after = substr($html, $startPos + $startLen + $endPos + $endLen);
        $replacement = $hasItems ? $ifContent : $elseContent;

        return $before . $replacement . $after;
    }

    /**
     * Rendu avec boucle {% for %}.
     *
     * @param array<int, array<string, mixed>> $items
     */
    private function renderWithLoop(string $template, string $loopVar, array $items): string
    {
        // Extraire le bloc de boucle
        $pattern = '/\{%\s*for\s+(\w+)\s+in\s+' . preg_quote($loopVar, '/') . '\s*%\}(.*?)\{%\s*endfor\s*%\}/s';

        if (!preg_match($pattern, $template, $matches)) {
            return $template;
        }

        $itemVar = $matches[1];
        $loopTemplate = $matches[2];

        // Générer le contenu de la boucle
        $loopContent = '';
        foreach ($items as $item) {
            $itemHtml = $loopTemplate;

            // Gérer les conditions avec comparaison {% if post.var > 0 %}...{% endif %}
            $compPattern = '/\{%\s*if\s+' . preg_quote($itemVar, '/') . '\.(\w+)\s*(>|<|>=|<=|==|!=)\s*(\d+(?:\.\d+)?)\s*%\}(.*?)\{%\s*endif\s*%\}/s';
            $itemHtml = preg_replace_callback($compPattern, function ($matches) use ($item) {
                $var = $matches[1];
                $operator = $matches[2];
                $compareValue = (float) $matches[3];
                $content = $matches[4];
                $itemValue = isset($item[$var]) ? (float) $item[$var] : 0;

                $result = match ($operator) {
                    '>' => $itemValue > $compareValue,
                    '<' => $itemValue < $compareValue,
                    '>=' => $itemValue >= $compareValue,
                    '<=' => $itemValue <= $compareValue,
                    '==' => $itemValue == $compareValue,
                    '!=' => $itemValue != $compareValue,
                    default => false,
                };

                return $result ? $content : '';
            }, $itemHtml);

            // Gérer les conditions {% if post.var %}...{% else %}...{% endif %}
            $condPatternWithElse = '/\{%\s*if\s+' . preg_quote($itemVar, '/') . '\.(\w+)\s*%\}(.*?)\{%\s*else\s*%\}(.*?)\{%\s*endif\s*%\}/s';
            $itemHtml = preg_replace_callback($condPatternWithElse, function ($matches) use ($item) {
                $var = $matches[1];
                $ifContent = $matches[2];
                $elseContent = $matches[3];
                if (isset($item[$var]) && !empty($item[$var])) {
                    return $ifContent;
                }
                return $elseContent;
            }, $itemHtml);

            // Gérer les conditions {% if post.var %}...{% endif %} (sans else)
            $condPattern = '/\{%\s*if\s+' . preg_quote($itemVar, '/') . '\.(\w+)\s*%\}(.*?)\{%\s*endif\s*%\}/s';
            $itemHtml = preg_replace_callback($condPattern, function ($matches) use ($item) {
                $var = $matches[1];
                $content = $matches[2];
                if (isset($item[$var]) && !empty($item[$var])) {
                    return $content;
                }
                return '';
            }, $itemHtml);

            // Si l'item est un tableau associatif, remplacer {{ var.key }} et {{ var.key|filter }}
            if (is_array($item)) {
                foreach ($item as $key => $value) {
                    if (is_string($value) || is_numeric($value)) {
                        // Standard replacement
                        $itemHtml = str_replace('{{ ' . $itemVar . '.' . $key . ' }}', (string) $value, $itemHtml);

                        // With |lower filter
                        $itemHtml = str_replace('{{ ' . $itemVar . '.' . $key . '|lower }}', strtolower((string) $value), $itemHtml);

                        // With |upper filter
                        $itemHtml = str_replace('{{ ' . $itemVar . '.' . $key . '|upper }}', strtoupper((string) $value), $itemHtml);

                        // With |slug filter
                        $itemHtml = str_replace('{{ ' . $itemVar . '.' . $key . '|slug }}', $this->slugify((string) $value), $itemHtml);
                    }
                }
            }

            // Si l'item est une valeur scalaire (string ou int), remplacer {{ var }} directement
            if (is_string($item) || is_numeric($item)) {
                $itemHtml = str_replace('{{ ' . $itemVar . ' }}', (string) $item, $itemHtml);
            }

            $loopContent .= $itemHtml;
        }

        // Remplacer la boucle par le contenu généré
        return preg_replace($pattern, $loopContent, $template);
    }

    /**
     * Écrit un fichier dans le dossier de sortie.
     */
    private function writeFile(string $relativePath, string $content): void
    {
        $fullPath = $this->outputPath . '/' . $relativePath;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);
    }

    /**
     * Supprime un fichier.
     */
    private function removeFile(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Supprime un répertoire récursivement.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Crée les répertoires nécessaires.
     */
    private function ensureDirectories(): void
    {
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }

        if (!is_dir($this->outputPath . '/posts')) {
            mkdir($this->outputPath . '/posts', 0755, true);
        }
    }

    /**
     * Generate HTML for rating stars display.
     */
    private function generateRatingStarsHtml(float $rating): string
    {
        if ($rating <= 0) {
            return '';
        }

        $html = '';
        $fullStars = (int) floor($rating);
        $hasHalf = ($rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($hasHalf ? 1 : 0);

        // Full stars
        for ($i = 0; $i < $fullStars; $i++) {
            $html .= '<span class="la-rating-star filled"><span class="la-icon xs">star</span></span>';
        }

        // Half star
        if ($hasHalf) {
            $html .= '<span class="la-rating-star half"><span class="la-icon xs">star_half</span></span>';
        }

        // Empty stars
        for ($i = 0; $i < $emptyStars; $i++) {
            $html .= '<span class="la-rating-star"><span class="la-icon xs">star_outline</span></span>';
        }

        return $html;
    }
}
