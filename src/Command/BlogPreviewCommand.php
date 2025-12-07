<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Content\MarkdownParser;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour prévisualiser un article en terminal.
 */
#[Command(name: 'blog:preview', description: 'Prévisualise un article dans le terminal.')]
class BlogPreviewCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $args = array_values(array_filter($args, fn($a) => !str_starts_with($a, '-')));

        if (empty($args)) {
            echo "Usage: blog:preview <id|slug>\n";
            return 1;
        }

        $identifier = $args[0];
        $showRaw = in_array('--raw', $args, true);
        $showMeta = in_array('--meta', $args, true);

        try {
            $basePath = dirname(__DIR__, 2);
            $postService = new PostService(new FileStorage($basePath . '/data/blog/posts'));
            $parser = new MarkdownParser();

            // Trouver l'article
            $post = $postService->findById($identifier);
            if (!$post) {
                $post = $postService->findBySlug($identifier);
            }

            if (!$post) {
                echo "✗ Article non trouvé : {$identifier}\n";
                return 1;
            }

            $width = $this->getTerminalWidth();

            echo "\n";
            echo $this->generateHeader($post, $width);
            echo "\n";

            if ($showMeta) {
                echo $this->generateMetaInfo($post, $width);
                echo "\n";
            }

            if ($showRaw) {
                echo $post->getContent() . "\n";
            } else {
                echo $this->renderMarkdownToTerminal($post->getContent(), $width);
            }

            echo "\n";
            echo $this->generateFooter($post, $width);
            echo "\n";

            return 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Génère l'en-tête de l'article.
     */
    private function generateHeader($post, int $width): string
    {
        $lines = [];
        $line = str_repeat('═', $width);

        $lines[] = "╔{$line}╗";
        $lines[] = $this->centerText($post->getTitle(), $width);

        if ($post->getExcerpt()) {
            $lines[] = "╟" . str_repeat('─', $width) . "╢";
            $excerpt = wordwrap($post->getExcerpt(), $width - 4, "\n", true);
            foreach (explode("\n", $excerpt) as $excerptLine) {
                $lines[] = $this->centerText($excerptLine, $width, '│');
            }
        }

        $lines[] = "╚{$line}╝";

        return implode("\n", $lines);
    }

    /**
     * Centre du texte dans une largeur donnée.
     */
    private function centerText(string $text, int $width, string $border = '║'): string
    {
        $textLen = mb_strlen($text);
        $padding = max(0, $width - $textLen);
        $leftPad = (int) floor($padding / 2);
        $rightPad = (int) ceil($padding / 2);

        return $border . str_repeat(' ', $leftPad) . $text . str_repeat(' ', $rightPad) . $border;
    }

    /**
     * Génère les métadonnées de l'article.
     */
    private function generateMetaInfo($post, int $width): string
    {
        $lines = [];
        $lines[] = "┌" . str_repeat('─', $width) . "┐";
        $lines[] = "│" . str_pad(" Métadonnées", $width) . "│";
        $lines[] = "├" . str_repeat('─', $width) . "┤";

        $meta = [
            'ID' => $post->getId(),
            'Slug' => $post->getSlug(),
            'Auteur' => $post->getAuthor() ?: '-',
            'Statut' => $post->getStatus()->value,
            'Créé' => $post->getCreatedAt()->format('d/m/Y H:i'),
            'Publié' => $post->getPublishedAt()?->format('d/m/Y H:i') ?? '-',
            'Mots' => $post->getWordCount(),
            'Lecture' => $post->getReadingTime() . ' min',
            'Tags' => implode(', ', $post->getTags()) ?: '-',
        ];

        foreach ($meta as $key => $value) {
            $line = sprintf("  %-12s: %s", $key, $value);
            $lines[] = "│" . str_pad($line, $width) . "│";
        }

        $lines[] = "└" . str_repeat('─', $width) . "┘";

        return implode("\n", $lines);
    }

    /**
     * Génère le pied de page.
     */
    private function generateFooter($post, int $width): string
    {
        $info = sprintf(
            "📝 %d mots | ⏱ %d min | 🏷 %d tags",
            $post->getWordCount(),
            $post->getReadingTime(),
            count($post->getTags())
        );

        if ($post->isRated()) {
            $info .= sprintf(" | ⭐ %.1f/5", $post->getAverageRating());
        }

        $lines = [];
        $lines[] = str_repeat('─', $width + 2);
        $lines[] = $info;

        return implode("\n", $lines);
    }

    /**
     * Convertit le Markdown en texte formaté pour le terminal.
     */
    private function renderMarkdownToTerminal(string $content, int $width): string
    {
        $lines = explode("\n", $content);
        $output = [];

        foreach ($lines as $line) {
            // Titres
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
                $level = strlen($m[1]);
                $text = $m[2];
                $output[] = '';
                $output[] = $this->formatTitle($text, $level);
                $output[] = '';
                continue;
            }

            // Code block
            if (str_starts_with($line, '```')) {
                $output[] = '  ┌' . str_repeat('─', $width - 4) . '┐';
                continue;
            }

            // Listes
            if (preg_match('/^(\s*)([-*])\s+(.+)$/', $line, $m)) {
                $indent = strlen($m[1]) / 2;
                $output[] = str_repeat('  ', (int)$indent) . '  • ' . $this->formatInline($m[3]);
                continue;
            }

            // Listes numérotées
            if (preg_match('/^(\s*)(\d+)\.\s+(.+)$/', $line, $m)) {
                $indent = strlen($m[1]) / 2;
                $output[] = str_repeat('  ', (int)$indent) . '  ' . $m[2] . '. ' . $this->formatInline($m[3]);
                continue;
            }

            // Citations
            if (str_starts_with($line, '>')) {
                $text = ltrim($line, '> ');
                $output[] = '  │ ' . "\033[3m" . $this->formatInline($text) . "\033[0m";
                continue;
            }

            // Ligne horizontale
            if (preg_match('/^[-*_]{3,}$/', trim($line))) {
                $output[] = '  ' . str_repeat('─', $width - 4);
                continue;
            }

            // Paragraphe normal
            if (!empty(trim($line))) {
                $wrapped = wordwrap($this->formatInline($line), $width - 4, "\n", true);
                foreach (explode("\n", $wrapped) as $wl) {
                    $output[] = '  ' . $wl;
                }
            } else {
                $output[] = '';
            }
        }

        return implode("\n", $output);
    }

    /**
     * Formate un titre.
     */
    private function formatTitle(string $text, int $level): string
    {
        $prefix = str_repeat('▸', $level) . ' ';
        $formatted = "\033[1;36m" . $prefix . $text . "\033[0m";

        return '  ' . $formatted;
    }

    /**
     * Formate le texte inline (gras, italique, code, liens).
     */
    private function formatInline(string $text): string
    {
        // Code inline
        $text = preg_replace('/`([^`]+)`/', "\033[33m\$1\033[0m", $text);

        // Gras
        $text = preg_replace('/\*\*([^*]+)\*\*/', "\033[1m\$1\033[0m", $text);

        // Italique
        $text = preg_replace('/\*([^*]+)\*/', "\033[3m\$1\033[0m", $text);
        $text = preg_replace('/_([^_]+)_/', "\033[3m\$1\033[0m", $text);

        // Liens
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', "\033[4;34m\$1\033[0m", $text);

        return $text;
    }

    /**
     * Obtient la largeur du terminal.
     */
    private function getTerminalWidth(): int
    {
        $width = (int) shell_exec('tput cols 2>/dev/null') ?: 80;
        return min(100, max(60, $width - 4));
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:preview <id|slug> [options]

Prévisualise un article dans le terminal avec un rendu formaté.

Arguments :
  id|slug     L'ID ou le slug de l'article à prévisualiser

Options :
  --raw       Affiche le contenu Markdown brut
  --meta      Affiche les métadonnées détaillées

Formatage :
  - Titres en couleur avec préfixe ▸
  - Code inline en jaune
  - Gras, italique, liens formatés
  - Listes avec puces
  - Citations en italique

Exemple :
  php bin/console blog:preview mon-article
  php bin/console blog:preview abc123 --meta
  php bin/console blog:preview mon-article --raw
HELP;
    }
}
