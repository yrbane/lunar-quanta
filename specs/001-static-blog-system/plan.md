# Implementation Plan: Blog Éco-Responsable avec HTML Statique

**Branch**: `001-static-blog-system` | **Date**: 2025-12-05 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-static-blog-system/spec.md`

## Summary

Système de blog éco-responsable générant des fichiers HTML statiques à la publication. Les visiteurs accèdent au contenu sans solliciter le serveur applicatif PHP. L'administration permet la rédaction en Markdown avec preview live, la gestion de catégories hiérarchiques, la suggestion automatique de tags/catégories via NLP, et l'acquisition d'images depuis multiple sources (upload, Pexels, IA).

## Technical Context

**Language/Version**: PHP 8.3+ (strict_types)
**Primary Dependencies**: Aucune dépendance runtime externe (Zero Dependencies - Constitution II)
**Storage**: JSON files (JsonStorage existant) + fichiers HTML statiques générés
**Testing**: PHPUnit 12 avec 100% code coverage
**Target Platform**: Linux server (Apache/Nginx pour fichiers statiques)
**Project Type**: Web application (admin PHP + public statique)
**Performance Goals**:
- Génération HTML < 5s par publication
- Pages statiques < 500ms chargement complet
- Suggestions tags < 2s
**Constraints**:
- Pas d'exécution PHP pour visiteurs non authentifiés
- Compatible OPcache
**Scale/Scope**: 1000+ articles, catégories hiérarchiques illimitées

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Gate | Status |
|-----------|------|--------|
| I. Test-First | TDD Red-Green-Refactor cycle planned, 100% coverage target | [x] |
| II. Zero Dependencies | No new runtime dependencies introduced | [x] |
| III. Security by Default | XSS (escaped output), CSRF (admin forms), input validation, session security | [x] |
| IV. Performance & Caching | Route < 1ms (existant), template < 5ms, génération HTML < 5s | [x] |
| V. Modern PHP Standards | PHP 8.3+, strict_types, PSR-12, PSR-4, PHPStan max | [x] |
| VI. SOLID Principles | Single Responsibility (services séparés), DI via Container | [x] |
| VII. Documentation | PHPDoc complet, code EN, docs FR | [x] |
| VIII. Error Handling | Exceptions dédiées (BlogException, etc.), PSR-3 logging | [x] |
| Git Workflow | Feature branch 001-static-blog-system, CI avant merge | [x] |

**Notes Constitution**:
- Zero Dependencies: Markdown parsing et NLP implémentés en PHP natif
- APIs externes (Pexels, OpenAI, Gemini) : wrappers HTTP natifs (file_get_contents/curl)

## Project Structure

### Documentation (this feature)

```text
specs/001-static-blog-system/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── api-blog.yaml    # OpenAPI spec
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code (repository root)

```text
src/
├── Entity/
│   ├── Post.php                    # Article entity
│   ├── Category.php                # Catégorie hiérarchique
│   ├── Tag.php                     # Tag entity
│   └── Image.php                   # Image avec métadonnées
├── Service/
│   ├── Blog/
│   │   ├── PostService.php         # CRUD articles
│   │   ├── CategoryService.php     # CRUD catégories + hiérarchie
│   │   ├── TagService.php          # CRUD tags
│   │   ├── SlugGenerator.php       # Génération slugs uniques
│   │   └── BlogException.php       # Exception base blog
│   ├── Content/
│   │   ├── MarkdownParser.php      # Parser Markdown natif
│   │   ├── HtmlSanitizer.php       # Sanitize HTML input
│   │   └── ContentAnalyzer.php     # NLP extraction mots-clés
│   ├── StaticSite/
│   │   ├── StaticGenerator.php     # Générateur HTML statique
│   │   ├── RssGenerator.php        # Générateur flux RSS
│   │   └── SitemapGenerator.php    # Générateur sitemap
│   ├── Media/
│   │   ├── ImageService.php        # Orchestration sources images
│   │   ├── PexelsClient.php        # Client API Pexels
│   │   ├── DalleClient.php         # Client API OpenAI DALL-E
│   │   ├── ImagenClient.php        # Client API Google Imagen
│   │   └── ImageOptimizer.php      # Optimisation images (existant: AvatarService)
│   └── Suggestion/
│       ├── TagSuggester.php        # Suggestion tags via NLP
│       └── CategorySuggester.php   # Suggestion catégories
├── Controller/
│   └── Admin/
│       ├── PostController.php      # Admin articles
│       ├── CategoryController.php  # Admin catégories
│       ├── TagController.php       # Admin tags
│       └── MediaController.php     # Admin images
└── template/
    ├── admin/
    │   ├── post/
    │   │   ├── index.html.tpl      # Liste articles
    │   │   ├── edit.html.tpl       # Édition article
    │   │   └── _form.html.tpl      # Formulaire partiel
    │   ├── category/
    │   └── media/
    └── blog/                        # Templates génération statique
        ├── post.html.tpl           # Article single
        ├── index.html.tpl          # Page d'accueil
        ├── category.html.tpl       # Liste par catégorie
        ├── tag.html.tpl            # Liste par tag
        └── rss.xml.tpl             # Template RSS

public/
├── blog/                            # HTML statique généré (gitignored)
│   ├── index.html
│   ├── posts/
│   │   └── {slug}.html
│   ├── categories/
│   │   └── {slug}/index.html
│   ├── tags/
│   │   └── {slug}/index.html
│   └── feed.xml
└── uploads/
    └── blog/                        # Images uploadées

tests/
├── Entity/
│   ├── PostTest.php
│   ├── CategoryTest.php
│   └── TagTest.php
├── Service/
│   ├── Blog/
│   ├── Content/
│   ├── StaticSite/
│   ├── Media/
│   └── Suggestion/
└── Controller/
    └── Admin/
```

**Structure Decision**: Web application avec séparation admin (PHP dynamique) et public (HTML statique). Le dossier `public/blog/` contient les fichiers générés, servis directement par Nginx/Apache sans passer par PHP.

## Complexity Tracking

> Aucune violation de la Constitution détectée. Complexité justifiée par les besoins fonctionnels.

| Item | Justification |
|------|--------------|
| APIs externes (Pexels, OpenAI, Gemini) | Fonctionnalité image obligatoire - wrappers HTTP natifs sans dépendance |
| NLP pour suggestions | Implémentation PHP native basée sur TF-IDF et extraction mots-clés |
| Markdown parser | Implémentation PHP native (CommonMark subset) |
