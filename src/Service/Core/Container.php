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
 * Lightweight DI container with recursive dependency resolution.
 *
 * Implements ContainerInterface for SOLID compliance.
 */
class Container implements ContainerInterface
{
    /**
     * Instantiated services (singletons).
     *
     * @var array<class-string, object>
     */
    private array $instances = [];

    /**
     * Classes currently being resolved (for circular dependency detection).
     *
     * @var array<class-string, bool>
     */
    private array $resolving = [];

    /**
     * Check if a service is registered or can be instantiated.
     */
    public function has(string $id): bool
    {
        if (isset($this->instances[$id])) {
            return true;
        }

        return class_exists($id);
    }

    /**
     * Instantiate a class by recursively resolving its dependencies.
     *
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws ContainerException if the class cannot be instantiated or has circular dependencies
     */
    public function get(string $className): object
    {
        if (isset($this->instances[$className])) {
            /** @var T */
            return $this->instances[$className];
        }

        // Circular dependency detection
        if (isset($this->resolving[$className])) {
            $chain = array_keys($this->resolving);
            $chain[] = $className;

            throw new ContainerException(
                sprintf(
                    'Circular dependency detected: %s',
                    implode(' -> ', $chain)
                )
            );
        }

        $this->resolving[$className] = true;

        try {
            $refClass = new \ReflectionClass($className);

            if (!$refClass->isInstantiable()) {
                throw new ContainerException("Class {$className} is not instantiable.");
            }

            $constructor = $refClass->getConstructor();
            if (null === $constructor) {
                $instance = new $className();
                $this->instances[$className] = $instance;

                return $instance;
            }

            $args = array_map(function (\ReflectionParameter $param) use ($className): object {
                $type = $param->getType();
                if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                    throw new ContainerException(
                        "Cannot resolve dependency `{$param->getName()}` in {$className}."
                    );
                }

                /** @var class-string $dependencyClass */
                $dependencyClass = $type->getName();

                return $this->get($dependencyClass); // recursion here
            }, $constructor->getParameters());

            $instance = $refClass->newInstanceArgs($args);
            $this->instances[$className] = $instance;

            return $instance;
        } finally {
            unset($this->resolving[$className]);
        }
    }
}
