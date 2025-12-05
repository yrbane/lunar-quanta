[% extends 'admin/base.html' %]

[% block header_actions %]
<a href="/admin/blog" class="btn btn-ghost">
    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="19" y1="12" x2="5" y2="12"/>
        <polyline points="12 19 5 12 12 5"/>
    </svg>
    Retour à la liste
</a>
[% endblock %]

[% block content %]
<form method="POST" class="post-form" id="postForm">
    <div class="form-grid">
        <!-- Main content -->
        <div class="form-main">
            <!-- Title -->
            <div class="form-group">
                <label for="title" class="form-label">Titre</label>
                <input type="text" id="title" name="title" class="form-input form-input-lg"
                       value="[[ data.title ]]" required autofocus
                       placeholder="Titre de l'article">
                [% if errors.title %]
                <span class="form-error">[[ errors.title ]]</span>
                [% endif %]
            </div>

            <!-- Content with preview -->
            <div class="form-group">
                <div class="content-header">
                    <label for="content" class="form-label">Contenu (Markdown)</label>
                    <div class="preview-toggle">
                        <button type="button" class="toggle-btn active" data-view="editor">Éditeur</button>
                        <button type="button" class="toggle-btn" data-view="preview">Aperçu</button>
                        <button type="button" class="toggle-btn" data-view="split">Split</button>
                    </div>
                </div>

                <div class="editor-container" id="editorContainer">
                    <div class="editor-pane">
                        <textarea id="content" name="content" class="form-textarea"
                                  rows="20" placeholder="Écrivez en Markdown...">[[ data.content ]]</textarea>
                    </div>
                    <div class="preview-pane" id="previewPane">
                        <div class="preview-content" id="previewContent">
                            <p class="preview-placeholder">L'aperçu s'affichera ici...</p>
                        </div>
                    </div>
                </div>
                [% if errors.content %]
                <span class="form-error">[[ errors.content ]]</span>
                [% endif %]

                <div class="markdown-help">
                    <details>
                        <summary>Aide Markdown</summary>
                        <div class="help-content">
                            <code># Titre</code> <code>## Sous-titre</code>
                            <code>**gras**</code> <code>*italique*</code>
                            <code>[lien](url)</code> <code>![image](url)</code>
                            <code>`code`</code> <code>```bloc```</code>
                            <code>> citation</code> <code>- liste</code>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="form-sidebar">
            <!-- Status card -->
            <div class="sidebar-card">
                <h3 class="card-title">Publication</h3>

                [% if post %]
                <div class="status-info">
                    <div class="status-row">
                        <span class="status-label">Statut</span>
                        [% if post.isPublished() %]
                            <span class="badge badge-success">Publié</span>
                        [% elseif post.isDraft() %]
                            <span class="badge badge-warning">Brouillon</span>
                        [% else %]
                            <span class="badge badge-secondary">Archivé</span>
                        [% endif %]
                    </div>
                    <div class="status-row">
                        <span class="status-label">Créé le</span>
                        <span>[[ post.getCreatedAt()|date('d/m/Y H:i') ]]</span>
                    </div>
                    <div class="status-row">
                        <span class="status-label">Modifié le</span>
                        <span>[[ post.getUpdatedAt()|date('d/m/Y H:i') ]]</span>
                    </div>
                    [% if post.isPublished() %]
                    <div class="status-row">
                        <span class="status-label">Publié le</span>
                        <span>[[ post.getPublishedAt()|date('d/m/Y H:i') ]]</span>
                    </div>
                    [% endif %]
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

                    [% if post and post.isDraft() %]
                    <button type="submit" formaction="/admin/blog/[[ post.getId() ]]/publish"
                            class="btn btn-success btn-block">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Publier
                    </button>
                    [% endif %]

                    [% if post and post.isPublished() %]
                    <a href="[[ post.getUrl() ]]" target="_blank" class="btn btn-ghost btn-block">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Voir l'article
                    </a>
                    [% endif %]
                </div>
            </div>

            <!-- Metadata card -->
            <div class="sidebar-card">
                <h3 class="card-title">Métadonnées</h3>

                <div class="form-group">
                    <label for="author" class="form-label">Auteur</label>
                    <input type="text" id="author" name="author" class="form-input"
                           value="[[ data.author ]]" placeholder="Nom de l'auteur">
                </div>

                <div class="form-group">
                    <label for="excerpt" class="form-label">Extrait</label>
                    <textarea id="excerpt" name="excerpt" class="form-textarea" rows="3"
                              placeholder="Description courte pour les listes et le SEO...">[[ data.excerpt ]]</textarea>
                    <span class="form-hint">Utilisé pour les aperçus et les meta descriptions.</span>
                </div>

                [% if post %]
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <div class="slug-display">
                        <code>/blog/posts/[[ post.getSlug() ]].html</code>
                    </div>
                </div>
                [% endif %]
            </div>

            [% if post and not isNew %]
            <!-- Danger zone -->
            <div class="sidebar-card card-danger">
                <h3 class="card-title">Zone de danger</h3>

                [% if post.isPublished() %]
                <form action="/admin/blog/[[ post.getId() ]]/unpublish" method="POST">
                    <button type="submit" class="btn btn-warning btn-block btn-sm">
                        Dépublier
                    </button>
                </form>
                [% endif %]

                [% if not post.isArchived() %]
                <form action="/admin/blog/[[ post.getId() ]]/archive" method="POST">
                    <button type="submit" class="btn btn-ghost btn-block btn-sm">
                        Archiver
                    </button>
                </form>
                [% endif %]

                <form action="/admin/blog/[[ post.getId() ]]/delete" method="POST"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.')">
                    <button type="submit" class="btn btn-danger btn-block btn-sm">
                        Supprimer définitivement
                    </button>
                </form>
            </div>
            [% endif %]
        </div>
    </div>
</form>
[% endblock %]

[% block scripts %]
<script>
(function() {
    const container = document.getElementById('editorContainer');
    const textarea = document.getElementById('content');
    const previewContent = document.getElementById('previewContent');
    const toggleBtns = document.querySelectorAll('.toggle-btn');

    let debounceTimer;

    // Toggle view mode
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            toggleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const view = btn.dataset.view;
            container.className = 'editor-container view-' + view;

            if (view !== 'editor') {
                updatePreview();
            }
        });
    });

    // Update preview with debounce
    textarea.addEventListener('input', () => {
        if (container.classList.contains('view-editor')) return;

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(updatePreview, 300);
    });

    function updatePreview() {
        const content = textarea.value;

        if (!content.trim()) {
            previewContent.innerHTML = '<p class="preview-placeholder">L\'aperçu s\'affichera ici...</p>';
            return;
        }

        fetch('/admin/blog/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'content=' + encodeURIComponent(content)
        })
        .then(response => response.text())
        .then(html => {
            previewContent.innerHTML = html;
        })
        .catch(err => {
            console.error('Preview error:', err);
        });
    }
})();
</script>
[% endblock %]
