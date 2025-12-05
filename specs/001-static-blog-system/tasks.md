# Tasks: Blog Éco-Responsable avec HTML Statique

**Input**: Design documents from `/specs/001-static-blog-system/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Per Constitution Principle I (Test-First), tests are MANDATORY. All tasks MUST follow TDD Red-Green-Refactor cycle with 100% code coverage target.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- Single project layout per plan.md: `src/`, `tests/` at repository root
- Templates: `template/`
- Static output: `public/blog/`
- Data storage: `data/blog/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization, directories, and exception classes

- [ ] T001 Create blog data directories `data/blog/posts/`, `data/blog/categories/`, `data/blog/tags/`, `data/blog/images/`
- [ ] T002 Create blog indexes directory `data/indexes/`
- [ ] T003 Create static output directory `public/blog/` and add to `.gitignore`
- [ ] T004 Create blog uploads directory `public/uploads/blog/`
- [ ] T005 [P] Create BlogException base class in `src/Service/Blog/BlogException.php`
- [ ] T006 [P] Create PostStatus enum in `src/Entity/PostStatus.php`
- [ ] T007 [P] Create ImageSource enum in `src/Entity/ImageSource.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core entities and services that ALL user stories depend on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Tests Foundational (TDD - Write FIRST, must FAIL)

- [ ] T008 [P] Create TagTest in `tests/Entity/TagTest.php`
- [ ] T009 [P] Create ImageEntityTest in `tests/Entity/ImageTest.php`
- [ ] T010 [P] Create SlugGeneratorTest in `tests/Service/Blog/SlugGeneratorTest.php`
- [ ] T011 [P] Create MarkdownParserTest in `tests/Service/Content/MarkdownParserTest.php`
- [ ] T012 [P] Create HtmlSanitizerTest in `tests/Service/Content/HtmlSanitizerTest.php`

### Implementation Foundational

- [ ] T013 [P] Create Tag entity in `src/Entity/Tag.php` (id, name, slug, createdAt)
- [ ] T014 [P] Create Image entity in `src/Entity/Image.php` (all fields per data-model.md)
- [ ] T015 [P] Create SlugGenerator service in `src/Service/Blog/SlugGenerator.php`
- [ ] T016 [P] Create MarkdownParser service in `src/Service/Content/MarkdownParser.php`
- [ ] T017 [P] Create HtmlSanitizer service in `src/Service/Content/HtmlSanitizer.php`
- [ ] T018 Create TagService in `src/Service/Blog/TagService.php` (CRUD, findBySlug, findOrCreate)
- [ ] T019 Create ImageStorageService in `src/Service/Media/ImageStorageService.php` (CRUD metadata)

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - Rédaction et Publication d'un Article (Priority: P1) 🎯 MVP

**Goal**: L'auteur peut créer, modifier et publier des articles. À la publication, le HTML statique est généré.

**Independent Test**: Créer un article, publier, vérifier que le fichier HTML existe dans `public/blog/posts/{slug}.html`

### Tests US1 (TDD - Write FIRST, must FAIL)

- [ ] T020 [P] [US1] Create PostTest in `tests/Entity/PostTest.php`
- [ ] T021 [P] [US1] Create PostServiceTest in `tests/Service/Blog/PostServiceTest.php`
- [ ] T022 [P] [US1] Create StaticGeneratorTest in `tests/Service/StaticSite/StaticGeneratorTest.php`
- [ ] T023 [P] [US1] Create PostControllerTest in `tests/Controller/Admin/PostControllerTest.php`

### Implementation US1

- [ ] T024 [US1] Create Post entity in `src/Entity/Post.php` (all fields per data-model.md)
- [ ] T025 [US1] Create PostService in `src/Service/Blog/PostService.php` (CRUD, publish, unpublish, archive)
- [ ] T026 [US1] Create StaticGenerator service in `src/Service/StaticSite/StaticGenerator.php`
- [ ] T027 [P] [US1] Create blog post template in `template/blog/post.html.tpl`
- [ ] T028 [P] [US1] Create blog index template in `template/blog/index.html.tpl`
- [ ] T029 [US1] Create PostController in `src/Controller/Admin/PostController.php` (index, create, edit, delete, publish)
- [ ] T030 [P] [US1] Create admin post list template in `template/admin/post/index.html.tpl`
- [ ] T031 [P] [US1] Create admin post form template in `template/admin/post/edit.html.tpl`
- [ ] T032 [US1] Implement publish action with static HTML generation in PostController
- [ ] T033 [US1] Add CSRF protection to all admin forms

**Checkpoint**: User Story 1 complete - articles can be created, published, and viewed as static HTML

---

## Phase 4: User Story 6 - Lecture Publique Éco-Responsable (Priority: P1)

**Goal**: Les visiteurs accèdent aux pages HTML statiques sans exécution PHP.

**Independent Test**: Vérifier via logs Nginx/Apache qu'aucun PHP n'est exécuté pour `/blog/*`

### Tests US6 (TDD)

- [ ] T034 [P] [US6] Create StaticSiteIntegrationTest in `tests/Service/StaticSite/StaticSiteIntegrationTest.php`

### Implementation US6

- [ ] T035 [US6] Create sample Nginx config in `docs/nginx-blog.conf`
- [ ] T036 [US6] Update StaticGenerator to regenerate index.html on each publish in `src/Service/StaticSite/StaticGenerator.php`
- [ ] T037 [US6] Add static file serving test script in `scripts/verify-static-serving.sh`

**Checkpoint**: User Stories 1 AND 6 complete - full MVP: articles published as static HTML

---

## Phase 5: User Story 2 - Gestion des Catégories Arborescentes (Priority: P2)

**Goal**: Admin peut créer/gérer des catégories hiérarchiques. Articles assignés à des catégories.

**Independent Test**: Créer catégorie parent + enfant, assigner un article, vérifier page catégorie statique

### Tests US2 (TDD)

- [ ] T038 [P] [US2] Create CategoryTest in `tests/Entity/CategoryTest.php`
- [ ] T039 [P] [US2] Create CategoryServiceTest in `tests/Service/Blog/CategoryServiceTest.php`
- [ ] T040 [P] [US2] Create CategoryControllerTest in `tests/Controller/Admin/CategoryControllerTest.php`

### Implementation US2

- [ ] T041 [US2] Create Category entity in `src/Entity/Category.php` (with hierarchy methods per data-model.md)
- [ ] T042 [US2] Create CategoryService in `src/Service/Blog/CategoryService.php` (CRUD, hierarchy, cycle detection)
- [ ] T043 [US2] Create CategoryController in `src/Controller/Admin/CategoryController.php`
- [ ] T044 [P] [US2] Create admin category list template in `template/admin/category/index.html.tpl`
- [ ] T045 [P] [US2] Create admin category form template in `template/admin/category/edit.html.tpl`
- [ ] T046 [P] [US2] Create blog category template in `template/blog/category.html.tpl`
- [ ] T047 [US2] Update StaticGenerator to generate category pages in `src/Service/StaticSite/StaticGenerator.php`
- [ ] T048 [US2] Update Post entity to reference categoryId in `src/Entity/Post.php`
- [ ] T049 [US2] Update PostService to handle category assignment in `src/Service/Blog/PostService.php`
- [ ] T050 [US2] Update admin post form with category selector in `template/admin/post/edit.html.tpl`

**Checkpoint**: User Story 2 complete - hierarchical categories functional

---

## Phase 6: User Story 3 - Suggestion Automatique de Tags et Catégories (Priority: P2)

**Goal**: Le système suggère tags et catégories basés sur l'analyse NLP du contenu.

**Independent Test**: Rédiger un article sur "PHP framework", vérifier suggestions "php", "framework"

### Tests US3 (TDD)

- [ ] T051 [P] [US3] Create ContentAnalyzerTest in `tests/Service/Content/ContentAnalyzerTest.php`
- [ ] T052 [P] [US3] Create TagSuggesterTest in `tests/Service/Suggestion/TagSuggesterTest.php`
- [ ] T053 [P] [US3] Create CategorySuggesterTest in `tests/Service/Suggestion/CategorySuggesterTest.php`

### Implementation US3

- [ ] T054 [US3] Create ContentAnalyzer service in `src/Service/Content/ContentAnalyzer.php` (TF-IDF, stopwords)
- [ ] T055 [US3] Create French/English stopwords lists in `data/nlp/stopwords_fr.txt` and `data/nlp/stopwords_en.txt`
- [ ] T056 [US3] Create TagSuggester service in `src/Service/Suggestion/TagSuggester.php`
- [ ] T057 [US3] Create CategorySuggester service in `src/Service/Suggestion/CategorySuggester.php`
- [ ] T058 [US3] Create SuggestionController in `src/Controller/Admin/SuggestionController.php` (AJAX endpoints)
- [ ] T059 [US3] Add JS for live suggestions in admin post form `assets/js/suggestions.js`
- [ ] T060 [US3] Update admin post form with suggestion UI in `template/admin/post/edit.html.tpl`

**Checkpoint**: User Story 3 complete - NLP-based suggestions working

---

## Phase 7: User Story 4 - Gestion des Images Multi-Sources (Priority: P2)

**Goal**: L'auteur peut ajouter des images via upload, Pexels, DALL-E ou Imagen.

**Independent Test**: Tester chaque source d'image et vérifier stockage local

### Tests US4 (TDD)

- [ ] T061 [P] [US4] Create ImageServiceTest in `tests/Service/Media/ImageServiceTest.php`
- [ ] T062 [P] [US4] Create PexelsClientTest in `tests/Service/Media/PexelsClientTest.php`
- [ ] T063 [P] [US4] Create DalleClientTest in `tests/Service/Media/DalleClientTest.php`
- [ ] T064 [P] [US4] Create ImagenClientTest in `tests/Service/Media/ImagenClientTest.php`
- [ ] T065 [P] [US4] Create MediaControllerTest in `tests/Controller/Admin/MediaControllerTest.php`

### Implementation US4

- [ ] T066 [P] [US4] Create PexelsClient in `src/Service/Media/PexelsClient.php`
- [ ] T067 [P] [US4] Create DalleClient in `src/Service/Media/DalleClient.php`
- [ ] T068 [P] [US4] Create ImagenClient in `src/Service/Media/ImagenClient.php`
- [ ] T069 [US4] Create ImageService orchestrator in `src/Service/Media/ImageService.php`
- [ ] T070 [US4] Create MediaController in `src/Controller/Admin/MediaController.php` (upload, search, generate, gallery)
- [ ] T071 [P] [US4] Create admin media gallery template in `template/admin/media/gallery.html.tpl`
- [ ] T072 [P] [US4] Create admin media picker modal in `template/admin/media/_picker.html.tpl`
- [ ] T073 [US4] Add JS for media picker in `assets/js/media-picker.js`
- [ ] T074 [US4] Update admin post form with image picker in `template/admin/post/edit.html.tpl`
- [ ] T075 [US4] Integrate existing ImageOptimizer (from AvatarService) for image processing

**Checkpoint**: User Story 4 complete - all image sources functional

---

## Phase 8: User Story 5 - Flux RSS (Priority: P3)

**Goal**: Le blog génère un flux RSS valide avec les derniers articles.

**Independent Test**: Valider feed.xml avec W3C Feed Validator

### Tests US5 (TDD)

- [ ] T076 [P] [US5] Create RssGeneratorTest in `tests/Service/StaticSite/RssGeneratorTest.php`

### Implementation US5

- [ ] T077 [US5] Create RssGenerator service in `src/Service/StaticSite/RssGenerator.php`
- [ ] T078 [P] [US5] Create RSS template in `template/blog/rss.xml.tpl`
- [ ] T079 [US5] Update StaticGenerator to call RssGenerator on publish in `src/Service/StaticSite/StaticGenerator.php`
- [ ] T080 [US5] Add RSS link to blog templates (index, post, category)

**Checkpoint**: User Story 5 complete - valid RSS feed generated

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Improvements affecting multiple user stories

### Tests Polish

- [ ] T081 [P] Create BlogIntegrationTest in `tests/Integration/BlogIntegrationTest.php`

### Implementation Polish

- [ ] T082 [P] Create blog tag template in `template/blog/tag.html.tpl`
- [ ] T083 Update StaticGenerator to generate tag pages in `src/Service/StaticSite/StaticGenerator.php`
- [ ] T084 [P] Create SitemapGenerator in `src/Service/StaticSite/SitemapGenerator.php`
- [ ] T085 [P] Create sitemap template in `template/blog/sitemap.xml.tpl`
- [ ] T086 Add sitemap generation to publish workflow
- [ ] T087 Add blog routes to main router configuration
- [ ] T088 Code review and PHPStan level max validation
- [ ] T089 Run quickstart.md validation script
- [ ] T090 Update CLAUDE.md with blog feature documentation

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-8)**: All depend on Foundational phase completion
- **Polish (Phase 9)**: Depends on all user stories being complete

### User Story Dependencies

| Story | Priority | Dependencies | Can Parallelize With |
|-------|----------|--------------|---------------------|
| US1 (Publication) | P1 | Foundational only | US6 |
| US6 (Lecture Statique) | P1 | US1 (needs content) | - |
| US2 (Catégories) | P2 | Foundational only | US3, US4 |
| US3 (Suggestions NLP) | P2 | Foundational only | US2, US4 |
| US4 (Images) | P2 | Foundational only | US2, US3 |
| US5 (RSS) | P3 | US1 (needs posts) | - |

### Within Each User Story

1. Tests MUST be written and FAIL before implementation
2. Entities before services
3. Services before controllers
4. Templates can be parallel with controllers
5. Integration last

### Parallel Opportunities Per Phase

**Phase 1 (Setup)**: T005, T006, T007 can run in parallel
**Phase 2 (Foundational)**: T008-T012 (tests) parallel, then T013-T017 (entities/services) parallel
**Phase 3 (US1)**: T020-T023 (tests) parallel, then T027-T028, T030-T031 (templates) parallel
**Phase 5 (US2)**: T038-T040 (tests) parallel, then T044-T046 (templates) parallel
**Phase 6 (US3)**: T051-T053 (tests) parallel
**Phase 7 (US4)**: T061-T065 (tests) parallel, then T066-T068 (clients) parallel, T071-T072 (templates) parallel

---

## Parallel Example: User Story 1

```bash
# Launch all tests for US1 together (TDD - must fail first):
Task: "Create PostTest in tests/Entity/PostTest.php"
Task: "Create PostServiceTest in tests/Service/Blog/PostServiceTest.php"
Task: "Create StaticGeneratorTest in tests/Service/StaticSite/StaticGeneratorTest.php"
Task: "Create PostControllerTest in tests/Controller/Admin/PostControllerTest.php"

# After tests written, launch all templates together:
Task: "Create blog post template in template/blog/post.html.tpl"
Task: "Create blog index template in template/blog/index.html.tpl"
Task: "Create admin post list template in template/admin/post/index.html.tpl"
Task: "Create admin post form template in template/admin/post/edit.html.tpl"
```

---

## Implementation Strategy

### MVP First (User Stories 1 + 6)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (Publication)
4. Complete Phase 4: User Story 6 (Lecture Statique)
5. **STOP and VALIDATE**: Test that articles publish as static HTML
6. Deploy/demo if ready - **MVP ACHIEVED**

### Incremental Delivery

1. MVP (US1 + US6) → Test → Deploy
2. Add US2 (Catégories) → Test → Deploy
3. Add US3 (Suggestions) + US4 (Images) in parallel → Test → Deploy
4. Add US5 (RSS) → Test → Deploy
5. Polish phase → Final validation → Release

### Parallel Team Strategy

With multiple developers after Foundational:

- **Developer A**: User Story 1 + 6 (MVP path)
- **Developer B**: User Story 2 (Catégories)
- **Developer C**: User Story 3 + 4 (Suggestions + Images)

---

## Summary

| Phase | Tasks | Stories | Tests |
|-------|-------|---------|-------|
| Setup | 7 | - | 0 |
| Foundational | 12 | - | 5 |
| US1 Publication | 14 | US1 | 4 |
| US6 Lecture | 4 | US6 | 1 |
| US2 Catégories | 13 | US2 | 3 |
| US3 Suggestions | 10 | US3 | 3 |
| US4 Images | 15 | US4 | 5 |
| US5 RSS | 5 | US5 | 1 |
| Polish | 10 | - | 1 |
| **TOTAL** | **90** | **6** | **23** |

---

## Notes

- [P] tasks = different files, no dependencies within phase
- [Story] label maps task to specific user story
- Each user story should be independently completable and testable
- Verify tests fail before implementing (TDD Red-Green-Refactor)
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Constitution: 100% code coverage required
