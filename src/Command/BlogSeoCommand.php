<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour analyser le SEO des articles.
 */
#[Command(name: 'blog:seo', description: 'Analyse le SEO des articles du blog.')]
class BlogSeoCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $format = 'table';
        if (in_array('--json', $args, true)) {
            $format = 'json';
        }

        $showAll = in_array('--all', $args, true);

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Analyse SEO                        ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        try {
            $basePath = dirname(__DIR__, 2);
            $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));

            $posts = $postService->findPublished();
            $results = [];
            $totalScore = 0;

            foreach ($posts as $post) {
                $analysis = $this->analyzePost($post);
                $results[] = $analysis;
                $totalScore += $analysis['score'];
            }

            if ($format === 'json') {
                echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                $this->displayResults($results, $showAll, count($posts) > 0 ? $totalScore / count($posts) : 0);
            }

            return 0;

        } catch (\Throwable $e) {
            echo "  ✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Analyse le SEO d'un article.
     */
    private function analyzePost($post): array
    {
        $issues = [];
        $score = 100;

        $title = $post->getTitle();
        $content = $post->getContent();
        $excerpt = $post->getExcerpt();
        $slug = $post->getSlug();

        // Titre
        $titleLength = mb_strlen($title);
        if ($titleLength < 30) {
            $issues[] = ['type' => 'warning', 'message' => 'Titre trop court (' . $titleLength . ' caractères, min 30)'];
            $score -= 10;
        } elseif ($titleLength > 60) {
            $issues[] = ['type' => 'warning', 'message' => 'Titre trop long (' . $titleLength . ' caractères, max 60)'];
            $score -= 5;
        }

        // Description / Excerpt
        if (empty($excerpt)) {
            $issues[] = ['type' => 'error', 'message' => 'Pas de description/extrait'];
            $score -= 20;
        } else {
            $excerptLength = mb_strlen($excerpt);
            if ($excerptLength < 120) {
                $issues[] = ['type' => 'warning', 'message' => 'Description trop courte (' . $excerptLength . ' caractères)'];
                $score -= 10;
            } elseif ($excerptLength > 160) {
                $issues[] = ['type' => 'info', 'message' => 'Description longue (' . $excerptLength . ' caractères)'];
                $score -= 5;
            }
        }

        // Contenu
        $wordCount = $post->getWordCount();
        if ($wordCount < 300) {
            $issues[] = ['type' => 'error', 'message' => 'Contenu trop court (' . $wordCount . ' mots, min 300)'];
            $score -= 20;
        } elseif ($wordCount < 600) {
            $issues[] = ['type' => 'warning', 'message' => 'Contenu court (' . $wordCount . ' mots, recommandé 600+)'];
            $score -= 10;
        }

        // Titres H2
        preg_match_all('/^##\s/m', $content, $h2Matches);
        if (count($h2Matches[0]) === 0) {
            $issues[] = ['type' => 'warning', 'message' => 'Pas de sous-titres (H2)'];
            $score -= 10;
        }

        // Liens internes
        preg_match_all('/\[([^\]]+)\]\(\/[^\)]+\)/', $content, $internalLinks);
        if (count($internalLinks[0]) === 0) {
            $issues[] = ['type' => 'info', 'message' => 'Pas de liens internes'];
            $score -= 5;
        }

        // Images avec alt
        preg_match_all('/!\[([^\]]*)\]\([^\)]+\)/', $content, $images);
        $imagesWithoutAlt = 0;
        foreach ($images[1] as $alt) {
            if (empty(trim($alt))) {
                $imagesWithoutAlt++;
            }
        }
        if ($imagesWithoutAlt > 0) {
            $issues[] = ['type' => 'warning', 'message' => $imagesWithoutAlt . ' image(s) sans texte alt'];
            $score -= $imagesWithoutAlt * 5;
        }

        // Slug
        if (mb_strlen($slug) > 50) {
            $issues[] = ['type' => 'info', 'message' => 'URL longue (' . mb_strlen($slug) . ' caractères)'];
            $score -= 3;
        }

        // Tags
        if (count($post->getTags()) === 0) {
            $issues[] = ['type' => 'info', 'message' => 'Pas de tags'];
            $score -= 5;
        }

        // Featured image
        if (empty($post->getFeaturedImage())) {
            $issues[] = ['type' => 'warning', 'message' => 'Pas d\'image à la une'];
            $score -= 10;
        }

        return [
            'id' => $post->getId(),
            'title' => $title,
            'slug' => $slug,
            'score' => max(0, $score),
            'issues' => $issues,
            'stats' => [
                'title_length' => $titleLength,
                'excerpt_length' => mb_strlen($excerpt),
                'word_count' => $wordCount,
                'h2_count' => count($h2Matches[0]),
                'image_count' => count($images[0]),
                'link_count' => count($internalLinks[0]),
                'tag_count' => count($post->getTags()),
            ],
        ];
    }

    /**
     * Affiche les résultats de l'analyse.
     */
    private function displayResults(array $results, bool $showAll, float $avgScore): void
    {
        // Score moyen
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                    SCORE GLOBAL                             │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";

        $scoreBar = $this->generateScoreBar($avgScore);
        printf("│  Score moyen : %-47s │\n", $scoreBar);
        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";

        // Filtrer pour ne montrer que les problématiques si pas --all
        $toShow = $showAll ? $results : array_filter($results, fn($r) => $r['score'] < 80);

        if (empty($toShow) && !$showAll) {
            echo "  ✓ Tous les articles ont un bon score SEO (≥80)\n";
            echo "  Utilisez --all pour voir tous les articles\n\n";
            return;
        }

        // Trier par score
        usort($toShow, fn($a, $b) => $a['score'] <=> $b['score']);

        foreach ($toShow as $result) {
            $this->displayArticleResult($result);
        }

        echo "\n  Légende : ✗ Erreur | ⚠ Avertissement | ℹ Info\n\n";
    }

    /**
     * Affiche le résultat d'un article.
     */
    private function displayArticleResult(array $result): void
    {
        $scoreBar = $this->generateScoreBar($result['score']);
        $title = mb_substr($result['title'], 0, 50);

        echo "┌──────────────────────────────────────────────────────────────┐\n";
        printf("│  %-58s  │\n", $title);
        printf("│  Score : %-51s  │\n", $scoreBar);

        if (!empty($result['issues'])) {
            echo "├──────────────────────────────────────────────────────────────┤\n";
            foreach ($result['issues'] as $issue) {
                $icon = match ($issue['type']) {
                    'error' => '✗',
                    'warning' => '⚠',
                    default => 'ℹ',
                };
                printf("│  %s %-57s  │\n", $icon, mb_substr($issue['message'], 0, 57));
            }
        }
        echo "└──────────────────────────────────────────────────────────────┘\n";
    }

    /**
     * Génère une barre de score visuelle.
     */
    private function generateScoreBar(float $score): string
    {
        $filled = (int) ($score / 5);
        $empty = 20 - $filled;

        $color = match (true) {
            $score >= 80 => '32', // Vert
            $score >= 60 => '33', // Jaune
            default => '31', // Rouge
        };

        return sprintf(
            "\033[%sm%s\033[0m%s %d/100",
            $color,
            str_repeat('█', $filled),
            str_repeat('░', $empty),
            (int) $score
        );
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:seo [options]

Analyse le SEO des articles publiés du blog.

Options :
  --json    Affiche les résultats au format JSON
  --all     Affiche tous les articles (pas seulement les problématiques)

Critères analysés :
  - Longueur du titre (30-60 caractères)
  - Description/extrait (120-160 caractères)
  - Longueur du contenu (min 300 mots)
  - Présence de sous-titres (H2)
  - Liens internes
  - Images avec texte alternatif
  - Tags
  - Image à la une

Scores :
  ≥80 : Bon
  60-79 : À améliorer
  <60 : Problématique

Exemple :
  php bin/console blog:seo
  php bin/console blog:seo --all
  php bin/console blog:seo --json
HELP;
    }
}
