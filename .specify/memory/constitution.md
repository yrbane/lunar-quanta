<!--
Sync Impact Report
==================
Version change: 1.0.0 → 1.1.1 (MINOR + PATCH)
  - 1.1.0: New principles and sections added
  - 1.1.1: Added bilingual documentation requirement (FR/EN)
Modified principles:
  - III. Security by Default: added CSRF, session security, input validation
  - IV. Performance & Caching: added concrete performance metrics
  - V. Modern PHP Standards: added PHPStan level max requirement
  - VII. Documentation Standards: added bilingual requirement (French + English)
Added sections:
  - VII. Documentation Standards (new principle)
  - VIII. Error Handling & Logging (new principle)
  - Backward Compatibility Policy (new section)
  - Branch Naming (Git Workflow subsection)
  - CI Requirements (Git Workflow subsection)
Removed sections: None
Templates requiring updates:
  - .specify/templates/plan-template.md ✅ (Constitution Check updated with all 8 principles)
  - .specify/templates/tasks-template.md ✅ (Tests marked MANDATORY)
Follow-up TODOs: None
-->

# Lunar Quanta Framework Constitution

## Core Principles

### I. Test-First (NON-NEGOTIABLE)

Test-Driven Development is mandatory for all code changes in this project.

- All new features and bug fixes MUST follow the Red-Green-Refactor cycle:
  1. Write a failing test that defines the expected behavior
  2. Implement the minimum code to make the test pass
  3. Refactor while keeping tests green
- Tests MUST be written before implementation code
- No code MUST be merged without corresponding test coverage
- All `phpunit` tests MUST pass before any commit is created
- Tests MUST cover 100% of PHP code (full code coverage required)

**Rationale**: TDD ensures design quality, prevents regressions, and documents
expected behavior. Full code coverage guarantees no untested code paths exist.
This is the project's most critical principle.

### II. Zero Dependencies

Lunar Quanta Framework MUST remain a standalone framework with minimal external
runtime dependencies.

- No external PHP frameworks or libraries MUST be required at runtime (except
  lunar-* which is the project's own package)
- The framework MUST be framework-agnostic and usable in any PHP project
- Development dependencies (PHPUnit, php-cs-fixer, PHPStan) are permitted
- Composer MUST only be used for autoloading and development tooling

**Rationale**: Minimal dependencies ensure maximum portability, reduce security
attack surface, and eliminate version conflicts for consumers.

### III. Security by Default

All output and operations MUST be secure by default without requiring developer
action.

- All variable output MUST be automatically HTML-escaped to prevent XSS
- Template paths MUST be validated and normalized to prevent directory traversal
- Compiled templates MUST be stored in designated cache directories only
- Raw/unescaped output MUST require explicit opt-in syntax
- Sensitive data (credentials, secrets) MUST use EncryptionService (AES-256)
- User input MUST be validated before processing
- CSRF tokens MUST be required for state-changing operations
- Session configuration MUST use secure defaults (httponly, samesite, secure)

**Rationale**: Security vulnerabilities in frameworks can affect all applications
using them. Safe defaults protect end users.

### IV. Performance & Caching

Smart caching and performance optimization MUST be prioritized throughout the
codebase.

- Templates MUST be compiled once and cached for subsequent requests
- Routes MUST be cached after initial scanning of controller attributes
- Cache invalidation MUST occur automatically when source files change
- The framework MUST be compatible with PHP OPcache
- Memory footprint MUST remain minimal during request handling
- Regex patterns MUST be optimized and compiled once where possible

**Performance Targets**:
- Route resolution MUST complete in < 1ms for cached routes
- Template rendering MUST complete in < 5ms for cached templates
- Container resolution MUST NOT exceed O(n) complexity

**Rationale**: Frameworks are called frequently in web applications; performance
directly impacts application response times.

### V. Modern PHP Standards

All code MUST adhere to modern PHP standards and best practices.

- PHP 8.3+ MUST be the minimum supported version
- Strict typing MUST be enabled in all PHP files (`declare(strict_types=1)`)
- PSR-12 coding style MUST be followed and enforced via php-cs-fixer
- PSR-4 autoloading MUST be used with namespace `App\`
- PHPStan analysis MUST pass at level max (9) before any commit
- Named arguments and constructor property promotion SHOULD be used where
  appropriate
- Code style MUST be fixed via php-cs-fixer before any PHP commit

**Rationale**: Modern PHP features improve code safety, readability, and
maintainability while enabling better IDE support.

### VI. SOLID Design Principles

All code MUST follow SOLID principles and employ appropriate design patterns.

- **Single Responsibility**: Each class MUST have one reason to change
- **Open/Closed**: Classes MUST be open for extension, closed for modification
- **Liskov Substitution**: Derived classes MUST be substitutable for base classes
- **Interface Segregation**: Clients MUST NOT depend on interfaces they don't use
- **Dependency Inversion**: Depend on abstractions, not concretions
- Design patterns (Strategy, Factory, Template Method, etc.) MUST be applied
  where they simplify the solution

**Rationale**: SOLID principles and design patterns produce maintainable,
testable, and extensible code that scales with project complexity.

### VII. Documentation Standards

All code MUST be properly documented for maintainability and onboarding.

- All public classes and methods MUST have PHPDoc with @param, @return, @throws
- PHPDoc MUST describe the "why", not just the "what"
- Code MUST be written in English (variables, methods, classes, namespaces)
- All documentation (README, guides, API docs) MUST be available in both French
  and English
- Complex algorithms MUST include inline comments explaining the logic
- Public API changes MUST be documented in CHANGELOG.md

**Rationale**: Good documentation reduces onboarding time, prevents
misuse of APIs, and serves as a secondary form of specification. Bilingual
documentation ensures accessibility for both French and international users.

### VIII. Error Handling & Logging

All errors MUST be handled consistently and logged appropriately.

- All exceptions MUST extend a base framework exception class
- Exceptions MUST provide meaningful, actionable error messages
- Framework MUST NOT expose internal errors to end users in production
- All errors MUST be loggable via PSR-3 compatible interface
- Stack traces MUST only be displayed in debug/development mode
- Failed operations MUST fail fast with clear error context

**Rationale**: Consistent error handling improves debugging experience
and prevents information leakage in production environments.

## Git Workflow & Commit Standards

All changes MUST follow strict git workflow and commit conventions.

### Branching Strategy

- Direct commits to `main` branch are FORBIDDEN
- All changes MUST go through Pull Requests
- Feature branches MUST be created for all work

### Branch Naming

- Feature: `feature/<issue>-<short-description>`
- Bugfix: `fix/<issue>-<short-description>`
- Hotfix: `hotfix/<issue>-<short-description>`
- Refactor: `refactor/<issue>-<short-description>`

### Commit Requirements

- Every commit MUST reference a GitHub issue (e.g., `#123` or `fixes #123`)
- All `phpunit` tests MUST pass before any commit is created
- Code style (php-cs-fixer) MUST be fixed before any PHP commit
- Commit messages MUST NOT contain references to AI assistants

### Commit Message Format

```
<type>: <description> (#<issue-number>)

[optional body]

[optional footer]
```

Where `<type>` is one of: `feat`, `fix`, `docs`, `style`, `refactor`, `test`,
`chore`.

### CI Requirements

All CI checks MUST pass before merge:

- PHPUnit MUST pass with 100% code coverage
- PHPStan MUST pass at level max (9)
- php-cs-fixer MUST pass with no diff

## Backward Compatibility Policy

Public API stability MUST be maintained according to semantic versioning.

- Public API changes MUST follow semantic versioning (MAJOR.MINOR.PATCH)
- Deprecations MUST be announced one minor version before removal
- Deprecated code MUST trigger E_USER_DEPRECATED warnings
- Breaking changes MUST be documented in CHANGELOG.md with migration guide
- Internal classes (marked @internal) MAY change without notice
- Experimental features (marked @experimental) MAY change in minor versions

**Rationale**: Predictable API evolution allows consumers to upgrade
confidently and plan for breaking changes.

## Governance

This constitution defines the non-negotiable principles governing Lunar Quanta
Framework development. All contributors MUST comply with these principles.

### Compliance Requirements

- All PRs/reviews MUST verify compliance with these principles
- Constitution principles supersede all other project practices
- Complexity additions MUST be justified against SOLID principles

### Amendment Procedure

1. Propose changes via GitHub issue with `constitution` label
2. Changes require maintainer approval
3. Document migration plan if changes affect existing code
4. Update version following semantic versioning rules:
   - MAJOR: Backward incompatible governance/principle removals or redefinitions
   - MINOR: New principle/section added or materially expanded guidance
   - PATCH: Clarifications, wording, typo fixes, non-semantic refinements

**Version**: 1.1.1 | **Ratified**: 2025-12-03 | **Last Amended**: 2025-12-03
