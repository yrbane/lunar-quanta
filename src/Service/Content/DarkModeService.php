<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service pour le mode sombre/clair.
 *
 * Génère le CSS et JavaScript pour le basculement de thème.
 *
 * @example
 * ```php
 * $darkMode = new DarkModeService();
 *
 * // Générer le toggle
 * $html = $darkMode->generateToggle();
 *
 * // Générer le JavaScript
 * $js = $darkMode->generateJs();
 *
 * // Générer les variables CSS
 * $css = $darkMode->generateCss();
 * ```
 */
final class DarkModeService
{
    private string $storageKey = 'theme';
    private string $defaultTheme = 'system';
    private string $dataAttribute = 'data-theme';
    private bool $respectSystemPreference = true;

    /** @var array<string, string> */
    private array $lightColors = [
        '--bg-primary' => '#ffffff',
        '--bg-secondary' => '#f8fafc',
        '--bg-tertiary' => '#f1f5f9',
        '--text-primary' => '#1e293b',
        '--text-secondary' => '#475569',
        '--text-muted' => '#94a3b8',
        '--border-color' => '#e2e8f0',
        '--link-color' => '#3b82f6',
        '--link-hover' => '#2563eb',
        '--accent-color' => '#3b82f6',
        '--code-bg' => '#f1f5f9',
        '--shadow-color' => 'rgba(0, 0, 0, 0.1)',
    ];

    /** @var array<string, string> */
    private array $darkColors = [
        '--bg-primary' => '#0f172a',
        '--bg-secondary' => '#1e293b',
        '--bg-tertiary' => '#334155',
        '--text-primary' => '#f1f5f9',
        '--text-secondary' => '#cbd5e1',
        '--text-muted' => '#64748b',
        '--border-color' => '#334155',
        '--link-color' => '#60a5fa',
        '--link-hover' => '#93c5fd',
        '--accent-color' => '#60a5fa',
        '--code-bg' => '#1e293b',
        '--shadow-color' => 'rgba(0, 0, 0, 0.3)',
    ];

    /**
     * Définit la clé de stockage.
     */
    public function setStorageKey(string $key): self
    {
        $this->storageKey = $key;
        return $this;
    }

    /**
     * Définit le thème par défaut.
     */
    public function setDefaultTheme(string $theme): self
    {
        $this->defaultTheme = in_array($theme, ['light', 'dark', 'system']) ? $theme : 'system';
        return $this;
    }

    /**
     * Définit l'attribut data pour le thème.
     */
    public function setDataAttribute(string $attribute): self
    {
        $this->dataAttribute = $attribute;
        return $this;
    }

    /**
     * Active/désactive le respect des préférences système.
     */
    public function setRespectSystemPreference(bool $respect): self
    {
        $this->respectSystemPreference = $respect;
        return $this;
    }

    /**
     * Définit les couleurs du thème clair.
     *
     * @param array<string, string> $colors
     */
    public function setLightColors(array $colors): self
    {
        $this->lightColors = array_merge($this->lightColors, $colors);
        return $this;
    }

    /**
     * Définit les couleurs du thème sombre.
     *
     * @param array<string, string> $colors
     */
    public function setDarkColors(array $colors): self
    {
        $this->darkColors = array_merge($this->darkColors, $colors);
        return $this;
    }

    /**
     * Génère le CSS des thèmes.
     */
    public function generateCss(): string
    {
        $lightVars = $this->formatCssVariables($this->lightColors);
        $darkVars = $this->formatCssVariables($this->darkColors);

        $systemDarkRule = $this->respectSystemPreference
            ? <<<CSS
@media (prefers-color-scheme: dark) {
    :root:not([{$this->dataAttribute}="light"]) {
        {$darkVars}
    }
}
CSS
            : '';

        return <<<CSS
/* Theme Colors - Light (default) */
:root,
[{$this->dataAttribute}="light"] {
    {$lightVars}
    color-scheme: light;
}

/* Theme Colors - Dark */
[{$this->dataAttribute}="dark"] {
    {$darkVars}
    color-scheme: dark;
}

{$systemDarkRule}

/* Transition for theme switching */
:root {
    --theme-transition: 0.2s ease;
}

body {
    background-color: var(--bg-primary);
    color: var(--text-primary);
    transition: background-color var(--theme-transition), color var(--theme-transition);
}

/* Theme toggle button styles */
.theme-toggle {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 9999px;
    padding: 0.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    transition: background-color var(--theme-transition);
}

.theme-toggle:hover {
    background: var(--bg-tertiary);
}

.theme-toggle .icon-sun,
.theme-toggle .icon-moon {
    width: 20px;
    height: 20px;
}

.theme-toggle .icon-sun {
    display: block;
}

.theme-toggle .icon-moon {
    display: none;
}

[{$this->dataAttribute}="dark"] .theme-toggle .icon-sun {
    display: none;
}

[{$this->dataAttribute}="dark"] .theme-toggle .icon-moon {
    display: block;
}
CSS;
    }

    /**
     * Génère le JavaScript pour le basculement de thème.
     */
    public function generateJs(): string
    {
        $storageKey = addslashes($this->storageKey);
        $dataAttribute = addslashes($this->dataAttribute);
        $defaultTheme = addslashes($this->defaultTheme);

        return <<<JS
(function() {
    'use strict';

    const STORAGE_KEY = '{$storageKey}';
    const DATA_ATTRIBUTE = '{$dataAttribute}';
    const DEFAULT_THEME = '{$defaultTheme}';

    // Get saved theme or default
    function getSavedTheme() {
        return localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
    }

    // Get effective theme (resolving 'system')
    function getEffectiveTheme(theme) {
        if (theme === 'system') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return theme;
    }

    // Apply theme to document
    function applyTheme(theme) {
        const effectiveTheme = getEffectiveTheme(theme);
        document.documentElement.setAttribute(DATA_ATTRIBUTE, effectiveTheme);

        // Dispatch event for other scripts
        document.dispatchEvent(new CustomEvent('themechange', {
            detail: { theme: effectiveTheme, saved: theme }
        }));
    }

    // Toggle between light and dark
    function toggleTheme() {
        const current = getSavedTheme();
        const effectiveCurrent = getEffectiveTheme(current);
        const newTheme = effectiveCurrent === 'dark' ? 'light' : 'dark';

        localStorage.setItem(STORAGE_KEY, newTheme);
        applyTheme(newTheme);
    }

    // Set specific theme
    function setTheme(theme) {
        if (!['light', 'dark', 'system'].includes(theme)) {
            theme = DEFAULT_THEME;
        }
        localStorage.setItem(STORAGE_KEY, theme);
        applyTheme(theme);
    }

    // Initialize
    function init() {
        // Apply saved theme immediately
        applyTheme(getSavedTheme());

        // Listen for system preference changes
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', function() {
            if (getSavedTheme() === 'system') {
                applyTheme('system');
            }
        });

        // Set up toggle buttons
        document.querySelectorAll('.theme-toggle').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                toggleTheme();
            });
        });
    }

    // Run init when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose API
    window.ThemeManager = {
        toggle: toggleTheme,
        setTheme: setTheme,
        getTheme: function() { return getEffectiveTheme(getSavedTheme()); },
        getSavedTheme: getSavedTheme
    };
})();
JS;
    }

    /**
     * Génère le script inline pour éviter le flash.
     */
    public function generateNoFlashScript(): string
    {
        $storageKey = addslashes($this->storageKey);
        $dataAttribute = addslashes($this->dataAttribute);

        return <<<JS
(function() {
    var theme = localStorage.getItem('{$storageKey}') || 'system';
    if (theme === 'system') {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('{$dataAttribute}', theme);
})();
JS;
    }

    /**
     * Génère le bouton de basculement.
     */
    public function generateToggle(string $class = 'theme-toggle'): string
    {
        return <<<HTML
<button type="button" class="{$class}" aria-label="Basculer le thème" title="Basculer le thème">
    <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
    <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
</button>
HTML;
    }

    /**
     * Génère le sélecteur de thème avec 3 options.
     */
    public function generateSelector(string $class = 'theme-selector'): string
    {
        return <<<HTML
<div class="{$class}">
    <button type="button" data-theme-value="light" aria-label="Thème clair">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
    </button>
    <button type="button" data-theme-value="dark" aria-label="Thème sombre">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    </button>
    <button type="button" data-theme-value="system" aria-label="Thème système">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </button>
</div>
HTML;
    }

    /**
     * Génère tout le code nécessaire.
     */
    public function generateAll(): array
    {
        return [
            'css' => $this->generateCss(),
            'js' => $this->generateJs(),
            'noFlashScript' => $this->generateNoFlashScript(),
            'toggle' => $this->generateToggle(),
        ];
    }

    /**
     * Formate les variables CSS.
     */
    private function formatCssVariables(array $colors): string
    {
        $lines = [];
        foreach ($colors as $name => $value) {
            $lines[] = "    {$name}: {$value};";
        }
        return implode("\n", $lines);
    }
}
