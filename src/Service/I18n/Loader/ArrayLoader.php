<?php

declare(strict_types=1);

namespace Lunar\Service\I18n\Loader;

/**
 * Loader qui charge les traductions depuis un tableau PHP.
 *
 * Utile pour les tests et les petites applications.
 *
 * @example
 * ```php
 * $loader = new ArrayLoader([
 *     'fr' => [
 *         'messages' => ['hello' => 'Bonjour'],
 *         'errors' => ['not_found' => 'Non trouvé']
 *     ],
 *     'en' => [
 *         'messages' => ['hello' => 'Hello']
 *     ]
 * ]);
 * ```
 */
final class ArrayLoader implements LoaderInterface
{
    /**
     * @param array<string, array<string, array<string, mixed>>> $translations
     *        Structure: [locale => [domain => [key => value]]]
     */
    public function __construct(
        private readonly array $translations = []
    ) {
    }

    public function load(string $locale, string $domain): array
    {
        return $this->translations[$locale][$domain] ?? [];
    }
}
