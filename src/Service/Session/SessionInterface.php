<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Session;

/**
 * Interface for session management.
 *
 * Provides methods for storing and retrieving session data,
 * including flash messages that persist only for one request.
 */
interface SessionInterface
{
    /**
     * Start the session.
     */
    public function start(): void;

    /**
     * Get a value from the session.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a value in the session.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a key exists in the session.
     */
    public function has(string $key): bool;

    /**
     * Remove a value from the session.
     */
    public function remove(string $key): void;

    /**
     * Set a flash message (available only for the next request).
     */
    public function flash(string $key, mixed $value): void;

    /**
     * Get a flash message and remove it.
     */
    public function getFlash(string $key, mixed $default = null): mixed;

    /**
     * Regenerate the session ID.
     */
    public function regenerate(): void;

    /**
     * Destroy the session.
     */
    public function destroy(): void;

    /**
     * Get all session data.
     *
     * @return array<string, mixed>
     */
    public function all(): array;
}
