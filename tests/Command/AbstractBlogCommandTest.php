<?php

declare(strict_types=1);

namespace Tests\Command;

use Lunar\Command\AbstractBlogCommand;
use Lunar\Entity\Post;
use Lunar\Service\Blog\PostService;
use Lunar\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

class AbstractBlogCommandTest extends TestCase
{
    private string $storagePath;
    private PostService $postService;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lunar_cmd_test_' . uniqid();
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

    public function testCreatePostServiceReturnsPostService(): void
    {
        $command = new class extends AbstractBlogCommand {
            public function execute(array $args): int { return 0; }
            public function getHelp(): string { return ''; }
            public function testCreatePostService(): PostService
            {
                return $this->createPostService();
            }
        };

        $service = $command->testCreatePostService();
        $this->assertInstanceOf(PostService::class, $service);
    }

    public function testFindPostOrFailFindsById(): void
    {
        $command = new class extends AbstractBlogCommand {
            public function execute(array $args): int { return 0; }
            public function getHelp(): string { return ''; }
            public function testFind(PostService $service, string $id): ?Post
            {
                return $this->findPostOrFail($service, $id);
            }
        };

        $post = $this->postService->create('Test Post', 'Content');
        $found = $command->testFind($this->postService, $post->getId());

        $this->assertNotNull($found);
        $this->assertSame($post->getId(), $found->getId());
    }

    public function testFindPostOrFailFindsBySlug(): void
    {
        $command = new class extends AbstractBlogCommand {
            public function execute(array $args): int { return 0; }
            public function getHelp(): string { return ''; }
            public function testFind(PostService $service, string $id): ?Post
            {
                return $this->findPostOrFail($service, $id);
            }
        };

        $this->postService->create('My Test Post', 'Content');
        $found = $command->testFind($this->postService, 'my-test-post');

        $this->assertNotNull($found);
        $this->assertSame('My Test Post', $found->getTitle());
    }

    public function testFindPostOrFailReturnsNullForMissing(): void
    {
        $command = new class extends AbstractBlogCommand {
            public function execute(array $args): int { return 0; }
            public function getHelp(): string { return ''; }
            public function testFind(PostService $service, string $id): ?Post
            {
                return $this->findPostOrFail($service, $id);
            }
        };

        $found = $command->testFind($this->postService, 'nonexistent');
        $this->assertNull($found);
    }
}
