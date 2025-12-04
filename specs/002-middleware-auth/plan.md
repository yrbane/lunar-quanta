# Implementation Plan: Middleware & Authentication System

**Branch**: `002-middleware-auth`
**Base**: `main`
**Spec**: [spec.md](./spec.md)
**Tasks**: [tasks.md](./tasks.md)

---

## Executive Summary

This plan implements a complete middleware pipeline with session management, CSRF protection, and authentication for Lunar Quanta Framework. The architecture follows PSR-15 principles while maintaining the framework's zero-dependency philosophy.

---

## Design Decisions

### 1. Middleware Pattern Choice

**Decision**: Use a simplified PSR-15-inspired pattern with `callable $next` instead of `RequestHandlerInterface`.

**Rationale**:
- Simpler API for developers
- No need for separate handler interface
- Compatible with closures for quick middlewares
- Can be upgraded to full PSR-15 later if needed

**Example**:
```php
// Simple approach (chosen)
public function process(Request $request, callable $next): Response

// vs PSR-15 (more complex)
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
```

### 2. Session Storage

**Decision**: Use PHP native sessions with secure configuration overlay.

**Rationale**:
- No external dependencies
- Battle-tested implementation
- Easy to override for testing
- Session handler interface for future extensibility (Redis, Database)

### 3. Authentication Architecture

**Decision**: Provider-based architecture with pluggable user sources.

**Rationale**:
- Decouples auth logic from storage
- Supports multiple user types (database, LDAP, OAuth)
- Easy to test with mock providers

```php
interface UserProviderInterface {
    public function findByCredentials(string $email): ?User;
    public function findByRememberToken(string $token): ?User;
    public function updateRememberToken(User $user, ?string $token): void;
}
```

### 4. Password Hashing Strategy

**Decision**: Argon2id with automatic rehashing.

**Rationale**:
- Argon2id is the recommended algorithm (OWASP)
- Automatic rehashing handles algorithm upgrades
- Fallback to bcrypt for older PHP versions

```php
// Auto-rehash on login if needed
if (password_needs_rehash($hash, PASSWORD_ARGON2ID)) {
    $user->password = password_hash($password, PASSWORD_ARGON2ID);
    $provider->update($user);
}
```

### 5. CSRF Token Strategy

**Decision**: Session-bound token with per-request validation.

**Rationale**:
- Single token per session (simpler)
- Token regeneration optional
- Works with AJAX via header

**Alternative considered**: Per-form tokens (more secure but complex).

---

## Implementation Approach

### Phase 1: Middleware Infrastructure (Foundation)

```
Day 1-2: Core middleware system
├── MiddlewareInterface (contract)
├── MiddlewareStack (chain management)
├── RequestHandler (terminal handler)
└── FrontController integration
```

**Key Implementation Details**:

```php
class MiddlewareStack
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function handle(Request $request, callable $final): Response
    {
        $handler = array_reduce(
            array_reverse($this->middlewares),
            fn($next, $middleware) => fn($req) => $middleware->process($req, $next),
            $final
        );

        return $handler($request);
    }
}
```

### Phase 2: Session Management

```
Day 3: Session service
├── SessionInterface
├── SessionService (PHP native wrapper)
├── SessionMiddleware (auto start/save)
└── Flash message support
```

**Security Configuration**:
```php
private function configureSession(): void
{
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $this->isSecure() ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');
}
```

### Phase 3: CSRF Protection

```
Day 4: CSRF system
├── CsrfService (token generation/validation)
├── CsrfMiddleware (automatic validation)
└── Template helper
```

**Token Validation Flow**:
```
1. SessionMiddleware starts session
2. CsrfMiddleware checks:
   - If GET/HEAD/OPTIONS → skip
   - If excluded route → skip
   - Else → validate token
3. Token from: $_POST['_token'] OR header('X-CSRF-TOKEN')
4. Compare with hash_equals()
5. On failure → 419 response
```

### Phase 4: Authentication

```
Day 5-6: Auth system
├── UserProviderInterface
├── AuthService (main service)
├── AuthMiddleware (route protection)
└── Remember me functionality
```

**Login Flow**:
```
1. User submits credentials
2. AuthService::attempt($email, $password)
3. Find user via provider
4. Verify password with password_verify()
5. Rehash if needed
6. Regenerate session ID
7. Store user ID in session
8. Generate remember token if requested
9. Return success/failure
```

**Remember Me Token Format**:
```php
$selector = bin2hex(random_bytes(16));  // Public lookup key
$validator = bin2hex(random_bytes(32)); // Private validation
$token = $selector . ':' . $validator;

// Store in DB:
// selector (indexed), hashed_validator, user_id, expires_at
```

---

## Route Attribute Enhancement

```php
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public array $methods = ['GET'],
        public ?string $name = null,
        public array $middlewares = [],  // NEW
    ) {}
}
```

**Usage**:
```php
#[Route('/dashboard', middlewares: [AuthMiddleware::class])]
public function dashboard(): Response

#[Route('/admin', middlewares: [AuthMiddleware::class, AdminMiddleware::class])]
public function admin(): Response
```

---

## Configuration Files

### config/middleware.json
```json
{
    "middleware": {
        "global": [
            "Lunar\\Service\\Session\\SessionMiddleware",
            "Lunar\\Service\\Security\\CsrfMiddleware"
        ],
        "groups": {
            "web": ["SessionMiddleware", "CsrfMiddleware"],
            "api": ["ThrottleMiddleware", "JsonMiddleware"]
        },
        "aliases": {
            "auth": "Lunar\\Service\\Auth\\AuthMiddleware",
            "guest": "Lunar\\Service\\Auth\\GuestMiddleware"
        }
    }
}
```

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
        "defaults": {
            "provider": "users"
        },
        "providers": {
            "users": {
                "driver": "database",
                "table": "users"
            }
        },
        "passwords": {
            "algorithm": "argon2id",
            "memory": 65536,
            "time": 4,
            "threads": 1
        },
        "remember": {
            "enabled": true,
            "lifetime": 43200,
            "cookie": "remember_token"
        }
    }
}
```

---

## Testing Strategy

### Unit Tests
- Mock dependencies
- Test each class in isolation
- Cover edge cases and error paths

### Integration Tests
```php
public function testFullAuthenticationFlow(): void
{
    // 1. Register user
    $user = $this->createUser('test@example.com', 'password123');

    // 2. Login
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        '_token' => $this->getCsrfToken(),
    ]);
    $this->assertRedirect($response, '/dashboard');

    // 3. Access protected route
    $response = $this->get('/dashboard');
    $this->assertOk($response);

    // 4. Logout
    $response = $this->post('/logout');
    $this->assertRedirect($response, '/');

    // 5. Cannot access protected route
    $response = $this->get('/dashboard');
    $this->assertRedirect($response, '/login');
}
```

### Security Tests
- Timing attack resistance
- Session fixation prevention
- CSRF token validation
- Password hashing verification

---

## Migration & Backwards Compatibility

1. **No breaking changes** to existing code
2. Middlewares are opt-in via configuration
3. Existing controllers work without modification
4. Session auto-starts only when middleware is active
5. Auth is optional (not required for basic routes)

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Session conflicts with existing code | Low | Medium | Namespace session keys |
| Performance overhead | Low | Low | Lazy loading, caching |
| Security vulnerabilities | Medium | High | Security tests, audit |
| API breaking changes | Low | High | Careful interface design |

---

## Success Criteria

1. All 47 tasks complete
2. ~80 tests passing
3. 100% coverage on new code
4. PHPStan level max passing
5. Performance benchmarks met
6. Security audit passed
7. Documentation complete

---

## Next Steps After Completion

1. Build example login/register pages
2. Create `make:auth` command for scaffolding
3. Add OAuth support (future milestone)
4. Add 2FA support (future milestone)
