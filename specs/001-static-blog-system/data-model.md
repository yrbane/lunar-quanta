# Data Model: Blog Éco-Responsable

**Feature**: 001-static-blog-system
**Date**: 2025-12-05

## Entities Overview

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│    User     │       │   Category  │       │     Tag     │
│  (existant) │       │             │       │             │
└──────┬──────┘       └──────┬──────┘       └──────┬──────┘
       │                     │ parent             │
       │ owner               │ (self-ref)         │
       │                     │                    │
       ▼                     ▼                    │
┌─────────────────────────────────────────────────┴───────┐
│                         Post                            │
│  (id, title, slug, content, summary, status, dates...)  │
└─────────────────────────────────────────────────────────┘
       │
       │ image
       ▼
┌─────────────┐
│    Image    │
│  (media)    │
└─────────────┘
```

---

## Entity: Post

Article de blog avec contenu Markdown et métadonnées SEO.

### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string (UUID) | Yes | Identifiant unique |
| title | string | Yes | Titre de l'article (max 200 chars) |
| slug | string | Yes | URL-friendly identifier (unique) |
| content | string | Yes | Contenu en Markdown + HTML |
| summary | string | Yes | Résumé court (max 500 chars) |
| status | enum | Yes | draft, published, archived |
| ownerId | string | Yes | Référence User.id |
| categoryId | string | No | Référence Category.id |
| tagIds | string[] | No | Références Tag.id[] |
| imageId | string | No | Référence Image.id |
| metaTitle | string | No | SEO title (default: title) |
| metaDescription | string | No | SEO description (default: summary) |
| metaKeywords | string[] | No | SEO keywords |
| createdAt | datetime | Yes | Date création |
| updatedAt | datetime | Yes | Date dernière modification |
| publishedAt | datetime | No | Date de publication |

### Validation Rules

- `title`: non vide, max 200 caractères
- `slug`: unique, format [a-z0-9-]+, max 100 caractères
- `content`: non vide
- `summary`: non vide, max 500 caractères
- `status`: valeur dans enum {draft, published, archived}
- `publishedAt`: requis si status = published

### State Transitions

```
         save()          publish()         archive()
[NEW] ────────► [DRAFT] ──────────► [PUBLISHED] ──────────► [ARCHIVED]
                  ▲                      │                      │
                  │      unpublish()     │       restore()      │
                  └──────────────────────┴──────────────────────┘
```

### Indexes

- Primary: `id`
- Unique: `slug`
- Filter: `status`, `ownerId`, `categoryId`, `publishedAt`

---

## Entity: Category

Catégorie hiérarchique pour organiser les articles.

### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string (UUID) | Yes | Identifiant unique |
| name | string | Yes | Nom affiché (max 100 chars) |
| slug | string | Yes | URL-friendly identifier (unique) |
| description | string | No | Description de la catégorie |
| color | string | No | Couleur hex (#RRGGBB) |
| imageId | string | No | Référence Image.id |
| parentId | string | No | Référence Category.id (self) |
| position | int | Yes | Ordre d'affichage (default: 0) |
| createdAt | datetime | Yes | Date création |
| updatedAt | datetime | Yes | Date modification |

### Validation Rules

- `name`: non vide, max 100 caractères
- `slug`: unique, format [a-z0-9-]+, max 50 caractères
- `color`: format hex valide ou null
- `parentId`: doit exister, ne peut pas créer de cycle

### Hierarchy Rules

- Une catégorie peut avoir un parent (arborescence)
- Profondeur illimitée
- Suppression bloquée si enfants ou articles liés
- Détection de cycles obligatoire lors de setParent()

### Methods

```php
getParent(): ?Category
getChildren(): Category[]
getAncestors(): Category[]      // Du parent à la racine
getDescendants(): Category[]    // Tous les enfants récursivement
getDepth(): int                 // 0 = racine
getPath(): string               // "parent/child/grandchild"
hasChildren(): bool
hasArticles(): bool
```

---

## Entity: Tag

Mot-clé pour classification transversale des articles.

### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string (UUID) | Yes | Identifiant unique |
| name | string | Yes | Nom affiché (max 50 chars) |
| slug | string | Yes | URL-friendly identifier (unique) |
| createdAt | datetime | Yes | Date création |

### Validation Rules

- `name`: non vide, max 50 caractères, unique (case-insensitive)
- `slug`: unique, format [a-z0-9-]+, max 50 caractères

### Notes

- Tags créés automatiquement lors de la première utilisation
- Suggestion basée sur NLP (ContentAnalyzer)

---

## Entity: Image

Ressource média avec métadonnées et source.

### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string (UUID) | Yes | Identifiant unique |
| filename | string | Yes | Nom du fichier stocké |
| originalName | string | Yes | Nom original uploadé |
| mimeType | string | Yes | Type MIME (image/jpeg, etc.) |
| size | int | Yes | Taille en bytes |
| width | int | Yes | Largeur en pixels |
| height | int | Yes | Hauteur en pixels |
| alt | string | No | Texte alternatif |
| credit | string | No | Crédit/attribution |
| source | enum | Yes | upload, pexels, dalle, imagen |
| sourceId | string | No | ID externe (Pexels, etc.) |
| sourceUrl | string | No | URL source originale |
| ownerId | string | Yes | Référence User.id |
| createdAt | datetime | Yes | Date création |

### Validation Rules

- `filename`: non vide, format sécurisé (pas de path traversal)
- `mimeType`: valeur dans {image/jpeg, image/png, image/gif, image/webp}
- `source`: valeur dans enum {upload, pexels, dalle, imagen}
- `size`: max 10 Mo

### Storage

- Fichiers stockés dans `public/uploads/blog/{year}/{month}/{filename}`
- Versions optimisées générées automatiquement
- Métadonnées en JSON (JsonStorage)

---

## Relationships Summary

| From | To | Type | Field |
|------|-----|------|-------|
| Post | User | Many-to-One | ownerId |
| Post | Category | Many-to-One | categoryId |
| Post | Tag | Many-to-Many | tagIds[] |
| Post | Image | Many-to-One | imageId |
| Category | Category | Self-Reference | parentId |
| Category | Image | Many-to-One | imageId |
| Image | User | Many-to-One | ownerId |

---

## JSON Storage Structure

```
data/
├── blog/
│   ├── posts/
│   │   └── {id}.json           # Post entity
│   ├── categories/
│   │   └── {id}.json           # Category entity
│   ├── tags/
│   │   └── {id}.json           # Tag entity
│   └── images/
│       └── {id}.json           # Image metadata
└── indexes/
    ├── posts_by_slug.json      # slug -> id mapping
    ├── posts_by_status.json    # status -> id[] mapping
    ├── categories_by_slug.json # slug -> id mapping
    └── tags_by_slug.json       # slug -> id mapping
```

---

## Data Volume Estimates

| Entity | Expected Volume | Growth Rate |
|--------|----------------|-------------|
| Post | 1000+ | ~2-5/week |
| Category | 20-50 | Stable |
| Tag | 200-500 | ~5-10/week |
| Image | 2000+ | ~5-10/week |
