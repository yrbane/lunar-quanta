<?php

declare(strict_types=1);

namespace Lunar\Service\I18n\Loader;

/**
 * Interface pour les chargeurs de traductions.
 *
 * Un loader est responsable de charger les traductions depuis une source
 * (fichiers PHP, JSON, base de données, etc.).
 */
interface LoaderInterface
{
    /**
     * Charge les traductions pour une locale et un domaine.
     *
     * @param string $locale La locale (ex: "fr", "en", "fr_CA")
     * @param string $domain Le domaine (ex: "messages", "errors")
     * @return array<string, mixed> Les traductions chargées
     */
    public function load(string $locale, string $domain): array;
}
