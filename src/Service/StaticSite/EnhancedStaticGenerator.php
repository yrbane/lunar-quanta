<?php

declare(strict_types=1);

namespace Lunar\Service\StaticSite;

use Lunar\Entity\Category;
use Lunar\Entity\Post;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\AccessibilityHelper;
use Lunar\Service\Content\AnchorLinkService;
use Lunar\Service\Content\CodeHighlighter;
use Lunar\Service\Content\DarkModeService;
use Lunar\Service\Content\ExcerptGenerator;
use Lunar\Service\Content\LazyLoadService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\Content\MetaTagService;
use Lunar\Service\Content\MinificationService;
use Lunar\Service\Content\PerformanceHelper;
use Lunar\Service\Content\PrintStyleService;
use Lunar\Service\Content\ReadingProgressService;
use Lunar\Service\Content\SchemaOrgService;
use Lunar\Service\Content\SocialShareService;
use Lunar\Service\Content\TableOfContentsGenerator;
use Lunar\Service\Content\WordStatisticsService;
use Lunar\Service\I18n\DateFormatHelper;
use Lunar\Template\AdvancedTemplateEngine;

/**
 * Générateur de site statique amélioré avec tous les services de contenu.
 *
 * Intègre tous les services développés pour une expérience optimale :
 * - SEO (Schema.org, Meta tags, Social sharing)
 * - Performance (Lazy loading, Minification, Resource hints)
 * - UX (Dark mode, Reading progress, Table of contents)
 * - Accessibilité (Skip links, ARIA, Focus)
 * - Impression (Print styles)
 * - Code highlighting
 */
final class EnhancedStaticGenerator
{
    /** @var callable[] */
    private array $publishCallbacks = [];
    private $progressCallback = null;

    // Core services
    private ?RssGenerator $rssGenerator = null;
    private ?SitemapGenerator $sitemapGenerator = null;
    private ?CategoryService $categoryService = null;

    // Content enhancement services
    private readonly LazyLoadService $lazyLoad;
    private readonly AnchorLinkService $anchorLinks;
    private readonly CodeHighlighter $codeHighlighter;
    private readonly TableOfContentsGenerator $tocGenerator;
    private readonly ReadingProgressService $readingProgress;
    private readonly DarkModeService $darkMode;
    private readonly PrintStyleService $printStyles;
    private readonly AccessibilityHelper $accessibility;
    private readonly SchemaOrgService $schemaOrg;
    private readonly MetaTagService $metaTags;
    private readonly SocialShareService $socialShare;
    private readonly PerformanceHelper $performance;
    private readonly MinificationService $minification;
    private readonly WordStatisticsService $wordStats;
    private readonly DateFormatHelper $dateFormat;
    private readonly ExcerptGenerator $excerptGenerator;

    private bool $enableMinification = true;
    private bool $enableLazyLoading = true;
    private bool $enableDarkMode = true;
    private ?AdvancedTemplateEngine $templateEngine = null;

    public function __construct(
        private readonly PostService $postService,
        private readonly MarkdownParser $markdownParser,
        private readonly string $outputPath,
        private readonly string $templatePath,
        private readonly string $siteUrl = ''
    ) {
        $this->ensureDirectories();

        // Initialize core generators
        if ($this->siteUrl !== '') {
            $this->rssGenerator = new RssGenerator(
                $this->postService,
                $this->siteUrl,
                'Lunar Blog',
                'Articles du blog Lunar Quanta'
            );
            $this->sitemapGenerator = new SitemapGenerator(
                $this->postService,
                $this->siteUrl
            );
        }

        // Initialize content services
        $this->lazyLoad = new LazyLoadService();
        $this->lazyLoad->setLoadingAnimation('fade')->setThreshold(200);

        $this->anchorLinks = new AnchorLinkService();
        $this->anchorLinks->setLevels([2, 3, 4])->setLinkSymbol('#');

        $this->codeHighlighter = new CodeHighlighter();

        $this->tocGenerator = new TableOfContentsGenerator();
        $this->tocGenerator->setMinLevel(2)->setMaxLevel(3)->setAddAnchors(true);

        $this->readingProgress = new ReadingProgressService();
        $this->readingProgress->setColor('var(--la-primary, #3b82f6)')->setHeight(4);

        $this->darkMode = new DarkModeService();
        $this->darkMode->setStorageKey('lunar-theme')->setDefaultTheme('system');

        $this->printStyles = new PrintStyleService();
        $this->printStyles->setShowUrls(true)->setShowPageNumbers(true);

        $this->accessibility = new AccessibilityHelper();

        $this->schemaOrg = new SchemaOrgService();
        $this->schemaOrg->setBaseUrl($this->siteUrl)->setSiteName('Lunar Blog');

        $this->metaTags = new MetaTagService();
        $this->metaTags->setSiteName('Lunar Blog');

        $this->socialShare = new SocialShareService();

        $this->performance = new PerformanceHelper();
        $this->performance
            ->addPreconnect('https://fonts.googleapis.com')
            ->addPreconnect('https://fonts.gstatic.com', true);

        $this->minification = new MinificationService();

        $this->wordStats = new WordStatisticsService();

        $this->dateFormat = new DateFormatHelper('fr');

        $this->excerptGenerator = new ExcerptGenerator();

        // Initialize template engine
        $cachePath = dirname($this->outputPath) . '/cache/templates';
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        $this->templateEngine = new AdvancedTemplateEngine($this->templatePath, $cachePath);
    }

    /**
     * Configure le service de catégories.
     */
    public function setCategoryService(CategoryService $categoryService): void
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Active/désactive la minification.
     */
    public function setEnableMinification(bool $enable): self
    {
        $this->enableMinification = $enable;
        return $this;
    }

    /**
     * Active/désactive le lazy loading.
     */
    public function setEnableLazyLoading(bool $enable): self
    {
        $this->enableLazyLoading = $enable;
        return $this;
    }

    /**
     * Active/désactive le dark mode.
     */
    public function setEnableDarkMode(bool $enable): self
    {
        $this->enableDarkMode = $enable;
        return $this;
    }

    /**
     * Génère le fichier HTML d'un article avec toutes les améliorations.
     */
    public function generatePost(Post $post): void
    {
        // Parse le Markdown
        $htmlContent = $this->markdownParser->parse($post->getContent());

        // Appliquer le code highlighting
        $htmlContent = $this->codeHighlighter->processContent($htmlContent);

        // Ajouter les liens d'ancrage aux titres
        $htmlContent = $this->anchorLinks->processContent($htmlContent);

        // Lazy loading des images (sauf la première pour LCP)
        if ($this->enableLazyLoading) {
            $htmlContent = $this->lazyLoad->processContent($htmlContent, true);
        }

        // Améliorer l'accessibilité
        $htmlContent = $this->accessibility->processContent($htmlContent);

        // Calculer les statistiques de texte
        $stats = $this->wordStats->analyze($post->getContent());

        // Récupérer la catégorie
        $categoryName = '';
        $categorySlug = '';
        if ($this->categoryService !== null && $post->getCategoryId() !== null) {
            $category = $this->categoryService->find($post->getCategoryId());
            $categoryName = $category?->getName() ?? '';
            $categorySlug = $this->slugify($categoryName);
        }

        // Articles similaires
        $relatedPosts = $this->findRelatedPosts($post, 4);

        // Note moyenne
        $averageRating = $post->getAverageRating();

        // Date formatée
        $publishedAt = $post->getPublishedAt();
        $formattedDate = $publishedAt ? $this->dateFormat->format($publishedAt) : '';
        $relativeDate = $publishedAt ? $this->dateFormat->formatRelative($publishedAt) : '';

        // Générer les meta tags
        $metaTagsHtml = $this->metaTags->generateAll([
            'title' => $post->getTitle(),
            'description' => $post->getExcerpt(),
            'author' => $post->getAuthor(),
            'image' => $post->getFeaturedImage(),
            'url' => $this->siteUrl . $post->getUrl(),
            'type' => 'article',
            'datePublished' => $publishedAt?->format('c'),
        ]);

        // Générer le Schema.org
        $schemaOrg = $this->schemaOrg->blogPosting([
            'title' => $post->getTitle(),
            'description' => $post->getExcerpt(),
            'author' => $post->getAuthor(),
            'datePublished' => $publishedAt?->format('c'),
            'dateModified' => $post->getUpdatedAt()?->format('c'),
            'image' => $post->getFeaturedImage(),
            'url' => $this->siteUrl . $post->getUrl(),
            'wordCount' => $stats['words'],
            'articleSection' => $categoryName,
            'keywords' => $post->getTags(),
        ]);

        // Générer les boutons de partage
        $shareButtons = $this->socialShare->generateButtons(
            $this->siteUrl . $post->getUrl(),
            $post->getTitle(),
            $post->getExcerpt()
        );

        // Générer le temps de lecture formaté
        $readingTimeFormatted = $this->dateFormat->formatReadingTime($stats['reading_time']);

        // Construire les injections head
        $headInjections = $this->buildHeadInjections();

        // Construire les injections body (avant </body>)
        $bodyEndInjections = $this->buildBodyEndInjections();

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
            'published_at_formatted' => $formattedDate,
            'published_at_relative' => $relativeDate,
            'reading_time' => $stats['reading_time'],
            'reading_time_formatted' => $readingTimeFormatted,
            'word_count' => $stats['words'],
            'url' => $post->getUrl(),
            'year' => date('Y'),
            'featured_image' => $post->getFeaturedImage() ?? '',
            'category' => $categoryName,
            'category_slug' => $categorySlug,
            'average_rating' => $averageRating > 0 ? number_format($averageRating, 1) : '0',
            'license' => $post->getLicense() ?? '',
            'original_url' => $post->getOriginalUrl() ?? '',
            'original_source' => $post->getOriginalSource() ?? '',
            'meta_tags' => $metaTagsHtml,
            'schema_org' => '<script type="application/ld+json">' . $schemaOrg . '</script>',
            'share_buttons' => $shareButtons,
            'head_injections' => $headInjections,
            'body_end_injections' => $bodyEndInjections,
            'tags' => $post->getTags(),
            'sources' => $post->getSources(),
            'related_posts' => $relatedPosts,
            // JSON data for JavaScript
            'tags_json' => json_encode($post->getTags()),
            'sources_json' => json_encode($post->getSources()),
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

        // Minifier si activé
        if ($this->enableMinification) {
            $html = $this->minification->html($html);
        }

        $this->writeFile('posts/' . $post->getSlug() . '.html', $html);

        foreach ($this->publishCallbacks as $callback) {
            $callback($post);
        }
    }

    /**
     * Construit les injections pour le head.
     */
    private function buildHeadInjections(): string
    {
        $parts = [];

        // Resource hints pour la performance
        $parts[] = $this->performance->generateResourceHints();

        // No-flash script pour le dark mode
        if ($this->enableDarkMode) {
            $parts[] = '<script>' . $this->darkMode->generateNoFlashScript() . '</script>';
        }

        // CSS des services
        $inlineCss = '';

        // Lazy loading CSS
        if ($this->enableLazyLoading) {
            $inlineCss .= $this->lazyLoad->generateCss();
        }

        // Anchor links CSS
        $inlineCss .= $this->anchorLinks->generateCss();

        // Code highlighter CSS
        $inlineCss .= $this->codeHighlighter->generateCss('dark');

        // Accessibility CSS
        $inlineCss .= $this->accessibility->generateCss();

        if ($inlineCss) {
            $parts[] = '<style>' . $this->minification->css($inlineCss) . '</style>';
        }

        // Print styles
        $parts[] = '<style media="print">' . $this->minification->css($this->printStyles->generateCss()) . '</style>';

        return implode("\n", $parts);
    }

    /**
     * Construit les injections pour la fin du body.
     */
    private function buildBodyEndInjections(): string
    {
        $parts = [];

        // Dark mode JS
        if ($this->enableDarkMode) {
            $parts[] = '<script>' . $this->darkMode->generateJs() . '</script>';
        }

        // Lazy loading JS
        if ($this->enableLazyLoading) {
            $parts[] = '<script>' . $this->lazyLoad->generateJs() . '</script>';
        }

        // Anchor links JS (smooth scroll)
        $parts[] = '<script>' . $this->anchorLinks->generateJs() . '</script>';

        return implode("\n", $parts);
    }

    /**
     * Génère la page d'index du blog.
     */
    public function generateIndex(): void
    {
        $posts = $this->postService->findPublished();

        usort($posts, fn($a, $b) => $b->getPublishedAt() <=> $a->getPublishedAt());

        // Collecter tous les tags uniques
        $allTags = [];
        foreach ($posts as $post) {
            foreach ($post->getTags() as $tag) {
                $allTags[$tag] = ($allTags[$tag] ?? 0) + 1;
            }
        }
        arsort($allTags);

        // Collecter toutes les catégories
        $allCategories = [];
        foreach ($posts as $post) {
            if ($post->getCategoryId() !== null) {
                $allCategories[$post->getCategoryId()] = true;
            }
        }

        // Générer le HTML des tags
        $tagsHtml = '';
        foreach ($allTags as $tag => $count) {
            $tagStr = (string) $tag;
            $slug = $this->slugify($tagStr);
            $tagsHtml .= sprintf(
                '<a href="/blog/tags/%s.html" class="tag-pill">%s <span class="tag-count">%d</span></a>',
                htmlspecialchars($slug),
                htmlspecialchars($tagStr),
                $count
            );
        }

        // Hero slider
        $sliderHtml = $this->generateHeroSlider(array_slice($posts, 0, 10));

        // Préparer les données des posts
        $postsData = array_map(function($post) {
            $categoryName = '';
            $categorySlug = '';
            if ($this->categoryService !== null && $post->getCategoryId() !== null) {
                $category = $this->categoryService->find($post->getCategoryId());
                $categoryName = $category?->getName() ?? '';
                $categorySlug = $this->slugify($categoryName);
            }

            $avgRating = $post->getAverageRating();

            return [
                'title' => $post->getTitle(),
                'url' => $post->getUrl(),
                'excerpt' => $post->getExcerpt() ?? '',
                'author' => $post->getAuthor() ?? '',
                'published_at' => $post->getPublishedAt()?->format('d/m/Y') ?? '',
                'published_at_relative' => $post->getPublishedAt()
                    ? $this->dateFormat->formatRelative($post->getPublishedAt())
                    : '',
                'reading_time' => $post->getReadingTime(),
                'featured_image' => $post->getFeaturedImage() ?? '',
                'category' => $categoryName,
                'category_slug' => $categorySlug,
                'slug' => $post->getSlug(),
                'tags_string' => implode(', ', $post->getTags()),
                'average_rating' => $avgRating > 0 ? number_format($avgRating, 1) : '',
                'rating_stars' => $this->generateRatingStarsHtml($avgRating),
            ];
        }, $posts);

        // Schema.org pour la page d'index
        $schemaOrg = $this->schemaOrg->website([
            'searchUrl' => $this->siteUrl . '/blog/?q={search_term_string}',
            'description' => 'Blog Lunar Quanta - Articles sur le développement et les technologies',
        ]);

        // Head injections
        $headInjections = $this->buildHeadInjections();

        // Body end injections
        $bodyEndInjections = $this->buildBodyEndInjections();

        // Utiliser le moteur de template lunar-template
        $html = $this->templateEngine->render('index.html', [
            'posts' => $postsData,
            'year' => date('Y'),
            'article_count' => count($posts),
            'categories_count' => count($allCategories),
            'tags_count' => count($allTags),
            'tags_list' => $tagsHtml,
            'slider_items' => $sliderHtml,
            'schema_org' => '<script type="application/ld+json">' . $schemaOrg . '</script>',
            'head_injections' => $headInjections,
            'body_end_injections' => $bodyEndInjections,
            'dark_mode_toggle' => $this->darkMode->generateToggle(),
        ]);

        if ($this->enableMinification) {
            $html = $this->minification->html($html);
        }

        $this->writeFile('index.html', $html);
    }

    /**
     * Génère le HTML du hero slider.
     *
     * @param Post[] $posts
     */
    private function generateHeroSlider(array $posts): string
    {
        $sliderHtml = '';

        foreach ($posts as $index => $post) {
            $categoryName = '';
            if ($this->categoryService !== null && $post->getCategoryId() !== null) {
                $category = $this->categoryService->find($post->getCategoryId());
                $categoryName = $category?->getName() ?? '';
            }

            $featuredImage = $post->getFeaturedImage() ?? '';

            // Premier slide : pas de lazy loading (LCP)
            if ($index === 0 && $featuredImage) {
                $imgHtml = sprintf(
                    '<img src="%s" alt="%s" fetchpriority="high">',
                    htmlspecialchars($featuredImage),
                    htmlspecialchars($post->getTitle())
                );
            } elseif ($featuredImage && $this->enableLazyLoading) {
                $placeholder = $this->lazyLoad->generatePlaceholder(1200, 600);
                $imgHtml = sprintf(
                    '<img src="%s" data-src="%s" alt="%s" class="lazy lazy-fade" loading="lazy">',
                    $placeholder,
                    htmlspecialchars($featuredImage),
                    htmlspecialchars($post->getTitle())
                );
            } else {
                $imgHtml = sprintf(
                    '<img src="%s" alt="%s">',
                    htmlspecialchars($featuredImage),
                    htmlspecialchars($post->getTitle())
                );
            }

            $sliderHtml .= sprintf(
                '<article class="la-hero-slide">
                    <div class="la-hero-slide-image">%s</div>
                    <div class="la-hero-slide-content">
                        <span class="la-hero-slide-category">%s</span>
                        <h2 class="la-hero-slide-title">%s</h2>
                        <p class="la-hero-slide-excerpt">%s</p>
                        <div class="la-hero-slide-meta">
                            <span>%s</span>
                            <span>•</span>
                            <span>%d min de lecture</span>
                        </div>
                        <a href="%s" class="la-hero-slide-link">Lire l\'article <span class="la-icon">arrow_forward</span></a>
                    </div>
                </article>',
                $imgHtml,
                htmlspecialchars($categoryName),
                htmlspecialchars($post->getTitle()),
                htmlspecialchars($post->getExcerpt() ?? ''),
                $post->getPublishedAt()?->format('d M Y') ?? '',
                $post->getReadingTime() ?? 5,
                htmlspecialchars($post->getUrl())
            );
        }

        return $sliderHtml;
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

        // Générer les fichiers CSS/JS combinés
        $this->generateAssets();

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
     * Génère les assets CSS/JS.
     */
    private function generateAssets(): void
    {
        // Générer le CSS combiné pour les services
        $css = '';
        $css .= "/* Lazy Loading */\n" . $this->lazyLoad->generateCss() . "\n";
        $css .= "/* Anchor Links */\n" . $this->anchorLinks->generateCss() . "\n";
        $css .= "/* Code Highlighting */\n" . $this->codeHighlighter->generateCss('dark') . "\n";
        $css .= "/* Accessibility */\n" . $this->accessibility->generateCss() . "\n";
        $css .= "/* Dark Mode */\n" . $this->darkMode->generateCss() . "\n";

        if ($this->enableMinification) {
            $css = $this->minification->css($css);
        }

        $this->writeFile('assets/enhanced.css', $css);

        // Générer le JS combiné
        $js = '';
        $js .= "/* Dark Mode */\n" . $this->darkMode->generateJs() . "\n";
        $js .= "/* Lazy Loading */\n" . $this->lazyLoad->generateJs() . "\n";
        $js .= "/* Anchor Links */\n" . $this->anchorLinks->generateJs() . "\n";

        if ($this->enableMinification) {
            $js = $this->minification->js($js);
        }

        $this->writeFile('assets/enhanced.js', $js);

        // Print styles
        $printCss = $this->printStyles->generateCss();
        if ($this->enableMinification) {
            $printCss = $this->minification->css($printCss);
        }
        $this->writeFile('assets/print.css', $printCss);
    }

    /**
     * Trouve les articles similaires.
     *
     * @return array<int, array{title: string, url: string, excerpt: string, featured_image: string}>
     */
    private function findRelatedPosts(Post $currentPost, int $limit = 4): array
    {
        $allPosts = $this->postService->findPublished();
        $currentTags = $currentPost->getTags();
        $currentCategoryId = $currentPost->getCategoryId();

        $scored = [];
        foreach ($allPosts as $post) {
            if ($post->getId() === $currentPost->getId()) {
                continue;
            }

            $score = 0;

            if ($currentCategoryId !== null && $post->getCategoryId() === $currentCategoryId) {
                $score += 10;
            }

            $commonTags = array_intersect($currentTags, $post->getTags());
            $score += count($commonTags) * 5;

            if ($score > 0) {
                $scored[] = ['post' => $post, 'score' => $score];
            }
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        $related = array_slice($scored, 0, $limit);

        return array_map(fn($item) => [
            'title' => $item['post']->getTitle(),
            'url' => $item['post']->getUrl(),
            'excerpt' => $item['post']->getExcerpt(),
            'featured_image' => $item['post']->getFeaturedImage() ?? '',
        ], $related);
    }

    /**
     * Génère les pages de tags avec progression.
     */
    private function generateTagPagesWithProgress(bool $reportProgress = true): int
    {
        $templatePath = $this->templatePath . '/tag.html';
        if (!file_exists($templatePath)) {
            $templatePath = $this->templatePath . '/tag.html.tpl';
            if (!file_exists($templatePath)) {
                return 0;
            }
        }

        $template = file_get_contents($templatePath);
        $posts = $this->postService->findPublished();

        $taggedPosts = [];
        foreach ($posts as $post) {
            foreach ($post->getTags() as $tag) {
                $taggedPosts[$tag][] = $post;
            }
        }

        $total = count($taggedPosts);
        $count = 0;
        foreach ($taggedPosts as $tag => $tagPosts) {
            $count++;
            if ($reportProgress) {
                $this->reportProgress($count, $total, 'tag', (string) $tag);
            }
            $this->generateTagPage($template, (string) $tag, $tagPosts);
        }

        return $count;
    }

    /**
     * Génère une page de tag.
     *
     * @param Post[] $posts
     */
    private function generateTagPage(string $template, string $tag, array $posts): void
    {
        $postsData = array_map(fn($post) => [
            'title' => $post->getTitle(),
            'url' => $post->getUrl(),
            'excerpt' => $post->getExcerpt(),
            'author' => $post->getAuthor(),
            'published_at' => $post->getPublishedAt()?->format('d/m/Y'),
            'featured_image' => $post->getFeaturedImage() ?? '',
        ], $posts);

        $html = $this->processLengthCondition($template, 'posts', count($postsData) > 0);
        $html = $this->renderWithLoop($html, 'posts', $postsData);
        $html = str_replace('{{ tag }}', htmlspecialchars($tag), $html);
        $html = str_replace('{{ count }}', (string) count($posts), $html);
        $html = str_replace('{{ year }}', date('Y'), $html);
        $html = str_replace('{{ head_injections }}', $this->buildHeadInjections(), $html);
        $html = str_replace('{{ body_end_injections }}', $this->buildBodyEndInjections(), $html);

        if ($this->enableMinification) {
            $html = $this->minification->html($html);
        }

        $this->writeFile('tags/' . $this->slugify($tag) . '.html', $html);
    }

    /**
     * Génère les pages de catégories avec progression.
     */
    private function generateCategoryPagesWithProgress(bool $reportProgress = true): int
    {
        if ($this->categoryService === null) {
            return 0;
        }

        $templatePath = $this->templatePath . '/category.html';
        if (!file_exists($templatePath)) {
            $templatePath = $this->templatePath . '/category.html.tpl';
            if (!file_exists($templatePath)) {
                return 0;
            }
        }

        $template = file_get_contents($templatePath);
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

            $this->generateCategoryPage($template, $category, array_values($categoryPosts));
        }

        return $count;
    }

    /**
     * Génère une page de catégorie.
     *
     * @param Post[] $posts
     */
    private function generateCategoryPage(string $template, Category $category, array $posts): void
    {
        $postsData = array_map(fn($post) => [
            'title' => $post->getTitle(),
            'url' => $post->getUrl(),
            'excerpt' => $post->getExcerpt(),
            'author' => $post->getAuthor(),
            'published_at' => $post->getPublishedAt()?->format('d/m/Y'),
            'reading_time' => $post->getReadingTime(),
            'featured_image' => $post->getFeaturedImage() ?? '',
        ], $posts);

        $html = $this->processLengthCondition($template, 'posts', count($postsData) > 0);
        $html = $this->renderWithLoop($html, 'posts', $postsData);
        $html = str_replace('{{ category_name }}', htmlspecialchars($category->getName()), $html);
        $html = str_replace('{{ category_description }}', htmlspecialchars($category->getDescription()), $html);
        $html = str_replace('{{ category_color }}', htmlspecialchars($category->getColor()), $html);
        $html = str_replace('{{ count }}', (string) count($posts), $html);
        $html = str_replace('{{ year }}', date('Y'), $html);
        $html = str_replace('{{ head_injections }}', $this->buildHeadInjections(), $html);
        $html = str_replace('{{ body_end_injections }}', $this->buildBodyEndInjections(), $html);

        if ($this->enableMinification) {
            $html = $this->minification->html($html);
        }

        $this->writeFile('categories/' . $category->getSlug() . '.html', $html);
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
        file_put_contents(dirname($this->outputPath) . '/sitemap.xml', $sitemap);

        return true;
    }

    /**
     * Supprime tous les fichiers générés.
     */
    public function clean(): void
    {
        $this->removeDirectory($this->outputPath . '/posts');
        $this->removeDirectory($this->outputPath . '/tags');
        $this->removeDirectory($this->outputPath . '/categories');
        $this->removeDirectory($this->outputPath . '/assets');
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
     * Enregistre un callback de publication.
     */
    public function onPublish(callable $callback): void
    {
        $this->publishCallbacks[] = $callback;
    }

    /**
     * Définit un callback de progression.
     */
    public function onProgress(callable $callback): void
    {
        $this->progressCallback = $callback;
    }

    private function reportProgress(int $current, int $total, string $type, string $item): void
    {
        if ($this->progressCallback !== null) {
            ($this->progressCallback)($current, $total, $type, $item);
        }
    }

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
     * @param array<string, mixed> $variables
     */
    private function render(string $template, array $variables): string
    {
        $html = $template;
        $html = $this->processConditions($html, $variables);

        foreach ($variables as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $html = str_replace('{{ ' . $key . ' }}', (string) $value, $html);
            }
        }

        $html = preg_replace('/\{\{\s*\w+\s*\}\}/', '', $html);

        return $html;
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function processConditions(string $html, array $variables): string
    {
        $pattern = '/\{%\s*if\s+(\w+)\s*%\}(.*?)\{%\s*endif\s*%\}/s';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $var = $matches[1];
            $content = $matches[2];

            if (isset($variables[$var]) && !empty($variables[$var])) {
                return $content;
            }

            return '';
        }, $html);
    }

    private function processLengthCondition(string $html, string $var, bool $hasItems): string
    {
        $startPattern = '/\{%\s*if\s+' . preg_quote($var, '/') . '\|length\s*>\s*0\s*%\}/';
        if (!preg_match($startPattern, $html, $startMatch, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $startPos = $startMatch[0][1];
        $startLen = strlen($startMatch[0][0]);
        $content = substr($html, $startPos + $startLen);

        $elsePos = null;
        $endPos = null;
        $endLen = 0;
        $level = 1;
        $pos = 0;

        while ($level > 0 && preg_match('/\{%\s*(if|else|endif)\b[^}]*%\}/', $content, $match, PREG_OFFSET_CAPTURE, $pos)) {
            $tag = $match[1][0];
            $tagPos = $match[0][1];
            $tagLen = strlen($match[0][0]);

            if ($tag === 'if') {
                $level++;
            } elseif ($tag === 'else' && $level === 1) {
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

        if ($elsePos !== null) {
            $ifContent = substr($content, 0, $elsePos);
            $elseContent = substr($content, $elsePos + $elseLen, $endPos - $elsePos - $elseLen);
        } else {
            $ifContent = substr($content, 0, $endPos);
            $elseContent = '';
        }

        $before = substr($html, 0, $startPos);
        $after = substr($html, $startPos + $startLen + $endPos + $endLen);
        $replacement = $hasItems ? $ifContent : $elseContent;

        return $before . $replacement . $after;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function renderWithLoop(string $template, string $loopVar, array $items): string
    {
        $pattern = '/\{%\s*for\s+(\w+)\s+in\s+' . preg_quote($loopVar, '/') . '\s*%\}(.*?)\{%\s*endfor\s*%\}/s';

        if (!preg_match($pattern, $template, $matches)) {
            return $template;
        }

        $itemVar = $matches[1];
        $loopTemplate = $matches[2];

        $loopContent = '';
        foreach ($items as $item) {
            $itemHtml = $loopTemplate;

            // Process conditions
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

            // Process variable replacements
            if (is_array($item)) {
                foreach ($item as $key => $value) {
                    if (is_string($value) || is_numeric($value)) {
                        $itemHtml = str_replace('{{ ' . $itemVar . '.' . $key . ' }}', (string) $value, $itemHtml);
                        $itemHtml = str_replace('{{ ' . $itemVar . '.' . $key . '|lower }}', strtolower((string) $value), $itemHtml);
                        $itemHtml = str_replace('{{ ' . $itemVar . '.' . $key . '|upper }}', strtoupper((string) $value), $itemHtml);
                        $itemHtml = str_replace('{{ ' . $itemVar . '.' . $key . '|slug }}', $this->slugify((string) $value), $itemHtml);
                    }
                }
            }

            $loopContent .= $itemHtml;
        }

        return preg_replace($pattern, $loopContent, $template);
    }

    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    private function writeFile(string $relativePath, string $content): void
    {
        $fullPath = $this->outputPath . '/' . $relativePath;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);
    }

    private function removeFile(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

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

    private function ensureDirectories(): void
    {
        $dirs = [
            $this->outputPath,
            $this->outputPath . '/posts',
            $this->outputPath . '/tags',
            $this->outputPath . '/categories',
            $this->outputPath . '/assets',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    private function generateRatingStarsHtml(float $rating): string
    {
        if ($rating <= 0) {
            return '';
        }

        $html = '';
        $fullStars = (int) floor($rating);
        $hasHalf = ($rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($hasHalf ? 1 : 0);

        for ($i = 0; $i < $fullStars; $i++) {
            $html .= '<span class="la-rating-star filled"><span class="la-icon xs">star</span></span>';
        }

        if ($hasHalf) {
            $html .= '<span class="la-rating-star half"><span class="la-icon xs">star_half</span></span>';
        }

        for ($i = 0; $i < $emptyStars; $i++) {
            $html .= '<span class="la-rating-star"><span class="la-icon xs">star_outline</span></span>';
        }

        return $html;
    }
}
