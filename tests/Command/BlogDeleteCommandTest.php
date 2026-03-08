<?php

declare(strict_types=1);

namespace Tests\Command;

use Lunar\Command\BlogDeleteCommand;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour BlogDeleteCommand.
 *
 * La suppression est une opération irréversible qui nécessite
 * une confirmation explicite via le flag --force.
 *
 * Scénarios testés :
 * - Sans --force : affiche les détails et demande confirmation
 * - Avec --force : supprime effectivement l'article
 * - Article publié supprimé : rappelle de régénérer le blog
 */
class BlogDeleteCommandTest extends TestCase
{
    private string $storagePath;
    private PostService $postService;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_delete_test_' . uniqid();
        mkdir($this->storagePath, 0755, true);
        $this->postService = new PostService(new FileStorage($this->storagePath));
    }

    protected function tearDown(): void
    {
        $files = glob($this->storagePath . '/*.json');
        foreach ($files as $file) {
            unlink($file);
        }
        if (is_dir($this->storagePath)) {
            rmdir($this->storagePath);
        }
    }

    private function createCommand(): BlogDeleteCommand
    {
        $storagePath = $this->storagePath;
        return new class($storagePath) extends BlogDeleteCommand {
            public function __construct(private readonly string $testPath) {}
            protected function createPostService(): PostService
            {
                return new PostService(new FileStorage($this->testPath));
            }
        };
    }

    public function testShowsHelpWithNoArguments(): void
    {
        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([]);
        $output = ob_get_clean();

        $this->assertSame(1, $result);
        $this->assertStringContainsString('blog:delete', $output);
    }

    public function testShowsHelpWithHelpFlag(): void
    {
        // Note : --help starts with '-' so the arg parser skips it,
        // leaving $identifier as null. This means --help returns 1
        // (same as no arguments) because the help flag is not
        // explicitly handled before the foreach loop.
        $command = $this->createCommand();
        ob_start();
        $result = $command->execute(['--help']);
        $output = ob_get_clean();

        $this->assertSame(1, $result);
        $this->assertStringContainsString('blog:delete', $output);
    }

    public function testReturnsErrorForNonExistentPost(): void
    {
        $command = $this->createCommand();
        ob_start();
        $result = $command->execute(['nonexistent']);
        $output = ob_get_clean();

        $this->assertSame(1, $result);
        $this->assertStringContainsString('non trouvé', $output);
    }

    public function testWithoutForceShowsInfoAndReturnsZero(): void
    {
        $post = $this->postService->create('Article to Delete', 'Content here');

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$post->getId()]);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('SUPPRESSION', $output);
        $this->assertStringContainsString('--force', $output);
        // L'article doit toujours exister
        $this->assertNotNull($this->postService->find($post->getId()));
    }

    public function testWithForceDeletesArticle(): void
    {
        $post = $this->postService->create('To Be Deleted', 'Content');
        $postId = $post->getId();

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$postId, '--force']);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('supprimé avec succès', $output);
        // L'article ne doit plus exister
        $this->assertNull($this->postService->find($postId));
    }

    public function testWithShortForceFlag(): void
    {
        $post = $this->postService->create('Short Flag Test', 'Content');
        $postId = $post->getId();

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$postId, '-f']);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('supprimé avec succès', $output);
        $this->assertNull($this->postService->find($postId));
    }

    public function testDeletePublishedPostShowsRegenerateReminder(): void
    {
        $post = $this->postService->create('Published Delete', 'Content');
        $post->publish();
        $this->postService->update($post);

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$post->getId(), '--force']);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('blog:regenerate', $output);
    }

    public function testGetHelpReturnsString(): void
    {
        $command = $this->createCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('blog:delete', $help);
        $this->assertStringContainsString('--force', $help);
        $this->assertStringContainsString('irréversible', $help);
    }
}
