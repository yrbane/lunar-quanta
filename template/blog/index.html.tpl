[% extends '_layout.html.tpl' %]

[% block title %]Lunar Blog - Framework PHP Moderne[% endblock %]

[% block description %]Articles et tutoriels sur Lunar Quanta, le framework PHP moderne sans dépendances.[% endblock %]

[% block head_extra %]
    [[ schema_org|raw ]]
    [[ head_injections|raw ]]
[% endblock %]

[% block nav_items %]
                <a href="/blog/" aria-current="page">Articles</a>
                <button onclick="focusSearch()" title="Rechercher (/)">
                    <span class="la-icon sm">search</span>
                </button>
                <button onclick="showKeyboardShortcuts()" title="Raccourcis (?)">
                    <span class="la-icon sm">keyboard</span>
                </button>
[% endblock %]

[% block content %]
    <section class="la-blog-hero">
        <div class="la-container">
            <div class="la-text-center">
                <span class="la-badge lg la-mb-4">
                    <span class="la-icon sm">layers</span>
                    Tech, Science & Innovation
                </span>
                <h1 class="la-blog-hero-title">Le Blog <span class="la-gradient-text">Lunar Quanta</span></h1>
                <p class="la-blog-hero-subtitle">Explorez les dernières tendances en IA, biotechnologie, informatique quantique et bien plus. Articles, tutoriels et analyses pour rester à la pointe de l'innovation.</p>

                <!-- Stats Cards -->
                <div class="la-hero-stats">
                    <div class="la-hero-stat">
                        <div class="la-hero-stat-value" id="articleCount">[[ article_count ]]</div>
                        <div class="la-hero-stat-label">Articles</div>
                    </div>
                    <div class="la-hero-stat">
                        <div class="la-hero-stat-value">[[ categories_count ]]</div>
                        <div class="la-hero-stat-label">Catégories</div>
                    </div>
                    <div class="la-hero-stat">
                        <div class="la-hero-stat-value">[[ tags_count ]]</div>
                        <div class="la-hero-stat-label">Tags</div>
                    </div>
                </div>

                <!-- Search -->
                <div class="la-search-wrapper la-max-w-md la-mx-auto">
                    <div class="la-search-form">
                        <span class="la-icon">search</span>
                        <input type="search" id="searchInput" placeholder="Rechercher un article..." autocomplete="search" class="la-input" aria-label="Rechercher un article">
                        <span class="la-search-shortcut">
                            <kbd>/</kbd>
                        </span>
                    </div>
                    <div class="la-search-results" id="searchResults" style="display: none;">
                        <div class="la-search-results-list" id="searchResultsList"></div>
                        <div class="la-search-no-results" id="searchNoResults" style="display: none;">
                            <span class="la-icon">search_off</span>
                            <span>Aucun résultat trouvé</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Articles Slider -->
            <div class="la-hero-slider" id="heroSlider">
                <div class="la-hero-slider-track" id="sliderTrack">
                    [[ slider_items|raw ]]
                </div>
                <div class="la-hero-slider-nav">
                    <button class="la-hero-slider-btn" id="sliderPrev" aria-label="Article précédent">
                        <span class="la-icon">chevron_left</span>
                    </button>
                    <div class="la-hero-slider-dots" id="sliderDots"></div>
                    <button class="la-hero-slider-btn" id="sliderNext" aria-label="Article suivant">
                        <span class="la-icon">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <main class="la-blog-main">
        <div class="la-container">
            <!-- Toolbar -->
            <div class="la-flex la-justify-between la-items-center la-flex-wrap la-gap-4 la-mb-6">
                <div class="la-flex la-items-center la-gap-3">
                    <h2 class="la-h3 la-mb-0">Articles</h2>
                    <span class="la-badge" id="visibleCount">[[ article_count ]]</span>
                </div>
                <div class="la-flex la-gap-4 la-items-center">
                    <div class="la-admin-tabs">
                        <button class="la-admin-tab active" data-filter="all">Tous</button>
                        <button class="la-admin-tab" data-filter="framework">Framework</button>
                        <button class="la-admin-tab" data-filter="tutoriels">Tutoriels</button>
                    </div>
                    <div class="la-flex la-gap-1">
                        <button class="la-btn ghost sm active" data-view="grid" title="Vue grille">
                            <span class="la-icon sm">grid_view</span>
                        </button>
                        <button class="la-btn ghost sm" data-view="list" title="Vue liste">
                            <span class="la-icon sm">view_list</span>
                        </button>
                    </div>
                </div>
            </div>

            [% if posts %]
            <div class="la-post-grid" id="articlesGrid">
                [% for post in posts %]
                <article class="la-post-card" data-title="[[ post.title ]]" data-category="[[ post.category ]]" data-tags="[[ post.tags_string ]]">
                    <div class="la-post-card-image[% if not post.featured_image %] placeholder[% endif %]">
                        [% if post.featured_image %]
                        <img src="[[ post.featured_image ]]" alt="[[ post.title ]]" loading="lazy">
                        [% else %]
                        <span class="la-icon xl">image</span>
                        [% endif %]
                    </div>
                    <div class="la-post-card-content">
                        <div class="la-post-card-meta">
                            [% if post.category %]
                            <a href="/blog/category/[[ post.category_slug ]].html" class="la-post-card-category">[[ post.category ]]</a>
                            [% endif %]
                            <span class="la-post-card-date">
                                <span class="la-icon xs">calendar_today</span>
                                [[ post.published_at ]]
                            </span>
                        </div>
                        <h3 class="la-post-card-title">
                            <a href="[[ post.url ]]">[[ post.title ]]</a>
                        </h3>
                        [% if post.excerpt %]
                        <p class="la-post-card-excerpt">[[ post.excerpt ]]</p>
                        [% endif %]
                        <div class="la-post-card-footer">
                            [% if post.author %]
                            <div class="la-post-card-author">
                                <span class="la-avatar sm">
                                    <span class="la-icon sm">person</span>
                                </span>
                                <span>[[ post.author ]]</span>
                            </div>
                            [% endif %]
                            <span class="la-post-card-read-time">
                                <span class="la-icon xs">schedule</span>
                                [[ post.reading_time ]] min
                            </span>
                        </div>
                        [% if post.average_rating %]
                        <div class="la-post-card-rating">
                            <div class="la-rating-stars">
                                [[ post.rating_stars|raw ]]
                            </div>
                            <span class="la-rating-value">[[ post.average_rating ]]</span>
                        </div>
                        [% endif %]
                    </div>
                </article>
                [% endfor %]
            </div>
            [% else %]
            <div class="la-empty-state">
                <div class="la-empty-state-icon">
                    <span class="la-icon xxl">edit_note</span>
                </div>
                <h2 class="la-empty-state-title">Aucun article pour l'instant</h2>
                <p class="la-empty-state-description">Les articles publiés apparaîtront ici.</p>
            </div>
            [% endif %]

            <!-- Tags Cloud (below articles) -->
            <section class="la-tags-section la-mt-12">
                <div class="la-flex la-justify-between la-items-center la-mb-6">
                    <h3 class="la-h4 la-mb-0">
                        <span class="la-icon">sell</span>
                        Explorer par tags
                    </h3>
                    <button class="la-btn ghost sm" onclick="toggleTags()">
                        <span id="tagsToggleText">Voir tout</span>
                        <span class="la-icon sm">expand_more</span>
                    </button>
                </div>
                <div class="la-tag-cloud collapsed" id="tagsCloud">
                    [[ tags_list|raw ]]
                </div>
            </section>
        </div>
    </main>

    <!-- Scroll to Top -->
    <button class="la-btn primary la-rounded-full la-fixed la-bottom-8 la-right-8 la-z-50 la-opacity-0 la-transition-opacity" id="scrollTop" onclick="scrollToTop()">
        <span class="la-icon">arrow_upward</span>
    </button>

    <!-- Keyboard Shortcuts Modal -->
    <div class="la-modal-backdrop" id="kbdModal">
        <div class="la-modal">
            <div class="la-modal-header">
                <h3 class="la-modal-title">Raccourcis clavier</h3>
                <button class="la-modal-close" onclick="hideKeyboardShortcuts()">
                    <span class="la-icon">close</span>
                </button>
            </div>
            <div class="la-modal-body">
                <div class="la-flex la-flex-col la-gap-3">
                    <div class="la-flex la-justify-between la-items-center">
                        <span>Rechercher</span>
                        <kbd class="la-badge outline">/</kbd>
                    </div>
                    <div class="la-flex la-justify-between la-items-center">
                        <span>Afficher cette aide</span>
                        <kbd class="la-badge outline">?</kbd>
                    </div>
                    <div class="la-flex la-justify-between la-items-center">
                        <span>Basculer clair/sombre</span>
                        <kbd class="la-badge outline">D</kbd>
                    </div>
                    <div class="la-flex la-justify-between la-items-center">
                        <span>Remonter en haut</span>
                        <kbd class="la-badge outline">T</kbd>
                    </div>
                    <div class="la-flex la-justify-between la-items-center">
                        <span>Vue grille</span>
                        <kbd class="la-badge outline">G</kbd>
                    </div>
                    <div class="la-flex la-justify-between la-items-center">
                        <span>Vue liste</span>
                        <kbd class="la-badge outline">L</kbd>
                    </div>
                    <div class="la-flex la-justify-between la-items-center">
                        <span>Fermer</span>
                        <kbd class="la-badge outline">Esc</kbd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="la-toast-container" id="toastContainer"></div>
[% endblock %]

[% block scripts %]
    <script>
        // Search functionality with live results
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const searchResultsList = document.getElementById('searchResultsList');
        const searchNoResults = document.getElementById('searchNoResults');
        const articlesGrid = document.getElementById('articlesGrid');
        const articles = articlesGrid ? Array.from(articlesGrid.querySelectorAll('.la-post-card')) : [];
        const visibleCount = document.getElementById('visibleCount');

        // Build search index from visible articles
        const searchIndex = articles.map(article => ({
            title: article.dataset.title || '',
            category: article.dataset.category || '',
            tags: article.dataset.tags || '',
            url: article.querySelector('.la-post-card-title a')?.href || '',
            excerpt: article.querySelector('.la-post-card-excerpt')?.textContent || '',
            date: article.querySelector('.la-post-card-date')?.textContent || '',
            author: article.querySelector('.la-post-card-author span:last-child')?.textContent || '',
            element: article
        }));

        let searchTimeout;

        function performSearch(query) {
            query = query.toLowerCase().trim();

            if (query.length < 2) {
                searchResults.style.display = 'none';
                articles.forEach(a => a.classList.remove('la-hidden'));
                visibleCount.textContent = articles.length;
                return;
            }

            const terms = query.split(/\s+/).filter(t => t.length >= 2);
            const results = [];

            searchIndex.forEach(item => {
                let score = 0;
                const titleLower = item.title.toLowerCase();
                const categoryLower = item.category.toLowerCase();
                const tagsLower = item.tags.toLowerCase();
                const excerptLower = item.excerpt.toLowerCase();

                terms.forEach(term => {
                    if (titleLower.includes(term)) score += 10;
                    if (categoryLower.includes(term)) score += 5;
                    if (tagsLower.includes(term)) score += 5;
                    if (excerptLower.includes(term)) score += 2;
                });

                if (score > 0) {
                    results.push({ ...item, score });
                }
            });

            results.sort((a, b) => b.score - a.score);

            let visible = 0;
            articles.forEach(article => {
                const inResults = results.some(r => r.element === article);
                article.classList.toggle('la-hidden', !inResults);
                if (inResults) visible++;
            });
            visibleCount.textContent = visible;

            if (results.length > 0) {
                searchNoResults.style.display = 'none';
                searchResultsList.innerHTML = results.slice(0, 8).map(r => `
                    <a href="${r.url}" class="la-search-result-item">
                        <div class="la-search-result-title">${highlightText(r.title, terms)}</div>
                        <div class="la-search-result-meta">
                            ${r.category ? `<span class="la-search-result-category">${r.category}</span>` : ''}
                            ${r.date ? `<span class="la-search-result-date">${r.date}</span>` : ''}
                        </div>
                    </a>
                `).join('');
                searchResults.style.display = 'block';
            } else {
                searchResultsList.innerHTML = '';
                searchNoResults.style.display = 'flex';
                searchResults.style.display = 'block';
            }
        }

        function highlightText(text, terms) {
            let result = text;
            terms.forEach(term => {
                const regex = new RegExp(`(${term})`, 'gi');
                result = result.replace(regex, '<mark>$1</mark>');
            });
            return result;
        }

        searchInput?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => performSearch(e.target.value), 150);
        });

        searchInput?.addEventListener('focus', () => {
            if (searchInput.value.length >= 2) {
                performSearch(searchInput.value);
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.la-search-wrapper')) {
                searchResults.style.display = 'none';
            }
        });

        function focusSearch() {
            searchInput?.focus();
        }

        // Filter tabs
        document.querySelectorAll('.la-admin-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.la-admin-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const filter = tab.dataset.filter;
                let visible = 0;

                articles.forEach(article => {
                    const category = article.dataset.category?.toLowerCase() || '';
                    const matches = filter === 'all' || category.includes(filter);
                    article.classList.toggle('la-hidden', !matches);
                    if (matches) visible++;
                });

                visibleCount.textContent = visible;
            });
        });

        // View toggle
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-view]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (btn.dataset.view === 'list') {
                    articlesGrid?.classList.add('la-post-list');
                    articlesGrid?.classList.remove('la-post-grid');
                } else {
                    articlesGrid?.classList.remove('la-post-list');
                    articlesGrid?.classList.add('la-post-grid');
                }
            });
        });

        // Tags toggle
        function toggleTags() {
            const cloud = document.getElementById('tagsCloud');
            const toggleText = document.getElementById('tagsToggleText');
            cloud.classList.toggle('collapsed');
            cloud.classList.toggle('expanded');
            if (toggleText) {
                toggleText.textContent = cloud.classList.contains('expanded') ? 'Réduire' : 'Voir tout';
            }
        }

        // Mobile nav toggle
        function toggleMobileNav() {
            document.querySelector('.la-blog-nav').classList.toggle('is-open');
        }

        // Scroll to top
        const scrollTopBtn = document.getElementById('scrollTop');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                scrollTopBtn.classList.remove('la-opacity-0');
                scrollTopBtn.classList.add('la-opacity-100');
            } else {
                scrollTopBtn.classList.add('la-opacity-0');
                scrollTopBtn.classList.remove('la-opacity-100');
            }
        });

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Keyboard shortcuts
        function showKeyboardShortcuts() {
            document.getElementById('kbdModal').classList.add('is-open');
        }

        function hideKeyboardShortcuts() {
            document.getElementById('kbdModal').classList.remove('is-open');
        }

        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT') return;

            switch(e.key) {
                case '/':
                    e.preventDefault();
                    focusSearch();
                    break;
                case '?':
                    showKeyboardShortcuts();
                    break;
                case 't':
                case 'T':
                    scrollToTop();
                    break;
                case 'g':
                case 'G':
                    document.querySelector('[data-view="grid"]')?.click();
                    break;
                case 'l':
                case 'L':
                    document.querySelector('[data-view="list"]')?.click();
                    break;
                case 'Escape':
                    hideKeyboardShortcuts();
                    searchInput?.blur();
                    break;
            }
        });

        // Toast notifications
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `la-toast ${type}`;
            toast.innerHTML = `
                <div class="la-toast-icon"><span class="la-icon">info</span></div>
                <div class="la-toast-content">
                    <div class="la-toast-message">${message}</div>
                </div>
                <button class="la-toast-close" onclick="this.parentElement.remove()">
                    <span class="la-icon sm">close</span>
                </button>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Click outside to close modal
        document.getElementById('kbdModal').addEventListener('click', (e) => {
            if (e.target.classList.contains('la-modal-backdrop')) hideKeyboardShortcuts();
        });

        // ═══════════════════════════════════════════════════════════════════
        // HERO SLIDER
        // ═══════════════════════════════════════════════════════════════════
        const HeroSlider = {
            currentSlide: 0,
            slideCount: 0,
            autoPlayInterval: null,
            autoPlayDelay: 5000,

            init() {
                this.track = document.getElementById('sliderTrack');
                this.dots = document.getElementById('sliderDots');
                this.prevBtn = document.getElementById('sliderPrev');
                this.nextBtn = document.getElementById('sliderNext');

                if (!this.track) return;

                this.slides = this.track.querySelectorAll('.la-hero-slide');
                this.slideCount = this.slides.length;

                if (this.slideCount === 0) return;

                this.createDots();

                this.prevBtn?.addEventListener('click', () => this.prev());
                this.nextBtn?.addEventListener('click', () => this.next());

                document.addEventListener('keydown', (e) => {
                    if (e.target.tagName === 'INPUT') return;
                    if (e.key === 'ArrowLeft') this.prev();
                    if (e.key === 'ArrowRight') this.next();
                });

                let touchStartX = 0;
                this.track.addEventListener('touchstart', (e) => {
                    touchStartX = e.touches[0].clientX;
                    this.stopAutoPlay();
                });
                this.track.addEventListener('touchend', (e) => {
                    const diff = touchStartX - e.changedTouches[0].clientX;
                    if (Math.abs(diff) > 50) {
                        diff > 0 ? this.next() : this.prev();
                    }
                    this.startAutoPlay();
                });

                const slider = document.getElementById('heroSlider');
                slider?.addEventListener('mouseenter', () => this.stopAutoPlay());
                slider?.addEventListener('mouseleave', () => this.startAutoPlay());

                this.startAutoPlay();
            },

            createDots() {
                if (!this.dots) return;
                this.dots.innerHTML = '';
                for (let i = 0; i < this.slideCount; i++) {
                    const dot = document.createElement('button');
                    dot.className = 'la-hero-slider-dot' + (i === 0 ? ' active' : '');
                    dot.setAttribute('aria-label', `Aller à l'article ${i + 1}`);
                    dot.addEventListener('click', () => this.goTo(i));
                    this.dots.appendChild(dot);
                }
            },

            updateDots() {
                if (!this.dots) return;
                this.dots.querySelectorAll('.la-hero-slider-dot').forEach((dot, i) => {
                    dot.classList.toggle('active', i === this.currentSlide);
                });
            },

            goTo(index) {
                this.currentSlide = (index + this.slideCount) % this.slideCount;
                this.track.style.transform = `translateX(-${this.currentSlide * 100}%)`;
                this.updateDots();
            },

            prev() {
                this.goTo(this.currentSlide - 1);
            },

            next() {
                this.goTo(this.currentSlide + 1);
            },

            startAutoPlay() {
                this.stopAutoPlay();
                this.autoPlayInterval = setInterval(() => this.next(), this.autoPlayDelay);
            },

            stopAutoPlay() {
                if (this.autoPlayInterval) {
                    clearInterval(this.autoPlayInterval);
                    this.autoPlayInterval = null;
                }
            }
        };

        HeroSlider.init();
    </script>
    [[ body_end_injections|raw ]]
[% endblock %]
