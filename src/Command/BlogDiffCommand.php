<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour comparer deux versions d'un article.
 */
#[Command(name: 'blog:diff', description: 'Compare deux articles ou versions.')]
class BlogDiffCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $args = array_values(array_filter($args, fn($a) => !str_starts_with($a, '-')));

        if (count($args) < 2) {
            echo "Usage: blog:diff <id1|slug1> <id2|slug2>\n";
            return 1;
        }

        $sideByDide = in_array('--side', $args, true);
        $contextLines = 3;

        try {
            $basePath = dirname(__DIR__, 2);
            $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));

            // Trouver les articles
            $post1 = $postService->findById($args[0]) ?? $postService->findBySlug($args[0]);
            $post2 = $postService->findById($args[1]) ?? $postService->findBySlug($args[1]);

            if (!$post1) {
                echo "✗ Premier article non trouvé : {$args[0]}\n";
                return 1;
            }

            if (!$post2) {
                echo "✗ Second article non trouvé : {$args[1]}\n";
                return 1;
            }

            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║              LUNAR BLOG - Comparaison                        ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";
            echo "\n";

            // Comparer les métadonnées
            echo "┌──────────────────────────────────────────────────────────────┐\n";
            echo "│                    MÉTADONNÉES                               │\n";
            echo "├───────────────┬────────────────────────┬────────────────────┤\n";
            printf("│ %-13s │ %-22s │ %-18s │\n", "Champ", mb_substr($post1->getTitle(), 0, 22), mb_substr($post2->getTitle(), 0, 18));
            echo "├───────────────┼────────────────────────┼────────────────────┤\n";

            $this->compareField('Titre', $post1->getTitle(), $post2->getTitle());
            $this->compareField('Slug', $post1->getSlug(), $post2->getSlug());
            $this->compareField('Auteur', $post1->getAuthor() ?? '-', $post2->getAuthor() ?? '-');
            $this->compareField('Statut', $post1->getStatus()->value, $post2->getStatus()->value);
            $this->compareField('Mots', (string)$post1->getWordCount(), (string)$post2->getWordCount());
            $this->compareField('Tags', implode(',', $post1->getTags()) ?: '-', implode(',', $post2->getTags()) ?: '-');

            echo "└───────────────┴────────────────────────┴────────────────────┘\n";
            echo "\n";

            // Comparer le contenu
            echo "┌──────────────────────────────────────────────────────────────┐\n";
            echo "│                      CONTENU                                 │\n";
            echo "└──────────────────────────────────────────────────────────────┘\n";

            $diff = $this->generateDiff(
                explode("\n", $post1->getContent()),
                explode("\n", $post2->getContent()),
                $contextLines
            );

            if (empty($diff)) {
                echo "\n  ✓ Le contenu est identique\n";
            } else {
                echo "\n";
                foreach ($diff as $line) {
                    echo $line . "\n";
                }
            }

            // Résumé
            echo "\n";
            echo "┌──────────────────────────────────────────────────────────────┐\n";
            echo "│                      RÉSUMÉ                                  │\n";
            echo "├──────────────────────────────────────────────────────────────┤\n";

            $stats = $this->computeDiffStats(
                explode("\n", $post1->getContent()),
                explode("\n", $post2->getContent())
            );

            printf("│  Lignes ajoutées    : %-38s │\n", "\033[32m+" . $stats['added'] . "\033[0m");
            printf("│  Lignes supprimées  : %-38s │\n", "\033[31m-" . $stats['removed'] . "\033[0m");
            printf("│  Lignes modifiées   : %-38s │\n", "\033[33m~" . $stats['modified'] . "\033[0m");
            printf("│  Lignes inchangées  : %-38s │\n", $stats['unchanged']);
            echo "└──────────────────────────────────────────────────────────────┘\n";
            echo "\n";

            return 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Compare un champ entre deux articles.
     */
    private function compareField(string $name, string $val1, string $val2): void
    {
        $val1 = mb_substr($val1, 0, 22);
        $val2 = mb_substr($val2, 0, 18);

        $icon = $val1 === $val2 ? ' ' : '≠';

        printf("│ %-13s │ %-22s │ %-18s │ %s\n", $name, $val1, $val2, $icon);
    }

    /**
     * Génère un diff entre deux tableaux de lignes.
     */
    private function generateDiff(array $lines1, array $lines2, int $context): array
    {
        $output = [];
        $max = max(count($lines1), count($lines2));
        $lastShown = -$context - 1;

        for ($i = 0; $i < $max; $i++) {
            $l1 = $lines1[$i] ?? null;
            $l2 = $lines2[$i] ?? null;

            if ($l1 === $l2) {
                // Lignes identiques - afficher seulement si dans le contexte
                if ($i - $lastShown <= $context) {
                    $output[] = "  " . ($l1 ?? '');
                }
                continue;
            }

            // Ajouter un séparateur si on saute des lignes
            if ($i - $lastShown > $context + 1) {
                $output[] = "\033[90m  ...\033[0m";
            }

            // Afficher les lignes de contexte avant
            for ($j = max(0, $i - $context); $j < $i; $j++) {
                if ($j > $lastShown) {
                    $output[] = "  " . ($lines1[$j] ?? $lines2[$j] ?? '');
                }
            }

            // Ligne modifiée
            if ($l1 !== null && $l2 !== null) {
                $output[] = "\033[31m- " . $l1 . "\033[0m";
                $output[] = "\033[32m+ " . $l2 . "\033[0m";
            } elseif ($l1 === null) {
                $output[] = "\033[32m+ " . $l2 . "\033[0m";
            } else {
                $output[] = "\033[31m- " . $l1 . "\033[0m";
            }

            $lastShown = $i;
        }

        return $output;
    }

    /**
     * Calcule les statistiques du diff.
     */
    private function computeDiffStats(array $lines1, array $lines2): array
    {
        $added = 0;
        $removed = 0;
        $modified = 0;
        $unchanged = 0;

        $max = max(count($lines1), count($lines2));

        for ($i = 0; $i < $max; $i++) {
            $l1 = $lines1[$i] ?? null;
            $l2 = $lines2[$i] ?? null;

            if ($l1 === $l2) {
                $unchanged++;
            } elseif ($l1 === null) {
                $added++;
            } elseif ($l2 === null) {
                $removed++;
            } else {
                $modified++;
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'modified' => $modified,
            'unchanged' => $unchanged,
        ];
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:diff <article1> <article2> [options]

Compare deux articles ou deux versions d'un article.

Arguments :
  article1    ID ou slug du premier article
  article2    ID ou slug du second article

Options :
  --side      Affichage côte à côte (non implémenté)

Comparaison :
  - Métadonnées (titre, slug, auteur, statut, etc.)
  - Contenu avec diff style git
  - Statistiques (lignes ajoutées, supprimées, modifiées)

Couleurs :
  Rouge (-) : Lignes supprimées ou dans article 1 seulement
  Vert (+)  : Lignes ajoutées ou dans article 2 seulement

Exemple :
  php bin/console blog:diff article-v1 article-v2
  php bin/console blog:diff abc123 def456
HELP;
    }
}
