# Data Model: Lunar Quanta Framework Baseline

**Date**: 2025-12-03
**Branch**: `001-framework-baseline`

## Overview

This document defines the core entities, their attributes, relationships, and state transitions for the Lunar Quanta Framework.

## Core Entities

### Route

Represents an HTTP endpoint mapped to a controller action.

| Attribute | Type | Description | Constraints |
|-----------|------|-------------|-------------|
| path | string | URL pattern (e.g., `/blog/{id}`) | Required, unique |
| name | string | Route identifier | Optional, unique if set |
| methods | array<string> | Allowed HTTP methods | Default: ['GET'] |
| controller | string | Fully qualified class name | Required |
| action | string | Method name to invoke | Required |
| parameters | array<string, mixed> | Extracted path parameters | Dynamic |

**Relationships**:
- Route → Controller (many-to-one)

**State Transitions**: None (immutable after registration)

### Controller

Handles HTTP requests and returns responses.

| Attribute | Type | Description | Constraints |
|-----------|------|-------------|-------------|
| class | string | Fully qualified class name | Required |
| dependencies | array<string> | Constructor parameters | Resolved by Container |
| templateEngine | TemplateAdapter | Injected template renderer | Optional |

**Relationships**:
- Controller → Route (one-to-many)
- Controller → Service (many-to-many via DI)

### Request

Represents an incoming HTTP request.

| Attribute | Type | Description | Constraints |
|-----------|------|-------------|-------------|
| method | string | HTTP method (GET, POST, etc.) | Required |
| uri | string | Request URI | Required |
| headers | array<string, string> | HTTP headers | Key-value pairs |
| query | array<string, mixed> | Query parameters ($_GET) | Parsed from URI |
| body | array<string, mixed> | Request body ($_POST) | Parsed if applicable |
| parameters | array<string, mixed> | Route parameters | Extracted from path |

**State Transitions**: None (immutable)

### Response

Represents an outgoing HTTP response.

| Attribute | Type | Description | Constraints |
|-----------|------|-------------|-------------|
| statusCode | int | HTTP status code | 100-599 |
| headers | array<string, string> | Response headers | Key-value pairs |
| body | string | Response content | Required |

**State Transitions**: None (immutable after creation)

### Template

A view file with Lunar template syntax.

| Attribute | Type | Description | Constraints |
|-----------|------|-------------|-------------|
| path | string | Template file path | Must exist, validated |
| parent | string\|null | Extended template path | Optional |
| blocks | array<string, string> | Named content blocks | Key-value pairs |
| variables | array<string, mixed> | Template context data | Passed at render |
| compiled | string | Compiled PHP code | Cached |

**Relationships**:
- Template → Template (self-referential for inheritance)

**State Transitions**:
```
Source → Compiled → Cached
         ↑____________|  (invalidated on source change)
```

### Command

A CLI action executable via `bin/console`.

| Attribute | Type | Description | Constraints |
|-----------|------|-------------|-------------|
| name | string | Command identifier (e.g., `cache:clear`) | Required, unique |
| description | string | Short description for help | Required |
| class | string | Fully qualified class name | Required |
| arguments | array<string, mixed> | Positional arguments | Defined by command |
| options | array<string, mixed> | Named options (--flag) | Defined by command |

**State Transitions**: None (immutable after registration)

### Service

A reusable component managed by the DI Container.

| Attribute | Type | Description | Constraints |
|-----------|------|-------------|-------------|
| class | string | Fully qualified class name | Required |
| dependencies | array<string> | Constructor parameters | Type-hinted |
| singleton | bool | Share instance across requests | Default: true |
| instance | object\|null | Resolved instance (if singleton) | Cached |

**Relationships**:
- Service → Service (dependency graph)

**State Transitions**:
```
Registered → Resolving → Resolved
                ↓           ↓
           (circular)   (cached if singleton)
```

### Entity

A data object for JsonStorage.

| Attribute | Type | Description | Constraints |
|-----------|------|-------------|-------------|
| id | string\|int | Unique identifier | Required |
| data | array<string, mixed> | Entity attributes | Serializable |
| encrypted | bool | Whether stored encrypted | Default: false |
| createdAt | DateTimeImmutable | Creation timestamp | Auto-set |
| updatedAt | DateTimeImmutable | Last modification | Auto-updated |

**State Transitions**:
```
New → Persisted → Modified → Persisted
       ↓
    Deleted
```

### Container

Dependency Injection container.

| Attribute | Type | Description | Constraints |
|-----------|------|-------------|-------------|
| services | array<string, Service> | Registered services | Class name keys |
| instances | array<string, object> | Resolved singletons | Cached instances |
| resolving | array<string> | Currently resolving | Circular detection |

**State Transitions**:
```
Empty → Services Registered → Resolving → Resolved
```

## Relationships Diagram

```
┌─────────────┐       ┌─────────────┐
│   Router    │◄──────│    Route    │
└─────────────┘       └──────┬──────┘
                             │
                             ▼
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│  Container  │──────►│ Controller  │──────►│  Response   │
└─────────────┘       └──────┬──────┘       └─────────────┘
       │                     │
       ▼                     ▼
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│   Service   │       │  Template   │──────►│   (HTML)    │
└─────────────┘       └─────────────┘       └─────────────┘
       │
       ▼
┌─────────────┐       ┌─────────────┐
│ JsonStorage │──────►│   Entity    │
└─────────────┘       └─────────────┘
       │
       ▼
┌─────────────┐
│ Encryption  │
└─────────────┘
```

## Validation Rules

### Route Path

- Must start with `/`
- Parameters use `{name}` syntax
- Optional parameters use `{name?}`
- Regex constraints use `{name:pattern}`

### Template Path

- Must be relative to template directory
- No `..` allowed (directory traversal prevention)
- Must end with `.tpl` or `.html.tpl`

### Command Name

- Format: `namespace:action` or single word
- Lowercase with hyphens allowed
- Examples: `cache:clear`, `make:controller`, `list`

### Entity ID

- Non-empty string or positive integer
- Unique within storage file

## Data Persistence

### JsonStorage Format

```json
{
  "entities": {
    "entity-id-1": {
      "id": "entity-id-1",
      "data": { ... },
      "createdAt": "2025-12-03T10:00:00+00:00",
      "updatedAt": "2025-12-03T10:30:00+00:00"
    }
  },
  "meta": {
    "version": 1,
    "encrypted": false
  }
}
```

### Encrypted Format

When encryption is enabled, the entire `entities` block is encrypted:

```json
{
  "entities": "base64(iv):base64(ciphertext):base64(hmac)",
  "meta": {
    "version": 1,
    "encrypted": true
  }
}
```
