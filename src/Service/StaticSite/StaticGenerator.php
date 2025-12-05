<?php

declare(strict_types=1);

namespace Lunar\Service\StaticSite;

use Lunar\Entity\Category;
use Lunar\Entity\Post;
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;

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

    private ?RssGenerator $rssGenerator = null;
    private ?SitemapGenerator $sitemapGenerator = null;
    private ?CategoryService $categoryService = null;

    public function __construct(
        private readonly PostService $postService,
        private readonly MarkdownParser $markdownParser,
        private readonly string $outputPath,
        private readonly string $templatePath,
        private readonly string $siteUrl = ''
    ) {
        $this->ensureDirectories();

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
    }

    /**
     * Génère le fichier HTML d'un article.
     */
    public function generatePost(Post $post): void
    {
        $template = $this->loadTemplate('post.html');
        $htmlContent = $this->markdownParser->parse($post->getContent());

        $html = $this->render($template, [
            'title' => $post->getTitle(),
            'content' => $htmlContent,
            'excerpt' => $post->getExcerpt(),
            'author' => $post->getAuthor(),
            'published_at' => $post->getPublishedAt()?->format('d/m/Y'),
            'reading_time' => $post->getReadingTime(),
            'url' => $post->getUrl(),
            'year' => date('Y'),
        ]);

        $this->writeFile('posts/' . $post->getSlug() . '.html', $html);

        // Déclencher les callbacks
        foreach ($this->publishCallbacks as $callback) {
            $callback($post);
        }
    }

    /**
     * Génère la page d'index du blog.
     */
    public function generateIndex(): void
    {
        $template = $this->loadTemplate('index.html');
        $posts = $this->postService->findPublished();

        // Trier par date décroissante
        usort($posts, fn($a, $b) => $b->getPublishedAt() <=> $a->getPublishedAt());

        // Préparer les données des posts
        $postsData = array_map(fn($post) => [
            'title' => $post->getTitle(),
            'url' => $post->getUrl(),
            'excerpt' => $post->getExcerpt(),
            'author' => $post->getAuthor(),
            'published_at' => $post->getPublishedAt()?->format('d/m/Y'),
            'reading_time' => $post->getReadingTime(),
        ], $posts);

        // Gérer {% if posts|length > 0 %} ... {% else %} ... {% endif %}
        $html = $this->processLengthCondition($template, 'posts', count($postsData) > 0);

        $html = $this->renderWithLoop($html, 'posts', $postsData);

        // Remplacer les variables globales
        $html = str_replace('{{ year }}', date('Y'), $html);

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
        $count = 0;

        foreach ($posts as $post) {
            $this->generatePost($post);
            $count++;
        }

        $this->generateIndex();
        $tagsCount = $this->generateTagPages();
        $categoriesCount = $this->generateCategoryPages();
        $rss = $this->generateRss();
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
        $templatePath = $this->templatePath . '/tag.html';
        if (!file_exists($templatePath)) {
            return 0;
        }

        $template = file_get_contents($templatePath);
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
        $count = 0;
        foreach ($taggedPosts as $tag => $tagPosts) {
            $this->generateTagPage($template, $tag, $tagPosts);
            $count++;
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
        ], $posts);

        $html = $this->processLengthCondition($template, 'posts', count($postsData) > 0);
        $html = $this->renderWithLoop($html, 'posts', $postsData);
        $html = str_replace('{{ tag }}', htmlspecialchars($tag), $html);
        $html = str_replace('{{ count }}', (string) count($posts), $html);
        $html = str_replace('{{ year }}', date('Y'), $html);

        $this->writeFile('tags/' . $this->slugify($tag) . '.html', $html);
    }

    /**
     * Génère les pages de catégories.
     *
     * @return int Nombre de pages générées
     */
    public function generateCategoryPages(): int
    {
        if ($this->categoryService === null) {
            return 0;
        }

        $templatePath = $this->templatePath . '/category.html';
        if (!file_exists($templatePath)) {
            return 0;
        }

        $template = file_get_contents($templatePath);
        $categories = $this->categoryService->all();
        $posts = $this->postService->findPublished();

        $count = 0;
        foreach ($categories as $category) {
            $categoryPosts = array_filter(
                $posts,
                fn(Post $post) => $post->getCategoryId() === $category->getId()
            );

            $this->generateCategoryPage($template, $category, array_values($categoryPosts));
            $count++;
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
        ], $posts);

        $html = $this->processLengthCondition($template, 'posts', count($postsData) > 0);
        $html = $this->renderWithLoop($html, 'posts', $postsData);
        $html = str_replace('{{ category_name }}', htmlspecialchars($category->getName()), $html);
        $html = str_replace('{{ category_description }}', htmlspecialchars($category->getDescription()), $html);
        $html = str_replace('{{ category_color }}', htmlspecialchars($category->getColor()), $html);
        $html = str_replace('{{ count }}', (string) count($posts), $html);
        $html = str_replace('{{ year }}', date('Y'), $html);

        $this->writeFile('categories/' . $category->getSlug() . '.html', $html);
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
     * Charge un template.
     */
    private function loadTemplate(string $name): string
    {
        $path = $this->templatePath . '/' . $name;

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
     * Traite {% if var|length > 0 %} ... {% else %} ... {% endif %}.
     */
    private function processLengthCondition(string $html, string $var, bool $hasItems): string
    {
        // Pattern avec {% else %}
        $patternWithElse = '/\{%\s*if\s+' . preg_quote($var, '/') . '\|length\s*>\s*0\s*%\}(.*?)\{%\s*else\s*%\}(.*?)\{%\s*endif\s*%\}/s';

        if (preg_match($patternWithElse, $html, $matches)) {
            $ifContent = $matches[1];
            $elseContent = $matches[2];
            return preg_replace($patternWithElse, $hasItems ? $ifContent : $elseContent, $html);
        }

        // Pattern sans {% else %}
        $patternNoElse = '/\{%\s*if\s+' . preg_quote($var, '/') . '\|length\s*>\s*0\s*%\}(.*?)\{%\s*endif\s*%\}/s';

        if (preg_match($patternNoElse, $html, $matches)) {
            $ifContent = $matches[1];
            return preg_replace($patternNoElse, $hasItems ? $ifContent : '', $html);
        }

        return $html;
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

            // Gérer les conditions {% if post.var %}
            $condPattern = '/\{%\s*if\s+' . preg_quote($itemVar, '/') . '\.(\w+)\s*%\}(.*?)\{%\s*endif\s*%\}/s';
            $itemHtml = preg_replace_callback($condPattern, function ($matches) use ($item) {
                $var = $matches[1];
                $content = $matches[2];
                if (isset($item[$var]) && !empty($item[$var])) {
                    return $content;
                }
                return '';
            }, $itemHtml);

            foreach ($item as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $itemHtml = str_replace('{{ ' . $itemVar . '.' . $key . ' }}', (string) $value, $itemHtml);
                }
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
}
