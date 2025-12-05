[% extends 'admin/base.html' %]

[% block header_actions %]
<a href="/admin/blog/create" class="btn btn-primary">
    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Nouvel article
</a>
[% endblock %]

[% block content %]
<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">[[ stats.total ]]</span>
            <span class="stat-label">Articles</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">[[ stats.published ]]</span>
            <span class="stat-label">Publiés</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">[[ stats.drafts ]]</span>
            <span class="stat-label">Brouillons</span>
        </div>
    </div>

    <div class="stat-card stat-card-action">
        <form action="/admin/blog/regenerate" method="POST">
            <button type="submit" class="stat-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"/>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
                Régénérer le site
            </button>
        </form>
    </div>
</div>

<!-- Filters -->
<div class="filters-bar">
    <div class="filter-tabs">
        <a href="/admin/blog" class="filter-tab [% if filter == 'all' %]active[% endif %]">
            Tous
        </a>
        <a href="/admin/blog?filter=published" class="filter-tab [% if filter == 'published' %]active[% endif %]">
            Publiés
        </a>
        <a href="/admin/blog?filter=drafts" class="filter-tab [% if filter == 'drafts' %]active[% endif %]">
            Brouillons
        </a>
    </div>
</div>

<!-- Posts Table -->
<div class="table-container">
    [% if posts|length > 0 %]
    <table class="data-table">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Statut</th>
                <th>Auteur</th>
                <th>Mise à jour</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            [% for post in posts %]
            <tr>
                <td>
                    <div class="post-title-cell">
                        <a href="/admin/blog/[[ post.getId() ]]/edit" class="post-title-link">
                            [[ post.getTitle() ]]
                        </a>
                        [% if post.isPublished() %]
                        <a href="[[ post.getUrl() ]]" class="post-view-link" target="_blank">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                        </a>
                        [% endif %]
                    </div>
                    <div class="post-meta">
                        [[ post.getReadingTime() ]] min de lecture
                    </div>
                </td>
                <td>
                    [% if post.isPublished() %]
                        <span class="badge badge-success">Publié</span>
                    [% elseif post.isDraft() %]
                        <span class="badge badge-warning">Brouillon</span>
                    [% else %]
                        <span class="badge badge-secondary">Archivé</span>
                    [% endif %]
                </td>
                <td>[[ post.getAuthor() ?: '-' ]]</td>
                <td>
                    <span class="date-cell" title="[[ post.getUpdatedAt()|date('d/m/Y H:i') ]]">
                        [[ post.getUpdatedAt()|date('d/m/Y') ]]
                    </span>
                </td>
                <td class="text-right">
                    <div class="action-buttons">
                        <a href="/admin/blog/[[ post.getId() ]]/edit" class="btn btn-sm btn-ghost" title="Modifier">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>

                        [% if post.isDraft() %]
                        <form action="/admin/blog/[[ post.getId() ]]/publish" method="POST" class="inline-form">
                            <button type="submit" class="btn btn-sm btn-ghost btn-success" title="Publier">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </button>
                        </form>
                        [% elseif post.isPublished() %]
                        <form action="/admin/blog/[[ post.getId() ]]/unpublish" method="POST" class="inline-form">
                            <button type="submit" class="btn btn-sm btn-ghost btn-warning" title="Dépublier">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                </svg>
                            </button>
                        </form>
                        [% endif %]

                        <form action="/admin/blog/[[ post.getId() ]]/delete" method="POST" class="inline-form"
                              onsubmit="return confirm('Supprimer cet article ?')">
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
            <path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
        </svg>
        <h3>Aucun article</h3>
        <p>Commencez par créer votre premier article.</p>
        <a href="/admin/blog/create" class="btn btn-primary">Créer un article</a>
    </div>
    [% endif %]
</div>
[% endblock %]
