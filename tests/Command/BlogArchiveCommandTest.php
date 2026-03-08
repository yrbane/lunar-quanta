<?php

declare(strict_types=1);

namespace Tests\Command;

use Lunar\Command\BlogArchiveCommand;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour BlogArchiveCommand.
 *
 * Vérifie l'archivage d'un article avec les cas :
 * - Article brouillon -> archivé
 * - Article publié -> archivé (avec rappel de régénérer)
 * - Article déjà archivé (idempotent)
 */
class BlogArchiveCommandTest extends TestCase
{
    private string $storagePath;
    private PostService $postService;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_archive_test_' . uniqid();
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

    private function createCommand(): BlogArchiveCommand
    {
        $storagePath = $this->storagePath;
        return new class($storagePath) extends BlogArchiveCommand {
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
        $this->assertStringContainsString('blog:archive', $output);
    }

    public function testShowsHelpWithHelpFlag(): void
    {
        $command = $this->createCommand();
        ob_start();
        $result = $command->execute(['--help']);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('blog:archive', $output);
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

    public function testArchivesDraftSuccessfully(): void
    {
        $post = $this->postService->create('Draft to Archive', 'Content');

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$post->getId()]);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('archivé avec succès', $output);

        $updated = $this->postService->find($post->getId());
        $this->assertTrue($updated->isArchived());
    }

    public function testArchivesPublishedWithRegenerateReminder(): void
    {
        $post = $this->postService->create('Published to Archive', 'Content');
        $post->publish();
        $this->postService->update($post);

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$post->getId()]);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('archivé avec succès', $output);
        // Rappel de régénérer car l'article était publié
        $this->assertStringContainsString('blog:regenerate', $output);
    }

    public function testAlreadyArchivedReturnsZero(): void
    {
        $post = $this->postService->create('Already Archived', 'Content');
        $post->archive();
        $this->postService->update($post);

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$post->getId()]);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('déjà archivé', $output);
    }

    public function testGetHelpReturnsString(): void
    {
        $command = $this->createCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('blog:archive', $help);
        $this->assertStringContainsString('<id|slug>', $help);
    }
}
