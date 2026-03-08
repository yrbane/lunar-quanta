<?php

declare(strict_types=1);

namespace Tests\Command;

use Lunar\Command\BlogUnpublishCommand;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour BlogUnpublishCommand.
 *
 * Vérifie le retour en brouillon d'un article publié.
 */
class BlogUnpublishCommandTest extends TestCase
{
    private string $storagePath;
    private PostService $postService;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_unpublish_test_' . uniqid();
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

    private function createCommand(): BlogUnpublishCommand
    {
        $storagePath = $this->storagePath;
        return new class($storagePath) extends BlogUnpublishCommand {
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
        $this->assertStringContainsString('blog:unpublish', $output);
    }

    public function testShowsHelpWithHelpFlag(): void
    {
        $command = $this->createCommand();
        ob_start();
        $result = $command->execute(['--help']);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('blog:unpublish', $output);
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

    public function testUnpublishesArticleSuccessfully(): void
    {
        // Créer et publier un article
        $post = $this->postService->create('Published Article', 'Content');
        $post->publish();
        $this->postService->update($post);
        $this->assertTrue($post->isPublished());

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$post->getId()]);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('dépublié avec succès', $output);

        // Vérifier le statut
        $updated = $this->postService->find($post->getId());
        $this->assertTrue($updated->isDraft());
    }

    public function testAlreadyDraftReturnsZero(): void
    {
        $post = $this->postService->create('Draft Article', 'Content');
        $this->assertTrue($post->isDraft());

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$post->getId()]);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('déjà en brouillon', $output);
    }

    public function testGetHelpReturnsString(): void
    {
        $command = $this->createCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('blog:unpublish', $help);
        $this->assertStringContainsString('<id|slug>', $help);
    }
}
