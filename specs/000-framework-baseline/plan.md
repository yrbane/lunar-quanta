# Implementation Plan: Lunar Quanta Framework Baseline

**Branch**: `001-framework-baseline` | **Date**: 2025-12-03 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-framework-baseline/spec.md`

## Summary

Baseline implementation of Lunar Quanta Framework - a modern PHP 8.3+ MVC framework featuring automatic routing via PHP 8 attributes, an advanced template engine with inheritance and macros, an integrated CLI console with 11 built-in commands, AES-256 encryption for secure data storage, a lightweight DI container, and smart caching for routes and templates.

## Technical Context

**Language/Version**: PHP 8.3+
**Primary Dependencies**: None (zero runtime dependencies per Constitution II)
**Dev Dependencies**: PHPUnit, php-cs-fixer, PHPStan
**Storage**: JSON files with optional AES-256 encryption (JsonStorage)
**Testing**: PHPUnit with 100% code coverage requirement
**Target Platform**: Linux/macOS/Windows servers with PHP 8.3+
**Project Type**: Single project (PHP framework)
**Performance Goals**: Route resolution < 1ms (cached), Template rendering < 5ms (cached)
**Constraints**: Zero external runtime dependencies, PSR-12/PSR-4 compliance
**Scale/Scope**: Framework for small-to-medium web applications

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Gate | Status |
|-----------|------|--------|
| I. Test-First | TDD Red-Green-Refactor cycle planned, 100% coverage target | [x] |
| II. Zero Dependencies | No new runtime dependencies introduced | [x] |
| III. Security by Default | XSS (auto-escape), path validation, AES-256 encryption | [x] |
| IV. Performance & Caching | Route < 1ms, template < 5ms, Container O(n) | [x] |
| V. Modern PHP Standards | PHP 8.3+, strict_types, PSR-12, PSR-4, PHPStan max | [x] |
| VI. SOLID Principles | Single Responsibility, DI, interface segregation | [x] |
| VII. Documentation | PHPDoc, code in English, docs bilingual (FR/EN) | [x] |
| VIII. Error Handling | Base exception class, PSR-3 logging, no prod leaks | [x] |
| Git Workflow | Feature branch, issue reference, CI passes before merge | [x] |

**All gates PASS** - No violations to justify.

## Project Structure

### Documentation (this feature)

```text
specs/001-framework-baseline/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output (internal contracts)
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
src/
├── Attribute/           # PHP 8 attributes (#[Route], #[Command])
│   ├── Route.php
│   └── Command.php
├── Command/             # CLI commands (11 built-in)
│   ├── CacheClearCommand.php
│   ├── MakeControllerCommand.php
│   ├── MakeCommandCommand.php
│   ├── RouterDebugCommand.php
│   ├── ServerStartCommand.php
│   ├── ServerStopCommand.php
│   ├── ServerStatusCommand.php
│   ├── ServerLogsCommand.php
│   ├── FilesystemTreeCommand.php
│   └── ...
├── Controller/          # Application controllers
│   └── BaseController.php (abstract)
├── Entity/              # Data entities
├── Exception/           # Framework exceptions (base class + specific)
├── Service/
│   ├── Cache/           # Route and template caching
│   ├── Command/         # CLI infrastructure (Console, CommandFactory)
│   ├── Core/            # Framework core
│   │   ├── Container.php      # DI Container
│   │   ├── FrontController.php
│   │   ├── Router.php
│   │   ├── Http/              # Request, Response
│   │   ├── Template/          # LunarTemplateAdapter
│   │   └── Debug/             # Development tools
│   ├── Generator/       # Code generators
│   ├── Router/          # Routing service
│   ├── Security/        # EncryptionService (AES-256)
│   ├── Server/          # Built-in dev server
│   └── Storage/         # JsonStorage
├── bin/
│   └── console          # CLI entry point
├── config/              # JSON configuration files
├── public/              # Web entry point
│   └── index.php
├── template/            # Template files
└── cache/               # Compiled routes and templates

tests/
├── Attribute/           # Attribute tests
├── Command/             # Command tests
├── Controller/          # Controller tests
├── Entity/              # Entity tests
├── Service/
│   ├── Cache/
│   ├── Command/
│   ├── Core/
│   │   ├── ContainerTest.php
│   │   ├── FrontControllerTest.php
│   │   ├── RouterTest.php
│   │   ├── Http/
│   │   ├── Template/
│   │   └── Debug/
│   ├── Security/
│   └── Storage/
└── bootstrap.php
```

**Structure Decision**: Single project structure matching existing Lunar Quanta layout. The framework follows PSR-4 autoloading with `App\` namespace. All source code in `src/`, all tests in `tests/` mirroring the source structure.

## Complexity Tracking

> No violations - all Constitution gates pass.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| N/A | - | - |
