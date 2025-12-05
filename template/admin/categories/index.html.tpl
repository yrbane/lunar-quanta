[% extends 'admin/base.html' %]

[% block header_actions %]
<a href="/admin/categories/create" class="btn btn-primary">
    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Nouvelle catégorie
</a>
[% endblock %]

[% block content %]
[% if flash %]
<div class="alert alert-[[ flash.type ]]">
    [[ flash.message ]]
</div>
[% endif %]

<!-- Categories Table -->
<div class="table-container">
    [% if categories|length > 0 %]
    <table class="data-table">
        <thead>
            <tr>
                <th>Couleur</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Ordre</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            [% for category in categories %]
            <tr>
                <td>
                    <span class="color-badge" style="background-color: [[ category.getColor() ]]"></span>
                </td>
                <td>
                    <a href="/admin/categories/[[ category.getId() ]]/edit" class="category-name-link">
                        [[ category.getName() ]]
                    </a>
                    <div class="category-slug">/[[ category.getSlug() ]]</div>
                </td>
                <td>
                    <span class="description-cell">
                        [[ category.getDescription() ?: '-' ]]
                    </span>
                </td>
                <td>[[ category.getSortOrder() ]]</td>
                <td class="text-right">
                    <div class="action-buttons">
                        <a href="/admin/categories/[[ category.getId() ]]/edit" class="btn btn-sm btn-ghost" title="Modifier">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>

                        <form action="/admin/categories/[[ category.getId() ]]/delete" method="POST" class="inline-form"
                              onsubmit="return confirm('Supprimer cette catégorie ?')">
                            <button type="submit" class="btn btn-sm btn-ghost btn-danger" title="Supprimer">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            [% endfor %]
        </tbody>
    </table>
    [% else %]
    <div class="empty-state">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
        </svg>
        <h3>Aucune catégorie</h3>
        <p>Commencez par créer votre première catégorie.</p>
        <a href="/admin/categories/create" class="btn btn-primary">Créer une catégorie</a>
    </div>
    [% endif %]
</div>

<style>
.color-badge {
    display: inline-block;
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 1px solid var(--color-border);
}

.category-name-link {
    font-weight: 600;
    color: var(--color-text);
    text-decoration: none;
}

.category-name-link:hover {
    color: var(--color-primary);
}

.category-slug {
    font-size: 0.75rem;
    color: var(--color-text-muted);
    font-family: monospace;
}

.description-cell {
    max-width: 300px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
[% endblock %]
