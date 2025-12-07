<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\SchemaOrgService;
use PHPUnit\Framework\TestCase;

final class SchemaOrgServiceTest extends TestCase
{
    private SchemaOrgService $service;

    protected function setUp(): void
    {
        $this->service = new SchemaOrgService();
        $this->service->setBaseUrl('https://example.com');
        $this->service->setSiteName('Mon Site');
    }

    public function testArticleReturnsValidJsonLd(): void
    {
        $json = $this->service->article([
            'title' => 'Mon Article',
            'description' => 'Description',
            'author' => 'John Doe',
            'datePublished' => '2025-01-15',
        ]);

        $data = json_decode($json, true);

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('Article', $data['@type']);
        $this->assertSame('Mon Article', $data['headline']);
        $this->assertSame('John Doe', $data['author']['name']);
    }

    public function testArticleWithImage(): void
    {
        $json = $this->service->article([
            'title' => 'Article',
            'image' => '/images/cover.jpg',
            'imageWidth' => 1200,
            'imageHeight' => 630,
        ]);

        $data = json_decode($json, true);

        $this->assertArrayHasKey('image', $data);
        $this->assertSame('https://example.com/images/cover.jpg', $data['image']['url']);
        $this->assertSame(1200, $data['image']['width']);
    }

    public function testArticleWithPublisher(): void
    {
        $this->service->setLogoUrl('/logo.png');
        $json = $this->service->article(['title' => 'Test']);

        $data = json_decode($json, true);

        $this->assertArrayHasKey('publisher', $data);
        $this->assertSame('Mon Site', $data['publisher']['name']);
        $this->assertArrayHasKey('logo', $data['publisher']);
    }

    public function testBlogPostingReturnsCorrectType(): void
    {
        $json = $this->service->blogPosting([
            'title' => 'Blog Post',
            'datePublished' => '2025-01-15',
        ]);

        $data = json_decode($json, true);

        $this->assertSame('BlogPosting', $data['@type']);
    }

    public function testBreadcrumbReturnsValidJsonLd(): void
    {
        $json = $this->service->breadcrumb([
            ['name' => 'Accueil', 'url' => '/'],
            ['name' => 'Blog', 'url' => '/blog'],
            ['name' => 'Article', 'url' => '/blog/article'],
        ]);

        $data = json_decode($json, true);

        $this->assertSame('BreadcrumbList', $data['@type']);
        $this->assertCount(3, $data['itemListElement']);
        $this->assertSame(1, $data['itemListElement'][0]['position']);
        $this->assertSame('Accueil', $data['itemListElement'][0]['name']);
    }

    public function testOrganizationReturnsValidJsonLd(): void
    {
        $json = $this->service->organization([
            'email' => 'contact@example.com',
            'phone' => '+33123456789',
            'sameAs' => ['https://twitter.com/example', 'https://facebook.com/example'],
        ]);

        $data = json_decode($json, true);

        $this->assertSame('Organization', $data['@type']);
        $this->assertSame('Mon Site', $data['name']);
        $this->assertSame('contact@example.com', $data['email']);
        $this->assertCount(2, $data['sameAs']);
    }

    public function testOrganizationWithAddress(): void
    {
        $json = $this->service->organization([
            'address' => [
                'street' => '123 Rue Test',
                'city' => 'Paris',
                'postalCode' => '75001',
                'country' => 'FR',
            ],
        ]);

        $data = json_decode($json, true);

        $this->assertArrayHasKey('address', $data);
        $this->assertSame('PostalAddress', $data['address']['@type']);
        $this->assertSame('Paris', $data['address']['addressLocality']);
    }

    public function testWebsiteReturnsValidJsonLd(): void
    {
        $json = $this->service->website([
            'searchUrl' => 'https://example.com/search?q={search_term_string}',
        ]);

        $data = json_decode($json, true);

        $this->assertSame('WebSite', $data['@type']);
        $this->assertArrayHasKey('potentialAction', $data);
        $this->assertSame('SearchAction', $data['potentialAction']['@type']);
    }

    public function testWebPageReturnsValidJsonLd(): void
    {
        $json = $this->service->webPage([
            'title' => 'Page Titre',
            'description' => 'Description de la page',
            'url' => '/about',
        ]);

        $data = json_decode($json, true);

        $this->assertSame('WebPage', $data['@type']);
        $this->assertSame('Page Titre', $data['name']);
    }

    public function testPersonReturnsValidJsonLd(): void
    {
        $json = $this->service->person([
            'name' => 'John Doe',
            'url' => '/authors/john',
            'image' => '/images/john.jpg',
            'jobTitle' => 'Developer',
            'sameAs' => ['https://github.com/john'],
        ]);

        $data = json_decode($json, true);

        $this->assertSame('Person', $data['@type']);
        $this->assertSame('John Doe', $data['name']);
        $this->assertSame('Developer', $data['jobTitle']);
    }

    public function testFaqReturnsValidJsonLd(): void
    {
        $json = $this->service->faq([
            ['question' => 'Question 1?', 'answer' => 'Réponse 1'],
            ['question' => 'Question 2?', 'answer' => 'Réponse 2'],
        ]);

        $data = json_decode($json, true);

        $this->assertSame('FAQPage', $data['@type']);
        $this->assertCount(2, $data['mainEntity']);
        $this->assertSame('Question', $data['mainEntity'][0]['@type']);
        $this->assertSame('Answer', $data['mainEntity'][0]['acceptedAnswer']['@type']);
    }

    public function testItemListReturnsValidJsonLd(): void
    {
        $json = $this->service->itemList([
            ['name' => 'Item 1', 'url' => '/item1'],
            ['name' => 'Item 2', 'url' => '/item2'],
        ]);

        $data = json_decode($json, true);

        $this->assertSame('ItemList', $data['@type']);
        $this->assertCount(2, $data['itemListElement']);
        $this->assertSame(1, $data['itemListElement'][0]['position']);
    }

    public function testReviewReturnsValidJsonLd(): void
    {
        $json = $this->service->review([
            'itemType' => 'Book',
            'itemName' => 'Mon Livre',
            'author' => 'John',
            'rating' => 4.5,
            'reviewBody' => 'Excellent livre!',
        ]);

        $data = json_decode($json, true);

        $this->assertSame('Review', $data['@type']);
        $this->assertSame('Book', $data['itemReviewed']['@type']);
        $this->assertSame(4.5, $data['reviewRating']['ratingValue']);
        $this->assertSame(5, $data['reviewRating']['bestRating']);
    }

    public function testEventReturnsValidJsonLd(): void
    {
        $json = $this->service->event([
            'name' => 'Conférence PHP',
            'startDate' => '2025-03-15T09:00:00',
            'endDate' => '2025-03-15T18:00:00',
            'location' => [
                'name' => 'Centre de congrès',
                'address' => 'Paris, France',
            ],
        ]);

        $data = json_decode($json, true);

        $this->assertSame('Event', $data['@type']);
        $this->assertSame('Conférence PHP', $data['name']);
        $this->assertArrayHasKey('location', $data);
        $this->assertSame('Place', $data['location']['@type']);
    }

    public function testCombineMultipleSchemas(): void
    {
        $website = $this->service->website();
        $org = $this->service->organization();

        $combined = $this->service->combine([$website, $org]);
        $data = json_decode($combined, true);

        $this->assertArrayHasKey('@graph', $data);
        $this->assertCount(2, $data['@graph']);
    }

    public function testCombineSingleSchema(): void
    {
        $website = $this->service->website();
        $combined = $this->service->combine([$website]);
        $data = json_decode($combined, true);

        $this->assertArrayNotHasKey('@graph', $data);
        $this->assertSame('WebSite', $data['@type']);
    }

    public function testToScriptWrapsInScriptTag(): void
    {
        $json = $this->service->website();
        $script = $this->service->toScript($json);

        $this->assertStringContainsString('<script type="application/ld+json">', $script);
        $this->assertStringContainsString('</script>', $script);
        $this->assertStringContainsString('WebSite', $script);
    }

    public function testUrlsAreNormalized(): void
    {
        $json = $this->service->article([
            'title' => 'Test',
            'url' => '/blog/test',
            'image' => '/images/test.jpg',
        ]);

        $data = json_decode($json, true);

        $this->assertSame('https://example.com/blog/test', $data['mainEntityOfPage']['@id']);
        $this->assertSame('https://example.com/images/test.jpg', $data['image']['url']);
    }

    public function testAbsoluteUrlsArePreserved(): void
    {
        $json = $this->service->article([
            'title' => 'Test',
            'image' => 'https://cdn.example.com/image.jpg',
        ]);

        $data = json_decode($json, true);

        $this->assertSame('https://cdn.example.com/image.jpg', $data['image']['url']);
    }

    public function testDefaultAuthorIsUsed(): void
    {
        $this->service->setDefaultAuthor('Default Author');
        $json = $this->service->article(['title' => 'Test']);

        $data = json_decode($json, true);

        $this->assertSame('Default Author', $data['author']['name']);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setBaseUrl('https://test.com')
            ->setSiteName('Test')
            ->setDefaultAuthor('Author')
            ->setLogoUrl('/logo.png');

        $this->assertSame($this->service, $result);
    }
}
