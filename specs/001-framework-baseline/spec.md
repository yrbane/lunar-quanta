# Feature Specification: Lunar Quanta Framework Baseline

**Feature Branch**: `001-framework-baseline`
**Created**: 2025-12-03
**Status**: Draft
**Input**: Baseline specification for Lunar Quanta Framework - a modern PHP 8.3+ MVC framework

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Web Application Development (Priority: P1)

As a PHP developer, I want to build web applications using a modern MVC framework so that I can create maintainable, performant websites with minimal boilerplate code.

**Why this priority**: This is the core value proposition of the framework. Without web application support, the framework has no purpose.

**Independent Test**: Can be fully tested by creating a simple controller with a route and rendering a template. Delivers a working web page accessible via browser.

**Acceptance Scenarios**:

1. **Given** a new Lunar Quanta project, **When** I create a controller with a `#[Route]` attribute, **Then** the route is automatically registered and accessible via HTTP
2. **Given** a controller action, **When** I call the render method with template name and variables, **Then** the template is compiled and displayed with proper HTML escaping
3. **Given** a template with inheritance syntax, **When** the page is requested, **Then** the child template extends the parent and blocks are properly replaced
4. **Given** a first request to a route, **When** the route is resolved, **Then** subsequent requests use cached route data for faster resolution

---

### User Story 2 - CLI Command Development (Priority: P2)

As a developer, I want to create and execute CLI commands so that I can automate tasks, generate code, and manage the application from the terminal.

**Why this priority**: CLI tooling is essential for developer productivity and application maintenance, but secondary to the core web functionality.

**Independent Test**: Can be fully tested by running `bin/console` and executing built-in commands. Delivers working command-line interface with help output.

**Acceptance Scenarios**:

1. **Given** the CLI entry point, **When** I run `bin/console` without arguments, **Then** a list of all available commands is displayed
2. **Given** a command class with `#[Command]` attribute, **When** I run the command via CLI, **Then** the command executes and outputs to stdout/stderr appropriately
3. **Given** the `make:controller` command, **When** I provide a controller name, **Then** a new controller file is generated with proper structure
4. **Given** the `server:start` command, **When** I execute it, **Then** a development server starts and serves the application

---

### User Story 3 - Template Rendering (Priority: P2)

As a developer, I want to use an advanced template engine so that I can create dynamic, reusable views with inheritance and macros.

**Why this priority**: Template rendering is core to web development, sharing priority with CLI for developer experience.

**Independent Test**: Can be fully tested by creating templates with various syntax features and rendering them. Delivers compiled HTML output.

**Acceptance Scenarios**:

1. **Given** a template with `[[ variable ]]` syntax, **When** rendered with data, **Then** the variable is output with automatic HTML escaping
2. **Given** a template with `[% if condition %]` syntax, **When** the condition is true, **Then** the conditional block is rendered
3. **Given** a template with `[% for item in items %]` syntax, **When** rendered with an array, **Then** the loop iterates over all items
4. **Given** a template with `[% extends 'parent.tpl' %]` syntax, **When** rendered, **Then** the child template inherits from the parent
5. **Given** a template with `[% block name %]` syntax, **When** a child template overrides it, **Then** the child's block content replaces the parent's
6. **Given** a template with `##macroName(args)##` syntax, **When** rendered, **Then** the macro is expanded with provided arguments

---

### User Story 4 - Secure Data Storage (Priority: P3)

As a developer, I want to store and retrieve sensitive data securely so that user credentials and secrets are protected at rest.

**Why this priority**: Security is critical but builds upon the core MVC and CLI functionality.

**Independent Test**: Can be fully tested by storing an entity with sensitive fields and verifying the stored data is encrypted. Delivers encrypted JSON files on disk.

**Acceptance Scenarios**:

1. **Given** an entity with sensitive data, **When** I save it via JsonStorage, **Then** the data is encrypted using AES-256 before being written to disk
2. **Given** an encrypted JSON file, **When** I retrieve the entity, **Then** the data is decrypted and returned as the original entity
3. **Given** environment configuration with APP_SECRET, **When** the encryption service is used, **Then** it derives encryption keys from the secret

---

### User Story 5 - Dependency Injection (Priority: P3)

As a developer, I want to use dependency injection so that my code is decoupled, testable, and follows SOLID principles.

**Why this priority**: DI is important for code quality but can be introduced after basic MVC is functional.

**Independent Test**: Can be fully tested by registering services and resolving dependencies in a controller. Delivers properly instantiated objects with dependencies.

**Acceptance Scenarios**:

1. **Given** a service class with constructor dependencies, **When** I request it from the Container, **Then** all dependencies are automatically resolved and injected
2. **Given** a controller with service dependencies, **When** the controller is instantiated, **Then** the Container resolves and injects the required services
3. **Given** a circular dependency, **When** resolution is attempted, **Then** a clear error message is provided

---

### Edge Cases

- What happens when a template file does not exist? → Clear error message with template path
- What happens when a route conflicts with an existing route? → Warning during route scanning, last route wins
- What happens when the cache directory is not writable? → Graceful fallback to non-cached mode with warning
- What happens when decryption fails due to wrong key? → Exception with clear message, no partial data exposed
- What happens when a command name conflicts with another? → First registered command wins with warning
- What happens when a controller has unresolvable dependencies? → Clear exception listing missing dependencies

## Requirements *(mandatory)*

### Functional Requirements

#### Core MVC

- **FR-001**: System MUST provide automatic route registration via PHP 8 `#[Route]` attributes
- **FR-002**: System MUST support route parameters (e.g., `/blog/{id}`) with type coercion
- **FR-003**: System MUST support HTTP method filtering (GET, POST, PUT, DELETE, etc.) on routes
- **FR-004**: System MUST provide a base controller with template rendering capabilities
- **FR-005**: System MUST cache compiled routes for subsequent requests
- **FR-006**: System MUST automatically invalidate route cache when controller files change

#### Template Engine

- **FR-007**: System MUST provide variable interpolation with `[[ variable ]]` syntax
- **FR-008**: System MUST automatically HTML-escape all variable output by default
- **FR-009**: System MUST provide raw/unescaped output via explicit opt-in syntax
- **FR-010**: System MUST support conditional blocks with `[% if/else/endif %]` syntax
- **FR-011**: System MUST support iteration with `[% for item in items %]` syntax
- **FR-012**: System MUST support template inheritance with `[% extends %]` and `[% block %]`
- **FR-013**: System MUST support reusable macros with `##macroName(args)##` syntax
- **FR-014**: System MUST compile templates once and cache for subsequent requests
- **FR-015**: System MUST automatically invalidate template cache when source files change

#### CLI Console

- **FR-016**: System MUST provide a CLI entry point (`bin/console`) for command execution
- **FR-017**: System MUST support command registration via PHP 8 `#[Command]` attributes
- **FR-018**: System MUST provide built-in commands for common development tasks
- **FR-019**: System MUST display help information for each command
- **FR-020**: System MUST provide code generators for controllers and commands

#### Security & Storage

- **FR-021**: System MUST provide AES-256 encryption for sensitive data
- **FR-022**: System MUST provide JSON-based storage with automatic encryption option
- **FR-023**: System MUST validate and normalize template paths to prevent directory traversal
- **FR-024**: System MUST support environment-based configuration via `.env` files

#### Dependency Injection

- **FR-025**: System MUST provide a lightweight DI container with automatic dependency resolution
- **FR-026**: System MUST support constructor injection for services
- **FR-027**: System MUST support recursive dependency resolution

#### Performance

- **FR-028**: System MUST be compatible with PHP OPcache
- **FR-029**: System MUST minimize memory footprint during request handling
- **FR-030**: System MUST provide a built-in development server for local testing

### Key Entities

- **Route**: Represents an HTTP endpoint with path, methods, controller, action, and parameters
- **Controller**: Handles HTTP requests and returns responses, has access to template rendering
- **Template**: A view file with special syntax for variables, conditions, loops, and inheritance
- **Command**: A CLI action with name, description, arguments, and execution logic
- **Service**: A reusable component managed by the DI container
- **Entity**: A data object that can be stored/retrieved via JsonStorage

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Developers can create a new route and access it via browser in under 2 minutes
- **SC-002**: Cached route resolution completes in under 1 millisecond
- **SC-003**: Cached template rendering completes in under 5 milliseconds
- **SC-004**: New developers can understand the framework basics within 30 minutes using documentation
- **SC-005**: All 11 built-in CLI commands execute successfully without errors
- **SC-006**: Framework operates with zero external runtime dependencies
- **SC-007**: All template output is HTML-escaped by default, preventing XSS vulnerabilities
- **SC-008**: Encrypted data cannot be read without the correct encryption key
- **SC-009**: 100% of framework code is covered by automated tests
- **SC-010**: Framework passes static analysis at maximum strictness level

## Assumptions

- PHP 8.3+ is available in the target environment
- Composer is used for autoloading (PSR-4)
- Developers have basic knowledge of MVC architecture
- File system is writable for cache and storage directories
- Standard PHP extensions are available: mbstring, json, openssl
