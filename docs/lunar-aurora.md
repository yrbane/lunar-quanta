# Lunar Aurora - CSS Framework

## Vue d'ensemble

Lunar Aurora est le framework CSS de Lunar Quanta. Construit avec des CSS Custom Properties modernes et l'espace colorimétrique OKLCH, il offre un système de thèmes complet et accessible.

## Architecture

```
assets/css/lunar-aurora/
├── aurora.css          # Bundle principal (import all)
├── aurora-blog.css     # Bundle blog optimisé
├── aurora-admin.css    # Bundle admin optimisé
├── reset.css           # Reset CSS moderne
├── tokens.css          # Design tokens (spacing, typography, colors)
├── themes.css          # Système de thèmes (30+ thèmes)
├── typography.css      # Styles typographiques
├── layout.css          # Grilles et layouts
├── components.css      # Composants UI
├── utilities.css       # Classes utilitaires
├── animations.css      # Animations et transitions
├── admin.css           # Composants admin spécifiques
└── blog.css            # Composants blog spécifiques
```

## Design Tokens

Les tokens définissent les valeurs fondamentales du système de design.

### Couleurs (OKLCH)

```css
:root {
    /* Hues primaires - modifiables par thème */
    --la-hue-primary: 250;
    --la-hue-secondary: 180;
    --la-hue-accent: 35;

    /* Couleurs sémantiques */
    --la-primary: oklch(55% 0.2 var(--la-hue-primary));
    --la-secondary: oklch(60% 0.15 var(--la-hue-secondary));
    --la-accent: oklch(70% 0.18 var(--la-hue-accent));

    /* États */
    --la-success: oklch(55% 0.18 142);
    --la-warning: oklch(75% 0.15 85);
    --la-error: oklch(55% 0.22 25);
    --la-info: oklch(55% 0.15 230);
}
```

### Surfaces

```css
:root {
    --la-surface-0: oklch(100% 0 0);      /* Background principal */
    --la-surface-1: oklch(98% 0.005 ...); /* Cards, containers */
    --la-surface-2: oklch(96% 0.008 ...); /* Éléments imbriqués */
    --la-surface-3: oklch(94% 0.01 ...);  /* Hover states */
    --la-surface-raised: ...;              /* Éléments surélevés */
    --la-surface-overlay: ...;             /* Modales, overlays */
}
```

### Texte

```css
:root {
    --la-text: oklch(15% 0.02 ...);           /* Texte principal */
    --la-text-secondary: oklch(40% 0.02 ...); /* Texte secondaire */
    --la-text-muted: oklch(55% 0.01 ...);     /* Texte désactivé */
    --la-text-disabled: oklch(70% 0.005 ...); /* Désactivé */
}
```

### Espacement

```css
:root {
    --la-space-1: 0.25rem;  /* 4px */
    --la-space-2: 0.5rem;   /* 8px */
    --la-space-3: 0.75rem;  /* 12px */
    --la-space-4: 1rem;     /* 16px */
    --la-space-6: 1.5rem;   /* 24px */
    --la-space-8: 2rem;     /* 32px */
    --la-space-12: 3rem;    /* 48px */
    --la-space-16: 4rem;    /* 64px */
}
```

### Typographie

```css
:root {
    --la-font-sans: 'Inter', system-ui, sans-serif;
    --la-font-mono: 'JetBrains Mono', monospace;
    --la-font-display: var(--la-font-sans);

    --la-text-xs: 0.75rem;
    --la-text-sm: 0.875rem;
    --la-text-base: 1rem;
    --la-text-lg: 1.125rem;
    --la-text-xl: 1.25rem;
    --la-text-2xl: 1.5rem;
    --la-text-3xl: 1.875rem;
    --la-text-4xl: 2.25rem;
}
```

### Bordures et ombres

```css
:root {
    /* Radius - modulable par --la-radius-factor */
    --la-radius-sm: calc(0.25rem * var(--la-radius-factor, 1));
    --la-radius-md: calc(0.5rem * var(--la-radius-factor, 1));
    --la-radius-lg: calc(1rem * var(--la-radius-factor, 1));
    --la-radius-full: 9999px;

    /* Ombres */
    --la-shadow-sm: 0 1px 2px oklch(0% 0 0 / 5%);
    --la-shadow-md: 0 4px 6px -1px oklch(0% 0 0 / 10%);
    --la-shadow-lg: 0 10px 15px -3px oklch(0% 0 0 / 10%);
    --la-shadow-glow: 0 0 20px oklch(var(--la-primary) / 30%);
}
```

## Système de Thèmes

### Utilisation

```html
<!-- Via attribut data-theme -->
<html data-theme="cyberpunk">

<!-- Ou automatique via préférences système -->
<html> <!-- Utilise prefers-color-scheme -->
```

### Thèmes disponibles

| Thème | Type | Description |
|-------|------|-------------|
| `dark` | Base | Thème sombre par défaut |
| `light` | Base | Thème clair |
| `cyberpunk` | Couleur | Néon magenta/cyan, style futuriste |
| `aurora` | Couleur | Aurore boréale, dégradés subtils |
| `ocean` | Couleur | Bleu profond, ambiance marine |
| `forest` | Couleur | Vert nature, organique |
| `sunset` | Couleur | Orange/rose chaleureux |
| `lavender` | Couleur | Violet doux, apaisant |
| `mono` / `mono-dark` | Minimal | Noir et blanc pur |
| `8bits` / `8bits-dark` | Rétro | Style pixel art, arcade |
| `bubble` / `bubble-dark` | Rétro | Pastel mignon, arcade |
| `galaxian` / `galaxian-light` | Rétro | Space invaders, cosmos |
| `geek` / `geek-dark` | Tech | Style terminal, code |
| `hacker` / `hacker-light` | Tech | Matrix, vert phosphore |
| `eco` / `eco-dark` | Nature | Écologique, durable |
| `win95` / `win95-dark` | Nostalgie | Windows 95 classic |
| `bsod` / `bsod-light` | Nostalgie | Blue Screen of Death |
| `mario` / `mario-dark` | Gaming | Super Mario Bros |
| `web90` / `web90-dark` | Nostalgie | GeoCities vibes |

### Variables de thème

Chaque thème peut définir :

```css
[data-theme="example"] {
    color-scheme: dark; /* ou light */

    /* Teintes de base */
    --la-hue-primary: 280;
    --la-hue-secondary: 180;
    --la-hue-accent: 45;

    /* Couleurs */
    --la-primary: oklch(65% 0.25 var(--la-hue-primary));
    --la-surface-0: oklch(10% 0.02 var(--la-hue-primary));
    --la-text: oklch(95% 0.01 var(--la-hue-primary));
    /* ... */

    /* Visuels */
    --la-hero-image: url('...');
    --la-icon-logo: 'icon_name';
    --la-font-sans: 'Custom Font', sans-serif;
    --la-font-display: 'Display Font', sans-serif;

    /* Modificateurs */
    --la-radius-factor: 0.5; /* 0 = carré, 2 = très arrondi */
    --la-shadow-glow: 0 0 20px oklch(...);
    --la-gradient-hero: linear-gradient(...);
}
```

### Code et Syntaxe

Variables pour les blocs de code et la coloration syntaxique :

```css
:root {
    /* Blocs de code */
    --la-code-bg: oklch(98% 0.005 ...);
    --la-code-text: oklch(30% 0.02 ...);
    --la-code-header: oklch(95% 0.008 ...);
    --la-code-border: oklch(85% 0.01 ...);
    --la-code-lang: oklch(55% 0.2 142);
    --la-code-lang-glow: oklch(55% 0.2 142 / 50%);
    --la-code-inline-bg: oklch(94% 0.01 ...);
    --la-code-selection: oklch(85% 0.08 ...);
    --la-code-line-highlight: oklch(92% 0.03 ...);
    --la-code-line-number: oklch(60% 0.01 ...);

    /* Coloration syntaxique */
    --la-syntax-comment: oklch(55% 0.02 ...);
    --la-syntax-keyword: oklch(50% 0.2 300);
    --la-syntax-function: oklch(50% 0.18 270);
    --la-syntax-string: oklch(45% 0.15 142);
    --la-syntax-number: oklch(50% 0.18 210);
    --la-syntax-operator: oklch(55% 0.22 25);
    --la-syntax-variable: oklch(55% 0.15 30);
    --la-syntax-class: oklch(55% 0.18 45);
    --la-syntax-property: oklch(50% 0.18 210);
    --la-syntax-tag: oklch(50% 0.2 142);
    --la-syntax-attribute: oklch(55% 0.15 200);
    --la-syntax-regex: oklch(50% 0.15 0);
    --la-syntax-punctuation: oklch(40% 0.02 ...);
}
```

## Composants

### Boutons

```html
<!-- Variantes -->
<button class="la-btn">Default</button>
<button class="la-btn primary">Primary</button>
<button class="la-btn secondary">Secondary</button>
<button class="la-btn success">Success</button>
<button class="la-btn warning">Warning</button>
<button class="la-btn danger">Danger</button>
<button class="la-btn ghost">Ghost</button>

<!-- Tailles -->
<button class="la-btn sm">Small</button>
<button class="la-btn lg">Large</button>

<!-- Avec icône -->
<button class="la-btn primary">
    <span class="la-icon">add</span>
    Créer
</button>
```

### Cards

```html
<div class="la-card">
    <div class="la-card-header">
        <h3 class="la-card-title">Titre</h3>
    </div>
    <div class="la-card-body">
        Contenu...
    </div>
    <div class="la-card-footer">
        <button class="la-btn">Action</button>
    </div>
</div>

<!-- Card interactive -->
<a href="#" class="la-card interactive">
    Cliquable
</a>
```

### Badges

```html
<span class="la-badge">Default</span>
<span class="la-badge primary">Primary</span>
<span class="la-badge success">Publié</span>
<span class="la-badge warning">Brouillon</span>
<span class="la-badge danger">Erreur</span>

<!-- Tailles -->
<span class="la-badge sm">Petit</span>
<span class="la-badge lg">Grand</span>
```

### Alertes

```html
<div class="la-alert">Message par défaut</div>
<div class="la-alert success">Succès !</div>
<div class="la-alert warning">Attention</div>
<div class="la-alert error">Erreur</div>
<div class="la-alert info">Information</div>
```

### Formulaires

```html
<div class="la-form-group">
    <label class="la-label">Label</label>
    <input type="text" class="la-input" placeholder="...">
    <span class="la-form-hint">Aide contextuelle</span>
</div>

<div class="la-form-group">
    <label class="la-label">Textarea</label>
    <textarea class="la-textarea" rows="4"></textarea>
</div>

<div class="la-form-group">
    <label class="la-label">Select</label>
    <select class="la-select">
        <option>Option 1</option>
        <option>Option 2</option>
    </select>
</div>
```

### Icônes

Utilise Google Material Icons via span :

```html
<span class="la-icon">home</span>
<span class="la-icon sm">settings</span>
<span class="la-icon lg">rocket_launch</span>
<span class="la-icon xl">star</span>

<!-- Tailles: xs, sm, (default), lg, xl, xxl -->
```

## Classes Utilitaires

### Flexbox

```html
<div class="la-flex">...</div>
<div class="la-flex la-flex-col">...</div>
<div class="la-flex la-items-center">...</div>
<div class="la-flex la-justify-between">...</div>
<div class="la-flex la-gap-4">...</div>
```

### Espacement

```html
<!-- Margin -->
<div class="la-m-4">margin: 1rem</div>
<div class="la-mt-4">margin-top</div>
<div class="la-mb-8">margin-bottom: 2rem</div>
<div class="la-mx-auto">margin horizontal auto</div>

<!-- Padding -->
<div class="la-p-4">padding: 1rem</div>
<div class="la-px-6">padding horizontal</div>
<div class="la-py-8">padding vertical</div>
```

### Texte

```html
<p class="la-text-sm">Petit texte</p>
<p class="la-text-lg">Grand texte</p>
<p class="la-text-center">Centré</p>
<p class="la-text-muted">Texte atténué</p>
<p class="la-text-secondary">Texte secondaire</p>
<p class="la-font-semibold">Semi-bold</p>
<p class="la-font-bold">Bold</p>
```

### Titres

```html
<h1 class="la-h1">Heading 1</h1>
<h2 class="la-h2">Heading 2</h2>
<h3 class="la-h3">Heading 3</h3>
<h4 class="la-h4">Heading 4</h4>
```

## Theme Switcher

Composant JavaScript pour changer de thème :

```html
<div class="la-theme-switcher">
    <button class="la-theme-trigger">
        <span class="la-icon">palette</span>
        <span class="la-theme-current">Thème</span>
    </button>
    <div class="la-theme-dropdown">
        <button class="la-theme-option" data-theme="dark">Dark</button>
        <button class="la-theme-option" data-theme="cyberpunk">Cyberpunk</button>
        <!-- ... -->
    </div>
</div>

<script>
const ThemeSwitcher = {
    storageKey: 'lunar-theme',

    init() {
        const saved = localStorage.getItem(this.storageKey);
        if (saved) this.setTheme(saved, false);
        // ... event listeners
    },

    setTheme(name, save = true) {
        document.documentElement.setAttribute('data-theme', name);
        if (save) localStorage.setItem(this.storageKey, name);
    }
};

ThemeSwitcher.init();
</script>
```

## Accessibilité

Le framework respecte les préférences utilisateur :

```css
/* Contraste élevé */
@media (prefers-contrast: more) {
    :root {
        --la-text: oklch(5% 0 0);
        --la-border: oklch(50% 0.02 ...);
    }
}

/* Contraste réduit */
@media (prefers-contrast: less) {
    :root {
        --la-text: oklch(35% 0.02 ...);
    }
}

/* Mouvement réduit */
@media (prefers-reduced-motion: reduce) {
    :root {
        --la-transition: none;
        --la-duration-base: 0ms;
    }
}
```

## Import

### Bundle complet

```css
@import 'lunar-aurora/aurora.css';
```

### Import sélectif

```css
@import 'lunar-aurora/reset.css';
@import 'lunar-aurora/tokens.css';
@import 'lunar-aurora/themes.css';
@import 'lunar-aurora/typography.css';
@import 'lunar-aurora/components.css';
@import 'lunar-aurora/utilities.css';
```

### Bundles optimisés

```html
<!-- Blog -->
<link rel="stylesheet" href="/css/lunar-aurora/aurora-blog.css">

<!-- Admin -->
<link rel="stylesheet" href="/css/lunar-aurora/aurora-admin.css">
```

## Créer un thème personnalisé

```css
/* Dans un fichier CSS séparé ou dans themes.css */
[data-theme="mon-theme"] {
    color-scheme: dark;

    /* Teintes */
    --la-hue-primary: 200;
    --la-hue-secondary: 160;
    --la-hue-accent: 45;

    /* Couleurs principales */
    --la-primary: oklch(60% 0.2 var(--la-hue-primary));
    --la-secondary: oklch(55% 0.15 var(--la-hue-secondary));

    /* Surfaces */
    --la-surface-0: oklch(10% 0.02 var(--la-hue-primary));
    --la-surface-1: oklch(14% 0.02 var(--la-hue-primary));
    --la-surface-2: oklch(18% 0.03 var(--la-hue-primary));

    /* Texte */
    --la-text: oklch(95% 0.01 var(--la-hue-primary));
    --la-text-secondary: oklch(75% 0.01 var(--la-hue-primary));

    /* Bordures */
    --la-border: oklch(25% 0.02 var(--la-hue-primary));

    /* Personnalisation visuelle */
    --la-hero-image: url('/images/custom-hero.jpg');
    --la-icon-logo: 'custom_icon';
    --la-font-sans: 'Custom Font', sans-serif;
    --la-radius-factor: 1.5;
    --la-gradient-hero: linear-gradient(135deg, ...);
}
```

## Bonnes pratiques

1. **Utiliser les variables** : Toujours utiliser `var(--la-*)` plutôt que des valeurs hardcodées

2. **Préfixer les classes** : Utiliser `la-` pour éviter les conflits

3. **OKLCH pour les couleurs** : Meilleure perception et manipulation des couleurs

4. **Tester l'accessibilité** : Vérifier le contraste et le support de `prefers-reduced-motion`

5. **Thèmes cohérents** : Définir toutes les variables requises dans un thème personnalisé
