<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[% block title %]Lunar Blog[% endblock %]</title>
    <meta name="description" content="[% block description %]Articles et tutoriels sur Lunar Quanta, le framework PHP moderne sans dépendances.[% endblock %]">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="[% block title %]Lunar Blog[% endblock %]">
    <meta name="twitter:description" content="[% block description %]Articles et tutoriels sur Lunar Quanta[% endblock %]">
    <link rel="alternate" type="application/rss+xml" title="Lunar Blog RSS" href="/blog/feed.xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- All theme fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&family=Press+Start+2P&family=VT323&family=Nunito:wght@400;600;700&family=Fredoka+One&family=Orbitron:wght@400;500;700&family=Share+Tech+Mono&family=Poppins:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Cormorant+Garamond:wght@400;500;600&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Lunar Aurora CSS Framework -->
    <link rel="stylesheet" href="/css/lunar-aurora/aurora-blog.css">
    <link rel="stylesheet" href="/blog/assets/enhanced.css">
    <link rel="stylesheet" href="/blog/assets/print.css" media="print">
    [% block head_extra %][% endblock %]
</head>
<body class="la-blog">
    <header class="la-blog-header">
        <div class="la-container">
            <a href="/blog/" class="la-blog-logo">
                <span class="la-icon">rocket_launch</span>
                <span>Lunar Blog</span>
            </a>
            <nav class="la-blog-nav">
                [% block nav_items %]
                <a href="/blog/">Articles</a>
                [% endblock %]
                <!-- Dark/Light Mode Toggle -->
                <button class="la-mode-toggle" id="modeToggle" title="Basculer clair/sombre (D)" aria-label="Basculer entre le mode clair et sombre (raccourci: D)">
                    <span class="la-icon la-mode-icon-light">light_mode</span>
                    <span class="la-icon la-mode-icon-dark">dark_mode</span>
                </button>
                <!-- Theme Switcher -->
                <div class="la-theme-switcher">
                    <button class="la-theme-trigger" title="Changer le thème">
                        <span class="la-icon">palette</span>
                        <span class="la-theme-current">Thème</span>
                        <span class="la-icon sm">expand_more</span>
                    </button>
                    <div class="la-theme-dropdown">
                        <div class="la-theme-group">
                            <div class="la-theme-group-title">Base</div>
                            <button class="la-theme-option" data-theme="default"><span class="la-theme-preview"></span><span class="la-theme-name">Lunar</span><span class="la-icon sm la-theme-check">check</span></button>
                            <button class="la-theme-option" data-theme="cyberpunk"><span class="la-theme-preview"></span><span class="la-theme-name">Cyberpunk</span><span class="la-icon sm la-theme-check">check</span></button>
                            <button class="la-theme-option" data-theme="aurora"><span class="la-theme-preview"></span><span class="la-theme-name">Aurora</span><span class="la-icon sm la-theme-check">check</span></button>
                        </div>
                        <div class="la-theme-group">
                            <div class="la-theme-group-title">Rétro</div>
                            <button class="la-theme-option" data-theme="8bits"><span class="la-theme-preview"></span><span class="la-theme-name">8-Bits</span><span class="la-icon sm la-theme-check">check</span></button>
                            <button class="la-theme-option" data-theme="bubble"><span class="la-theme-preview"></span><span class="la-theme-name">Bubble</span><span class="la-icon sm la-theme-check">check</span></button>
                            <button class="la-theme-option" data-theme="galaxian"><span class="la-theme-preview"></span><span class="la-theme-name">Galaxian</span><span class="la-icon sm la-theme-check">check</span></button>
                            <button class="la-theme-option" data-theme="mario"><span class="la-theme-preview"></span><span class="la-theme-name">Mario</span><span class="la-icon sm la-theme-check">check</span></button>
                        </div>
                        <div class="la-theme-group">
                            <div class="la-theme-group-title">Geek</div>
                            <button class="la-theme-option" data-theme="web90"><span class="la-theme-preview"></span><span class="la-theme-name">Web 90s</span><span class="la-icon sm la-theme-check">check</span></button>
                            <button class="la-theme-option" data-theme="geek"><span class="la-theme-preview"></span><span class="la-theme-name">Geek</span><span class="la-icon sm la-theme-check">check</span></button>
                            <button class="la-theme-option" data-theme="hacker"><span class="la-theme-preview"></span><span class="la-theme-name">Hacker</span><span class="la-icon sm la-theme-check">check</span></button>
                            <button class="la-theme-option" data-theme="eco"><span class="la-theme-preview"></span><span class="la-theme-name">Eco</span><span class="la-icon sm la-theme-check">check</span></button>
                        </div>
                        <div class="la-theme-group">
                            <div class="la-theme-group-title">Système</div>
                            <button class="la-theme-option" data-theme="win95"><span class="la-theme-preview"></span><span class="la-theme-name">Win95</span><span class="la-icon sm la-theme-check">check</span></button>
                            <button class="la-theme-option" data-theme="bsod"><span class="la-theme-preview"></span><span class="la-theme-name">BSOD</span><span class="la-icon sm la-theme-check">check</span></button>
                        </div>
                    </div>
                </div>
                <a href="https://github.com/yrbane/lunar-quanta" target="_blank" title="GitHub">
                    <span class="la-icon sm">code</span>
                </a>
            </nav>
        </div>
    </header>

    [% block content %][% endblock %]

    <footer class="la-blog-footer">
        <div class="la-container">
            <div class="la-blog-footer-bottom">
                <div class="la-flex la-items-center la-gap-3">
                    <span class="la-icon">rocket_launch</span>
                    <span class="la-font-semibold">Lunar Quanta</span>
                </div>
                <div class="la-blog-social">
                    <a href="/blog/feed.xml" title="RSS"><span class="la-icon sm">rss_feed</span></a>
                    <a href="https://github.com/yrbane/lunar-quanta" target="_blank" title="GitHub"><span class="la-icon sm">code</span></a>
                </div>
                <p>&copy; [[ year ]] Lunar Quanta</p>
            </div>
        </div>
    </footer>

    <script>
    // ═══════════════════════════════════════════════════════════════════
    // THEME & MODE SWITCHER (common to all pages)
    // ═══════════════════════════════════════════════════════════════════
    const ThemeSwitcher = {
        themeKey: 'lunar-theme',
        modeKey: 'lunar-mode',

        init() {
            const savedTheme = localStorage.getItem(this.themeKey) || 'default';
            const savedMode = localStorage.getItem(this.modeKey) || 'dark';
            this.applyTheme(savedTheme, savedMode, false);

            document.querySelectorAll('.la-theme-switcher').forEach(s => this.initSwitcher(s));

            document.getElementById('modeToggle')?.addEventListener('click', () => this.toggleMode());

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.la-theme-switcher')) {
                    document.querySelectorAll('.la-theme-dropdown.is-open').forEach(d => d.classList.remove('is-open'));
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.la-theme-dropdown.is-open').forEach(d => d.classList.remove('is-open'));
                }
                if ((e.key === 'd' || e.key === 'D') && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                    this.toggleMode();
                }
            });
        },

        initSwitcher(container) {
            const trigger = container.querySelector('.la-theme-trigger');
            const dropdown = container.querySelector('.la-theme-dropdown');

            trigger?.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.la-theme-dropdown.is-open').forEach(d => {
                    if (d !== dropdown) d.classList.remove('is-open');
                });
                dropdown.classList.toggle('is-open');
            });

            dropdown?.querySelectorAll('.la-theme-option').forEach(option => {
                option.addEventListener('click', () => {
                    this.setTheme(option.dataset.theme);
                    dropdown.classList.remove('is-open');
                });
            });

            this.updateActiveState(container);
        },

        getMode() { return localStorage.getItem(this.modeKey) || 'dark'; },
        getTheme() { return localStorage.getItem(this.themeKey) || 'default'; },

        toggleMode() {
            const newMode = this.getMode() === 'dark' ? 'light' : 'dark';
            this.applyTheme(this.getTheme(), newMode, true);
        },

        setTheme(themeName) {
            this.applyTheme(themeName, this.getMode(), true);
        },

        applyTheme(theme, mode, save = true) {
            let fullTheme;
            if (theme === 'default') {
                fullTheme = mode;
            } else {
                fullTheme = mode === 'dark' ? `${theme}-dark` : theme;
                if (['cyberpunk', 'aurora', 'galaxian', 'hacker', 'bsod'].includes(theme)) {
                    fullTheme = mode === 'light' ? `${theme}-light` : theme;
                }
            }

            document.documentElement.setAttribute('data-theme', fullTheme);
            document.documentElement.setAttribute('data-mode', mode);

            if (save) {
                localStorage.setItem(this.themeKey, theme);
                localStorage.setItem(this.modeKey, mode);
            }

            this.updateModeToggle(mode);
            document.querySelectorAll('.la-theme-switcher').forEach(switcher => {
                this.updateActiveState(switcher);
                const label = switcher.querySelector('.la-theme-current');
                const activeOption = switcher.querySelector(`.la-theme-option[data-theme="${theme}"] .la-theme-name`);
                if (label && activeOption) label.textContent = activeOption.textContent;
            });
        },

        updateModeToggle(mode) {
            const toggle = document.getElementById('modeToggle');
            if (toggle) {
                toggle.classList.toggle('is-light', mode === 'light');
                toggle.classList.toggle('is-dark', mode === 'dark');
            }
        },

        updateActiveState(container) {
            const currentTheme = this.getTheme();
            container.querySelectorAll('.la-theme-option').forEach(option => {
                option.classList.toggle('is-active', option.dataset.theme === currentTheme);
            });
        }
    };

    ThemeSwitcher.init();
    </script>
    [% block scripts %][% endblock %]
</body>
</html>
