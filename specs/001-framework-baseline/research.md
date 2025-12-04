# Research: Lunar Quanta Framework Baseline

**Date**: 2025-12-03
**Branch**: `001-framework-baseline`

## Overview

This document consolidates research findings for the Lunar Quanta Framework baseline implementation. No NEEDS CLARIFICATION items existed in the Technical Context, so research focuses on documenting architectural decisions, best practices, and patterns aligned with Constitution principles.

## Architectural Decisions

### 1. Routing System

**Decision**: Attribute-based routing with PHP 8 `#[Route]` attributes

**Rationale**:
- Native PHP 8 feature - no external library needed (Constitution II)
- Colocated with controller code for better maintainability
- Type-safe parameter binding via reflection
- Cacheable for performance (Constitution IV)

**Alternatives Considered**:
- YAML/XML configuration: Rejected (external parsing, separation from code)
- Annotation-based (Doctrine): Rejected (external dependency)
- Centralized route file: Rejected (harder to maintain, less DX)

### 2. Template Engine

**Decision**: Custom syntax `[[ ]]` for variables, `[% %]` for control structures

**Rationale**:
- Distinct from PHP syntax to prevent confusion
- Auto-escaping by default (Constitution III)
- Compiled to PHP for performance (Constitution IV)
- No external template library required (Constitution II)

**Alternatives Considered**:
- Twig: Rejected (external dependency)
- Blade: Rejected (Laravel-specific)
- Pure PHP templates: Rejected (no auto-escaping, XSS risk)

### 3. Dependency Injection Container

**Decision**: Lightweight container with constructor injection and recursive resolution

**Rationale**:
- O(n) resolution complexity (Constitution IV)
- Supports SOLID principles (Constitution VI)
- No external DI library (Constitution II)
- Type-hint based autowiring via reflection

**Alternatives Considered**:
- PHP-DI: Rejected (external dependency)
- Symfony DI: Rejected (external dependency, overkill)
- Service Locator: Rejected (anti-pattern, hides dependencies)

### 4. CLI Architecture

**Decision**: Attribute-based command registration with `#[Command]`

**Rationale**:
- Consistent with routing approach
- Self-documenting via attribute parameters
- Easy to discover and extend
- No external CLI library (Constitution II)

**Alternatives Considered**:
- Symfony Console: Rejected (external dependency)
- Configuration-based: Rejected (less maintainable)

### 5. Encryption & Storage

**Decision**: AES-256-CBC encryption with HMAC verification

**Rationale**:
- Industry-standard encryption strength
- Built into PHP OpenSSL extension
- HMAC prevents tampering
- Key derivation from APP_SECRET

**Alternatives Considered**:
- Sodium/libsodium: Considered for future (requires PHP 7.2+)
- Third-party encryption: Rejected (external dependency)

### 6. Caching Strategy

**Decision**: File-based caching with automatic invalidation

**Rationale**:
- No external cache system required (Constitution II)
- OPcache-compatible for compiled PHP (Constitution IV)
- Filemtime-based invalidation for development ease
- Configurable cache directories

**Alternatives Considered**:
- Redis/Memcached: Rejected for core (optional adapter possible)
- APCu: Rejected (requires extension, not always available)

## Best Practices Applied

### PHP 8.3+ Features Used

| Feature | Usage |
|---------|-------|
| Attributes | `#[Route]`, `#[Command]` for metadata |
| Constructor property promotion | Service constructors |
| Named arguments | Optional parameters in attributes |
| Match expressions | Router method matching |
| Readonly properties | Immutable value objects |
| Enums | HTTP methods, command types |
| Fibers | Potential async operations (future) |

### PSR Compliance

| Standard | Implementation |
|----------|----------------|
| PSR-4 | Autoloading with `App\` namespace |
| PSR-12 | Enforced via php-cs-fixer |
| PSR-3 | Logger interface for error handling |
| PSR-7 | Request/Response (internal implementation) |

### Security Patterns

| Pattern | Implementation |
|---------|----------------|
| Output encoding | Auto HTML-escape in templates |
| Input validation | Type coercion in route parameters |
| Path traversal prevention | Template path normalization |
| Encryption at rest | AES-256 for JsonStorage |
| Secret management | Environment-based (.env) |

## Performance Benchmarks (Targets)

| Operation | Target | Method |
|-----------|--------|--------|
| Route resolution (cached) | < 1ms | PHP array lookup |
| Template rendering (cached) | < 5ms | Compiled PHP include |
| Container resolution | O(n) | Recursive DI |
| Cold start | < 50ms | OPcache preload |

## Testing Strategy

| Layer | Tool | Coverage |
|-------|------|----------|
| Unit | PHPUnit | 100% per Constitution I |
| Integration | PHPUnit | Key workflows |
| Static Analysis | PHPStan | Level max (9) |
| Code Style | php-cs-fixer | PSR-12 |

## Conclusion

All technical decisions align with Constitution principles. No external research was required as the framework design follows established patterns with zero runtime dependencies. The baseline specification can proceed to Phase 1 (Design & Contracts).
