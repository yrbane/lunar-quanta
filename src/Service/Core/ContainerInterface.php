<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

namespace Lunar\Service\Core;

use Lunar\Exception\ContainerException;

/**
 * Interface for the dependency injection container.
 *
 * Defines the contract for service registration and resolution.
 */
interface ContainerInterface
{
    /**
     * Check if a service is registered.
     */
    public function has(string $id): bool;

    /**
     * Get a service by class name or interface.
     *
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     *
     * @throws ContainerException
     */
    public function get(string $id): object;
}
