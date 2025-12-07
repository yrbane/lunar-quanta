<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Entity\PostStatus;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;

/**
 * Commande CLI pour nettoyer les données obsolètes du blog.
 *
 * Supprime les fichiers orphelins, les brouillons anciens, etc.
 */
#[Command(name: 'blog:clean', description: 'Nettoie les données obsolètes du blog.')]
class BlogCleanCommand implements CommandInterface
{
    private string $basePath;
    private bool $verbose = false;
    private bool $dryRun = false;

    public function execute(array $args): int
    {
        $this->basePath = dirname(__DIR__, 2);
        $this->verbose = in_array('-v', $args, true) || in_array('--verbose', $args, true);
        $this->dryRun = in_array('--dry-run', $args, true);
        $force = in_array('--force', $args, true);

        // Options de nettoyage
        $cleanOrphans = in_array('--orphans', $args, true) || in_array('--all', $args, true);
        $cleanDrafts = in_array('--drafts', $args, true) || in_array('--all', $args, true);
        $cleanRevisions = in_array('--revisions', $args, true) || in_array('--all', $args, true);
        $cleanGenerated = in_array('--generated', $args, true) || in_array('--all', $args, true);
        $cleanTempFiles = in_array('--temp', $args, true) || in_array('--all', $args, true);

        // Extraire la limite d'âge pour les brouillons
        $draftDays = $this->parseIntOption($args, '--draft-days', 90);

        $this->printHeader();

        if ($this->dryRun) {
            echo "Mode simulation : aucune suppression ne sera effectuée.\n\n";
        }

        $stats = [
            'orphan_media' => 0,
            'orphan_media_size' => 0,
            'old_drafts' => 0,
            'old_revisions' => 0,
            'generated_files' => 0,
            'temp_files' => 0,
        ];

        try {
            // Nettoyer les médias orphelins
            if ($cleanOrphans) {
                echo "→ Recherche des médias orphelins...\n";
                $result = $this->cleanOrphanMedia();
                $stats['orphan_media'] = $result['count'];
                $stats['orphan_media_size'] = $result['size'];
            }

            // Nettoyer les vieux brouillons
            if ($cleanDrafts) {
                echo "→ Recherche des brouillons de plus de {$draftDays} jours...\n";
                $stats['old_drafts'] = $this->cleanOldDrafts($draftDays, $force);
            }

            // Nettoyer les vieilles révisions
            if ($cleanRevisions) {
                echo "→ Nettoyage des révisions...\n";
                $stats['old_revisions'] = $this->cleanOldRevisions();
            }

            // Nettoyer les fichiers générés
            if ($cleanGenerated) {
                echo "→ Nettoyage des fichiers générés...\n";
                $stats['generated_files'] = $this->cleanGeneratedFiles();
            }

            // Nettoyer les fichiers temporaires
            if ($cleanTempFiles) {
                echo "→ Nettoyage des fichiers temporaires...\n";
                $stats['temp_files'] = $this->cleanTempFiles();
            }

            if (!$cleanOrphans && !$cleanDrafts && !$cleanRevisions && !$cleanGenerated && !$cleanTempFiles) {
                echo "Aucune option de nettoyage spécifiée.\n";
                echo "Utilisez --help pour voir les options disponibles.\n";
                return 0;
            }

            $this->printResults($stats);

            return 0;

        } catch (\Throwable $e) {
            echo "✗ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Nettoie les médias non référencés.
     */
    private function cleanOrphanMedia(): array
    {
        $uploadsDir = $this->basePath . '/public/uploads';
        if (!is_dir($uploadsDir)) {
            return ['count' => 0, 'size' => 0];
        }

        // Collecter tous les médias référencés
        $referenced = $this->collectReferencedMedia();

        // Scanner les fichiers uploadés
        $orphans = [];
        $totalSize = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadsDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relativePath = '/uploads/' . str_replace('\\', '/', $iterator->getSubPathname());

            // Vérifier si le fichier est référencé
            if (!in_array($relativePath, $referenced)) {
                $orphans[] = $file->getPathname();
                $totalSize += $file->getSize();

                if ($this->verbose) {
                    $size = $this->formatBytes($file->getSize());
                    echo "  " . ($this->dryRun ? "[dry-run] " : "") . "Orphelin : {$relativePath} ({$size})\n";
                }
            }
        }

        // Supprimer les orphelins
        if (!$this->dryRun) {
            foreach ($orphans as $file) {
                unlink($file);
            }
        }

        return ['count' => count($orphans), 'size' => $totalSize];
    }

    /**
     * Collecte tous les médias référencés dans les articles.
     */
    private function collectReferencedMedia(): array
    {
        $referenced = [];
        $postsDir = $this->basePath . '/data/blog/posts';

        if (!is_dir($postsDir)) {
            return $referenced;
        }

        $postService = new PostService(new FileStorage($postsDir));
        $posts = $postService->all();

        foreach ($posts as $post) {
            // Image mise en avant
            $featuredImage = $post->getFeaturedImage();
            if ($featuredImage !== null && !empty($featuredImage)) {
                $referenced[] = $featuredImage;
            }

            // Avatar auteur
            $avatar = $post->getAuthorAvatar();
            if ($avatar !== null && !empty($avatar)) {
                $referenced[] = $avatar;
            }

            // Images dans le contenu
            $content = $post->getContent();
            preg_match_all('/!\[[^\]]*\]\(([^)]+)\)/', $content, $matches);
            foreach ($matches[1] ?? [] as $url) {
                if (str_starts_with($url, '/uploads/')) {
                    $referenced[] = $url;
                }
            }

            // Liens vers des fichiers dans le contenu
            preg_match_all('/\[[^\]]*\]\(([^)]+)\)/', $content, $linkMatches);
            foreach ($linkMatches[1] ?? [] as $url) {
                if (str_starts_with($url, '/uploads/')) {
                    $referenced[] = $url;
                }
            }
        }

        return array_unique($referenced);
    }

    /**
     * Nettoie les vieux brouillons.
     */
    private function cleanOldDrafts(int $days, bool $force): int
    {
        $postsDir = $this->basePath . '/data/blog/posts';
        if (!is_dir($postsDir)) {
            return 0;
        }

        $postService = new PostService(new FileStorage($postsDir));
        $posts = $postService->all();

        $cutoff = new \DateTimeImmutable("-{$days} days");
        $deleted = 0;

        foreach ($posts as $post) {
            if ($post->getStatus() !== PostStatus::DRAFT) {
                continue;
            }

            $updatedAt = $post->getUpdatedAt() ?? $post->getCreatedAt();
            if ($updatedAt === null || $updatedAt > $cutoff) {
                continue;
            }

            if ($this->verbose) {
                $age = $updatedAt->diff(new \DateTimeImmutable())->days;
                echo "  " . ($this->dryRun ? "[dry-run] " : "") . "Brouillon ancien : {$post->getTitle()} ({$age} jours)\n";
            }

            if (!$this->dryRun) {
                if ($force) {
                    $postService->delete($post->getId());
                    $deleted++;
                } else {
                    // Archiver au lieu de supprimer
                    $post->archive();
                    $postService->update($post);
                    $deleted++;
                }
            } else {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Nettoie les vieilles révisions.
     */
    private function cleanOldRevisions(): int
    {
        $revisionsDir = $this->basePath . '/data/blog/revisions';
        if (!is_dir($revisionsDir)) {
            return 0;
        }

        $deleted = 0;
        $cutoff = new \DateTimeImmutable('-30 days');

        $files = glob($revisionsDir . '/*/*.json');
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime < $cutoff->getTimestamp()) {
                if ($this->verbose) {
                    echo "  " . ($this->dryRun ? "[dry-run] " : "") . "Révision ancienne : " . basename($file) . "\n";
                }

                if (!$this->dryRun) {
                    unlink($file);
                }
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Nettoie les fichiers générés.
     */
    private function cleanGeneratedFiles(): int
    {
        $blogDir = $this->basePath . '/public/blog';
        if (!is_dir($blogDir)) {
            return 0;
        }

        $deleted = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($blogDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                if ($this->verbose) {
                    echo "  " . ($this->dryRun ? "[dry-run] " : "") . "Fichier généré : " . $iterator->getSubPathname() . "\n";
                }

                if (!$this->dryRun) {
                    unlink($item->getPathname());
                }
                $deleted++;
            } elseif ($item->isDir()) {
                if (!$this->dryRun) {
                    @rmdir($item->getPathname());
                }
            }
        }

        return $deleted;
    }

    /**
     * Nettoie les fichiers temporaires.
     */
    private function cleanTempFiles(): int
    {
        $tempDirs = [
            $this->basePath . '/var/cache',
            $this->basePath . '/var/tmp',
        ];

        $deleted = 0;

        foreach ($tempDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    if ($this->verbose) {
                        echo "  " . ($this->dryRun ? "[dry-run] " : "") . "Fichier temp : " . $iterator->getSubPathname() . "\n";
                    }

                    if (!$this->dryRun) {
                        unlink($item->getPathname());
                    }
                    $deleted++;
                } elseif ($item->isDir()) {
                    if (!$this->dryRun) {
                        @rmdir($item->getPathname());
                    }
                }
            }
        }

        return $deleted;
    }

    /**
     * Parse une option integer.
     */
    private function parseIntOption(array $args, string $option, int $default): int
    {
        foreach ($args as $i => $arg) {
            if ($arg === $option && isset($args[$i + 1])) {
                return (int) $args[$i + 1];
            }
            if (str_starts_with($arg, $option . '=')) {
                return (int) substr($arg, strlen($option) + 1);
            }
        }
        return $default;
    }

    /**
     * Formate une taille en bytes.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              LUNAR BLOG - Nettoyage                          ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    private function printResults(array $stats): void
    {
        echo "\n";
        echo "┌──────────────────────────────────────────────────────────────┐\n";
        echo "│                    NETTOYAGE TERMINÉ                         │\n";
        echo "├──────────────────────────────────────────────────────────────┤\n";

        if ($stats['orphan_media'] > 0 || $stats['orphan_media_size'] > 0) {
            $size = $this->formatBytes($stats['orphan_media_size']);
            printf("│  %-25s %21s (%s) │\n", "Médias orphelins", $stats['orphan_media'], $size);
        }
        if ($stats['old_drafts'] > 0) {
            printf("│  %-25s %35s │\n", "Brouillons anciens", $stats['old_drafts']);
        }
        if ($stats['old_revisions'] > 0) {
            printf("│  %-25s %35s │\n", "Révisions anciennes", $stats['old_revisions']);
        }
        if ($stats['generated_files'] > 0) {
            printf("│  %-25s %35s │\n", "Fichiers générés", $stats['generated_files']);
        }
        if ($stats['temp_files'] > 0) {
            printf("│  %-25s %35s │\n", "Fichiers temporaires", $stats['temp_files']);
        }

        $total = array_sum($stats) - $stats['orphan_media_size'];
        printf("│  %-25s %35s │\n", "TOTAL", $total);

        echo "└──────────────────────────────────────────────────────────────┘\n";
        echo "\n";

        if ($this->dryRun) {
            echo "Mode simulation : aucun fichier n'a été supprimé.\n";
            echo "Utilisez sans --dry-run pour effectuer le nettoyage.\n";
        } else {
            echo "✓ Nettoyage terminé !\n";
        }
        echo "\n";
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage: blog:clean [options]

Nettoie les données obsolètes du blog.

Options :
  -v, --verbose       Affiche les fichiers traités
  --dry-run           Simule le nettoyage sans supprimer
  --all               Active toutes les options de nettoyage
  --orphans           Supprime les médias non référencés
  --drafts            Archive/supprime les vieux brouillons
  --revisions         Supprime les vieilles révisions
  --generated         Supprime les fichiers HTML générés
  --temp              Supprime les fichiers temporaires
  --draft-days=N      Âge en jours pour les brouillons (défaut: 90)
  --force             Supprime au lieu d'archiver les brouillons

Exemples :
  blog:clean --orphans                    # Médias orphelins seulement
  blog:clean --all --dry-run              # Prévisualisation complète
  blog:clean --drafts --draft-days=30     # Brouillons > 30 jours
  blog:clean --all --force --verbose      # Nettoyage complet détaillé

Types de nettoyage :
  --orphans     Fichiers dans uploads/ non liés à des articles
  --drafts      Brouillons non modifiés depuis N jours
  --revisions   Révisions de plus de 30 jours
  --generated   Fichiers HTML dans public/blog/
  --temp        Fichiers dans var/cache/ et var/tmp/
HELP;
    }
}
