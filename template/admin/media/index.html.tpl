[% extends "admin/base.html.tpl" %]

[% block head %]
<style>
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
}

.media-card {
    position: relative;
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    transition: box-shadow 0.2s, transform 0.2s;
    cursor: pointer;
}

.media-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.media-card img {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
}

.media-card-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0.5rem;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    color: white;
    font-size: 0.75rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.media-card:hover .media-card-overlay {
    opacity: 1;
}

.media-card-actions {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.media-card:hover .media-card-actions {
    opacity: 1;
}

.media-card-actions button {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: var(--radius-sm);
}

.upload-zone {
    border: 2px dashed var(--color-border);
    border-radius: var(--radius-lg);
    padding: 3rem;
    text-align: center;
    background: var(--color-surface);
    transition: border-color 0.2s, background 0.2s;
    cursor: pointer;
}

.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--color-primary);
    background: var(--color-primary-bg);
}

.upload-zone-icon {
    width: 3rem;
    height: 3rem;
    margin: 0 auto 1rem;
    color: var(--color-muted);
}

.upload-zone-text {
    color: var(--color-muted);
}

.upload-zone-text strong {
    color: var(--color-primary);
}

.search-section {
    margin-bottom: 2rem;
}

.search-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-form input[type="text"] {
    flex: 1;
    min-width: 200px;
}

.search-results {
    margin-top: 1rem;
}

.providers-info {
    font-size: 0.875rem;
    color: var(--color-muted);
    margin-bottom: 1rem;
}

.providers-info .badge {
    margin-right: 0.25rem;
}

.tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 0.5rem;
}

.tab {
    padding: 0.5rem 1rem;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--color-muted);
    font-weight: 500;
    transition: color 0.2s, background 0.2s;
}

.tab:hover {
    color: var(--color-text);
}

.tab.active {
    color: var(--color-primary);
    background: var(--color-primary-bg);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.generate-form {
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
    flex-wrap: wrap;
}

.generate-form textarea {
    flex: 1;
    min-width: 300px;
    resize: vertical;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--color-muted);
}

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: var(--color-surface);
    border-radius: var(--radius-lg);
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1rem;
}

.modal-body img {
    max-width: 100%;
    border-radius: var(--radius-md);
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid var(--color-border);
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.image-info {
    margin-top: 1rem;
    font-size: 0.875rem;
}

.image-info dt {
    font-weight: 600;
    color: var(--color-muted);
}

.image-info dd {
    margin-bottom: 0.5rem;
}

.loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--color-muted);
}

.loading::before {
    content: '';
    width: 1.5rem;
    height: 1.5rem;
    border: 2px solid var(--color-border);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
[% endblock %]

[% block header_actions %]
<button class="btn btn-primary" onclick="document.getElementById('file-input').click()">
    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
        <polyline points="17 8 12 3 7 8"/>
        <line x1="12" y1="3" x2="12" y2="15"/>
    </svg>
    Upload
</button>
<input type="file" id="file-input" accept="image/*" style="display: none" onchange="uploadFile(this)">
[% endblock %]

[% block content %]
<div class="tabs">
    <button class="tab active" data-tab="gallery">Galerie</button>
    <button class="tab" data-tab="search">Recherche</button>
    [% if generativeProviders|length > 0 %]
    <button class="tab" data-tab="generate">Générer</button>
    [% endif %]
</div>

<!-- Onglet Galerie -->
<div id="tab-gallery" class="tab-content active">
    <div class="upload-zone" id="upload-zone">
        <svg class="upload-zone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
        </svg>
        <p class="upload-zone-text">
            Glissez-déposez vos images ici<br>
            ou <strong>cliquez pour parcourir</strong>
        </p>
        <p class="upload-zone-text" style="font-size: 0.75rem; margin-top: 0.5rem;">
            JPG, PNG, GIF, WebP - Max 10 Mo
        </p>
    </div>

    [% if images|length > 0 %]
    <div class="media-grid" style="margin-top: 2rem;">
        [% for image in images %]
        <div class="media-card" onclick="showImageModal('[[ image.filename ]]')">
            <img src="[[ image.thumb ]]" alt="[[ image.filename ]]" loading="lazy">
            <div class="media-card-overlay">
                [[ image.filename ]]
            </div>
            <div class="media-card-actions">
                <button class="btn btn-xs btn-danger" onclick="event.stopPropagation(); deleteImage('[[ image.filename ]]')">
                    Supprimer
                </button>
            </div>
        </div>
        [% endfor %]
    </div>
    [% else %]
    <div class="empty-state" style="margin-top: 2rem;">
        <p>Aucune image dans la galerie.</p>
        <p>Uploadez votre première image pour commencer.</p>
    </div>
    [% endif %]
</div>

<!-- Onglet Recherche -->
<div id="tab-search" class="tab-content">
    [% if providers|length > 0 %]
    <div class="providers-info">
        Fournisseurs disponibles :
        [% for provider in providers %]
        <span class="badge">[[ provider ]]</span>
        [% endfor %]
    </div>

    <form class="search-form" onsubmit="searchImages(event)">
        <input type="text" id="search-query" placeholder="Rechercher des images..." class="input">
        <select id="search-provider" class="input" style="width: auto;">
            <option value="">Tous les fournisseurs</option>
            [% for provider in providers %]
            [% if provider != 'dalle' && provider != 'imagen' %]
            <option value="[[ provider ]]">[[ provider ]]</option>
            [% endif %]
            [% endfor %]
        </select>
        <button type="submit" class="btn btn-primary">Rechercher</button>
    </form>

    <div id="search-results" class="search-results"></div>
    [% else %]
    <div class="empty-state">
        <p>Aucun fournisseur configuré.</p>
        <p>Configurez les clés API (PEXELS_API_KEY) pour activer la recherche.</p>
    </div>
    [% endif %]
</div>

<!-- Onglet Génération -->
[% if generativeProviders|length > 0 %]
<div id="tab-generate" class="tab-content">
    <div class="providers-info">
        Fournisseurs IA disponibles :
        [% for provider in generativeProviders %]
        <span class="badge badge-primary">[[ provider ]]</span>
        [% endfor %]
    </div>

    <form class="generate-form" onsubmit="generateImage(event)">
        <textarea id="generate-prompt" placeholder="Décrivez l'image que vous souhaitez générer..." class="input" rows="3"></textarea>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <select id="generate-provider" class="input">
                [% for provider in generativeProviders %]
                <option value="[[ provider ]]">[[ provider ]]</option>
                [% endfor %]
            </select>
            <button type="submit" class="btn btn-primary">Générer</button>
        </div>
    </form>

    <div id="generate-results" class="search-results"></div>
</div>
[% endif %]

<!-- Modal détails image -->
<div class="modal-overlay" id="image-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Détails de l'image</h3>
            <button class="btn btn-ghost" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <img id="modal-image" src="" alt="">
            <dl class="image-info">
                <dt>URL</dt>
                <dd><input type="text" id="modal-url" class="input" readonly onclick="this.select()"></dd>
                <dt>Dimensions</dt>
                <dd id="modal-dimensions">-</dd>
                <dt>Taille</dt>
                <dd id="modal-size">-</dd>
            </dl>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="deleteImageFromModal()">Supprimer</button>
            <button class="btn btn-secondary" onclick="closeModal()">Fermer</button>
            <button class="btn btn-primary" onclick="copyUrl()">Copier l'URL</button>
        </div>
    </div>
</div>

[% endblock %]

[% block scripts %]
<script>
let currentImage = null;

// Gestion des onglets
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
    });
});

// Drag & Drop
const uploadZone = document.getElementById('upload-zone');

uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('dragover');
});

uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('dragover');
});

uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        uploadFileData(files[0]);
    }
});

uploadZone.addEventListener('click', () => {
    document.getElementById('file-input').click();
});

// Upload
function uploadFile(input) {
    if (input.files.length > 0) {
        uploadFileData(input.files[0]);
    }
}

async function uploadFileData(file) {
    const formData = new FormData();
    formData.append('image', file);

    try {
        uploadZone.innerHTML = '<div class="loading">Upload en cours...</div>';

        const response = await fetch('/admin/media/upload', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Erreur lors de l\'upload');
            location.reload();
        }
    } catch (err) {
        alert('Erreur lors de l\'upload');
        location.reload();
    }
}

// Recherche
async function searchImages(e) {
    e.preventDefault();
    const query = document.getElementById('search-query').value;
    const provider = document.getElementById('search-provider').value;
    const resultsDiv = document.getElementById('search-results');

    if (!query.trim()) return;

    resultsDiv.innerHTML = '<div class="loading">Recherche en cours...</div>';

    try {
        const params = new URLSearchParams({ q: query, limit: 20 });
        if (provider) params.append('provider', provider);

        const response = await fetch('/admin/media/search?' + params);
        const data = await response.json();

        if (data.results.length === 0) {
            resultsDiv.innerHTML = '<div class="empty-state">Aucun résultat trouvé.</div>';
            return;
        }

        resultsDiv.innerHTML = '<div class="media-grid">' + data.results.map(img => `
            <div class="media-card" onclick="downloadExternalImage('${img.url}')">
                <img src="${img.thumbnailUrl || img.url}" alt="${img.alt || ''}" loading="lazy">
                <div class="media-card-overlay">
                    ${img.photographer ? 'Par ' + img.photographer : img.provider}
                </div>
            </div>
        `).join('') + '</div>';
    } catch (err) {
        resultsDiv.innerHTML = '<div class="empty-state">Erreur lors de la recherche.</div>';
    }
}

// Génération IA
async function generateImage(e) {
    e.preventDefault();
    const prompt = document.getElementById('generate-prompt').value;
    const provider = document.getElementById('generate-provider').value;
    const resultsDiv = document.getElementById('generate-results');

    if (!prompt.trim()) return;

    resultsDiv.innerHTML = '<div class="loading">Génération en cours (cela peut prendre quelques secondes)...</div>';

    try {
        const response = await fetch('/admin/media/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ prompt, provider })
        });

        const data = await response.json();

        if (data.error) {
            resultsDiv.innerHTML = `<div class="empty-state">${data.error}</div>`;
            return;
        }

        resultsDiv.innerHTML = `
            <div class="media-grid">
                <div class="media-card" onclick="downloadExternalImage('${data.image.url}')">
                    <img src="${data.image.url}" alt="${data.image.alt || ''}">
                    <div class="media-card-overlay">
                        Cliquez pour ajouter à la galerie
                    </div>
                </div>
            </div>
        `;
    } catch (err) {
        resultsDiv.innerHTML = '<div class="empty-state">Erreur lors de la génération.</div>';
    }
}

// Télécharger une image externe
async function downloadExternalImage(url) {
    if (!confirm('Ajouter cette image à votre galerie ?')) return;

    try {
        const response = await fetch('/admin/media/download', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ url })
        });

        const data = await response.json();

        if (data.success) {
            alert('Image ajoutée à la galerie !');
            location.reload();
        } else {
            alert(data.error || 'Erreur lors du téléchargement');
        }
    } catch (err) {
        alert('Erreur lors du téléchargement');
    }
}

// Modal
async function showImageModal(filename) {
    currentImage = filename;
    document.getElementById('modal-title').textContent = filename;
    document.getElementById('modal-image').src = '/uploads/media/' + filename;
    document.getElementById('modal-url').value = '/uploads/media/' + filename;
    document.getElementById('image-modal').classList.add('active');

    try {
        const response = await fetch('/admin/media/' + filename);
        const data = await response.json();

        document.getElementById('modal-dimensions').textContent = data.width + ' × ' + data.height + ' px';
        document.getElementById('modal-size').textContent = formatBytes(data.size);
    } catch (err) {
        // Ignorer les erreurs
    }
}

function closeModal() {
    document.getElementById('image-modal').classList.remove('active');
    currentImage = null;
}

function copyUrl() {
    const input = document.getElementById('modal-url');
    input.select();
    document.execCommand('copy');
    alert('URL copiée !');
}

// Suppression
async function deleteImage(filename) {
    if (!confirm('Supprimer cette image ?')) return;

    try {
        const response = await fetch('/admin/media/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ path: filename })
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Erreur lors de la suppression');
        }
    } catch (err) {
        alert('Erreur lors de la suppression');
    }
}

function deleteImageFromModal() {
    if (currentImage) {
        deleteImage(currentImage);
    }
}

// Utilitaires
function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// Fermer modal avec Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});

// Fermer modal en cliquant à l'extérieur
document.getElementById('image-modal').addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) closeModal();
});
</script>
[% endblock %]
