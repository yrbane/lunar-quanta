<?php

declare(strict_types=1);

namespace Lunar\Command;

use Lunar\Cli\Attribute\Command;
use Lunar\Cli\CommandInterface;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Blog\ArticleValidator;
use Lunar\Service\Blog\SourceFinder;
use Lunar\Service\Storage\FileStorage;

#[Command(name: 'blog:batch-process', description: 'Batch process articles: validate and find sources.')]
class BlogBatchProcessCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $basePath = dirname(__DIR__, 2);
        $dryRun = in_array('--dry-run', $args, true);

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║            BATCH PROCESSING BLOG ARTICLES                    ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        if ($dryRun) {
            echo "ℹ DRY RUN MODE: No changes will be saved.\n\n";
        }

        try {
            $postStorage = new FileStorage($basePath . '/data/blog/posts');
            $postService = new PostService($postStorage);
            $validator = new ArticleValidator();
            $sourceFinder = new SourceFinder();

            $posts = $postService->all();
            $total = count($posts);
            echo "Found {$total} articles to process.\n\n";

            $stats = [
                'processed' => 0,
                'updated' => 0,
                'errors' => 0,
                'sources_added' => 0,
            ];

            foreach ($posts as $post) {
                $stats['processed']++;
                $isUpdated = false;
                echo "• Processing: " . mb_substr($post->getTitle(), 0, 40) . "... ";

                // 1. Initial Validation
                $validationErrors = $validator->validate($post);
                
                // 2. Source Finding if needed
                if (!$post->hasValidSources(2)) {
                    echo "[Searching sources] ";
                    $foundSources = $sourceFinder->findSources($post);
                    
                    if (!empty($foundSources)) {
                        $count = count($foundSources);
                        foreach ($foundSources as $src) {
                            $post->addSource($src['title'], $src['url'], $src['description'] ?? null);
                        }
                        $isUpdated = true;
                        $stats['sources_added'] += $count;
                        echo "(Added {$count}) ";
                    }
                }

                // 3. Re-Validation
                $finalErrors = $validator->validate($post);

                if (empty($finalErrors)) {
                    echo "✓ OK\n";
                } else {
                    echo "⚠ Issues:\n";
                    foreach ($finalErrors as $err) {
                        echo "    - {$err}\n";
                    }
                    $stats['errors']++;
                }

                // 4. Save if needed
                if ($isUpdated && !$dryRun) {
                    $postService->update($post);
                    $stats['updated']++;
                }
            }

            echo "\n";
            echo "┌──────────────────────────────────────────────────────────────┐\n";
            echo "│                      SUMMARY                                 │\n";
            echo "├──────────────────────────────────────────────────────────────┤\n";
            printf("│  %-25s : %-30d │\n", "Articles Processed", $stats['processed']);
            printf("│  %-25s : %-30d │\n", "Articles Updated", $stats['updated']);
            printf("│  %-25s : %-30d │\n", "Sources Added", $stats['sources_added']);
            printf("│  %-25s : %-30d │\n", "Articles with Issues", $stats['errors']);
            echo "└──────────────────────────────────────────────────────────────┘\n";
            echo "\n";

            return 0;

        } catch (\Throwable $e) {
            echo "\n✗ Fatal Error: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            return 1;
        }
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Command: blog:batch-process
Iterates through all blog posts, validates them, and attempts to find missing sources/references.

Usage:
  ./bin/console blog:batch-process [options]

Options:
  --dry-run    Run without saving changes.
  --help       Show this help.

HELP;
    }
}
