<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\MetaTagService;
use PHPUnit\Framework\TestCase;

final class MetaTagServiceTest extends TestCase
{
    private MetaTagService $service;

    protected function setUp(): void
    {
        $this->service = new MetaTagService();
    }

    public function testGenerateBasicReturnsDescription(): void
    {
        $result = $this->service->generateBasic([
            'description' => 'Test description',
        ]);

        $this->assertStringContainsString('name="description"', $result);
        $this->assertStringContainsString('Test description', $result);
    }

    public function testGenerateBasicReturnsAuthor(): void
    {
        $result = $this->service->generateBasic([
            'author' => 'John Doe',
        ]);

        $this->assertStringContainsString('name="author"', $result);
        $this->assertStringContainsString('John Doe', $result);
    }

    public function testGenerateBasicReturnsKeywords(): void
    {
        $result = $this->service->generateBasic([
            'keywords' => ['php', 'test'],
        ]);

        $this->assertStringContainsString('name="keywords"', $result);
        $this->assertStringContainsString('php, test', $result);
    }

    public function testGenerateBasicReturnsCanonicalUrl(): void
    {
        $result = $this->service->generateBasic([
            'url' => 'https://example.com/article',
        ]);

        $this->assertStringContainsString('rel="canonical"', $result);
        $this->assertStringContainsString('https://example.com/article', $result);
    }

    public function testGenerateOpenGraphReturnsTitle(): void
    {
        $result = $this->service->generateOpenGraph([
            'title' => 'Test Title',
        ]);

        $this->assertStringContainsString('og:title', $result);
        $this->assertStringContainsString('Test Title', $result);
    }

    public function testGenerateOpenGraphReturnsTitleWithSiteName(): void
    {
        $this->service->setSiteName('My Site');
        $result = $this->service->generateOpenGraph([
            'title' => 'Test Title',
        ]);

        $this->assertStringContainsString('Test Title - My Site', $result);
    }

    public function testGenerateOpenGraphReturnsDescription(): void
    {
        $result = $this->service->generateOpenGraph([
            'description' => 'Test description',
        ]);

        $this->assertStringContainsString('og:description', $result);
        $this->assertStringContainsString('Test description', $result);
    }

    public function testGenerateOpenGraphReturnsType(): void
    {
        $result = $this->service->generateOpenGraph([
            'type' => 'article',
        ]);

        $this->assertStringContainsString('og:type', $result);
        $this->assertStringContainsString('article', $result);
    }

    public function testGenerateOpenGraphReturnsUrl(): void
    {
        $result = $this->service->generateOpenGraph([
            'url' => 'https://example.com/article',
        ]);

        $this->assertStringContainsString('og:url', $result);
        $this->assertStringContainsString('https://example.com/article', $result);
    }

    public function testGenerateOpenGraphReturnsImage(): void
    {
        $result = $this->service->generateOpenGraph([
            'image' => 'https://example.com/image.jpg',
        ]);

        $this->assertStringContainsString('og:image', $result);
        $this->assertStringContainsString('https://example.com/image.jpg', $result);
    }

    public function testGenerateOpenGraphUsesDefaultImage(): void
    {
        $this->service->setDefaultImage('https://example.com/default.jpg');
        $result = $this->service->generateOpenGraph([
            'title' => 'Test',
        ]);

        $this->assertStringContainsString('og:image', $result);
        $this->assertStringContainsString('https://example.com/default.jpg', $result);
    }

    public function testGenerateOpenGraphMakesImageAbsolute(): void
    {
        $this->service->setSiteUrl('https://example.com');
        $result = $this->service->generateOpenGraph([
            'image' => '/images/cover.jpg',
        ]);

        $this->assertStringContainsString('https://example.com/images/cover.jpg', $result);
    }

    public function testGenerateTwitterCardsReturnsSummaryLargeImage(): void
    {
        $result = $this->service->generateTwitterCards([
            'image' => 'https://example.com/image.jpg',
        ]);

        $this->assertStringContainsString('twitter:card', $result);
        $this->assertStringContainsString('summary_large_image', $result);
    }

    public function testGenerateTwitterCardsReturnsSummaryWithoutImage(): void
    {
        $result = $this->service->generateTwitterCards([
            'title' => 'Test',
        ]);

        $this->assertStringContainsString('twitter:card', $result);
        $this->assertStringContainsString('summary', $result);
        $this->assertStringNotContainsString('summary_large_image', $result);
    }

    public function testGenerateTwitterCardsReturnsTwitterSite(): void
    {
        $this->service->setTwitterSite('@mysite');
        $result = $this->service->generateTwitterCards([
            'title' => 'Test',
        ]);

        $this->assertStringContainsString('twitter:site', $result);
        $this->assertStringContainsString('@mysite', $result);
    }

    public function testGenerateArticleMetaReturnsPublishedTime(): void
    {
        $result = $this->service->generateArticleMeta([
            'datePublished' => '2024-01-15T10:00:00Z',
        ]);

        $this->assertStringContainsString('article:published_time', $result);
        $this->assertStringContainsString('2024-01-15T10:00:00Z', $result);
    }

    public function testGenerateArticleMetaReturnsModifiedTime(): void
    {
        $result = $this->service->generateArticleMeta([
            'dateModified' => '2024-01-16T10:00:00Z',
        ]);

        $this->assertStringContainsString('article:modified_time', $result);
        $this->assertStringContainsString('2024-01-16T10:00:00Z', $result);
    }

    public function testGenerateArticleMetaReturnsTags(): void
    {
        $result = $this->service->generateArticleMeta([
            'keywords' => ['php', 'test'],
        ]);

        $this->assertStringContainsString('article:tag', $result);
        $this->assertStringContainsString('php', $result);
        $this->assertStringContainsString('test', $result);
    }

    public function testGenerateAllCombinesAllTags(): void
    {
        $result = $this->service->generateAll([
            'title' => 'Test Title',
            'description' => 'Test description',
            'author' => 'John Doe',
            'url' => 'https://example.com/article',
            'type' => 'article',
        ]);

        $this->assertStringContainsString('name="description"', $result);
        $this->assertStringContainsString('og:title', $result);
        $this->assertStringContainsString('twitter:card', $result);
        $this->assertStringContainsString('article:author', $result);
    }

    public function testMetaGeneratesSimpleTag(): void
    {
        $result = $this->service->meta('robots', 'index, follow');

        $this->assertStringContainsString('name="robots"', $result);
        $this->assertStringContainsString('index, follow', $result);
    }

    public function testPropertyGeneratesPropertyTag(): void
    {
        $result = $this->service->property('og:custom', 'value');

        $this->assertStringContainsString('property="og:custom"', $result);
        $this->assertStringContainsString('value', $result);
    }

    public function testDescriptionIsTruncated(): void
    {
        $longDescription = str_repeat('a', 200);
        $result = $this->service->generateBasic([
            'description' => $longDescription,
        ]);

        $this->assertStringContainsString('...', $result);
    }

    public function testSetLocaleChangesLocale(): void
    {
        $this->service->setLocale('en_US');
        $result = $this->service->generateOpenGraph([
            'title' => 'Test',
        ]);

        $this->assertStringContainsString('og:locale', $result);
        $this->assertStringContainsString('en_US', $result);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setSiteName('Test')
            ->setSiteUrl('https://example.com')
            ->setDefaultImage('/default.jpg')
            ->setTwitterSite('@test')
            ->setLocale('en_US');

        $this->assertSame($this->service, $result);
    }

    public function testEscapesSpecialCharacters(): void
    {
        $result = $this->service->generateBasic([
            'description' => 'Test "with" special & characters',
        ]);

        $this->assertStringContainsString('&quot;with&quot;', $result);
        $this->assertStringContainsString('special &amp; characters', $result);
    }
}
