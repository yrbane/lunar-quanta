<?php

declare(strict_types=1);

namespace Tests\Service\Content;

use Lunar\Service\Content\NewsletterService;
use PHPUnit\Framework\TestCase;

final class NewsletterServiceTest extends TestCase
{
    private NewsletterService $service;
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/newsletter-test-' . uniqid() . '.json';
        $this->service = new NewsletterService($this->storagePath);
    }

    protected function tearDown(): void
    {
        @unlink($this->storagePath);
    }

    public function testSubscribeReturnsSuccess(): void
    {
        $result = $this->service->subscribe('test@example.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testSubscribeCreatesFile(): void
    {
        $this->service->subscribe('test@example.com');

        $this->assertFileExists($this->storagePath);
    }

    public function testSubscribeStoresEmail(): void
    {
        $this->service->subscribe('test@example.com', 'John Doe');

        $content = file_get_contents($this->storagePath);
        $data = json_decode($content, true);

        $this->assertCount(1, $data);
        $this->assertSame('test@example.com', $data[0]['email']);
        $this->assertSame('John Doe', $data[0]['name']);
    }

    public function testSubscribeRejectsDuplicate(): void
    {
        $this->service->subscribe('test@example.com');
        $result = $this->service->subscribe('test@example.com');

        $this->assertFalse($result['success']);
    }

    public function testSubscribeRejectsInvalidEmail(): void
    {
        $result = $this->service->subscribe('invalid-email');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalide', $result['message']);
    }

    public function testSubscribeNormalizesEmail(): void
    {
        $this->service->subscribe('  TEST@Example.COM  ');

        $content = file_get_contents($this->storagePath);
        $data = json_decode($content, true);

        $this->assertSame('test@example.com', $data[0]['email']);
    }

    public function testSubscribeWithTags(): void
    {
        $this->service->subscribe('test@example.com', '', ['newsletter', 'updates']);

        $content = file_get_contents($this->storagePath);
        $data = json_decode($content, true);

        $this->assertSame(['newsletter', 'updates'], $data[0]['tags']);
    }

    public function testUnsubscribeMarksAsUnsubscribed(): void
    {
        $this->service->subscribe('test@example.com');
        $result = $this->service->unsubscribe('test@example.com');

        $this->assertTrue($result);

        $content = file_get_contents($this->storagePath);
        $data = json_decode($content, true);

        $this->assertSame('unsubscribed', $data[0]['status']);
    }

    public function testUnsubscribeReturnsFalseForUnknown(): void
    {
        $result = $this->service->unsubscribe('unknown@example.com');

        $this->assertFalse($result);
    }

    public function testIsSubscribedReturnsTrue(): void
    {
        $this->service->subscribe('test@example.com');

        $this->assertTrue($this->service->isSubscribed('test@example.com'));
    }

    public function testIsSubscribedReturnsFalseForUnsubscribed(): void
    {
        $this->service->subscribe('test@example.com');
        $this->service->unsubscribe('test@example.com');

        $this->assertFalse($this->service->isSubscribed('test@example.com'));
    }

    public function testGetActiveCountReturnsCount(): void
    {
        $this->service->subscribe('test1@example.com');
        $this->service->subscribe('test2@example.com');

        $this->assertSame(2, $this->service->getActiveCount());
    }

    public function testGetActiveCountExcludesUnsubscribed(): void
    {
        $this->service->subscribe('test1@example.com');
        $this->service->subscribe('test2@example.com');
        $this->service->unsubscribe('test1@example.com');

        $this->assertSame(1, $this->service->getActiveCount());
    }

    public function testGetActiveSubscribersReturnsActive(): void
    {
        $this->service->subscribe('test1@example.com');
        $this->service->subscribe('test2@example.com');

        $subscribers = $this->service->getActiveSubscribers();

        $this->assertCount(2, $subscribers);
    }

    public function testExportCsvReturnsValidCsv(): void
    {
        $this->service->subscribe('test@example.com', 'John Doe');

        $csv = $this->service->exportCsv();

        $this->assertStringContainsString('email,name,subscribed_at', $csv);
        $this->assertStringContainsString('test@example.com', $csv);
        $this->assertStringContainsString('John Doe', $csv);
    }

    public function testDoubleOptInCreatesPendingStatus(): void
    {
        $this->service->setDoubleOptIn(true);
        $result = $this->service->subscribe('test@example.com');

        $content = file_get_contents($this->storagePath);
        $data = json_decode($content, true);

        $this->assertSame('pending', $data[0]['status']);
        $this->assertArrayHasKey('confirmation_token', $data[0]);
    }

    public function testConfirmActivatesSubscription(): void
    {
        $this->service->setDoubleOptIn(true);
        $result = $this->service->subscribe('test@example.com');

        $content = file_get_contents($this->storagePath);
        $data = json_decode($content, true);
        $token = $data[0]['confirmation_token'];

        $confirmed = $this->service->confirm($token);

        $this->assertTrue($confirmed);

        $content = file_get_contents($this->storagePath);
        $data = json_decode($content, true);

        $this->assertSame('active', $data[0]['status']);
        $this->assertArrayNotHasKey('confirmation_token', $data[0]);
    }

    public function testConfirmReturnsFalseForInvalidToken(): void
    {
        $result = $this->service->confirm('invalid-token');

        $this->assertFalse($result);
    }

    public function testGenerateFormReturnsHtml(): void
    {
        $form = $this->service->generateForm('/subscribe', 'Newsletter');

        $this->assertStringContainsString('<form', $form);
        $this->assertStringContainsString('action="/subscribe"', $form);
        $this->assertStringContainsString('Newsletter', $form);
        $this->assertStringContainsString('type="email"', $form);
    }

    public function testGenerateCompactFormReturnsHtml(): void
    {
        $form = $this->service->generateCompactForm('/subscribe');

        $this->assertStringContainsString('la-newsletter-compact', $form);
        $this->assertStringContainsString('type="email"', $form);
    }

    public function testGenerateCssReturnsValidCss(): void
    {
        $css = $this->service->generateCss();

        $this->assertStringContainsString('.la-newsletter-form', $css);
        $this->assertStringContainsString('.la-newsletter-fields', $css);
    }

    public function testGenerateScriptReturnsJs(): void
    {
        $script = $this->service->generateScript('/api/subscribe');

        $this->assertStringContainsString('/api/subscribe', $script);
        $this->assertStringContainsString('fetch', $script);
        $this->assertStringContainsString('addEventListener', $script);
    }

    public function testSetSuccessMessageChangesMessage(): void
    {
        $this->service->setSuccessMessage('Custom success');
        $result = $this->service->subscribe('test@example.com');

        $this->assertSame('Custom success', $result['message']);
    }

    public function testFluentInterface(): void
    {
        $result = $this->service
            ->setFormClass('custom-form')
            ->setSuccessMessage('Success!')
            ->setErrorMessage('Error!')
            ->setDoubleOptIn(true);

        $this->assertSame($this->service, $result);
    }

    public function testSubscriberHasMetadata(): void
    {
        $this->service->subscribe('test@example.com');

        $content = file_get_contents($this->storagePath);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('subscribed_at', $data[0]);
    }
}
