<?php

declare(strict_types=1);

namespace Lunar\Service\Media;

/**
 * Interface pour les fournisseurs d'images.
 *
 * Définit le contrat commun pour Pexels, DALL-E, Imagen, etc.
 */
interface ImageProviderInterface
{
    /**
     * Recherche des images par mots-clés.
     *
     * @param int $limit Nombre max de résultats
     * @return ImageResult[]
     */
    public function search(string $query, int $limit = 10): array;

    /**
     * Génère une image à partir d'un prompt (pour les IA génératives).
     *
     * @return ImageResult|null
     */
    public function generate(string $prompt): ?ImageResult;

    /**
     * Retourne le nom du fournisseur.
     */
    public function getName(): string;

    /**
     * Vérifie si le fournisseur supporte la génération d'images.
     */
    public function supportsGeneration(): bool;
}
