# Internal Contracts: Lunar Quanta Framework

**Date**: 2025-12-03
**Branch**: `001-framework-baseline`

## Overview

This document defines the public interfaces (contracts) that framework components must implement. These interfaces ensure SOLID compliance (Constitution VI) and enable testability.

## Core Interfaces

### RouterInterface

```php
<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Service\Core\Http\Request;

interface RouterInterface
{
    /**
     * Register a route from controller attributes.
     *
     * @param string $path URL pattern
     * @param string $controller Fully qualified class name
     * @param string $action Method name
     * @param array<string> $methods HTTP methods
     * @param string|null $name Route name
     */
    public function addRoute(
        string $path,
        string $controller,
        string $action,
        array $methods = ['GET'],
        ?string $name = null
    ): void;

    /**
     * Match a request to a registered route.
     *
     * @return array{controller: string, action: string, parameters: array<string, mixed>}|null
     */
    public function match(Request $request): ?array;

    /**
     * Generate URL for a named route.
     *
     * @param array<string, mixed> $parameters
     */
    public function generate(string $name, array $parameters = []): string;

    /**
     * Get all registered routes.
     *
     * @return array<array{path: string, controller: string, action: string, methods: array<string>}>
     */
    public function getRoutes(): array;
}
```

### ContainerInterface

```php
<?php

declare(strict_types=1);

namespace App\Service\Core;

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
     * @param class-string<T> $id
     * @return T
     * @throws ServiceNotFoundException
     * @throws CircularDependencyException
     */
    public function get(string $id): object;

    /**
     * Register a service factory.
     *
     * @param callable(): object $factory
     */
    public function set(string $id, callable $factory): void;
}
```

### TemplateAdapterInterface

```php
<?php

declare(strict_types=1);

namespace App\Service\Core\Template;

interface TemplateAdapterInterface
{
    /**
     * Render a template with given variables.
     *
     * @param array<string, mixed> $variables
     * @throws TemplateNotFoundException
     * @throws TemplateCompilationException
     */
    public function render(string $template, array $variables = []): string;

    /**
     * Check if a template exists.
     */
    public function exists(string $template): bool;

    /**
     * Clear compiled template cache.
     */
    public function clearCache(): void;
}
```

### CommandInterface

```php
<?php

declare(strict_types=1);

namespace App\Service\Command;

interface CommandInterface
{
    /**
     * Execute the command.
     *
     * @param array<string> $args Command-line arguments
     * @return int Exit code (0 = success)
     */
    public function execute(array $args): int;

    /**
     * Get help text for the command.
     */
    public function getHelp(): string;
}
```

### EncryptionInterface

```php
<?php

declare(strict_types=1);

namespace App\Service\Security;

interface EncryptionInterface
{
    /**
     * Encrypt data.
     *
     * @throws EncryptionException
     */
    public function encrypt(string $plaintext): string;

    /**
     * Decrypt data.
     *
     * @throws DecryptionException
     */
    public function decrypt(string $ciphertext): string;
}
```

### StorageInterface

```php
<?php

declare(strict_types=1);

namespace App\Service\Storage;

interface StorageInterface
{
    /**
     * Save an entity.
     *
     * @param array<string, mixed> $data
     */
    public function save(string $id, array $data): void;

    /**
     * Find an entity by ID.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array;

    /**
     * Find all entities.
     *
     * @return array<string, array<string, mixed>>
     */
    public function findAll(): array;

    /**
     * Delete an entity.
     */
    public function delete(string $id): bool;

    /**
     * Check if an entity exists.
     */
    public function exists(string $id): bool;
}
```

### CacheInterface

```php
<?php

declare(strict_types=1);

namespace App\Service\Cache;

interface CacheInterface
{
    /**
     * Get cached value.
     */
    public function get(string $key): mixed;

    /**
     * Set cached value.
     *
     * @param int|null $ttl Time-to-live in seconds
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Check if key exists in cache.
     */
    public function has(string $key): bool;

    /**
     * Delete cached value.
     */
    public function delete(string $key): bool;

    /**
     * Clear all cached values.
     */
    public function clear(): void;
}
```

## HTTP Contracts

### Request Contract

```php
<?php

declare(strict_types=1);

namespace App\Service\Core\Http;

interface RequestInterface
{
    public function getMethod(): string;
    public function getUri(): string;
    public function getHeader(string $name): ?string;
    public function getHeaders(): array;
    public function getQueryParam(string $name, mixed $default = null): mixed;
    public function getBodyParam(string $name, mixed $default = null): mixed;
    public function getParameter(string $name, mixed $default = null): mixed;
}
```

### Response Contract

```php
<?php

declare(strict_types=1);

namespace App\Service\Core\Http;

interface ResponseInterface
{
    public function getStatusCode(): int;
    public function getHeaders(): array;
    public function getBody(): string;
    public function withStatus(int $code): self;
    public function withHeader(string $name, string $value): self;
    public function withBody(string $body): self;
}
```

## Exception Hierarchy

```
LunarException (base)
├── RouterException
│   ├── RouteNotFoundException
│   └── RouteConflictException
├── ContainerException
│   ├── ServiceNotFoundException
│   └── CircularDependencyException
├── TemplateException
│   ├── TemplateNotFoundException
│   └── TemplateCompilationException
├── SecurityException
│   ├── EncryptionException
│   └── DecryptionException
├── StorageException
│   ├── EntityNotFoundException
│   └── StorageWriteException
└── CommandException
    └── CommandNotFoundException
```

## Contract Guarantees

| Interface | Guarantee |
|-----------|-----------|
| RouterInterface | Route matching in O(n) worst case, O(1) cached |
| ContainerInterface | Dependency resolution in O(n), circular detection |
| TemplateAdapterInterface | Auto-escaping on all variable output |
| EncryptionInterface | AES-256-CBC with HMAC verification |
| StorageInterface | Atomic write operations |
| CacheInterface | Automatic invalidation on source change |
