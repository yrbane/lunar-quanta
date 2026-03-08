[% extends '_layout.html.tpl' %]

[% block title %][[ title ]] - Lunar Blog[% endblock %]

[% block description %][[ excerpt ]][% endblock %]

[% block head_extra %]
    <meta name="author" content="[[ author ]]">
    [[ meta_tags|raw ]]
    [[ schema_org|raw ]]
    [[ head_injections|raw ]]
    <style>
        .la-rating-badge { display: inline-flex; align-items: center; gap: var(--la-space-2); padding: var(--la-space-2) var(--la-space-4); background: var(--la-surface-2); border-radius: var(--la-radius-full); font-size: var(--la-text-sm); font-weight: var(--la-weight-semibold); }
        .la-rating-badge .la-icon { color: var(--la-warning, #f59e0b); }
        .la-author-box { display: flex; gap: var(--la-space-6); padding: var(--la-space-8); background: linear-gradient(135deg, var(--la-surface-1) 0%, var(--la-surface-2) 100%); border-radius: var(--la-radius-2xl); border: 1px solid var(--la-border); position: relative; overflow: hidden; }
        .la-author-box::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--la-gradient-primary); }
        .la-author-avatar { flex-shrink: 0; width: 100px; height: 100px; border-radius: var(--la-radius-full); overflow: hidden; border: 3px solid var(--la-primary); box-shadow: var(--la-shadow-lg); }
        .la-author-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .la-author-avatar-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--la-gradient-primary); color: white; font-size: 2.5rem; }
        .la-author-info { flex: 1; }
        .la-author-name { font-size: var(--la-text-xl); font-weight: var(--la-weight-bold); margin-bottom: var(--la-space-1); }
        .la-author-institution { font-size: var(--la-text-sm); color: var(--la-primary); font-weight: var(--la-weight-medium); margin-bottom: var(--la-space-3); }
        .la-author-bio { color: var(--la-text-secondary); line-height: 1.6; }
        .la-author-badge { display: inline-flex; align-items: center; gap: var(--la-space-1); padding: var(--la-space-1) var(--la-space-3); background: var(--la-surface-3); border-radius: var(--la-radius-full); font-size: var(--la-text-xs); color: var(--la-text-muted); margin-top: var(--la-space-3); }
        .la-sources-box { padding: var(--la-space-6); background: var(--la-surface-1); border-radius: var(--la-radius-xl); border: 1px solid var(--la-border); border-left: 4px solid var(--la-primary); margin-top: var(--la-space-12); }
        .la-sources-title { display: flex; align-items: center; gap: var(--la-space-2); font-size: var(--la-text-lg); font-weight: var(--la-weight-semibold); margin-bottom: var(--la-space-4); }
        .la-source-item { display: flex; align-items: flex-start; gap: var(--la-space-3); padding: var(--la-space-3) 0; border-bottom: 1px solid var(--la-border-subtle); }
        .la-source-item:last-child { border-bottom: none; }
        .la-source-icon { flex-shrink: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: var(--la-surface-2); border-radius: var(--la-radius-md); color: var(--la-primary); }
        .la-source-content { flex: 1; min-width: 0; }
        .la-source-title { font-weight: var(--la-weight-medium); color: var(--la-text); }
        .la-source-title a { color: inherit; text-decoration: none; }
        .la-source-title a:hover { color: var(--la-primary); text-decoration: underline; }
        .la-source-domain { font-size: var(--la-text-xs); color: var(--la-text-muted); }
        .la-license-badge { display: inline-flex; align-items: center; gap: var(--la-space-2); padding: var(--la-space-2) var(--la-space-4); background: rgba(34, 197, 94, 0.15); color: var(--la-success, #22c55e); border-radius: var(--la-radius-full); font-size: var(--la-text-xs); font-weight: var(--la-weight-medium); }
        .la-locked-badge { display: inline-flex; align-items: center; gap: var(--la-space-2); padding: var(--la-space-2) var(--la-space-4); background: rgba(245, 158, 11, 0.15); color: var(--la-warning, #f59e0b); border-radius: var(--la-radius-full); font-size: var(--la-text-xs); font-weight: var(--la-weight-medium); }
        .la-comments-section { margin-top: var(--la-space-16); }
        .la-comment-form { padding: var(--la-space-6); background: var(--la-surface-1); border-radius: var(--la-radius-xl); border: 1px solid var(--la-border); margin-bottom: var(--la-space-8); }
        .la-comment-form textarea { width: 100%; min-height: 120px; padding: var(--la-space-4); background: var(--la-surface-2); border: 1px solid var(--la-border); border-radius: var(--la-radius-lg); color: var(--la-text); font-family: inherit; resize: vertical; }
        .la-comment-form textarea:focus { outline: none; border-color: var(--la-primary); }
        .la-comment-form-row { display: flex; gap: var(--la-space-4); margin-bottom: var(--la-space-4); }
        .la-comment-form-row input { flex: 1; padding: var(--la-space-3) var(--la-space-4); background: var(--la-surface-2); border: 1px solid var(--la-border); border-radius: var(--la-radius-lg); color: var(--la-text); font-family: inherit; }
        .la-comment-form-row input:focus { outline: none; border-color: var(--la-primary); }
        .la-comments-list { display: flex; flex-direction: column; gap: var(--la-space-4); }
        .la-comment { padding: var(--la-space-5); background: var(--la-surface-1); border-radius: var(--la-radius-lg); border: 1px solid var(--la-border-subtle); }
        .la-comment-header { display: flex; align-items: center; gap: var(--la-space-3); margin-bottom: var(--la-space-3); }
        .la-comment-avatar { width: 40px; height: 40px; border-radius: var(--la-radius-full); background: var(--la-gradient-primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: var(--la-weight-bold); }
        .la-comment-author { font-weight: var(--la-weight-semibold); }
        .la-comment-date { font-size: var(--la-text-xs); color: var(--la-text-muted); }
        .la-comment-content { color: var(--la-text-secondary); line-height: 1.6; }
        .la-no-comments { padding: var(--la-space-8); text-align: center; color: var(--la-text-muted); background: var(--la-surface-1); border-radius: var(--la-radius-xl); }
        .la-rating-module { background: linear-gradient(135deg, var(--la-surface-1) 0%, var(--la-surface-2) 100%); border-radius: var(--la-radius-2xl); border: 1px solid var(--la-border); overflow: hidden; }
        .la-rating-header { display: flex; justify-content: space-between; align-items: center; padding: var(--la-space-6); background: var(--la-surface-2); border-bottom: 1px solid var(--la-border); }
        .la-rating-score { display: flex; align-items: center; gap: var(--la-space-4); }
        .la-rating-score-number { font-size: var(--la-text-4xl); font-weight: var(--la-weight-bold); color: var(--la-warning, #f59e0b); line-height: 1; }
        .la-rating-score-details { display: flex; flex-direction: column; }
        .la-rating-body { padding: var(--la-space-6); }
        .la-original-source-box { margin-top: var(--la-space-8); padding: var(--la-space-6); background: var(--la-surface-2); border-radius: var(--la-radius-xl); text-align: center; }
        @media (max-width: 640px) { .la-author-box { flex-direction: column; text-align: center; } .la-author-avatar { margin: 0 auto; } .la-comment-form-row { flex-direction: column; } }
    </style>
[% endblock %]

[% block nav_items %]
                <a href="/blog/"><span class="la-icon sm">arrow_back</span> Articles</a>
[% endblock %]

[% block content %]
    <div class="la-reading-progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="Progression de lecture"><div class="la-reading-progress-bar" id="progress"></div></div>

    <div class="la-post-featured-image" id="featured-image-container" style="display:none;">
        <img src="[[ featured_image ]]" alt="[[ title ]]" id="featured-img">
    </div>

    <main class="la-blog-main">
        <div class="la-container">
            <div class="la-with-sidebar" style="--sidebar-width: 220px; --content-min: 65%;">
                <aside class="la-toc" id="toc">
                    <div class="la-rating-badge la-mb-6">
                        <span class="la-icon sm">star</span>
                        <span id="sidebar-rating">[[ average_rating ]]</span>/5
                    </div>
                    <div class="la-toc-title"><span class="la-icon sm">list</span> Sommaire</div>
                    <ul class="la-toc-list" id="toc-list"></ul>
                </aside>

                <article class="la-post">
                    <header class="la-post-header">
                        <div class="la-flex la-gap-3 la-justify-center la-flex-wrap la-mb-4">
                            <a href="/blog/category/[[ category_slug ]].html" class="la-post-card-category" id="category-link">[[ category ]]</a>
                            <div class="la-rating-badge"><span class="la-icon sm">star</span><span>[[ average_rating ]]/5</span></div>
                            <span class="la-license-badge" id="license-badge" style="display:none;"><span class="la-icon xs">verified</span> <span id="license-text">[[ license ]]</span></span>
                            <span class="la-locked-badge" id="locked-badge" style="display:none;"><span class="la-icon xs">lock</span> Article protege</span>
                        </div>
                        <h1 class="la-post-title">[[ title ]]</h1>
                        <p class="la-text-xl la-text-muted la-mt-4">[[ excerpt ]]</p>
                        <div class="la-post-meta la-mt-6">
                            <span><span class="la-icon">person</span> [[ author ]]</span>
                            <span id="institution-span" style="display:none;" class="la-text-primary">[[ author_institution ]]</span>
                            <span><span class="la-icon">calendar_today</span> [[ published_at ]]</span>
                            <span><span class="la-icon">schedule</span> [[ reading_time ]] min</span>
                        </div>
                        <div class="la-flex la-gap-3 la-mt-6 la-justify-center la-flex-wrap">
                            <button class="la-btn outline sm" id="bookmark-btn" title="Favoris"><span class="la-icon sm">bookmark</span><span id="bookmark-text">Favoris</span></button>
                            <button class="la-btn outline sm" onclick="window.print()" title="Imprimer"><span class="la-icon sm">print</span></button>
                            <button class="la-btn outline sm" onclick="navigator.clipboard.writeText(window.location.href)" title="Copier le lien"><span class="la-icon sm">link</span></button>
                            [[ share_buttons|raw ]]
                        </div>
                    </header>

                    <section class="la-author-box la-mt-8">
                        <div class="la-author-avatar">
                            <img src="[[ author_avatar ]]" alt="[[ author ]]" id="author-avatar-img" style="display:none;">
                            <div class="la-author-avatar-placeholder" id="author-avatar-placeholder"><span class="la-icon">person</span></div>
                        </div>
                        <div class="la-author-info">
                            <div class="la-flex la-items-center la-gap-3 la-flex-wrap">
                                <h3 class="la-author-name">[[ author ]]</h3>
                                <span class="la-badge sm" id="original-source-badge" style="display:none;">via [[ original_source ]]</span>
                            </div>
                            <p class="la-author-institution" id="author-institution" style="display:none;">[[ author_institution ]]</p>
                            <p class="la-author-bio" id="author-bio">[[ author_bio ]]</p>
                            <div class="la-author-badge" id="locked-author-badge" style="display:none;"><span class="la-icon xs">verified</span> Contenu original protege</div>
                        </div>
                    </section>

                    <div class="la-prose la-mt-12" id="article-content">
                        [[ content|raw ]]
                    </div>

                    <div class="la-post-tags" id="tags-container"></div>

                    <section class="la-sources-box" id="sources-section" style="display:none;">
                        <h3 class="la-sources-title"><span class="la-icon">link</span> Sources et References</h3>
                        <div class="la-sources-list" id="sources-list"></div>
                    </section>

                    <div class="la-original-source-box" id="original-source-section" style="display:none;">
                        <p class="la-text-sm la-text-muted la-mb-3"><span class="la-icon sm">open_in_new</span> Article original publie sur <span id="original-source-name">[[ original_source ]]</span></p>
                        <a href="[[ original_url ]]" target="_blank" rel="noopener" class="la-btn primary sm" id="original-url-link">Lire l'article original</a>
                    </div>

                    <section class="la-rating-module la-mt-12" id="rating-section">
                        <div class="la-rating-header">
                            <h3 class="la-h5 la-mb-0 la-flex la-items-center la-gap-2"><span class="la-icon">star</span> Evaluez cet article</h3>
                            <div class="la-rating-score">
                                <span class="la-rating-score-number">[[ average_rating ]]</span>
                                <div class="la-rating-score-details">
                                    <div class="la-rating-stars" id="display-stars"></div>
                                    <span class="la-text-xs la-text-muted">sur 5 criteres</span>
                                </div>
                            </div>
                        </div>
                        <div class="la-rating-body" id="rating-form">
                            <p class="la-text-sm la-text-muted la-mb-4">Notez cet article selon 5 criteres :</p>
                            <div class="la-rating-details">
                                <div class="la-rating-criterion" data-criterion="relevance"><div class="la-rating-criterion-header"><span class="la-rating-criterion-name"><span class="la-icon xs">check_circle</span> Pertinence</span><span class="la-rating-criterion-value">0/5</span></div><div class="la-rating-interactive"><button class="la-rating-btn" data-value="1"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="2"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="3"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="4"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="5"><span class="la-icon sm">star_outline</span></button></div></div>
                                <div class="la-rating-criterion" data-criterion="depth"><div class="la-rating-criterion-header"><span class="la-rating-criterion-name"><span class="la-icon xs">layers</span> Profondeur</span><span class="la-rating-criterion-value">0/5</span></div><div class="la-rating-interactive"><button class="la-rating-btn" data-value="1"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="2"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="3"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="4"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="5"><span class="la-icon sm">star_outline</span></button></div></div>
                                <div class="la-rating-criterion" data-criterion="clarity"><div class="la-rating-criterion-header"><span class="la-rating-criterion-name"><span class="la-icon xs">visibility</span> Clarte</span><span class="la-rating-criterion-value">0/5</span></div><div class="la-rating-interactive"><button class="la-rating-btn" data-value="1"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="2"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="3"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="4"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="5"><span class="la-icon sm">star_outline</span></button></div></div>
                                <div class="la-rating-criterion" data-criterion="freshness"><div class="la-rating-criterion-header"><span class="la-rating-criterion-name"><span class="la-icon xs">update</span> Actualite</span><span class="la-rating-criterion-value">0/5</span></div><div class="la-rating-interactive"><button class="la-rating-btn" data-value="1"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="2"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="3"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="4"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="5"><span class="la-icon sm">star_outline</span></button></div></div>
                                <div class="la-rating-criterion" data-criterion="usefulness"><div class="la-rating-criterion-header"><span class="la-rating-criterion-name"><span class="la-icon xs">thumb_up</span> Utilite</span><span class="la-rating-criterion-value">0/5</span></div><div class="la-rating-interactive"><button class="la-rating-btn" data-value="1"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="2"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="3"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="4"><span class="la-icon sm">star_outline</span></button><button class="la-rating-btn" data-value="5"><span class="la-icon sm">star_outline</span></button></div></div>
                            </div>
                            <div class="la-text-center la-mt-6"><button class="la-btn primary" id="submit-rating" disabled><span class="la-icon sm">send</span> Envoyer</button></div>
                        </div>
                        <div id="rating-thanks" class="la-hidden la-text-center la-py-12"><span class="la-icon xxl la-text-success la-mb-4">check_circle</span><p class="la-h5">Merci !</p></div>
                    </section>

                    <section class="la-comments-section la-mt-12">
                        <h2 class="la-h4 la-mb-6 la-flex la-items-center la-gap-2"><span class="la-icon">chat</span> Commentaires <span class="la-badge sm" id="comments-count">0</span></h2>
                        <div class="la-comment-form">
                            <h4 class="la-h6 la-mb-4">Laisser un commentaire</h4>
                            <div class="la-comment-form-row"><input type="text" id="comment-name" placeholder="Votre nom" required><input type="email" id="comment-email" placeholder="Email (optionnel)"></div>
                            <textarea id="comment-content" placeholder="Votre commentaire..." required></textarea>
                            <div class="la-flex la-justify-between la-items-center la-mt-4"><span class="la-text-xs la-text-muted">Les commentaires sont moderes.</span><button class="la-btn primary" id="submit-comment"><span class="la-icon sm">send</span> Envoyer</button></div>
                        </div>
                        <div class="la-comments-list" id="comments-list">
                            <div class="la-no-comments" id="no-comments"><span class="la-icon xl la-text-muted la-mb-3">chat_bubble_outline</span><p>Soyez le premier a commenter !</p></div>
                        </div>
                    </section>

                    <section class="la-mt-16 la-pt-8" id="related-section" style="border-top: 1px solid var(--la-border); display:none;">
                        <h2 class="la-h3 la-mb-6"><span class="la-icon">auto_stories</span> Articles similaires</h2>
                        <div class="la-grid la-cols-1 la-gap-6" id="related-posts"></div>
                    </section>

                    <nav class="la-mt-12 la-text-center"><a href="/blog/" class="la-btn outline"><span class="la-icon sm">arrow_back</span> Retour</a></nav>
                </article>
            </div>
        </div>
    </main>

    <button class="la-btn primary la-rounded-full la-fixed la-bottom-8 la-right-8 la-z-50 la-opacity-0 la-transition-opacity" id="scroll-top"><span class="la-icon">arrow_upward</span></button>
    <div class="la-toast-container" id="toastContainer"></div>
[% endblock %]

[% block scripts %]
    <!-- Data embedded by generator -->
    <script id="post-data" type="application/json">
    {
        "tags": [[ tags_json|raw ]],
        "sources": [[ sources_json|raw ]],
        "related": [[ related_json|raw ]],
        "hasAvatar": [[ has_avatar|raw ]],
        "hasInstitution": [[ has_institution|raw ]],
        "hasBio": [[ has_bio|raw ]],
        "hasLicense": [[ has_license|raw ]],
        "isLocked": [[ is_locked|raw ]],
        "hasOriginalSource": [[ has_original_source|raw ]],
        "hasFeaturedImage": [[ has_featured_image|raw ]]
    }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const data = JSON.parse(document.getElementById('post-data').textContent);

        // Featured image
        if (data.hasFeaturedImage) {
            document.getElementById('featured-image-container').style.display = 'block';
        }

        // Author avatar
        if (data.hasAvatar) {
            document.getElementById('author-avatar-img').style.display = 'block';
            document.getElementById('author-avatar-placeholder').style.display = 'none';
        }

        // Institution
        if (data.hasInstitution) {
            document.getElementById('institution-span').style.display = 'inline';
            document.getElementById('author-institution').style.display = 'block';
        }

        // Bio - show default if empty
        if (!data.hasBio) {
            document.getElementById('author-bio').textContent = 'Auteur de cet article.';
            document.getElementById('author-bio').classList.add('la-text-muted');
        }

        // License
        if (data.hasLicense) {
            document.getElementById('license-badge').style.display = 'inline-flex';
        }

        // Locked
        if (data.isLocked) {
            document.getElementById('locked-badge').style.display = 'inline-flex';
            document.getElementById('locked-author-badge').style.display = 'inline-flex';
        }

        // Original source
        if (data.hasOriginalSource) {
            document.getElementById('original-source-badge').style.display = 'inline';
            document.getElementById('original-source-section').style.display = 'block';
        }

        // Tags
        const tagsContainer = document.getElementById('tags-container');
        if (data.tags && data.tags.length > 0) {
            tagsContainer.innerHTML = data.tags.map(tag =>
                `<a href="/blog/tag/${tag}.html" class="la-tag"><span class="la-icon xs">tag</span> ${tag}</a>`
            ).join('');
        }

        // Sources
        if (data.sources && data.sources.length > 0) {
            document.getElementById('sources-section').style.display = 'block';
            document.getElementById('sources-list').innerHTML = data.sources.map(s =>
                `<div class="la-source-item"><div class="la-source-icon"><span class="la-icon sm">article</span></div><div class="la-source-content"><div class="la-source-title"><a href="${s.url}" target="_blank">${s.title}</a></div><div class="la-source-domain">${s.domain}</div></div></div>`
            ).join('');
        }

        // Related posts
        if (data.related && data.related.length > 0) {
            document.getElementById('related-section').style.display = 'block';
            document.getElementById('related-posts').innerHTML = data.related.map(r =>
                `<a href="${r.url}" class="la-card interactive la-p-6"><h3 class="la-h5 la-mb-2">${r.title}</h3><p class="la-text-sm la-text-muted">${r.excerpt}</p></a>`
            ).join('');
        }

        // Category link - hide if empty
        const catLink = document.getElementById('category-link');
        if (!catLink.textContent.trim()) catLink.style.display = 'none';

        // Reading progress
        window.addEventListener('scroll', () => {
            const h = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const pct = Math.round((window.scrollY / h) * 100);
            const bar = document.getElementById('progress');
            bar.style.width = pct + '%';
            bar.closest('[role="progressbar"]')?.setAttribute('aria-valuenow', pct);
        });

        // Scroll top
        const scrollTopBtn = document.getElementById('scroll-top');
        window.addEventListener('scroll', () => {
            scrollTopBtn.style.opacity = window.scrollY > 500 ? '1' : '0';
        });
        scrollTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

        // TOC generation
        const content = document.getElementById('article-content');
        const tocList = document.getElementById('toc-list');
        const headings = content.querySelectorAll('h2, h3');
        if (headings.length === 0) { document.getElementById('toc').style.display = 'none'; }
        headings.forEach((h, i) => {
            if (!h.id) h.id = 'heading-' + i;
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = '#' + h.id;
            a.className = h.tagName === 'H3' ? 'level-3' : '';
            a.textContent = h.textContent.trim();
            li.appendChild(a);
            tocList.appendChild(li);
        });

        // Bookmarks
        const articleSlug = window.location.pathname.split('/').pop().replace('.html', '');
        const bookmarkBtn = document.getElementById('bookmark-btn');
        const bookmarkText = document.getElementById('bookmark-text');
        function updateBookmark() {
            const bm = JSON.parse(localStorage.getItem('bookmarks') || '[]');
            const is = bm.includes(articleSlug);
            bookmarkBtn.classList.toggle('active', is);
            bookmarkText.textContent = is ? 'Retire' : 'Favoris';
        }
        bookmarkBtn.addEventListener('click', () => {
            const bm = JSON.parse(localStorage.getItem('bookmarks') || '[]');
            const i = bm.indexOf(articleSlug);
            if (i > -1) bm.splice(i, 1); else bm.push(articleSlug);
            localStorage.setItem('bookmarks', JSON.stringify(bm));
            updateBookmark();
        });
        updateBookmark();

        // Rating
        const ratings = {};
        document.querySelectorAll('.la-rating-criterion').forEach(c => {
            const name = c.dataset.criterion;
            c.querySelectorAll('.la-rating-btn').forEach((btn, i) => {
                btn.addEventListener('click', () => {
                    ratings[name] = i + 1;
                    c.querySelectorAll('.la-rating-btn').forEach((b, j) => {
                        b.querySelector('.la-icon').textContent = j <= i ? 'star' : 'star_outline';
                        b.classList.toggle('filled', j <= i);
                    });
                    c.querySelector('.la-rating-criterion-value').textContent = (i+1) + '/5';
                    document.getElementById('submit-rating').disabled = Object.keys(ratings).length < 5;
                });
            });
        });
        const votes = JSON.parse(localStorage.getItem('lunar-votes') || '{}');
        if (votes[articleSlug]) {
            document.getElementById('rating-form').classList.add('la-hidden');
            document.getElementById('rating-thanks').classList.remove('la-hidden');
        }
        document.getElementById('submit-rating').addEventListener('click', () => {
            votes[articleSlug] = ratings;
            localStorage.setItem('lunar-votes', JSON.stringify(votes));
            document.getElementById('rating-form').classList.add('la-hidden');
            document.getElementById('rating-thanks').classList.remove('la-hidden');
        });

        // Comments
        const comments = JSON.parse(localStorage.getItem('lunar-comments') || '{}');
        const postComments = comments[articleSlug] || [];
        function renderComments() {
            const list = document.getElementById('comments-list');
            if (postComments.length > 0) {
                document.getElementById('no-comments').style.display = 'none';
                list.innerHTML = postComments.map(c =>
                    `<div class="la-comment"><div class="la-comment-header"><div class="la-comment-avatar">${c.author.charAt(0).toUpperCase()}</div><div><div class="la-comment-author">${c.author}</div><div class="la-comment-date">${c.date}</div></div></div><div class="la-comment-content">${c.content}</div></div>`
                ).join('');
            }
            document.getElementById('comments-count').textContent = postComments.length;
        }
        renderComments();
        document.getElementById('submit-comment').addEventListener('click', () => {
            const name = document.getElementById('comment-name').value.trim();
            const content = document.getElementById('comment-content').value.trim();
            if (!name || !content) return;
            postComments.push({ author: name, content: content, date: new Date().toLocaleDateString('fr-FR') });
            comments[articleSlug] = postComments;
            localStorage.setItem('lunar-comments', JSON.stringify(comments));
            document.getElementById('comment-name').value = '';
            document.getElementById('comment-content').value = '';
            renderComments();
        });
    });
    </script>
    [[ body_end_injections|raw ]]
[% endblock %]
