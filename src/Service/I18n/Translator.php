<?php

declare(strict_types=1);

namespace Lunar\Service\I18n;

use Lunar\Service\I18n\Loader\ArrayLoader;
use Lunar\Service\I18n\Loader\LoaderInterface;

/**
 * Service de traduction (i18n).
 *
 * Gère les traductions de l'application avec support pour :
 * - Plusieurs locales
 * - Plusieurs domaines (messages, errors, admin, etc.)
 * - Interpolation de paramètres ({name}, {count})
 * - Pluralisation (ICU-like syntax)
 * - Fallback vers une locale par défaut
 * - Clés imbriquées (user.profile.title)
 *
 * @example
 * ```php
 * $translator = new Translator('fr', [
 *     'fr' => [
 *         'messages' => [
 *             'hello' => 'Bonjour',
 *             'welcome' => 'Bienvenue, {name}!',
 *             'items' => '{0}Aucun article|{1}Un article|[2,*]{count} articles'
 *         ]
 *     ]
 * ]);
 *
 * $translator->trans('messages.hello'); // "Bonjour"
 * $translator->trans('messages.welcome', ['name' => 'Jean']); // "Bienvenue, Jean!"
 * $translator->transChoice('messages.items', 5, ['count' => 5]); // "5 articles"
 * ```
 */
final class Translator implements TranslatorInterface
{
    private string $locale;
    private ?string $fallbackLocale = null;
    /** @var array<string> */
    private array $fallbackLocales = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $catalogues = [];

    private ?LoaderInterface $loader = null;

    /**
     * @param string $locale La locale par défaut
     * @param array<string, array<string, array<string, mixed>>>|null $translations
     *        Traductions initiales [locale => [domain => [key => value]]]
     */
    public function __construct(string $locale, ?array $translations = null)
    {
        $this->locale = $locale;

        if ($translations !== null) {
            $this->catalogues = $translations;
            $this->loader = new ArrayLoader($translations);
        }
    }

    public function trans(string $id, array $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;
        $message = $this->getMessage($id, $locale);

        return $this->interpolate($message, $parameters);
    }

    public function transChoice(string $id, int $count, array $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;
        $message = $this->getMessage($id, $locale);

        // Sélectionner la forme plurielle appropriée
        $message = $this->selectPlural($message, $count);

        // Ajouter count aux paramètres s'il n'est pas déjà présent
        if (!isset($parameters['count'])) {
            $parameters['count'] = $count;
        }

        return $this->interpolate($message, $parameters);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Définit la locale de fallback unique.
     */
    public function setFallbackLocale(string $locale): void
    {
        $this->fallbackLocale = $locale;
        $this->fallbackLocales = [$locale];
    }

    /**
     * Définit la chaîne de fallback.
     *
     * @param array<string> $locales
     */
    public function setFallbackLocales(array $locales): void
    {
        $this->fallbackLocales = $locales;
        $this->fallbackLocale = $locales[0] ?? null;
    }

    /**
     * Définit le loader de traductions.
     */
    public function setLoader(LoaderInterface $loader): void
    {
        $this->loader = $loader;
    }

    /**
     * Récupère un message traduit.
     */
    private function getMessage(string $id, string $locale): string
    {
        // Essayer la locale demandée
        $message = $this->findMessage($id, $locale);
        if ($message !== null) {
            return $message;
        }

        // Essayer les fallbacks
        foreach ($this->fallbackLocales as $fallbackLocale) {
            $message = $this->findMessage($id, $fallbackLocale);
            if ($message !== null) {
                return $message;
            }
        }

        // Retourner la clé si non trouvé
        return $id;
    }

    /**
     * Cherche un message dans le catalogue.
     */
    private function findMessage(string $id, string $locale): ?string
    {
        // Parser l'ID : "domain.key" ou "domain.nested.key"
        $parts = explode('.', $id, 2);
        if (count($parts) < 2) {
            return null;
        }

        [$domain, $key] = $parts;

        // Charger le catalogue si nécessaire
        $this->loadCatalogue($locale, $domain);

        // Récupérer le message
        $catalogue = $this->catalogues[$locale][$domain] ?? [];

        return $this->getNestedValue($catalogue, $key);
    }

    /**
     * Charge un catalogue de traductions.
     */
    private function loadCatalogue(string $locale, string $domain): void
    {
        if (isset($this->catalogues[$locale][$domain])) {
            return;
        }

        if ($this->loader === null) {
            return;
        }

        $translations = $this->loader->load($locale, $domain);
        $this->catalogues[$locale][$domain] = $translations;
    }

    /**
     * Récupère une valeur imbriquée dans un tableau.
     *
     * @param array<string, mixed> $array
     */
    private function getNestedValue(array $array, string $key): ?string
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Interpole les paramètres dans un message.
     *
     * @param array<string, mixed> $parameters
     */
    private function interpolate(string $message, array $parameters): string
    {
        $replacements = [];

        foreach ($parameters as $key => $value) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replacements['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replacements);
    }

    /**
     * Sélectionne la forme plurielle appropriée.
     *
     * Supporte deux formats :
     * 1. Simple : "one apple|many apples" (singulier si count=1, pluriel sinon)
     * 2. ICU-like : "{0}zero|{1}one|[2,5]few|[6,*]many"
     */
    private function selectPlural(string $message, int $count): string
    {
        // Pas de pluralisation si pas de pipe
        if (!str_contains($message, '|')) {
            return $message;
        }

        $parts = explode('|', $message);

        // Format simple : "one|many"
        if (count($parts) === 2 && !str_starts_with($parts[0], '{') && !str_starts_with($parts[0], '[')) {
            return $count === 1 ? $parts[0] : $parts[1];
        }

        // Format ICU-like
        foreach ($parts as $part) {
            if ($this->matchPluralRule($part, $count, $match)) {
                return $match;
            }
        }

        // Fallback : dernière partie
        return $this->cleanPluralPart(end($parts));
    }

    /**
     * Vérifie si une règle plurielle correspond au count.
     */
    private function matchPluralRule(string $part, int $count, ?string &$match): bool
    {
        // Format {n} exact
        if (preg_match('/^\{(\d+)\}(.*)$/', $part, $m)) {
            if ((int) $m[1] === $count) {
                $match = $m[2];
                return true;
            }
            return false;
        }

        // Format [min,max] range
        if (preg_match('/^\[(\d+),(\d+|\*)\](.*)$/', $part, $m)) {
            $min = (int) $m[1];
            $max = $m[2] === '*' ? PHP_INT_MAX : (int) $m[2];

            if ($count >= $min && $count <= $max) {
                $match = $m[3];
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Nettoie une partie plurielle (enlève le préfixe de règle).
     */
    private function cleanPluralPart(string $part): string
    {
        // Enlever {n} ou [n,m] au début
        return preg_replace('/^(\{\d+\}|\[\d+,(\d+|\*)\])/', '', $part);
    }
}
