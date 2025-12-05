<?php
/**
 * Lunar Quanta Framework - Règle de Validation Pattern.
 *
 * =============================================================================
 * RÈGLE : PATTERN (Expression Régulière)
 * =============================================================================
 *
 * Cette règle vérifie qu'une valeur correspond à une expression régulière.
 *
 * QU'EST-CE QU'UNE EXPRESSION RÉGULIÈRE ?
 *
 * Une expression régulière (regex) est un "motif" de recherche qui décrit
 * un ensemble de chaînes de caractères possibles.
 *
 * ```
 * EXEMPLES DE PATTERNS :
 *
 * Pattern                    Signification
 * ──────────────────────────────────────────────────────
 * /^[a-z]+$/i                Lettres uniquement (insensible à la casse)
 * /^\d{5}$/                  Exactement 5 chiffres (code postal)
 * /^[a-z0-9_]+$/i            Lettres, chiffres ou underscore
 * /^\+?\d{10,15}$/           Numéro de téléphone international
 * /^#[0-9A-Fa-f]{6}$/        Code couleur hexadécimal (#RRGGBB)
 * ```
 *
 * ```
 * Exemple : pattern:/^[a-z0-9_]+$/i
 *
 * VALIDE                    INVALIDE
 *
 * "john_doe"          ✓      "john doe"        ✗ (espace)
 * "user123"           ✓      "user@123"        ✗ (caractère @)
 * "ABC_123"           ✓      "user-name"       ✗ (tiret)
 * ```
 *
 * @package Lunar\Service\Validation\Rule
 */
declare(strict_types=1);

namespace Lunar\Service\Validation\Rule;

use Lunar\Service\Validation\ValidationRuleInterface;

/**
 * Règle de validation : expression régulière.
 */
class PatternRule implements ValidationRuleInterface
{
    /**
     * L'expression régulière à tester.
     */
    private string $pattern;

    /**
     * Crée une règle de pattern.
     *
     * @param string $pattern L'expression régulière (avec délimiteurs, ex: /^[a-z]+$/)
     */
    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * Valide que la valeur correspond au pattern.
     *
     * @param mixed $value La valeur à valider
     *
     * @return bool true si la valeur correspond au pattern
     */
    public function validate(mixed $value): bool
    {
        // Les valeurs vides sont valides
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        // Vérifie que le pattern compile
        $result = @preg_match($this->pattern, $value);

        if ($result === false) {
            // Pattern invalide → on considère la validation échouée
            return false;
        }

        return $result === 1;
    }

    /**
     * Retourne le message d'erreur.
     *
     * @return string Le message d'erreur
     */
    public function getMessage(): string
    {
        return 'Ce champ ne correspond pas au format attendu.';
    }

    /**
     * Retourne le nom de la règle.
     *
     * @return string Le nom de la règle
     */
    public function getName(): string
    {
        return 'pattern';
    }
}
