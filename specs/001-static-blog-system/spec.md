# Feature Specification: Blog Éco-Responsable avec HTML Statique

**Feature Branch**: `001-static-blog-system`
**Created**: 2025-12-05
**Status**: Draft
**Input**: Blog personnel éco-responsable avec génération HTML statique à la publication, catégories arborescentes, tags auto-suggérés, images IA/Pexels/upload, flux RSS.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Rédaction et Publication d'un Article (Priority: P1)

L'auteur se connecte à l'administration, rédige un nouvel article avec titre, contenu et résumé. Le système suggère automatiquement des tags et catégories pertinents basés sur le contenu. L'auteur choisit une image (upload, Pexels, IA, ou galerie existante). À la publication, le système génère les fichiers HTML statiques accessibles publiquement.

**Why this priority**: Fonctionnalité centrale du blog - sans publication d'articles, le blog n'existe pas.

**Independent Test**: Peut être testé en créant un article et vérifiant que le fichier HTML statique est généré et accessible sans authentification.

**Acceptance Scenarios**:

1. **Given** un auteur connecté à l'admin, **When** il rédige un article et clique sur "Publier", **Then** un fichier HTML statique est généré et accessible publiquement sans solliciter le serveur applicatif.
2. **Given** un article en brouillon, **When** l'auteur modifie le contenu, **Then** les suggestions de tags et catégories sont mises à jour dynamiquement.
3. **Given** un article publié, **When** un visiteur non connecté accède à l'URL, **Then** le serveur sert uniquement le fichier HTML statique sans exécution de code applicatif.

---

### User Story 2 - Gestion des Catégories Arborescentes (Priority: P2)

L'administrateur crée et organise des catégories en arborescence (parent/enfant). Chaque catégorie possède un nom, slug, description, couleur et image. Les articles peuvent être classés dans ces catégories hiérarchiques.

**Why this priority**: Structure essentielle pour organiser le contenu et la navigation.

**Independent Test**: Peut être testé en créant une hiérarchie de catégories et vérifiant l'affichage arborescent dans l'admin et sur le site public.

**Acceptance Scenarios**:

1. **Given** une catégorie parente existante, **When** l'admin crée une sous-catégorie, **Then** la hiérarchie est correctement représentée.
2. **Given** une catégorie avec des articles, **When** le visiteur navigue vers cette catégorie, **Then** tous les articles associés (y compris ceux des sous-catégories) sont listés.
3. **Given** une catégorie, **When** l'admin définit une couleur et image, **Then** ces éléments visuels sont utilisés dans l'affichage public.

---

### User Story 3 - Suggestion Automatique de Tags et Catégories (Priority: P2)

Pendant la rédaction, le système analyse le contenu de l'article et propose automatiquement des tags et catégories pertinents. L'auteur peut accepter, refuser ou modifier ces suggestions.

**Why this priority**: Améliore la productivité de l'auteur et garantit une catégorisation cohérente.

**Independent Test**: Peut être testé en rédigeant un texte sur un sujet spécifique et vérifiant que les suggestions correspondent au thème.

**Acceptance Scenarios**:

1. **Given** un article en cours de rédaction, **When** le contenu dépasse 100 mots, **Then** le système affiche des suggestions de tags basées sur l'analyse du texte.
2. **Given** des catégories existantes, **When** l'auteur rédige, **Then** le système suggère les catégories les plus pertinentes avec un score de pertinence.
3. **Given** une suggestion de tag, **When** l'auteur la refuse, **Then** elle n'est plus proposée pour cet article.

---

### User Story 4 - Gestion des Images Multi-Sources (Priority: P2)

L'auteur peut associer une image à son article via quatre méthodes : upload manuel, recherche dans la banque Pexels, génération par IA, ou sélection parmi les images précédemment uploadées.

**Why this priority**: Les images sont essentielles pour l'engagement visuel du blog.

**Independent Test**: Peut être testé en utilisant chacune des quatre méthodes d'ajout d'image et vérifiant leur intégration dans l'article.

**Acceptance Scenarios**:

1. **Given** l'éditeur d'article, **When** l'auteur uploade une image, **Then** elle est redimensionnée/optimisée et associée à l'article.
2. **Given** l'éditeur d'article, **When** l'auteur recherche sur Pexels, **Then** les résultats s'affichent et l'image sélectionnée est téléchargée localement.
3. **Given** l'éditeur d'article, **When** l'auteur demande une génération IA avec un prompt, **Then** une image est générée et proposée pour validation.
4. **Given** des images déjà uploadées, **When** l'auteur ouvre la galerie, **Then** il peut sélectionner une image existante.

---

### User Story 5 - Flux RSS (Priority: P3)

Le blog génère automatiquement un flux RSS contenant les derniers articles publiés. Le flux est mis à jour à chaque publication/modification d'article.

**Why this priority**: Fonctionnalité standard attendue mais non bloquante pour le lancement.

**Independent Test**: Peut être testé en validant le flux RSS avec un lecteur RSS standard et vérifiant la conformité du format.

**Acceptance Scenarios**:

1. **Given** des articles publiés, **When** un utilisateur accède au flux RSS, **Then** il obtient un XML valide contenant les 20 derniers articles.
2. **Given** un nouvel article publié, **When** le flux RSS est régénéré, **Then** le nouvel article apparaît en premier.
3. **Given** le flux RSS, **When** il est lu par un agrégateur standard, **Then** titre, résumé, date et lien sont correctement interprétés.

---

### User Story 6 - Lecture Publique Éco-Responsable (Priority: P1)

Les visiteurs non connectés accèdent au blog via des pages HTML pré-générées. Aucun code applicatif n'est exécuté pour servir ces pages, minimisant la charge serveur et l'empreinte carbone.

**Why this priority**: Objectif central du projet - éco-responsabilité par le HTML statique.

**Independent Test**: Peut être testé en mesurant qu'aucune exécution serveur n'a lieu lors de la consultation publique.

**Acceptance Scenarios**:

1. **Given** un article publié, **When** un visiteur accède à l'URL, **Then** le serveur web sert directement le fichier HTML sans traitement applicatif.
2. **Given** la page d'accueil, **When** un visiteur non connecté y accède, **Then** seuls des fichiers statiques (HTML, CSS, JS, images) sont servis.
3. **Given** une modification d'article, **When** l'auteur republie, **Then** les fichiers HTML statiques sont régénérés.

---

### Edge Cases

- Que se passe-t-il si l'API Pexels est indisponible ? → Afficher un message d'erreur et proposer les autres méthodes.
- Que se passe-t-il si la génération IA échoue ? → Afficher un message et suggérer une nouvelle tentative ou autre méthode.
- Que se passe-t-il si une catégorie parente est supprimée ? → Les sous-catégories deviennent racines ou la suppression est bloquée si des articles y sont liés.
- Que se passe-t-il si le slug d'un article existe déjà ? → Ajouter automatiquement un suffixe numérique.
- Que se passe-t-il si la génération HTML échoue ? → L'article reste en brouillon avec notification d'erreur.

## Requirements *(mandatory)*

### Functional Requirements

**Articles**
- **FR-001**: Le système DOIT permettre de créer, modifier, supprimer et publier des articles.
- **FR-002**: Chaque article DOIT avoir : id, date création, date modification, date publication, slug, titre, contenu, résumé, tags, catégorie, image, propriétaire, meta SEO.
- **FR-003**: Le système DOIT générer automatiquement un slug unique à partir du titre.
- **FR-004**: Le système DOIT supporter les états : brouillon, publié, archivé.

**Génération Statique**
- **FR-005**: Le système DOIT générer des fichiers HTML statiques lors de la publication d'un article.
- **FR-006**: Le système DOIT régénérer la page d'accueil, les pages de catégories et le flux RSS lors de chaque publication.
- **FR-007**: Les visiteurs non authentifiés NE DOIVENT PAS déclencher d'exécution de code applicatif.

**Catégories**
- **FR-008**: Le système DOIT supporter les catégories hiérarchiques (parent/enfant sans limite de profondeur).
- **FR-009**: Chaque catégorie DOIT avoir : parent (optionnel), nom, slug, description, couleur, image.
- **FR-010**: Le système DOIT empêcher la création de cycles dans la hiérarchie des catégories.

**Tags et Suggestions**
- **FR-011**: Le système DOIT suggérer des tags pertinents basés sur l'analyse du contenu de l'article.
- **FR-012**: Le système DOIT suggérer des catégories existantes pertinentes avec un indicateur de pertinence.
- **FR-013**: Les suggestions DOIVENT être mises à jour dynamiquement pendant la rédaction.

**Images**
- **FR-014**: Le système DOIT permettre l'upload d'images par l'utilisateur.
- **FR-015**: Le système DOIT permettre la recherche et sélection d'images depuis Pexels.
- **FR-016**: Le système DOIT permettre la génération d'images via une IA à partir d'un prompt textuel.
- **FR-017**: Le système DOIT afficher une galerie des images précédemment uploadées.
- **FR-018**: Les images DOIVENT être optimisées (compression, redimensionnement) avant stockage.

**RSS**
- **FR-019**: Le système DOIT générer un flux RSS valide contenant les derniers articles.
- **FR-020**: Le flux RSS DOIT être régénéré automatiquement à chaque publication.

**SEO**
- **FR-021**: Chaque article DOIT supporter des meta SEO personnalisables (title, description, keywords).
- **FR-022**: Le système DOIT générer des URLs SEO-friendly basées sur les slugs.

### Key Entities

- **Post (Article)**: Contenu principal du blog avec titre, contenu riche, résumé, métadonnées SEO, dates de cycle de vie, association à un propriétaire, une catégorie et plusieurs tags.

- **Category (Catégorie)**: Organisation hiérarchique du contenu avec relation parent/enfant, identité visuelle (couleur, image), et métadonnées descriptives.

- **Tag**: Mot-clé libre associé aux articles pour le classement transversal, avec nom et slug unique.

- **Image**: Ressource média avec source (upload, Pexels, IA), métadonnées (alt, crédit), et versions optimisées.

- **User (Propriétaire)**: Auteur des articles avec droits d'administration sur ses propres contenus.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Les visiteurs peuvent accéder à n'importe quel article publié en moins de 500ms (temps de chargement complet).
- **SC-002**: La publication d'un article génère tous les fichiers HTML statiques en moins de 5 secondes.
- **SC-003**: 100% des pages publiques sont servies sans exécution de code applicatif (vérifiable par logs serveur).
- **SC-004**: Le flux RSS est valide selon les standards RSS 2.0 (vérifiable par validateur W3C).
- **SC-005**: Les suggestions de tags/catégories apparaissent en moins de 2 secondes après modification du contenu.
- **SC-006**: 80% des suggestions de tags sont pertinentes par rapport au contenu (évaluation manuelle sur échantillon).
- **SC-007**: Les auteurs peuvent publier un article complet (avec image et catégorie) en moins de 10 minutes.
- **SC-008**: Le blog supporte au minimum 1000 articles publiés sans dégradation des performances de navigation.

## Clarifications

### Session 2025-12-05

- Q: Format de rédaction du contenu des articles ? → A: Markdown avec preview live + support HTML intégré
- Q: Service de génération d'images IA ? → A: OpenAI DALL-E (ChatGPT Team) et/ou Google Imagen (Gemini Pro) - comptes existants

## Assumptions

- L'hébergement dispose d'un serveur web capable de servir des fichiers statiques (Apache, Nginx).
- Une clé API Pexels est disponible pour l'intégration de la banque d'images.
- La génération d'images IA utilise OpenAI DALL-E (via ChatGPT Team) ou Google Imagen (via Gemini Pro) selon disponibilité.
- Le framework Lunar Quanta existant fournit l'authentification, les sessions et le système de templates.
- Le contenu des articles est rédigé en Markdown avec preview live et support HTML pour les cas avancés.
- L'analyse de contenu pour les suggestions utilise des techniques de traitement du langage naturel basiques (extraction de mots-clés, fréquence).

## Dependencies

- Module d'authentification utilisateur (Phase 6 - déjà implémenté).
- Système de templates Lunar (déjà implémenté).
- Service externe Pexels API.
- OpenAI API (DALL-E) via compte ChatGPT Team existant.
- Google Gemini API (Imagen) via compte Gemini Pro existant.
