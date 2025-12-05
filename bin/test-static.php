#!/usr/bin/env php
<?php
/**
 * Script de test pour vérifier le bon fonctionnement du site statique.
 *
 * Ce script :
 * 1. Crée des articles de test
 * 2. Génère le site statique
 * 3. Vérifie que tous les fichiers existent
 * 4. Affiche un rapport
 *
 * Usage: php bin/test-static.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\StaticSite\StaticGenerator;
use Lunar\Service\Storage\FileStorage;

$basePath = dirname(__DIR__);

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║           Lunar Quanta - Static Site Test                ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Initialisation des services
echo "→ Initializing services...\n";

$postService = new PostService(
    new FileStorage($basePath . '/data/blog/posts')
);

$categoryService = new CategoryService(
    new FileStorage($basePath . '/data/blog/categories')
);

$generator = new StaticGenerator(
    $postService,
    new MarkdownParser(),
    $basePath . '/public/blog',
    $basePath . '/template/blog',
    'https://example.com'
);

$generator->setCategoryService($categoryService);

// Statistiques avant génération
$stats = [
    'total_posts' => $postService->count(),
    'published_posts' => count($postService->findPublished()),
    'draft_posts' => count($postService->findDrafts()),
    'categories' => $categoryService->count(),
];

echo "\n📊 Current state:\n";
echo "   Total posts: {$stats['total_posts']}\n";
echo "   Published: {$stats['published_posts']}\n";
echo "   Drafts: {$stats['draft_posts']}\n";
echo "   Categories: {$stats['categories']}\n";

// Si aucun article, créer des exemples
if ($stats['published_posts'] === 0) {
    echo "\n⚠️  No published posts found. Creating test data...\n";

    // Créer une catégorie
    $category = $categoryService->create('Test Category');
    $category->setDescription('Test category for static site generation');
    $categoryService->update($category);

    // Créer un article
    $post = $postService->create('Test Article', <<<'MD'
# Welcome to the Test Article

This is a test article for the static site generator.

## Features

- Markdown support
- Tag support
- Category support

## Code Example

```php
echo "Hello, World!";
```

> This is a quote

Enjoy your reading!
MD
    );
    $post->setExcerpt('This is a test article for the static site generator.');
    $post->setAuthor('Test Author');
    $post->setCategoryId($category->getId());
    $post->addTag('test');
    $post->addTag('example');
    $postService->update($post);
    $postService->publish($post->getId());

    echo "   ✓ Created test category\n";
    echo "   ✓ Created and published test article\n";
}

// Génération du site statique
echo "\n→ Generating static site...\n";
$startTime = microtime(true);

$result = $generator->generateAll();

$duration = round((microtime(true) - $startTime) * 1000);

echo "\n✅ Generation complete in {$duration}ms!\n";
echo "\n📁 Generated files:\n";
echo "   Posts: {$result['posts']}\n";
echo "   Index: " . ($result['index'] ? '✓' : '✗') . "\n";
echo "   RSS: " . ($result['rss'] ? '✓' : '✗') . "\n";
echo "   Sitemap: " . ($result['sitemap'] ? '✓' : '✗') . "\n";
echo "   Tag pages: {$result['tags']}\n";
echo "   Category pages: {$result['categories']}\n";

// Vérification des fichiers
echo "\n→ Verifying generated files...\n";

$outputPath = $basePath . '/public/blog';
$checks = [
    'Index' => $outputPath . '/index.html',
    'RSS feed' => $outputPath . '/feed.xml',
    'Sitemap' => $basePath . '/public/sitemap.xml',
    'Posts directory' => $outputPath . '/posts',
];

$allOk = true;
foreach ($checks as $name => $path) {
    $exists = file_exists($path);
    $status = $exists ? '✓' : '✗';
    echo "   {$status} {$name}: " . ($exists ? 'OK' : 'MISSING') . "\n";
    if (!$exists) {
        $allOk = false;
    }
}

// Vérifier les posts
if ($result['posts'] > 0) {
    $postsDir = $outputPath . '/posts';
    $htmlFiles = glob($postsDir . '/*.html');
    echo "   ✓ Found " . count($htmlFiles) . " post HTML files\n";
}

// Résumé final
echo "\n╔═══════════════════════════════════════════════════════════╗\n";
if ($allOk) {
    echo "║  ✅ All checks passed! Static site is ready.              ║\n";
} else {
    echo "║  ❌ Some checks failed. Check the output above.           ║\n";
}
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║  Blog URL: /blog/                                         ║\n";
echo "║  RSS URL:  /blog/feed.xml                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";

exit($allOk ? 0 : 1);
