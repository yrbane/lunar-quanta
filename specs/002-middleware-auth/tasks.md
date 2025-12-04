# Tasks: Middleware & Authentication System

**Input**: Design documents from `/specs/002-middleware-auth/`
**Prerequisites**: spec.md
**Branch**: `002-middleware-auth`
**Milestone**: v1.1 - Middleware & Auth

---

## Phase 1: Middleware Infrastructure

### Tests (TDD - Red)
- [ ] T001 [P] Unit test for MiddlewareInterface in tests/Service/Core/Middleware/MiddlewareInterfaceTest.php
- [ ] T002 [P] Unit test for MiddlewareStack in tests/Service/Core/Middleware/MiddlewareStackTest.php
- [ ] T003 [P] Unit test for RequestHandler in tests/Service/Core/Middleware/RequestHandlerTest.php

### Implementation (TDD - Green)
- [ ] T004 Implement MiddlewareInterface in src/Service/Core/Middleware/MiddlewareInterface.php
- [ ] T005 Implement MiddlewareStack in src/Service/Core/Middleware/MiddlewareStack.php
- [ ] T006 Implement RequestHandler in src/Service/Core/Middleware/RequestHandler.php
- [ ] T007 Update Route attribute to support middlewares parameter
- [ ] T008 Integrate MiddlewareStack into FrontController

### Integration
- [ ] T009 Integration test for middleware chain in tests/Integration/MiddlewareIntegrationTest.php

**Checkpoint**: Middleware system works with global and route-specific middlewares

---

## Phase 2: Session Management

### Tests (TDD - Red)
- [ ] T010 [P] Unit test for SessionInterface in tests/Service/Session/SessionInterfaceTest.php
- [ ] T011 [P] Unit test for SessionService in tests/Service/Session/SessionServiceTest.php
- [ ] T012 [P] Unit test for SessionMiddleware in tests/Service/Session/SessionMiddlewareTest.php
- [ ] T013 [P] Unit test for flash messages in tests/Service/Session/FlashMessagesTest.php

### Implementation (TDD - Green)
- [ ] T014 Implement SessionInterface in src/Service/Session/SessionInterface.php
- [ ] T015 Implement SessionService in src/Service/Session/SessionService.php
- [ ] T016 Implement SessionMiddleware in src/Service/Session/SessionMiddleware.php
- [ ] T017 Add session configuration in config/session.json
- [ ] T018 Register SessionService in Container

### Integration
- [ ] T019 Integration test for session lifecycle in tests/Integration/SessionIntegrationTest.php

**Checkpoint**: Sessions work with secure defaults and flash messages

---

## Phase 3: CSRF Protection

### Tests (TDD - Red)
- [ ] T020 [P] Unit test for CsrfService in tests/Service/Security/CsrfServiceTest.php
- [ ] T021 [P] Unit test for CsrfMiddleware in tests/Service/Security/CsrfMiddlewareTest.php
- [ ] T022 [P] Security test for CSRF timing attacks in tests/Security/CsrfSecurityTest.php

### Implementation (TDD - Green)
- [ ] T023 Implement CsrfService in src/Service/Security/CsrfService.php
- [ ] T024 Implement CsrfMiddleware in src/Service/Security/CsrfMiddleware.php
- [ ] T025 Add csrf_field() template helper
- [ ] T026 Add CSRF configuration (excluded routes)

### Integration
- [ ] T027 Integration test for CSRF flow in tests/Integration/CsrfIntegrationTest.php

**Checkpoint**: Forms are protected with CSRF tokens

---

## Phase 4: Authentication System

### Tests (TDD - Red)
- [ ] T028 [P] Unit test for AuthInterface in tests/Service/Auth/AuthInterfaceTest.php
- [ ] T029 [P] Unit test for AuthService in tests/Service/Auth/AuthServiceTest.php
- [ ] T030 [P] Unit test for AuthMiddleware in tests/Service/Auth/AuthMiddlewareTest.php
- [ ] T031 [P] Unit test for password hashing in tests/Service/Auth/PasswordHashingTest.php
- [ ] T032 [P] Unit test for remember me tokens in tests/Service/Auth/RememberMeTest.php

### Implementation (TDD - Green)
- [ ] T033 Implement UserProviderInterface in src/Service/Auth/UserProviderInterface.php
- [ ] T034 Implement AuthInterface in src/Service/Auth/AuthInterface.php
- [ ] T035 Implement AuthService in src/Service/Auth/AuthService.php
- [ ] T036 Implement AuthMiddleware in src/Service/Auth/AuthMiddleware.php
- [ ] T037 Add auth configuration in config/auth.json
- [ ] T038 Register AuthService in Container

### Integration
- [ ] T039 Integration test for full auth flow in tests/Integration/AuthIntegrationTest.php
- [ ] T040 Security test for auth (timing, session fixation) in tests/Security/AuthSecurityTest.php

**Checkpoint**: Authentication works with login, logout, remember me

---

## Phase 5: Polish & Documentation

- [ ] T041 [P] Add PHPDoc to all new classes
- [ ] T042 [P] Run php-cs-fixer on new files
- [ ] T043 [P] Run PHPStan level max on new files
- [ ] T044 [P] Update doc/middleware.md with usage guide
- [ ] T045 [P] Update doc/auth.md with authentication guide
- [ ] T046 Performance benchmark for middleware stack
- [ ] T047 Security audit for auth and CSRF

---

## Summary

| Phase | Tasks | Description |
|-------|-------|-------------|
| 1 | T001-T009 | Middleware infrastructure |
| 2 | T010-T019 | Session management |
| 3 | T020-T027 | CSRF protection |
| 4 | T028-T040 | Authentication system |
| 5 | T041-T047 | Polish & documentation |

**Total Tasks**: 47
**Estimated Test Count**: ~80 tests

---

## Dependencies Graph

```
T004 (MiddlewareInterface)
  └── T005 (MiddlewareStack)
        └── T006 (RequestHandler)
              └── T008 (FrontController integration)

T014 (SessionInterface)
  └── T015 (SessionService)
        └── T016 (SessionMiddleware)
              └── T023 (CsrfService) ──┐
                    └── T024 (CsrfMiddleware)
                                       │
T033 (UserProviderInterface)           │
  └── T034 (AuthInterface)             │
        └── T035 (AuthService) ◄───────┘
              └── T036 (AuthMiddleware)
```

---

## Notes

- All tasks follow TDD (Red-Green-Refactor)
- [P] = Can run in parallel
- Constitution principles apply (Security by Default, Performance, etc.)
