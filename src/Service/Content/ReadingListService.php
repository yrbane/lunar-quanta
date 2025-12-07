<?php

declare(strict_types=1);

namespace Lunar\Service\Content;

/**
 * Service pour gérer les listes de lecture (bookmarks).
 *
 * Génère le JavaScript nécessaire pour sauvegarder
 * les articles en favoris dans le localStorage.
 *
 * @example
 * ```php
 * $reading = new ReadingListService();
 * echo $reading->generateScript();
 * echo $reading->generateCss();
 * ```
 */
final class ReadingListService
{
    private string $storageKey = 'lunar-reading-list';
    private int $maxItems = 50;
    private string $buttonClass = 'la-bookmark-btn';
    private string $activeClass = 'is-bookmarked';

    /**
     * Définit la clé de stockage.
     */
    public function setStorageKey(string $key): self
    {
        $this->storageKey = $key;
        return $this;
    }

    /**
     * Définit le nombre maximum d'articles sauvegardés.
     */
    public function setMaxItems(int $max): self
    {
        $this->maxItems = max(1, $max);
        return $this;
    }

    /**
     * Définit la classe CSS du bouton.
     */
    public function setButtonClass(string $class): self
    {
        $this->buttonClass = $class;
        return $this;
    }

    /**
     * Définit la classe CSS quand l'article est sauvegardé.
     */
    public function setActiveClass(string $class): self
    {
        $this->activeClass = $class;
        return $this;
    }

    /**
     * Génère le bouton de favori.
     */
    public function generateButton(string $postId, string $title, string $url): string
    {
        $escapedId = htmlspecialchars($postId);
        $escapedTitle = htmlspecialchars($title);
        $escapedUrl = htmlspecialchars($url);

        return <<<HTML
<button class="{$this->buttonClass}"
        data-post-id="{$escapedId}"
        data-post-title="{$escapedTitle}"
        data-post-url="{$escapedUrl}"
        title="Ajouter à ma liste de lecture"
        aria-label="Ajouter à ma liste de lecture">
    <span class="la-icon sm">bookmark</span>
    <span class="bookmark-text">Favoris</span>
</button>
HTML;
    }

    /**
     * Génère le widget de liste de lecture.
     */
    public function generateWidget(): string
    {
        return <<<HTML
<div class="la-reading-list-widget" id="readingListWidget">
    <button class="la-reading-list-toggle" id="readingListToggle">
        <span class="la-icon">bookmark</span>
        <span class="la-reading-list-count" id="readingListCount">0</span>
    </button>
    <div class="la-reading-list-dropdown" id="readingListDropdown">
        <div class="la-reading-list-header">
            <h4>Ma liste de lecture</h4>
            <button class="la-reading-list-clear" id="readingListClear" title="Vider la liste">
                <span class="la-icon sm">delete</span>
            </button>
        </div>
        <div class="la-reading-list-items" id="readingListItems">
            <p class="la-reading-list-empty">Aucun article sauvegardé</p>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * Génère le JavaScript pour la liste de lecture.
     */
    public function generateScript(): string
    {
        $storageKey = $this->storageKey;
        $maxItems = $this->maxItems;
        $buttonClass = $this->buttonClass;
        $activeClass = $this->activeClass;

        return <<<JS
const ReadingList = {
    storageKey: '{$storageKey}',
    maxItems: {$maxItems},
    buttonClass: '{$buttonClass}',
    activeClass: '{$activeClass}',

    init() {
        this.items = this.load();
        this.bindEvents();
        this.updateUI();
    },

    load() {
        const data = localStorage.getItem(this.storageKey);
        return data ? JSON.parse(data) : [];
    },

    save() {
        localStorage.setItem(this.storageKey, JSON.stringify(this.items));
    },

    add(item) {
        // Vérifier si déjà présent
        if (this.items.some(i => i.id === item.id)) {
            return false;
        }

        // Ajouter au début
        this.items.unshift({
            id: item.id,
            title: item.title,
            url: item.url,
            addedAt: new Date().toISOString()
        });

        // Limiter le nombre d'éléments
        if (this.items.length > this.maxItems) {
            this.items = this.items.slice(0, this.maxItems);
        }

        this.save();
        this.updateUI();
        return true;
    },

    remove(id) {
        this.items = this.items.filter(item => item.id !== id);
        this.save();
        this.updateUI();
    },

    has(id) {
        return this.items.some(item => item.id === id);
    },

    clear() {
        this.items = [];
        this.save();
        this.updateUI();
    },

    bindEvents() {
        // Boutons de favoris
        document.querySelectorAll('.' + this.buttonClass).forEach(btn => {
            const postId = btn.dataset.postId;

            // État initial
            if (this.has(postId)) {
                btn.classList.add(this.activeClass);
                btn.querySelector('.bookmark-text')?.textContent = 'Sauvegardé';
            }

            btn.addEventListener('click', () => {
                if (this.has(postId)) {
                    this.remove(postId);
                    btn.classList.remove(this.activeClass);
                    btn.querySelector('.bookmark-text')?.textContent = 'Favoris';
                } else {
                    this.add({
                        id: postId,
                        title: btn.dataset.postTitle,
                        url: btn.dataset.postUrl
                    });
                    btn.classList.add(this.activeClass);
                    btn.querySelector('.bookmark-text')?.textContent = 'Sauvegardé';
                }
            });
        });

        // Widget toggle
        const toggle = document.getElementById('readingListToggle');
        const dropdown = document.getElementById('readingListDropdown');

        toggle?.addEventListener('click', () => {
            dropdown?.classList.toggle('is-open');
        });

        // Clear button
        document.getElementById('readingListClear')?.addEventListener('click', () => {
            if (confirm('Vider la liste de lecture ?')) {
                this.clear();
            }
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#readingListWidget')) {
                dropdown?.classList.remove('is-open');
            }
        });
    },

    updateUI() {
        // Update count
        const countEl = document.getElementById('readingListCount');
        if (countEl) {
            countEl.textContent = this.items.length;
            countEl.style.display = this.items.length > 0 ? 'flex' : 'none';
        }

        // Update dropdown list
        const listEl = document.getElementById('readingListItems');
        if (listEl) {
            if (this.items.length === 0) {
                listEl.innerHTML = '<p class="la-reading-list-empty">Aucun article sauvegardé</p>';
            } else {
                listEl.innerHTML = this.items.map(item => `
                    <div class="la-reading-list-item">
                        <a href="\${item.url}">\${item.title}</a>
                        <button class="la-reading-list-remove" data-id="\${item.id}" title="Retirer">
                            <span class="la-icon xs">close</span>
                        </button>
                    </div>
                `).join('');

                // Bind remove buttons
                listEl.querySelectorAll('.la-reading-list-remove').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.remove(btn.dataset.id);
                    });
                });
            }
        }

        // Update bookmark buttons
        document.querySelectorAll('.' + this.buttonClass).forEach(btn => {
            const postId = btn.dataset.postId;
            if (this.has(postId)) {
                btn.classList.add(this.activeClass);
                btn.querySelector('.bookmark-text')?.textContent = 'Sauvegardé';
            } else {
                btn.classList.remove(this.activeClass);
                btn.querySelector('.bookmark-text')?.textContent = 'Favoris';
            }
        });
    },

    getAll() {
        return [...this.items];
    },

    export() {
        return JSON.stringify(this.items, null, 2);
    },

    import(data) {
        try {
            const items = JSON.parse(data);
            if (Array.isArray(items)) {
                this.items = items.slice(0, this.maxItems);
                this.save();
                this.updateUI();
                return true;
            }
        } catch (e) {
            console.error('Failed to import reading list:', e);
        }
        return false;
    }
};

document.addEventListener('DOMContentLoaded', () => ReadingList.init());
JS;
    }

    /**
     * Génère le CSS pour la liste de lecture.
     */
    public function generateCss(): string
    {
        return <<<CSS
.{$this->buttonClass} {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid var(--la-border, #e5e7eb);
    border-radius: 0.375rem;
    background: transparent;
    cursor: pointer;
    transition: all 0.2s;
}

.{$this->buttonClass}:hover {
    background: var(--la-surface, #f9fafb);
}

.{$this->buttonClass}.{$this->activeClass} {
    background: var(--la-primary, #3b82f6);
    border-color: var(--la-primary, #3b82f6);
    color: white;
}

.la-reading-list-widget {
    position: relative;
}

.la-reading-list-toggle {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem;
    background: transparent;
    border: none;
    cursor: pointer;
    border-radius: 0.375rem;
}

.la-reading-list-toggle:hover {
    background: var(--la-surface, #f9fafb);
}

.la-reading-list-count {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    background: var(--la-primary, #3b82f6);
    color: white;
    border-radius: 9999px;
}

.la-reading-list-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 320px;
    max-height: 400px;
    background: var(--la-bg, white);
    border: 1px solid var(--la-border, #e5e7eb);
    border-radius: 0.5rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s;
    z-index: 1000;
    overflow: hidden;
}

.la-reading-list-dropdown.is-open {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.la-reading-list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid var(--la-border, #e5e7eb);
}

.la-reading-list-header h4 {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
}

.la-reading-list-clear {
    padding: 0.25rem;
    background: transparent;
    border: none;
    cursor: pointer;
    opacity: 0.5;
}

.la-reading-list-clear:hover {
    opacity: 1;
    color: var(--la-error, #ef4444);
}

.la-reading-list-items {
    max-height: 300px;
    overflow-y: auto;
}

.la-reading-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--la-border, #e5e7eb);
}

.la-reading-list-item:last-child {
    border-bottom: none;
}

.la-reading-list-item a {
    flex: 1;
    font-size: 0.875rem;
    color: var(--la-text, #111827);
    text-decoration: none;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.la-reading-list-item a:hover {
    color: var(--la-primary, #3b82f6);
}

.la-reading-list-remove {
    padding: 0.25rem;
    background: transparent;
    border: none;
    cursor: pointer;
    opacity: 0.5;
}

.la-reading-list-remove:hover {
    opacity: 1;
    color: var(--la-error, #ef4444);
}

.la-reading-list-empty {
    padding: 2rem;
    text-align: center;
    color: var(--la-text-muted, #6b7280);
    font-size: 0.875rem;
}
CSS;
    }
}
