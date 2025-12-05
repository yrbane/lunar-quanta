<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

/**
 * Générateur de slugs URL-friendly.
 *
 * Transforme un texte en slug :
 * - Conversion en minuscules
 * - Remplacement des accents
 * - Suppression des caractères spéciaux
 * - Remplacement des espaces par des tirets
 *
 * @example
 * ```php
 * $generator = new SlugGenerator();
 *
 * $slug = $generator->generate('Mon Article éco-responsable!');
 * // "mon-article-eco-responsable"
 *
 * // Avec limite de longueur
 * $slug = $generator->generate('Un titre très long...', 20);
 *
 * // Slug unique
 * $existingSlugs = ['mon-article', 'mon-article-1'];
 * $slug = $generator->generateUnique('Mon Article', $existingSlugs);
 * // "mon-article-2"
 *
 * // Méthode statique
 * $slug = SlugGenerator::slugify('Hello World');
 * ```
 */
final class SlugGenerator
{
    /**
     * Table de translittération pour les caractères accentués.
     */
    private const TRANSLITERATION = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'Æ' => 'AE', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd', 'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'th',
        'ÿ' => 'y', 'Œ' => 'OE', 'œ' => 'oe', 'Š' => 'S', 'š' => 's',
        'Ž' => 'Z', 'ž' => 'z', 'ƒ' => 'f',
    ];

    /**
     * Génère un slug à partir d'un texte.
     */
    public function generate(string $text, ?int $maxLength = null): string
    {
        if ($text === '') {
            return '';
        }

        // 1. Translittérer les accents
        $slug = strtr($text, self::TRANSLITERATION);

        // 2. Convertir en minuscules
        $slug = mb_strtolower($slug, 'UTF-8');

        // 3. Gérer les caractères échappés par backslash
        $slug = preg_replace('/\\\\([*_`\[\]])/', '$1', $slug);

        // 4. Remplacer tout ce qui n'est pas alphanumérique par des tirets
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // 5. Supprimer les tirets multiples
        $slug = preg_replace('/-+/', '-', $slug);

        // 6. Supprimer les tirets en début et fin
        $slug = trim($slug, '-');

        // 7. Appliquer la limite de longueur si spécifiée
        if ($maxLength !== null && strlen($slug) > $maxLength) {
            $slug = $this->truncateAtWord($slug, $maxLength);
        }

        return $slug;
    }

    /**
     * Génère un slug unique parmi une liste existante.
     *
     * @param string[] $existingSlugs
     */
    public function generateUnique(string $text, array $existingSlugs): string
    {
        $baseSlug = $this->generate($text);

        if (!in_array($baseSlug, $existingSlugs, true)) {
            return $baseSlug;
        }

        // Trouver le prochain numéro disponible
        $counter = 1;
        while (in_array($baseSlug . '-' . $counter, $existingSlugs, true)) {
            $counter++;
        }

        return $baseSlug . '-' . $counter;
    }

    /**
     * Méthode statique pour une utilisation rapide.
     */
    public static function slugify(string $text, ?int $maxLength = null): string
    {
        return (new self())->generate($text, $maxLength);
    }

    /**
     * Tronque le slug sur une limite de mot.
     */
    private function truncateAtWord(string $slug, int $maxLength): string
    {
        if (strlen($slug) <= $maxLength) {
            return $slug;
        }

        // Trouver le dernier tiret avant la limite
        $truncated = substr($slug, 0, $maxLength);
        $lastDash = strrpos($truncated, '-');

        if ($lastDash !== false && $lastDash > 0) {
            return substr($truncated, 0, $lastDash);
        }

        // Pas de tiret trouvé, tronquer brutalement
        return rtrim($truncated, '-');
    }
}
