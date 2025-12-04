<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Session;

/**
 * Session service with secure defaults.
 *
 * Provides session management with flash message support
 * and security best practices.
 */
class SessionService implements SessionInterface
{
    private const FLASH_KEY = '_flash';
    private const FLASH_NEW_KEY = '_flash_new';

    /** @var array<string, mixed> In-memory session data for test mode */
    private array $testData = [];

    private bool $testMode;
    private bool $started = false;

    /**
     * Create a new session service.
     *
     * @param bool $testMode If true, uses in-memory storage instead of PHP sessions
     */
    public function __construct(bool $testMode = false)
    {
        $this->testMode = $testMode;

        if (!$testMode) {
            $this->configureSecureSession();
        }
    }

    /**
     * Configure secure session settings.
     */
    private function configureSecureSession(): void
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            return;
        }

        // Security settings
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');

        // session.sid_length and session.sid_bits_per_character are deprecated in PHP 8.4+
        // Default values in PHP 8.4+ provide adequate security

        // Set secure flag if HTTPS
        if (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']) {
            ini_set('session.cookie_secure', '1');
        }
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (!$this->testMode) {
            if (PHP_SESSION_ACTIVE !== session_status()) {
                session_start();
            }
            $this->ageFlashData();
        }

        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        if ($this->testMode) {
            return $this->testData[$key] ?? $default;
        }

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();

        if ($this->testMode) {
            $this->testData[$key] = $value;
        } else {
            $_SESSION[$key] = $value;
        }
    }

    public function has(string $key): bool
    {
        $this->ensureStarted();

        if ($this->testMode) {
            return array_key_exists($key, $this->testData);
        }

        return array_key_exists($key, $_SESSION);
    }

    public function remove(string $key): void
    {
        $this->ensureStarted();

        if ($this->testMode) {
            unset($this->testData[$key]);
        } else {
            unset($_SESSION[$key]);
        }
    }

    public function flash(string $key, mixed $value): void
    {
        $this->ensureStarted();

        if ($this->testMode) {
            /** @var array<string, mixed> $flash */
            $flash = $this->testData[self::FLASH_KEY] ?? [];
            $flash[$key] = $value;
            $this->testData[self::FLASH_KEY] = $flash;

            /** @var array<string> $newFlash */
            $newFlash = $this->testData[self::FLASH_NEW_KEY] ?? [];
            $newFlash[] = $key;
            $this->testData[self::FLASH_NEW_KEY] = array_unique($newFlash);
        } else {
            /** @var array<string, mixed> $flash */
            $flash = $_SESSION[self::FLASH_KEY] ?? [];
            $flash[$key] = $value;
            $_SESSION[self::FLASH_KEY] = $flash;

            /** @var array<string> $newFlash */
            $newFlash = $_SESSION[self::FLASH_NEW_KEY] ?? [];
            $newFlash[] = $key;
            $_SESSION[self::FLASH_NEW_KEY] = array_unique($newFlash);
        }
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        if ($this->testMode) {
            /** @var array<string, mixed> $flash */
            $flash = $this->testData[self::FLASH_KEY] ?? [];
            $value = $flash[$key] ?? $default;
            unset($flash[$key]);
            $this->testData[self::FLASH_KEY] = $flash;
            return $value;
        }

        /** @var array<string, mixed> $flash */
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        $value = $flash[$key] ?? $default;
        unset($flash[$key]);
        $_SESSION[self::FLASH_KEY] = $flash;

        return $value;
    }

    public function regenerate(): void
    {
        if (!$this->testMode && PHP_SESSION_ACTIVE === session_status()) {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        if ($this->testMode) {
            $this->testData = [];
            $this->started = false;
            return;
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                $sessionName = session_name();
                if (false !== $sessionName) {
                    setcookie(
                        $sessionName,
                        '',
                        time() - 42000,
                        $params['path'],
                        $params['domain'],
                        $params['secure'],
                        $params['httponly']
                    );
                }
            }

            session_destroy();
        }

        $this->started = false;
    }

    public function all(): array
    {
        $this->ensureStarted();

        if ($this->testMode) {
            $data = $this->testData;
            unset($data[self::FLASH_KEY], $data[self::FLASH_NEW_KEY]);
            /** @var array<string, mixed> $data */
            return $data;
        }

        $data = $_SESSION;
        unset($data[self::FLASH_KEY], $data[self::FLASH_NEW_KEY]);
        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * Age flash data - remove old flash, keep new flash.
     */
    private function ageFlashData(): void
    {
        if ($this->testMode) {
            return;
        }

        /** @var array<string, mixed> $flash */
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        /** @var array<string> $newKeys */
        $newKeys = $_SESSION[self::FLASH_NEW_KEY] ?? [];

        // Remove flash data that wasn't set in this request
        foreach (array_keys($flash) as $key) {
            if (!in_array($key, $newKeys, true)) {
                unset($flash[$key]);
            }
        }

        $_SESSION[self::FLASH_KEY] = $flash;
        $_SESSION[self::FLASH_NEW_KEY] = [];
    }

    /**
     * Ensure session is started.
     */
    private function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }
}
