<?php

declare(strict_types=1);

namespace Lunar\Service\I18n\Loader;

/**
 * Loader qui charge les traductions depuis des fichiers PHP.
 *
 * Convention de nommage des fichiers : {domain}.{locale}.php
 * Exemple : messages.fr.php, errors.en.php
 *
 * Le fichier doit retourner un tableau associatif :
 * ```php
 * <?php
 * return [
 *     'hello' => 'Bonjour',
 *     'goodbye' => 'Au revoir',
 *     'user' => [
 *         'profile' => 'Mon profil'
 *     ]
 * ];
 * ```
 */
final class PhpFileLoader implements LoaderInterface
{
    public function __construct(
        private readonly string $directory
    ) {
    }

    public function load(string $locale, string $domain): array
    {
        $filename = sprintf('%s/%s.%s.php', $this->directory, $domain, $locale);

        if (!file_exists($filename)) {
            return [];
        }

        $translations = require $filename;

        if (!is_array($translations)) {
            throw new \RuntimeException(
                "Translation file {$filename} must return an array"
            );
        }

        return $translations;
    }
}
