# Interface d'Administration

## Vue d'ensemble

L'interface d'administration de Lunar offre une expérience moderne et intuitive pour gérer le contenu du site. Elle est construite avec un design responsive et des composants réutilisables.

## Architecture

```
template/admin/
├── base.html.tpl          # Layout principal
└── blog/
    ├── index.html.tpl     # Liste des articles
    └── form.html.tpl      # Création/édition

assets/css/
└── admin.css              # Styles de l'admin
```

## Layout principal

Le layout admin (`base.html.tpl`) comprend :

### Sidebar

- Logo et branding
- Navigation principale (Articles, Catégories, Tags, Médias)
- Section système (Paramètres)
- Liens vers le site public et déconnexion

### Header

- Titre de la page
- Actions contextuelles (boutons)

### Zone de contenu

- Alertes et notifications
- Contenu principal

## Composants CSS

### Variables

```css
:root {
    --admin-sidebar-width: 260px;
    --admin-header-height: 64px;

    /* Couleurs */
    --admin-bg: #f8fafc;
    --admin-sidebar-bg: #1e293b;
    --admin-card-bg: #ffffff;
    --admin-border: #e2e8f0;

    /* Status */
    --admin-success: #10b981;
    --admin-warning: #f59e0b;
    --admin-danger: #ef4444;
    --admin-info: #3b82f6;
}
```

### Boutons

```html
<!-- Primaire -->
<button class="btn btn-primary">Action</button>

<!-- Succès -->
<button class="btn btn-success">Publier</button>

<!-- Danger -->
<button class="btn btn-danger">Supprimer</button>

<!-- Ghost (outline) -->
<button class="btn btn-ghost">Annuler</button>

<!-- Avec icône -->
<button class="btn btn-primary">
    <svg class="btn-icon">...</svg>
    Créer
</button>

<!-- Tailles -->
<button class="btn btn-sm">Petit</button>
<button class="btn btn-block">Pleine largeur</button>
```

### Cards de statistiques

```html
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
            <svg>...</svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">42</span>
            <span class="stat-label">Articles</span>
        </div>
    </div>
</div>
```

Variantes d'icônes : `stat-icon-blue`, `stat-icon-green`, `stat-icon-orange`

### Filtres

```html
<div class="filters-bar">
    <div class="filter-tabs">
        <a href="?filter=all" class="filter-tab active">Tous</a>
        <a href="?filter=published" class="filter-tab">Publiés</a>
        <a href="?filter=drafts" class="filter-tab">Brouillons</a>
    </div>
</div>
```

### Tables de données

```html
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Statut</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Mon article</td>
                <td><span class="badge badge-success">Publié</span></td>
                <td class="text-right">
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-ghost">Modifier</button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### Badges

```html
<span class="badge badge-success">Publié</span>
<span class="badge badge-warning">Brouillon</span>
<span class="badge badge-secondary">Archivé</span>
```

### Alertes

```html
<div class="alert alert-success">Opération réussie !</div>
<div class="alert alert-error">Une erreur est survenue.</div>
<div class="alert alert-warning">Attention !</div>
```

### Formulaires

```html
<div class="form-group">
    <label for="title" class="form-label">Titre</label>
    <input type="text" id="title" class="form-input" required>
    <span class="form-error">Le titre est obligatoire</span>
    <span class="form-hint">Conseil d'utilisation</span>
</div>

<!-- Textarea -->
<textarea class="form-textarea" rows="10"></textarea>

<!-- Input large -->
<input class="form-input form-input-lg">
```

### Sidebar Cards

```html
<div class="sidebar-card">
    <h3 class="card-title">Publication</h3>
    <div class="status-info">
        <div class="status-row">
            <span class="status-label">Statut</span>
            <span class="badge badge-success">Publié</span>
        </div>
    </div>
    <div class="card-actions">
        <button class="btn btn-primary btn-block">Enregistrer</button>
    </div>
</div>

<!-- Zone de danger -->
<div class="sidebar-card card-danger">
    <h3 class="card-title">Zone de danger</h3>
    <button class="btn btn-danger btn-block btn-sm">Supprimer</button>
</div>
```

### État vide

```html
<div class="empty-state">
    <svg class="empty-icon">...</svg>
    <h3>Aucun article</h3>
    <p>Commencez par créer votre premier article.</p>
    <a href="/admin/blog/create" class="btn btn-primary">Créer un article</a>
</div>
```

## Éditeur Markdown

L'éditeur propose trois modes de vue :

```html
<div class="preview-toggle">
    <button class="toggle-btn active" data-view="editor">Éditeur</button>
    <button class="toggle-btn" data-view="preview">Aperçu</button>
    <button class="toggle-btn" data-view="split">Split</button>
</div>

<div class="editor-container" id="editorContainer">
    <div class="editor-pane">
        <textarea class="form-textarea">...</textarea>
    </div>
    <div class="preview-pane">
        <div class="preview-content">...</div>
    </div>
</div>
```

### JavaScript de prévisualisation

```javascript
// Toggle des vues
toggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        container.className = 'editor-container view-' + btn.dataset.view;
        if (view !== 'editor') updatePreview();
    });
});

// Mise à jour AJAX
function updatePreview() {
    fetch('/admin/blog/preview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'content=' + encodeURIComponent(textarea.value)
    })
    .then(r => r.text())
    .then(html => previewContent.innerHTML = html);
}
```

## Responsive

L'interface s'adapte aux différentes tailles d'écran :

### Desktop (> 1024px)
- Sidebar fixe visible
- Formulaire en deux colonnes (contenu + sidebar)

### Tablet (768px - 1024px)
- Sidebar fixe visible
- Formulaire en une colonne

### Mobile (< 768px)
- Sidebar cachée (toggle)
- Layout simplifié
- Stats en grille 2x2

## Personnalisation

### Ajouter un menu

Dans `base.html.tpl` :

```html
<div class="nav-section">
    <span class="nav-section-title">Mon Section</span>
    <a href="/admin/custom" class="nav-link [% if active_menu == 'custom' %]active[% endif %]">
        <svg class="nav-icon">...</svg>
        Custom
    </a>
</div>
```

### Créer une nouvelle page admin

1. Créer le contrôleur :

```php
#[Route('/admin/custom')]
class CustomController extends BaseController
{
    #[Route('', methods: ['GET'], name: 'admin.custom.index')]
    public function index(): Response
    {
        return $this->renderAdmin('admin/custom/index', [
            'title' => 'Custom',
            'active_menu' => 'custom',
        ]);
    }
}
```

2. Créer le template `template/admin/custom/index.html.tpl` :

```html
[% extends 'admin/base.html' %]

[% block content %]
<div class="table-container">
    <!-- Contenu -->
</div>
[% endblock %]
```

## Icônes

L'admin utilise des icônes SVG inline pour les performances. Exemples :

```html
<!-- Crayon (éditer) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
</svg>

<!-- Check (publier) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <polyline points="20 6 9 17 4 12"/>
</svg>

<!-- Corbeille (supprimer) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <polyline points="3 6 5 6 21 6"/>
    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
</svg>
```

## Bonnes pratiques

1. **Toujours définir `active_menu`** dans les données du template pour highlight la navigation

2. **Utiliser les classes utilitaires** : `text-right`, `btn-block`, `btn-sm`

3. **Confirmer les actions destructives** : `onsubmit="return confirm('Êtes-vous sûr ?')"`

4. **Afficher les flash messages** via la variable `flash`

5. **Préfixer les routes admin** avec `/admin/` pour la cohérence
