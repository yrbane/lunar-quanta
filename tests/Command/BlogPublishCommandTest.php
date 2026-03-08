<?php

declare(strict_types=1);

namespace Tests\Command;

use Lunar\Command\BlogPublishCommand;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour BlogPublishCommand.
 *
 * Vérifie les différents scénarios d'exécution :
 * - Affichage de l'aide (--help ou aucun argument)
 * - Article non trouvé
 * - Article déjà publié (idempotent)
 * - Publication réussie
 */
class BlogPublishCommandTest extends TestCase
{
    private string $storagePath;
    private PostService $postService;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_publish_test_' . uniqid();
        mkdir($this->storagePath, 0755, true);
        $this->postService = new PostService(new FileStorage($this->storagePath));
    }

    protected function tearDown(): void
    {
        // Nettoyer les fichiers de test
        $files = glob($this->storagePath . '/*.json');
        foreach ($files as $file) {
            unlink($file);
        }
        if (is_dir($this->storagePath)) {
            rmdir($this->storagePath);
        }
    }

    private function createCommand(): BlogPublishCommand
    {
        // Sous-classe anonyme pour surcharger la résolution du PostService
        $storagePath = $this->storagePath;
        return new class($storagePath) extends BlogPublishCommand {
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
        $this->assertStringContainsString('blog:publish', $output);
    }

    public function testShowsHelpWithHelpFlag(): void
    {
        $command = $this->createCommand();
        ob_start();
        $result = $command->execute(['--help']);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('blog:publish', $output);
    }

    public function testReturnsErrorForNonExistentPost(): void
    {
        $command = $this->createCommand();
        ob_start();
        $result = $command->execute(['nonexistent-id']);
        $output = ob_get_clean();

        $this->assertSame(1, $result);
        $this->assertStringContainsString('non trouvé', $output);
    }

    public function testPublishesArticleSuccessfully(): void
    {
        $post = $this->postService->create('Test Article', 'Content');
        $this->assertTrue($post->isDraft());

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$post->getId()]);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('publié avec succès', $output);

        // Vérifier que l'article est bien publié en relisant depuis le storage
        $updated = $this->postService->find($post->getId());
        $this->assertTrue($updated->isPublished());
    }

    public function testPublishesBySlug(): void
    {
        $post = $this->postService->create('Mon Article Test', 'Content');

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute(['mon-article-test']);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('publié avec succès', $output);
    }

    public function testAlreadyPublishedReturnsZero(): void
    {
        $post = $this->postService->create('Already Published', 'Content');
        $post->publish();
        $this->postService->update($post);

        $command = $this->createCommand();
        ob_start();
        $result = $command->execute([$post->getId()]);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('déjà publié', $output);
    }

    public function testGetHelpReturnsString(): void
    {
        $command = $this->createCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('blog:publish', $help);
        $this->assertStringContainsString('<id|slug>', $help);
    }
}
