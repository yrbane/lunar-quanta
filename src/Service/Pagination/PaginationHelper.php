<?php

declare(strict_types=1);

namespace Lunar\Service\Pagination;

/**
 * Helper pour la génération de pagination.
 *
 * Génère les liens de pagination avec fenêtre glissante.
 *
 * @example
 * ```php
 * $pagination = new PaginationHelper(100, 10, 5);
 * echo $pagination->render('/blog/page/');
 * ```
 */
final class PaginationHelper
{
    private int $total;
    private int $perPage;
    private int $currentPage;
    private int $window;
    private int $totalPages;

    /**
     * @param int $total Nombre total d'éléments
     * @param int $perPage Nombre d'éléments par page
     * @param int $currentPage Page courante (1-indexed)
     * @param int $window Nombre de pages de chaque côté de la page courante
     */
    public function __construct(int $total, int $perPage, int $currentPage, int $window = 2)
    {
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->totalPages = (int) ceil($this->total / $this->perPage);
        $this->currentPage = max(1, min($currentPage, $this->totalPages ?: 1));
        $this->window = max(1, $window);
    }

    /**
     * Retourne les métadonnées de pagination.
     */
    public function getMeta(): array
    {
        return [
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'total_pages' => $this->totalPages,
            'has_previous' => $this->hasPrevious(),
            'has_next' => $this->hasNext(),
            'from' => $this->getFrom(),
            'to' => $this->getTo(),
        ];
    }

    /**
     * Vérifie s'il y a une page précédente.
     */
    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Vérifie s'il y a une page suivante.
     */
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Retourne le numéro de la page précédente.
     */
    public function getPreviousPage(): ?int
    {
        return $this->hasPrevious() ? $this->currentPage - 1 : null;
    }

    /**
     * Retourne le numéro de la page suivante.
     */
    public function getNextPage(): ?int
    {
        return $this->hasNext() ? $this->currentPage + 1 : null;
    }

    /**
     * Retourne l'index du premier élément de la page.
     */
    public function getFrom(): int
    {
        if ($this->total === 0) {
            return 0;
        }
        return (($this->currentPage - 1) * $this->perPage) + 1;
    }

    /**
     * Retourne l'index du dernier élément de la page.
     */
    public function getTo(): int
    {
        return min($this->currentPage * $this->perPage, $this->total);
    }

    /**
     * Retourne les numéros de page à afficher.
     */
    public function getPages(): array
    {
        if ($this->totalPages <= 1) {
            return [];
        }

        $pages = [];

        // Toujours inclure la première page
        $pages[] = 1;

        // Calculer la fenêtre
        $start = max(2, $this->currentPage - $this->window);
        $end = min($this->totalPages - 1, $this->currentPage + $this->window);

        // Ajouter ellipsis si nécessaire
        if ($start > 2) {
            $pages[] = '...';
        }

        // Ajouter les pages de la fenêtre
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        // Ajouter ellipsis si nécessaire
        if ($end < $this->totalPages - 1) {
            $pages[] = '...';
        }

        // Toujours inclure la dernière page
        if ($this->totalPages > 1) {
            $pages[] = $this->totalPages;
        }

        return $pages;
    }

    /**
     * Génère le HTML de la pagination.
     */
    public function render(string $baseUrl, string $class = 'pagination'): string
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<nav class="' . $this->escape($class) . '" aria-label="Pagination">';
        $html .= '<ul>';

        // Bouton précédent
        if ($this->hasPrevious()) {
            $html .= '<li class="pagination-prev">';
            $html .= '<a href="' . $this->buildUrl($baseUrl, $this->getPreviousPage()) . '">';
            $html .= '&laquo; Précédent</a></li>';
        } else {
            $html .= '<li class="pagination-prev disabled"><span>&laquo; Précédent</span></li>';
        }

        // Pages
        foreach ($this->getPages() as $page) {
            if ($page === '...') {
                $html .= '<li class="pagination-ellipsis"><span>...</span></li>';
            } elseif ($page === $this->currentPage) {
                $html .= '<li class="pagination-current" aria-current="page">';
                $html .= '<span>' . $page . '</span></li>';
            } else {
                $html .= '<li><a href="' . $this->buildUrl($baseUrl, $page) . '">' . $page . '</a></li>';
            }
        }

        // Bouton suivant
        if ($this->hasNext()) {
            $html .= '<li class="pagination-next">';
            $html .= '<a href="' . $this->buildUrl($baseUrl, $this->getNextPage()) . '">';
            $html .= 'Suivant &raquo;</a></li>';
        } else {
            $html .= '<li class="pagination-next disabled"><span>Suivant &raquo;</span></li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        // Informations
        $html .= '<p class="pagination-info">';
        $html .= "Affichage {$this->getFrom()} - {$this->getTo()} sur {$this->total} résultats";
        $html .= '</p>';

        return $html;
    }

    /**
     * Génère une pagination simple (prev/next seulement).
     */
    public function renderSimple(string $baseUrl, string $class = 'pagination-simple'): string
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<nav class="' . $this->escape($class) . '">';

        if ($this->hasPrevious()) {
            $html .= '<a href="' . $this->buildUrl($baseUrl, $this->getPreviousPage()) . '" class="prev">';
            $html .= '&laquo; Précédent</a>';
        }

        $html .= '<span class="page-info">Page ' . $this->currentPage . ' sur ' . $this->totalPages . '</span>';

        if ($this->hasNext()) {
            $html .= '<a href="' . $this->buildUrl($baseUrl, $this->getNextPage()) . '" class="next">';
            $html .= 'Suivant &raquo;</a>';
        }

        $html .= '</nav>';

        return $html;
    }

    /**
     * Construit l'URL pour une page.
     */
    private function buildUrl(string $baseUrl, int $page): string
    {
        // Si la baseUrl contient {page}, remplacer
        if (str_contains($baseUrl, '{page}')) {
            return str_replace('{page}', (string) $page, $baseUrl);
        }

        // Sinon, ajouter le numéro de page à la fin
        $baseUrl = rtrim($baseUrl, '/');

        if ($page === 1) {
            // Pour la page 1, retourner l'URL de base
            return $baseUrl . '/';
        }

        return $baseUrl . '/' . $page . '/';
    }

    /**
     * Échappe le contenu HTML.
     */
    private function escape(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
