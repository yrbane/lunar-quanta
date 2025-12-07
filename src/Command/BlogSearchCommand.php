<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Entity\PostStatus;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour rechercher dans les articles du blog.
 *
 * Permet de rechercher des articles par mots-clés avec différents
 * filtres et options d'affichage.
 */
#[Command(name: 'blog:search', description: 'Recherche dans les articles du blog.')]
class BlogSearchCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        // Extraire la requête de recherche
        $query = $this->extractQuery($args);

        if (empty($query)) {
            echo "Usage: blog:search <query> [options]\n";
            echo "Utilisez --help pour plus d'informations.\n";
            return 1;
        }

        $format = 'table';
        if (in_array('--json', $args, true)) {
            $format = 'json';
        } elseif (in_array('--simple', $args, true)) {
            $format = 'simple';
        }

        $limit = $this->parseIntOption($args, '--limit', 10);
        $status = $this->parseOption($args, '--status');
        $includeContent = in_array('--content', $args, true);

        try {
            $basePath = dirname(__DIR__, 2);
            $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));

            // Effectuer la recherche
            $publishedOnly = $status !== 'all' && $status !== 'draft';
            $results = $postService->search($query, $publishedOnly);

            // Filtrer par statut si spécifié
            if ($status === 'draft') {
                $results = array_filter($results, fn($r) => $r['post']->getStatus() === PostStatus::DRAFT);
            } elseif ($status === 'archived') {
                $results = array_filter($results, fn($r) => $r['post']->getStatus() === PostStatus::ARCHIVED);
            }

            // Limiter les résultats
            $results = array_slice($results, 0, $limit);

            if (empty($results)) {
                echo "Aucun article trouvé pour : \"{$query}\"\n";
                return 0;
            }

            // Afficher les résultats selon le format
            if ($format === 'json') {
                $this->displayJson($results, $query, $includeContent);
            } elseif ($format === 'simple') {
                $this->displaySimple($results);
            } else {
                $this->displayTable($results, $query);
            }

            return 0;

        } catch (\Throwable $e) {
            echo "Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Extrait la requête des arguments.
     */
    private function extractQuery(array $args): string
    {
        $query = [];
        $skipNext = false;

        foreach ($args as $i => $arg) {
            if ($skipNext) {
                $skipNext = false;
                continue;
            }

            // Ignorer les options
            if (str_starts_with($arg, '--')) {
                if (in_array($arg, ['--limit', '--status'])) {
                    $skipNext = true;
                }
                continue;
            }

            $query[] = $arg;
        }

        return implode(' ', $query);
    }

    /**
     * Parse une option string.
     */
    private function parseOption(array $args, string $option): ?string
    {
        foreach ($args as $i => $arg) {
            if ($arg === $option && isset($args[$i + 1])) {
                return $args[$i + 1];
            }
            if (str_starts_with($arg, $option . '=')) {
                return substr($arg, strlen($option) + 1);
            }
        }
        return null;
    }

    /**
     * Parse une option integer.
     */
    private function parseIntOption(array $args, string $option, int $default): int
    {
        $value = $this->parseOption($args, $option);
        return $value !== null ? (int) $value : $default;
    }

    /**
     * Affiche les résultats en tableau.
     */
    private function displayTable(array $results, string $query): void
    {
        $count = count($results);

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              RECHERCHE BLOG - Résultats                      ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Requête : \"{$query}\"\n";
        echo "Résultats : {$count}\n";
        echo "\n";

        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│  #  │ Score │ Titre                           │ Statut      │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";

        foreach ($results as $i => $result) {
            $post = $result['post'];
            $score = $result['score'];
            $num = str_pad((string) ($i + 1), 2, ' ', STR_PAD_LEFT);
            $title = mb_substr($post->getTitle(), 0, 30);
            $title = str_pad($title, 30);
            $status = $this->formatStatus($post->getStatus());

            printf("│ %s  │ %5d │ %-30s │ %-11s │\n", $num, $score, $title, $status);
        }

        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";

        // Afficher les détails du premier résultat
        if (!empty($results)) {
            $first = $results[0]['post'];
            echo "Premier résultat :\n";
            echo "  ID    : " . $first->getId() . "\n";
            echo "  Slug  : " . $first->getSlug() . "\n";
            echo "  Auteur: " . ($first->getAuthor() ?: 'N/A') . "\n";
            if ($first->getExcerpt()) {
                echo "  Extrait: " . mb_substr($first->getExcerpt(), 0, 80) . "...\n";
            }
        }
    }

    /**
     * Affiche les résultats en format simple.
     */
    private function displaySimple(array $results): void
    {
        foreach ($results as $result) {
            $post = $result['post'];
            echo $post->getSlug() . "\t" . $post->getTitle() . "\n";
        }
    }

    /**
     * Affiche les résultats en JSON.
     */
    private function displayJson(array $results, string $query, bool $includeContent): void
    {
        $output = [
            'query' => $query,
            'count' => count($results),
            'results' => [],
        ];

        foreach ($results as $result) {
            $post = $result['post'];
            $item = [
                'id' => $post->getId(),
                'slug' => $post->getSlug(),
                'title' => $post->getTitle(),
                'score' => $result['score'],
                'status' => $post->getStatus()->value,
                'author' => $post->getAuthor(),
                'excerpt' => $post->getExcerpt(),
                'tags' => $post->getTags(),
                'created_at' => $post->getCreatedAt()->format('c'),
            ];

            if ($includeContent) {
                $item['content'] = $post->getContent();
            }

            $output['results'][] = $item;
        }

        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * Formate le statut pour l'affichage.
     */
    private function formatStatus(PostStatus $status): string
    {
        return match ($status) {
            PostStatus::PUBLISHED => 'Publié',
            PostStatus::DRAFT => 'Brouillon',
            PostStatus::ARCHIVED => 'Archivé',
        };
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:search <query> [options]

Recherche dans les articles du blog par mots-clés.

Arguments :
  <query>              Termes de recherche (mots-clés)

Options :
  --limit=<n>          Nombre maximum de résultats (défaut: 10)
  --status=<status>    Filtrer par statut (published, draft, archived, all)
  --json               Afficher les résultats en JSON
  --simple             Afficher en format simple (slug + titre)
  --content            Inclure le contenu dans la sortie JSON

Exemples :
  blog:search php                         # Recherche "php"
  blog:search "web development"           # Recherche phrase
  blog:search php --limit=5               # Limite à 5 résultats
  blog:search php --status=all            # Inclut brouillons
  blog:search php --json                  # Sortie JSON
  blog:search php --json --content        # JSON avec contenu

Algorithme de scoring :
  - Correspondance dans le titre : 100 points
  - Correspondance dans les tags : 80 points
  - Correspondance dans l'extrait : 40 points
  - Correspondance dans l'auteur : 30 points
  - Correspondance dans le contenu : jusqu'à 50 points
HELP;
    }
}
