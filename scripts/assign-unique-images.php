<?php
/**
 * Script pour assigner des images uniques à tous les articles.
 *
 * Utilise plusieurs sources d'images avec des paramètres uniques pour chaque article :
 * - Picsum Photos (photos de haute qualité)
 * - LoremFlickr (photos par mot-clé)
 * - Unsplash Source (photos Unsplash)
 *
 * Chaque article obtient une URL unique basée sur son ID pour garantir l'unicité.
 *
 * Usage: php scripts/assign-unique-images.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

$basePath = dirname(__DIR__);

// Initialize services
$postStorage = new FileStorage($basePath . '/data/blog/posts');
$postService = new PostService($postStorage);

$posts = $postService->all();
$total = count($posts);

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          ASSIGNATION D'IMAGES UNIQUES AUX ARTICLES           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "→ {$total} articles à traiter\n\n";

// Keywords mapping for categories - more specific for better image relevance
$categoryKeywords = [
    // AI & Machine Learning - specific categories
    'ai-agents' => 'robot,automation',
    'ai-applications' => 'artificial,intelligence',
    'ai-coding' => 'code,programming',
    'ai-infrastructure' => 'server,datacenter',
    'ai-reasoning' => 'brain,thinking',
    'ai-research' => 'laboratory,research',
    'ai-safety' => 'security,shield',
    'generative-ai' => 'creative,digital,art',
    'generative-models' => 'neural,network',
    'llm-optimization' => 'computer,chip',
    'multimodal-ai' => 'vision,camera',
    'nlp-advanced' => 'language,text',
    'robotics-ai' => 'robot,mechanical',

    // Quantum - specific categories
    'quantum-algorithms' => 'mathematics,formula',
    'quantum-applications' => 'quantum,physics',
    'quantum-cryptography' => 'encryption,security',
    'quantum-error-correction' => 'error,correction',
    'quantum-future' => 'future,technology',
    'quantum-hardware' => 'processor,chip',
    'quantum-industry' => 'industry,factory',
    'quantum-networking' => 'network,connection',
    'quantum-physics' => 'physics,atom',
    'quantum-software' => 'software,code',

    // Biology - specific categories
    'agricultural-biotech' => 'agriculture,farming',
    'bioinformatics' => 'data,biology',
    'cell-therapy' => 'cell,medical',
    'crispr' => 'dna,genetics',
    'diagnostics' => 'medical,diagnosis',
    'drug-discovery' => 'medicine,pharmaceutical',
    'genomics' => 'genome,dna',
    'immunology' => 'immune,medical',
    'neuroscience' => 'brain,neuron',
    'synthetic-biology' => 'laboratory,biology',

    // Tech - specific categories
    'blockchain' => 'blockchain,crypto',
    'cloud-native' => 'cloud,server',
    'cybersecurity' => 'security,cyber',
    'edge-computing' => 'edge,computing',
    'framework' => 'code,framework',
    'low-code' => 'interface,application',
    'python-data' => 'python,data',
    'spatial-computing' => 'virtual,reality',
    'tutoriels' => 'tutorial,learning',

    // Generic fallbacks
    'ai' => 'artificial,intelligence',
    'machine-learning' => 'machine,learning',
    'deep-learning' => 'neural,network',
    'nlp' => 'language,processing',
    'computer-vision' => 'vision,camera',
    'robotics' => 'robot,mechanical',
    'llm' => 'language,model',
    'quantum' => 'quantum,physics',
    'biology' => 'biology,science',
    'biotech' => 'biotechnology,laboratory',
    'web' => 'web,development',
    'cloud' => 'cloud,computing',
    'security' => 'cybersecurity,lock',
    'devops' => 'server,infrastructure',
    'mobile' => 'smartphone,mobile',
    'iot' => 'internet,devices',
    'vr' => 'virtual,reality',
    'ar' => 'augmented,reality',
    'spatial' => 'spatial,3d',

    // Default
    'default' => 'technology,digital',
];

// Multiple image sources - all using keywords for relevance
$imageSources = [
    // LoremFlickr with category (reliable keyword-based service)
    function($id, $index, $keyword) {
        $lock = abs(crc32($id . 'flickr1'));
        return "https://loremflickr.com/1200/630/{$keyword}?lock={$lock}";
    },
    // LoremFlickr alternate (different lock for variety)
    function($id, $index, $keyword) {
        $lock = abs(crc32($id . 'flickr2'));
        return "https://loremflickr.com/1200/630/{$keyword}?lock={$lock}";
    },
    // Unsplash Source (direct link with unique sig)
    function($id, $index, $keyword) {
        $sig = hash('md5', $id . 'unsplash' . $index);
        return "https://source.unsplash.com/1200x630/?{$keyword}&sig={$sig}";
    },
];

$updated = 0;
$articleIndex = 0;
$sourceDistribution = [0 => 0, 1 => 0, 2 => 0];

foreach ($posts as $post) {
    $postId = $post->getId();

    // Determine keyword for image
    $keyword = 'technology';
    $categoryId = $post->getCategoryId() ?? '';
    $tags = $post->getTags();

    // Match category or tags to keywords
    foreach ($categoryKeywords as $cat => $kw) {
        if (stripos($categoryId, $cat) !== false) {
            $keyword = $kw;
            break;
        }
        foreach ($tags as $tag) {
            if (stripos($tag, $cat) !== false) {
                $keyword = $kw;
                break 2;
            }
        }
    }

    // Rotate through sources for variety
    $sourceIndex = $articleIndex % 3;
    $sourceDistribution[$sourceIndex]++;

    // Generate unique image URL
    $imageUrl = $imageSources[$sourceIndex]($postId, $articleIndex, $keyword);
    $articleIndex++;

    // Update post
    $post->setFeaturedImage($imageUrl);
    $postService->update($post);
    $updated++;

    if ($updated % 50 === 0) {
        echo "  ✓ {$updated}/{$total} articles mis à jour\n";
    }
}

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│                      RÉSULTATS                               │\n";
echo "├──────────────────────────────────────────────────────────────┤\n";
printf("│  %-25s %32d │\n", "Articles mis à jour", $updated);
printf("│  %-25s %32d │\n", "Source 1 (LoremFlickr)", $sourceDistribution[0]);
printf("│  %-25s %32d │\n", "Source 2 (LoremFlickr alt)", $sourceDistribution[1]);
printf("│  %-25s %32d │\n", "Source 3 (Unsplash)", $sourceDistribution[2]);
echo "└──────────────────────────────────────────────────────────────┘\n";
echo "\n✓ Toutes les images sont maintenant uniques et pertinentes !\n\n";

echo "💡 Note : Les images utilisent des mots-clés basés sur la catégorie\n";
echo "   de l'article pour garantir la pertinence des visuels.\n\n";
