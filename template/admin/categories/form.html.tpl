[% extends 'admin/base.html' %]

[% block header_actions %]
<a href="/admin/categories" class="btn btn-ghost">
    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="19" y1="12" x2="5" y2="12"/>
        <polyline points="12 19 5 12 12 5"/>
    </svg>
    Retour à la liste
</a>
[% endblock %]

[% block content %]
[% if flash %]
<div class="alert alert-[[ flash.type ]]">
    [[ flash.message ]]
</div>
[% endif %]

<form method="POST" class="category-form">
    <div class="form-grid form-grid-narrow">
        <!-- Main content -->
        <div class="form-main">
            <div class="sidebar-card">
                <!-- Name -->
                <div class="form-group">
                    <label for="name" class="form-label">Nom de la catégorie</label>
                    <input type="text" id="name" name="name" class="form-input form-input-lg"
                           value="[[ data.name ]]" required autofocus
                           placeholder="Ex: Développement Web">
                    [% if errors.name %]
                    <span class="form-error">[[ errors.name ]]</span>
                    [% endif %]
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-textarea" rows="4"
                              placeholder="Description courte de la catégorie...">[[ data.description ]]</textarea>
                    <span class="form-hint">Apparaît sur la page de la catégorie.</span>
                </div>

                <!-- Color and Sort Order -->
                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="color" class="form-label">Couleur</label>
                        <div class="color-input-wrapper">
                            <input type="color" id="color" name="color" class="form-color"
                                   value="[[ data.color ]]">
                            <input type="text" id="colorText" class="form-input form-input-sm"
                                   value="[[ data.color ]]" pattern="^#[0-9A-Fa-f]{6}$"
                                   placeholder="#6b7280">
                        </div>
                        [% if errors.color %]
                        <span class="form-error">[[ errors.color ]]</span>
                        [% endif %]
                    </div>

                    <div class="form-group form-group-half">
                        <label for="sortOrder" class="form-label">Ordre d'affichage</label>
                        <input type="number" id="sortOrder" name="sortOrder" class="form-input"
                               value="[[ data.sortOrder ]]" min="0" step="1">
                        <span class="form-hint">Les catégories sont triées par ordre croissant.</span>
                    </div>
                </div>

                [% if category %]
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <div class="slug-display">
                        <code>/blog/categories/[[ category.getSlug() ]].html</code>
                    </div>
                </div>
                [% endif %]
            </div>
        </div>

        <!-- Sidebar -->
        <div class="form-sidebar">
            <!-- Actions card -->
            <div class="sidebar-card">
                <h3 class="card-title">Actions</h3>

                [% if category %]
                <div class="status-info">
                    <div class="status-row">
                        <span class="status-label">Créée le</span>
                        <span>[[ category.getCreatedAt()|date('d/m/Y H:i') ]]</span>
                    </div>
                    <div class="status-row">
                        <span class="status-label">Modifiée le</span>
                        <span>[[ category.getUpdatedAt()|date('d/m/Y H:i') ]]</span>
                    </div>
                </div>
                [% endif %]

                <div class="card-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Enregistrer
                    </button>
                </div>
            </div>

            [% if category and not isNew %]
            <!-- Danger zone -->
            <div class="sidebar-card card-danger">
                <h3 class="card-title">Zone de danger</h3>

                <form action="/admin/categories/[[ category.getId() ]]/delete" method="POST"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')">
                    <button type="submit" class="btn btn-danger btn-block btn-sm">
                        Supprimer la catégorie
                    </button>
                </form>
            </div>
            [% endif %]
        </div>
    </div>
</form>

<style>
.form-grid-narrow {
    max-width: 900px;
}

.form-row {
    display: flex;
    gap: 1.5rem;
}

.form-group-half {
    flex: 1;
}

.color-input-wrapper {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.form-color {
    width: 48px;
    height: 38px;
    padding: 4px;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    cursor: pointer;
    background: var(--color-bg);
}

.form-input-sm {
    flex: 1;
    font-family: monospace;
}
</style>
[% endblock %]

[% block scripts %]
<script>
(function() {
    const colorInput = document.getElementById('color');
    const colorText = document.getElementById('colorText');

    colorInput.addEventListener('input', () => {
        colorText.value = colorInput.value;
    });

    colorText.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(colorText.value)) {
            colorInput.value = colorText.value;
        }
    });
})();
</script>
[% endblock %]
