<?php
/**
 * Lunar Quanta Framework - Commande de notation d'un article.
 *
 * @package    Lunar\Command
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.0.0
 */
declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Entity\Post;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour noter un article selon 5 critères.
 *
 * Critères de notation (1-5 étoiles) :
 * - relevance  : Pertinence - L'article traite-t-il bien du sujet ?
 * - depth      : Profondeur - Le sujet est-il traité en profondeur ?
 * - clarity    : Clarté - L'article est-il clair et bien structuré ?
 * - freshness  : Actualité - L'information est-elle à jour ?
 * - usefulness : Utilité - L'article est-il utile/pratique ?
 */
#[Command(name: 'blog:rate', description: 'Note un article selon 5 critères (1-5 étoiles).')]
class BlogRateCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $basePath = dirname(__DIR__, 2);

        // Parse arguments
        $identifier = null;
        $ratings = [];
        $random = false;
        $allPosts = false;

        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];

            if ($arg === '--random' || $arg === '-r') {
                $random = true;
                continue;
            }

            if ($arg === '--all' || $arg === '-a') {
                $allPosts = true;
                continue;
            }

            if (!str_starts_with($arg, '-') && $identifier === null) {
                $identifier = $arg;
                continue;
            }

            // Parse rating options
            foreach (Post::RATING_CRITERIA as $criterion => $label) {
                if ($arg === "--{$criterion}") {
                    $value = (int) ($args[++$i] ?? 0);
                    if ($value >= 1 && $value <= 5) {
                        $ratings[$criterion] = $value;
                    }
                }
            }
        }

        // Rate all posts with random values
        if ($allPosts && $random) {
            return $this->rateAllRandom($basePath);
        }

        if ($identifier === null || $identifier === '--help') {
            echo $this->getHelp();
            return $identifier === '--help' ? 0 : 1;
        }

        try {
            $postStorage = new FileStorage($basePath . '/data/blog/posts');
            $postService = new PostService($postStorage);

            // Find post
            $post = $postService->find($identifier);
            if ($post === null) {
                $post = $postService->findBySlug($identifier);
            }

            if ($post === null) {
                echo "\n✗ Article non trouvé : {$identifier}\n\n";
                return 1;
            }

            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║                    NOTATION D'ARTICLE                        ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";
            echo "\n";

            echo "  📝 " . mb_substr($post->getTitle(), 0, 55) . "\n";
            echo "  🆔 " . $post->getId() . "\n\n";

            // Generate random ratings if requested
            if ($random) {
                foreach (Post::RATING_CRITERIA as $criterion => $label) {
                    // Generate weighted random (3-5 more likely)
                    $ratings[$criterion] = $this->weightedRandom();
                }
            }

            // Apply ratings
            if (empty($ratings)) {
                // Show current ratings
                $this->displayRatings($post);
                echo "  💡 Utilisez les options pour noter l'article.\n";
                echo "     Exemple : blog:rate {$post->getId()} --relevance 5 --clarity 4\n\n";
                return 0;
            }

            foreach ($ratings as $criterion => $value) {
                $post->setRating($criterion, $value);
            }

            $postService->update($post);

            echo "  ✓ Notations mises à jour !\n\n";
            $this->displayRatings($post);

            return 0;

        } catch (\Throwable $e) {
            echo "\n✗ Erreur : " . $e->getMessage() . "\n\n";
            return 1;
        }
    }

    /**
     * Rate all posts with random values.
     */
    private function rateAllRandom(string $basePath): int
    {
        try {
            $postStorage = new FileStorage($basePath . '/data/blog/posts');
            $postService = new PostService($postStorage);

            $posts = $postService->all();
            $count = count($posts);

            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║              NOTATION AUTOMATIQUE DE TOUS LES ARTICLES       ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";
            echo "\n";

            echo "  → Notation de {$count} articles...\n\n";

            $progress = 0;
            foreach ($posts as $post) {
                foreach (Post::RATING_CRITERIA as $criterion => $label) {
                    $post->setRating($criterion, $this->weightedRandom());
                }
                $postService->update($post);
                $progress++;

                if ($progress % 50 === 0) {
                    echo "  ✓ {$progress}/{$count} articles notés\n";
                }
            }

            echo "\n  ✓ {$count} articles notés avec succès !\n\n";

            // Show sample stats
            $avgRatings = [];
            foreach (Post::RATING_CRITERIA as $criterion => $label) {
                $sum = 0;
                foreach ($posts as $post) {
                    $sum += $post->getRating($criterion);
                }
                $avgRatings[$criterion] = round($sum / $count, 1);
            }

            echo "  📊 Moyennes par critère :\n";
            foreach (Post::RATING_CRITERIA as $criterion => $label) {
                echo sprintf("     • %-12s : %.1f/5 %s\n",
                    $label,
                    $avgRatings[$criterion],
                    $this->getStarsString($avgRatings[$criterion])
                );
            }
            echo "\n";

            return 0;

        } catch (\Throwable $e) {
            echo "\n✗ Erreur : " . $e->getMessage() . "\n\n";
            return 1;
        }
    }

    /**
     * Generate a weighted random rating (3-5 more likely).
     */
    private function weightedRandom(): int
    {
        $weights = [1 => 5, 2 => 10, 3 => 25, 4 => 35, 5 => 25];
        $rand = mt_rand(1, 100);
        $cumulative = 0;
        foreach ($weights as $value => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $value;
            }
        }
        return 4;
    }

    /**
     * Display ratings for a post.
     */
    private function displayRatings(Post $post): void
    {
        echo "  ┌──────────────────────────────────────────────────────────┐\n";
        echo "  │                    NOTATIONS                             │\n";
        echo "  ├──────────────────────────────────────────────────────────┤\n";

        foreach (Post::RATING_CRITERIA as $criterion => $label) {
            $value = $post->getRating($criterion);
            $stars = $this->getStarsString($value);
            $bar = $this->getBarString($value);
            printf("  │  %-12s %s %s  %d/5 │\n", $label, $stars, $bar, $value);
        }

        echo "  ├──────────────────────────────────────────────────────────┤\n";
        $avg = $post->getAverageRating();
        $avgStars = $this->getStarsString($avg);
        printf("  │  %-12s %s      %.1f/5     │\n", "MOYENNE", $avgStars, $avg);
        echo "  └──────────────────────────────────────────────────────────┘\n\n";
    }

    /**
     * Get stars display string.
     */
    private function getStarsString(float $rating): string
    {
        $full = (int) floor($rating);
        $half = ($rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;

        return str_repeat('★', $full) . str_repeat('☆', $half + $empty);
    }

    /**
     * Get progress bar string.
     */
    private function getBarString(int $value): string
    {
        $filled = $value * 4;
        $empty = 20 - $filled;
        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : blog:rate
Note un article selon 5 critères (1-5 étoiles).

Utilisation :
  ./bin/console blog:rate <id|slug> [options]
  ./bin/console blog:rate --all --random

Arguments :
    <id|slug>               ID ou slug de l'article

Critères de notation (1-5) :
    --relevance <n>         Pertinence : L'article traite-t-il bien du sujet ?
    --depth <n>             Profondeur : Le sujet est-il traité en profondeur ?
    --clarity <n>           Clarté : L'article est-il clair et structuré ?
    --freshness <n>         Actualité : L'information est-elle à jour ?
    --usefulness <n>        Utilité : L'article est-il pratique/utile ?

Options :
    -r, --random            Générer des notes aléatoires (pondérées 3-5)
    -a, --all               Appliquer à tous les articles (avec --random)
    --help                  Affiche cette aide

Exemples :
    ./bin/console blog:rate abc123
    ./bin/console blog:rate abc123 --relevance 5 --clarity 4 --depth 4
    ./bin/console blog:rate mon-slug --random
    ./bin/console blog:rate --all --random

HELP;
    }
}
