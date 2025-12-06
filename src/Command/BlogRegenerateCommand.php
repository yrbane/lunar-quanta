<?php
/**
 * Lunar Quanta Framework - Commande de Régénération du Blog Statique.
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
use Lunar\Service\Blog\CategoryService;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Blog\TagService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\StaticSite\StaticGenerator;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour régénérer le blog statique.
 *
 * Cette commande génère tous les fichiers HTML statiques du blog :
 * - Pages d'articles individuels
 * - Page d'index du blog
 * - Pages de catégories
 * - Pages de tags
 * - Flux RSS (feed.xml)
 * - Sitemap (sitemap.xml)
 *
 * ==========================================================================
 * UTILISATION
 * ==========================================================================
 *
 * ```bash
 * # Régénérer tout le blog
 * ./bin/console blog:regenerate
 *
 * # Mode silencieux
 * ./bin/console blog:regenerate --quiet
 *
 * # Afficher l'aide
 * ./bin/console blog:regenerate --help
 * ```
 */
#[Command(name: 'blog:regenerate', description: 'Régénère les pages statiques du blog.')]
class BlogRegenerateCommand implements CommandInterface
{
    private const PROGRESS_BAR_WIDTH = 50;

    /** @var string Type de génération en cours */
    private string $currentPhase = '';

    /** @var int Largeur du terminal */
    private int $terminalWidth = 80;

    /**
     * Exécute la commande de régénération du blog.
     *
     * @param array<string> $args Arguments passés à la commande
     *
     * @return int Code de sortie (0 = succès, 1 = erreur)
     */
    public function execute(array $args): int
    {
        $startTime = microtime(true);
        $quiet = in_array('--quiet', $args, true) || in_array('-q', $args, true);

        // Détecter la largeur du terminal
        $this->terminalWidth = $this->getTerminalWidth();

        if (!$quiet) {
            echo "\n";
            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║           LUNAR BLOG - Régénération Statique                 ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";
            echo "\n";
        }

        try {
            $basePath = dirname(__DIR__, 2);

            // Initialisation des services
            if (!$quiet) {
                echo "→ Initialisation des services...\n";
            }

            $postStorage = new FileStorage($basePath . '/data/blog/posts');
            $tagStorage = new FileStorage($basePath . '/data/blog/tags');
            $categoryStorage = new FileStorage($basePath . '/data/blog/categories');

            $tagService = new TagService($tagStorage);
            $categoryService = new CategoryService($categoryStorage);
            $postService = new PostService($postStorage);

            $markdownParser = new MarkdownParser();

            // Configuration du générateur
            $outputDir = $basePath . '/public/blog';
            $templateDir = $basePath . '/template/blog';
            $baseUrl = 'https://example.com'; // TODO: Configurable

            if (!$quiet) {
                echo "→ Répertoire de sortie : {$outputDir}\n";
                echo "→ Templates : {$templateDir}\n";
                echo "\n";
            }

            $generator = new StaticGenerator(
                $postService,
                $markdownParser,
                $outputDir,
                $templateDir,
                $baseUrl
            );
            $generator->setCategoryService($categoryService);

            // Configurer la barre de progression
            if (!$quiet) {
                $generator->onProgress(function (int $current, int $total, string $type, string $item) {
                    $this->updateProgress($current, $total, $type, $item);
                });
            }

            // Régénération
            if (!$quiet) {
                echo "→ Génération en cours...\n\n";
            }

            $result = $generator->regenerate();

            // Effacer la dernière ligne de progression
            if (!$quiet) {
                echo "\r" . str_repeat(' ', $this->terminalWidth) . "\r";
            }

            // Résultats
            $duration = round((microtime(true) - $startTime) * 1000);

            if (!$quiet) {
                echo "\n";
                echo "┌──────────────────────────────────────────────────────────────┐\n";
                echo "│                      RÉSULTATS                               │\n";
                echo "├──────────────────────────────────────────────────────────────┤\n";
                printf("│  %-20s %38d │\n", "Articles générés", $result['posts']);
                printf("│  %-20s %38d │\n", "Pages de tags", $result['tags']);
                printf("│  %-20s %38d │\n", "Pages de catégories", $result['categories']);
                echo "├──────────────────────────────────────────────────────────────┤\n";
                printf("│  %-20s %35s ms │\n", "Temps d'exécution", $duration);
                echo "└──────────────────────────────────────────────────────────────┘\n";
                echo "\n";

                if ($result['posts'] > 0) {
                    echo "✓ Blog régénéré avec succès !\n";
                } else {
                    echo "⚠ Aucun article publié trouvé.\n";
                }

                echo "\n";
            }

            return 0;

        } catch (\Throwable $e) {
            // Effacer la ligne de progression en cas d'erreur
            echo "\r" . str_repeat(' ', $this->terminalWidth) . "\r";
            echo "\n";
            echo "✗ Erreur : " . $e->getMessage() . "\n";
            echo "  Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "\n";

            return 1;
        }
    }

    /**
     * Met à jour l'affichage de la barre de progression.
     */
    private function updateProgress(int $current, int $total, string $type, string $item): void
    {
        // Labels pour chaque type
        $labels = [
            'post' => '📄 Articles',
            'tag' => '🏷️  Tags',
            'category' => '📁 Catégories',
            'index' => '🏠 Index',
            'rss' => '📡 RSS',
            'sitemap' => '🗺️  Sitemap',
        ];

        $label = $labels[$type] ?? ucfirst($type);

        // Calculer le pourcentage
        $percent = $total > 0 ? ($current / $total) * 100 : 100;

        // Générer la barre de progression
        $filled = (int) round(self::PROGRESS_BAR_WIDTH * $percent / 100);
        $empty = self::PROGRESS_BAR_WIDTH - $filled;

        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);

        // Tronquer le nom de l'item s'il est trop long
        $maxItemLen = 25;
        $itemDisplay = mb_strlen($item) > $maxItemLen
            ? mb_substr($item, 0, $maxItemLen - 3) . '...'
            : str_pad($item, $maxItemLen);

        // Formater la ligne
        $line = sprintf(
            "\r%s [%s] %3d%% (%d/%d) %s",
            str_pad($label, 12),
            $bar,
            (int) $percent,
            $current,
            $total,
            $itemDisplay
        );

        // Effacer la ligne précédente et afficher la nouvelle
        echo "\r" . str_repeat(' ', $this->terminalWidth) . $line;
    }

    /**
     * Récupère la largeur du terminal.
     */
    private function getTerminalWidth(): int
    {
        // Essayer avec stty
        if (function_exists('exec')) {
            $width = (int) @exec('tput cols 2>/dev/null');
            if ($width > 0) {
                return $width;
            }
        }

        // Valeur par défaut
        return 120;
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Commande : blog:regenerate
Régénère les pages statiques du blog.

Utilisation :
  ./bin/console blog:regenerate [--help]

Options :
    --help         Affiche cette aide

Description :
    Cette commande régénère tous les fichiers HTML statiques du blog :

    - public/blog/index.html        Page d'accueil du blog
    - public/blog/posts/*.html      Pages des articles
    - public/blog/category/*.html   Pages des catégories
    - public/blog/tag/*.html        Pages des tags
    - public/blog/feed.xml          Flux RSS
    - public/blog/sitemap.xml       Sitemap XML

Exemples :
    ./bin/console blog:regenerate
    ./bin/console blog:regenerate --help

Remarque :
    Seuls les articles avec le statut "published" sont générés.
    Les articles en brouillon (draft) ou archivés ne sont pas inclus.

HELP;
    }
}
