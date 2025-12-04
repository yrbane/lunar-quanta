# Specification: Middleware & Authentication System

**Version**: 1.1.0
**Status**: Draft
**Branch**: `002-middleware-auth`
**Milestone**: [v1.1 - Middleware & Auth](https://github.com/yrbane/lunar-quanta/milestone/1)

## Overview

This specification defines the middleware pipeline, session management, authentication system, and CSRF protection for Lunar Quanta Framework.

## User Stories

### US1: Middleware Pipeline
**As a** developer
**I want** to add cross-cutting concerns via middlewares
**So that** I can handle authentication, logging, and other concerns without modifying controllers

### US2: Session Management
**As a** developer
**I want** secure session handling
**So that** I can store user data between requests safely

### US3: Authentication
**As a** developer
**I want** a complete auth system
**So that** I can protect routes and identify users

### US4: CSRF Protection
**As a** developer
**I want** automatic CSRF protection
**So that** forms are protected from cross-site attacks

---

## Architecture

### Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        FrontController                          │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      MiddlewareStack                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐    │
│  │  Session │──│   CSRF   │──│   Auth   │──│ Controller   │    │
│  │Middleware│  │Middleware│  │Middleware│  │   Handler    │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
      ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
      │SessionService│ │  CsrfService │ │  AuthService │
      └──────────────┘ └──────────────┘ └──────────────┘
```

### Request Flow

1. Request enters `FrontController`
2. `MiddlewareStack` processes global middlewares
3. Router matches route and adds route-specific middlewares
4. Middlewares execute in order (FIFO)
5. Controller action executes
6. Response passes back through middlewares (LIFO)
7. Response sent to client

---

## Interfaces

### MiddlewareInterface

```php
<?php
namespace Lunar\Service\Core\Middleware;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;

interface MiddlewareInterface
{
    /**
     * Process the request and return a response.
     *
     * @param Request $request The incoming request
     * @param callable(Request): Response $next The next handler
     * @return Response
     */
    public function process(Request $request, callable $next): Response;
}
```

### SessionInterface

```php
<?php
namespace Lunar\Service\Session;

interface SessionInterface
{
    public function start(): void;
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function remove(string $key): void;
    public function flash(string $key, mixed $value): void;
    public function getFlash(string $key, mixed $default = null): mixed;
    public function regenerate(): void;
    public function destroy(): void;
}
```

### AuthInterface

```php
<?php
namespace Lunar\Service\Auth;

use Lunar\Entity\User;

interface AuthInterface
{
    public function attempt(string $email, string $password, bool $remember = false): bool;
    public function login(User $user, bool $remember = false): void;
    public function logout(): void;
    public function check(): bool;
    public function user(): ?User;
    public function id(): ?int;
}
```

---

## Implementation Details

### 1. Middleware System

#### Files to Create
- `src/Service/Core/Middleware/MiddlewareInterface.php`
- `src/Service/Core/Middleware/MiddlewareStack.php`
- `src/Service/Core/Middleware/RequestHandler.php`

#### Route Attribute Enhancement

```php
#[Route('/admin', methods: ['GET'], middlewares: [AuthMiddleware::class])]
```

#### Configuration

```php
// config/middleware.php
return [
    'global' => [
        SessionMiddleware::class,
        CsrfMiddleware::class,
    ],
    'groups' => [
        'web' => [SessionMiddleware::class, CsrfMiddleware::class],
        'api' => [ThrottleMiddleware::class],
    ],
];
```

### 2. Session Service

#### Security Defaults
- `session.cookie_httponly = true`
- `session.cookie_secure = true` (production)
- `session.cookie_samesite = 'Lax'`
- `session.use_strict_mode = true`

#### Flash Messages
Stored with `_flash` prefix, auto-removed after read.

### 3. Authentication

#### Password Hashing
- Algorithm: `PASSWORD_ARGON2ID`
- Fallback: `PASSWORD_BCRYPT`

#### Remember Me Token
- 64 bytes random token
- Stored hashed in database
- Rotated on each use

### 4. CSRF Protection

#### Token Generation
```php
$token = bin2hex(random_bytes(32));
$_SESSION['_csrf_token'] = $token;
```

#### Validation
- Check POST/PUT/PATCH/DELETE requests
- Compare token from `_token` field or `X-CSRF-TOKEN` header
- Use `hash_equals()` for timing-safe comparison

---

## File Structure

```
src/
├── Service/
│   ├── Core/
│   │   └── Middleware/
│   │       ├── MiddlewareInterface.php
│   │       ├── MiddlewareStack.php
│   │       └── RequestHandler.php
│   ├── Session/
│   │   ├── SessionInterface.php
│   │   ├── SessionService.php
│   │   └── SessionMiddleware.php
│   ├── Auth/
│   │   ├── AuthInterface.php
│   │   ├── AuthService.php
│   │   ├── AuthMiddleware.php
│   │   └── UserProviderInterface.php
│   └── Security/
│       ├── CsrfService.php
│       └── CsrfMiddleware.php
├── Attribute/
│   └── Route.php (modified)
tests/
├── Service/
│   ├── Core/
│   │   └── Middleware/
│   ├── Session/
│   ├── Auth/
│   └── Security/
└── Integration/
    ├── MiddlewareIntegrationTest.php
    ├── AuthIntegrationTest.php
    └── CsrfIntegrationTest.php
```

---

## Configuration

### config/session.json
```json
{
    "session": {
        "driver": "native",
        "lifetime": 120,
        "expire_on_close": false,
        "cookie": {
            "name": "lunar_session",
            "path": "/",
            "domain": null,
            "secure": true,
            "httponly": true,
            "samesite": "Lax"
        }
    }
}
```

### config/auth.json
```json
{
    "auth": {
        "provider": "database",
        "table": "users",
        "password_column": "password",
        "remember_token_column": "remember_token",
        "remember_lifetime": 43200
    }
}
```

---

## Security Considerations

1. **Session Fixation**: Regenerate session ID on login/privilege change
2. **Session Hijacking**: HttpOnly + Secure cookies, IP/UA validation (optional)
3. **Password Storage**: Argon2id with automatic rehashing
4. **Timing Attacks**: Use `hash_equals()` for all comparisons
5. **CSRF**: Double-submit cookie + session token validation
6. **Remember Me**: Secure random tokens, single-use with rotation

---

## Performance Requirements

| Operation | Target |
|-----------|--------|
| Middleware stack overhead | < 0.5ms |
| Session start | < 1ms |
| Auth check (cached) | < 0.1ms |
| CSRF validation | < 0.1ms |

---

## Dependencies

- None external (uses PHP native session handling)
- Internal: Container for DI

---

## Test Coverage Requirements

- Unit tests: 100% coverage for all services
- Integration tests: Full auth flow, middleware chain
- Security tests: Timing attacks, token validation, session handling

---

## Migration Path

1. Existing controllers continue to work (no middleware by default)
2. Add global middlewares via configuration
3. Add route-specific middlewares via attribute
4. Gradual adoption without breaking changes
