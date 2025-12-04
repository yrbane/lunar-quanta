<?php

declare(strict_types=1);

namespace Tests\Security;

use Lunar\Service\Core\Template\LunarTemplateAdapter;
use Lunar\Service\Security\EncryptionService;
use PHPUnit\Framework\TestCase;

/**
 * Security audit tests per Constitution III requirements.
 *
 * Verifies:
 * - XSS prevention via output escaping
 * - Path traversal prevention
 * - Encryption integrity (HMAC verification)
 * - Timing attack resistance
 */
class SecurityAuditTest extends TestCase
{
    private string $templatePath;

    protected function setUp(): void
    {
        $this->templatePath = dirname(__DIR__, 2) . '/template';
    }

    protected function tearDown(): void
    {
        // Clean up test templates
        $testFiles = glob($this->templatePath . '/sec_test_*.html.tpl');
        if ($testFiles) {
            foreach ($testFiles as $file) {
                @unlink($file);
            }
        }
    }

    // ========================================
    // XSS Prevention Tests
    // ========================================

    public function testTemplateEscapesHtmlEntities(): void
    {
        file_put_contents(
            $this->templatePath . '/sec_test_xss.html.tpl',
            '<p>[[ content ]]</p>'
        );

        $adapter = new LunarTemplateAdapter($this->templatePath);
        $output = $adapter->render('sec_test_xss.html', [
            'content' => '<script>alert("XSS")</script>',
        ]);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testTemplateEscapesAttributeBreakout(): void
    {
        file_put_contents(
            $this->templatePath . '/sec_test_attr.html.tpl',
            '<a href="[[ url ]]">Link</a>'
        );

        $adapter = new LunarTemplateAdapter($this->templatePath);
        $output = $adapter->render('sec_test_attr.html', [
            'url' => '" onclick="alert(\'XSS\')" data-x="',
        ]);

        // Should escape quotes to prevent attribute breakout
        // The malicious content stays inside the href attribute as escaped text
        $this->assertStringContainsString('&quot;', $output);
        // Verify it's a single href attribute, not broken out into onclick
        $this->assertMatchesRegularExpression('/<a href="[^"]*">/', $output);
    }

    public function testTemplateEscapesQuotes(): void
    {
        file_put_contents(
            $this->templatePath . '/sec_test_quotes.html.tpl',
            '<div title="[[ title ]]">Test</div>'
        );

        $adapter = new LunarTemplateAdapter($this->templatePath);
        $output = $adapter->render('sec_test_quotes.html', [
            'title' => '"><script>alert(1)</script>',
        ]);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&quot;', $output);
    }

    public function testTemplateEscapesEventHandlers(): void
    {
        file_put_contents(
            $this->templatePath . '/sec_test_event.html.tpl',
            '<div>[[ content ]]</div>'
        );

        $adapter = new LunarTemplateAdapter($this->templatePath);
        $output = $adapter->render('sec_test_event.html', [
            'content' => '<img src=x onerror="alert(1)">',
        ]);

        // The <img> tag should be escaped, preventing it from being rendered as HTML
        $this->assertStringNotContainsString('<img', $output);
        $this->assertStringContainsString('&lt;img', $output);
    }

    // ========================================
    // Path Traversal Prevention Tests
    // ========================================

    public function testTemplateRejectsPathTraversal(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        // Attempt path traversal
        $this->assertFalse(
            $adapter->templateExists('../../../etc/passwd'),
            'Path traversal should be rejected'
        );
    }

    public function testTemplateRejectsAbsolutePath(): void
    {
        $adapter = new LunarTemplateAdapter($this->templatePath);

        $this->assertFalse(
            $adapter->templateExists('/etc/passwd'),
            'Absolute paths outside template directory should be rejected'
        );
    }

    // ========================================
    // Encryption Security Tests
    // ========================================

    public function testEncryptionUsesRandomIV(): void
    {
        $encryption = new EncryptionService('test_key');
        $plaintext = 'Same data';

        $encrypted1 = $encryption->encrypt($plaintext);
        $encrypted2 = $encryption->encrypt($plaintext);

        // Same plaintext should produce different ciphertext due to random IV
        $this->assertNotEquals($encrypted1, $encrypted2);
    }

    public function testHmacDetectsTampering(): void
    {
        $this->expectException(\Lunar\Exception\SecurityException::class);

        $encryption = new EncryptionService('test_key');
        $encrypted = $encryption->encrypt('Sensitive data');

        // Tamper with ciphertext
        $decoded = base64_decode($encrypted);
        $tampered = substr($decoded, 0, 20) . chr(ord($decoded[20]) ^ 0xFF) . substr($decoded, 21);
        $tamperedEncrypted = base64_encode($tampered);

        $encryption->decrypt($tamperedEncrypted);
    }

    public function testHmacDetectsTruncation(): void
    {
        $this->expectException(\Lunar\Exception\SecurityException::class);

        $encryption = new EncryptionService('test_key');
        $encrypted = $encryption->encrypt('Sensitive data');

        // Truncate the HMAC
        $decoded = base64_decode($encrypted);
        $truncated = substr($decoded, 0, -16);
        $truncatedEncrypted = base64_encode($truncated);

        $encryption->decrypt($truncatedEncrypted);
    }

    public function testDifferentKeysCannotDecrypt(): void
    {
        $this->expectException(\Lunar\Exception\SecurityException::class);

        $encryption1 = new EncryptionService('key1');
        $encryption2 = new EncryptionService('key2');

        $encrypted = $encryption1->encrypt('Secret');
        $encryption2->decrypt($encrypted);
    }

    public function testInvalidBase64Rejected(): void
    {
        $this->expectException(\Lunar\Exception\SecurityException::class);

        $encryption = new EncryptionService('test_key');
        $encryption->decrypt('not-valid-base64!!!');
    }

    // ========================================
    // Timing Attack Resistance Tests
    // ========================================

    public function testHmacComparisonIsConstantTime(): void
    {
        $encryption = new EncryptionService('test_key');
        $plaintext = 'Test data for timing analysis';
        $encrypted = $encryption->encrypt($plaintext);

        // Valid decryption timing
        $validTimes = [];
        for ($i = 0; $i < 10; $i++) {
            $start = hrtime(true);
            try {
                $encryption->decrypt($encrypted);
            } catch (\Exception $e) {
                // Ignore
            }
            $validTimes[] = hrtime(true) - $start;
        }

        // Invalid HMAC timing (should be similar due to constant-time comparison)
        $invalidTimes = [];
        $decoded = base64_decode($encrypted);
        $tampered = substr($decoded, 0, -1) . 'X'; // Change last HMAC byte
        $tamperedEncrypted = base64_encode($tampered);

        for ($i = 0; $i < 10; $i++) {
            $start = hrtime(true);
            try {
                $encryption->decrypt($tamperedEncrypted);
            } catch (\Exception $e) {
                // Expected
            }
            $invalidTimes[] = hrtime(true) - $start;
        }

        // Calculate averages (excluding outliers)
        sort($validTimes);
        sort($invalidTimes);

        $validAvg = array_sum(array_slice($validTimes, 2, 6)) / 6;
        $invalidAvg = array_sum(array_slice($invalidTimes, 2, 6)) / 6;

        // Timing should be similar (within 10x) - constant time comparison
        // Note: This is a weak test due to system noise, but validates the approach
        $ratio = $validAvg > 0 ? $invalidAvg / $validAvg : 1;
        $this->assertLessThan(10, $ratio, 'HMAC comparison should be constant-time');
        $this->assertGreaterThan(0.1, $ratio, 'HMAC comparison should be constant-time');
    }
}
