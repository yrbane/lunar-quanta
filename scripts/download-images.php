<?php
/**
 * Script pour télécharger des images uniques pour tous les articles.
 *
 * Sources utilisées :
 * - Picsum Photos (https://picsum.photos)
 * - Lorem.space
 * - PlaceImg (fallback)
 * - LoremFlickr
 *
 * Usage: php scripts/download-images.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

$basePath = dirname(__DIR__);

// Configuration
$imagesDir = $basePath . '/public/blog/images';
$width = 1200;
$height = 630;

// Ensure images directory exists
if (!is_dir($imagesDir)) {
    mkdir($imagesDir, 0755, true);
    echo "✓ Répertoire images créé : {$imagesDir}\n";
}

// Initialize services
$postStorage = new FileStorage($basePath . '/data/blog/posts');
$postService = new PostService($postStorage);

$posts = $postService->all();
$total = count($posts);

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║        TÉLÉCHARGEMENT D'IMAGES UNIQUES POUR ARTICLES         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "→ {$total} articles à traiter\n";
echo "→ Dimensions : {$width}x{$height}\n";
echo "→ Répertoire : {$imagesDir}\n\n";

// Keywords by category for more relevant images
$categoryKeywords = [
    // AI & Tech
    'ai' => ['technology', 'computer', 'robot', 'circuit', 'data', 'network'],
    'machine-learning' => ['algorithm', 'data', 'neural', 'computer', 'tech'],
    'deep-learning' => ['neural', 'brain', 'network', 'technology'],
    'nlp' => ['language', 'text', 'communication', 'typing'],
    'computer-vision' => ['camera', 'eye', 'vision', 'digital'],
    'robotics' => ['robot', 'machine', 'automation', 'mechanical'],
    'llm' => ['chat', 'text', 'ai', 'conversation'],
    'generative-ai' => ['art', 'creative', 'digital', 'design'],
    'ai-agents' => ['automation', 'robot', 'intelligent', 'system'],
    'ai-ethics' => ['balance', 'ethics', 'thinking', 'philosophy'],

    // Quantum
    'quantum' => ['physics', 'abstract', 'energy', 'particle'],
    'quantum-computing' => ['computer', 'circuit', 'quantum', 'physics'],
    'quantum-hardware' => ['hardware', 'chip', 'technology', 'circuit'],
    'quantum-software' => ['code', 'software', 'programming'],
    'quantum-algorithms' => ['math', 'algorithm', 'formula'],
    'quantum-error' => ['error', 'correction', 'digital'],
    'quantum-cryptography' => ['security', 'lock', 'encryption'],
    'quantum-simulation' => ['simulation', 'virtual', 'digital'],
    'quantum-ml' => ['ai', 'quantum', 'learning'],
    'quantum-future' => ['future', 'innovation', 'technology'],

    // Biology
    'biology' => ['nature', 'biology', 'cell', 'dna', 'life'],
    'crispr' => ['dna', 'genetics', 'science', 'laboratory'],
    'gene-therapy' => ['medical', 'dna', 'therapy', 'health'],
    'synthetic-biology' => ['laboratory', 'science', 'biology'],
    'bioinformatics' => ['data', 'biology', 'computer', 'science'],
    'stem-cells' => ['cell', 'medical', 'research', 'biology'],
    'immunotherapy' => ['medical', 'health', 'immune', 'therapy'],
    'microbiome' => ['bacteria', 'microscope', 'biology'],
    'neuroscience' => ['brain', 'neuron', 'science', 'mind'],
    'biotech' => ['laboratory', 'science', 'technology', 'research'],

    // Tech general
    'framework' => ['code', 'programming', 'development', 'tech'],
    'web' => ['web', 'internet', 'network', 'browser'],
    'cloud' => ['cloud', 'server', 'network', 'data'],
    'security' => ['security', 'lock', 'shield', 'protection'],
    'devops' => ['server', 'infrastructure', 'automation'],
    'mobile' => ['phone', 'mobile', 'app', 'smartphone'],
    'iot' => ['sensor', 'device', 'smart', 'connected'],
    'blockchain' => ['chain', 'crypto', 'digital', 'network'],
    'vr' => ['virtual', 'reality', 'headset', 'immersive'],
    'ar' => ['augmented', 'reality', 'digital', 'overlay'],

    // Default
    'default' => ['technology', 'science', 'innovation', 'digital', 'modern'],
];

// Image sources with different styles
$imageSources = [
    // Picsum - beautiful random photos
    function($id, $width, $height, $keyword) {
        $seed = crc32($id . $keyword);
        return "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
    },
    // LoremFlickr - keyword-based
    function($id, $width, $height, $keyword) {
        return "https://loremflickr.com/{$width}/{$height}/{$keyword}?lock=" . crc32($id);
    },
    // Placeholder with gradient colors
    function($id, $width, $height, $keyword) {
        $colors = ['0f172a', '1e293b', '334155', '475569', '1e3a5f', '172554', '0c4a6e', '164e63', '134e4a', '14532d'];
        $color = $colors[crc32($id) % count($colors)];
        return "https://via.placeholder.com/{$width}x{$height}/{$color}/fff?text=";
    },
];

$downloaded = 0;
$skipped = 0;
$errors = 0;

foreach ($posts as $index => $post) {
    $slug = $post->getSlug();
    $imagePath = "{$imagesDir}/{$slug}.jpg";

    // Skip if image already exists
    if (file_exists($imagePath)) {
        $skipped++;
        continue;
    }

    // Determine keyword based on category or tags
    $keyword = 'technology';
    $categoryId = $post->getCategoryId();
    $tags = $post->getTags();

    // Try to find relevant keywords
    foreach ($categoryKeywords as $cat => $keywords) {
        if ($categoryId && stripos($categoryId, $cat) !== false) {
            $keyword = $keywords[array_rand($keywords)];
            break;
        }
        foreach ($tags as $tag) {
            if (stripos($tag, $cat) !== false) {
                $keyword = $keywords[array_rand($keywords)];
                break 2;
            }
        }
    }

    // Fallback to default keywords
    if ($keyword === 'technology') {
        $keyword = $categoryKeywords['default'][array_rand($categoryKeywords['default'])];
    }

    // Try each source until one works
    $success = false;
    foreach ($imageSources as $sourceIndex => $getUrl) {
        if ($sourceIndex === 2) {
            // Skip placeholder for real downloads
            continue;
        }

        $url = $getUrl($post->getId(), $width, $height, $keyword);

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; LunarQuanta/1.0)',
                'follow_location' => true,
            ],
        ]);

        $imageData = @file_get_contents($url, false, $context);

        if ($imageData !== false && strlen($imageData) > 1000) {
            if (file_put_contents($imagePath, $imageData) !== false) {
                $downloaded++;
                $success = true;

                if ($downloaded % 10 === 0) {
                    echo "  ✓ {$downloaded} images téléchargées...\n";
                }
                break;
            }
        }

        // Small delay between attempts
        usleep(100000); // 100ms
    }

    if (!$success) {
        $errors++;
        // Use picsum as final fallback with direct save
        $fallbackUrl = "https://picsum.photos/seed/" . md5($post->getId()) . "/{$width}/{$height}";
        $imageData = @file_get_contents($fallbackUrl);
        if ($imageData !== false) {
            file_put_contents($imagePath, $imageData);
            $downloaded++;
        }
    }

    // Rate limiting - be nice to free services
    usleep(200000); // 200ms between requests
}

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│                      RÉSULTATS                               │\n";
echo "├──────────────────────────────────────────────────────────────┤\n";
printf("│  %-20s %38d │\n", "Images téléchargées", $downloaded);
printf("│  %-20s %38d │\n", "Images existantes", $skipped);
printf("│  %-20s %38d │\n", "Erreurs", $errors);
echo "└──────────────────────────────────────────────────────────────┘\n";
echo "\n";

// Now update all posts to use local images
echo "→ Mise à jour des articles avec les images locales...\n";

$updated = 0;
foreach ($posts as $post) {
    $slug = $post->getSlug();
    $localPath = "/blog/images/{$slug}.jpg";
    $fullPath = "{$imagesDir}/{$slug}.jpg";

    if (file_exists($fullPath) && $post->getFeaturedImage() !== $localPath) {
        $post->setFeaturedImage($localPath);
        $postService->update($post);
        $updated++;
    }
}

echo "  ✓ {$updated} articles mis à jour avec les images locales.\n";
echo "\n✓ Terminé !\n\n";
