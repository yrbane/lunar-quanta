<?php
/**
 * Lunar Quanta Framework - Règle de Validation MinLength.
 *
 * =============================================================================
 * RÈGLE : MIN (Longueur Minimale)
 * =============================================================================
 *
 * Cette règle vérifie qu'une chaîne a au moins N caractères.
 *
 * ```
 * Exemple : min:8 (minimum 8 caractères)
 *
 * VALIDE                    INVALIDE
 *
 * "password123"      ✓      "abc"          ✗ (3 < 8)
 * "12345678"         ✓      "1234567"      ✗ (7 < 8)
 * ""                 ✓      (vide = valide, utilisez 'required' si obligatoire)
 * ```
 *
 * @package Lunar\Service\Validation\Rule
 */
declare(strict_types=1);

namespace Lunar\Service\Validation\Rule;

use Lunar\Service\Validation\ValidationRuleInterface;

/**
 * Règle de validation : longueur minimale.
 */
class MinLengthRule implements ValidationRuleInterface
{
    /**
     * La longueur minimale requise.
     */
    private int $minLength;

    /**
     * Crée une règle de longueur minimale.
     *
     * @param int $minLength La longueur minimale requise
     */
    public function __construct(int $minLength)
    {
        $this->minLength = $minLength;
    }

    /**
     * Valide que la valeur a au moins N caractères.
     *
     * @param mixed $value La valeur à valider
     *
     * @return bool true si la longueur est suffisante
     */
    public function validate(mixed $value): bool
    {
        // Les valeurs vides sont valides (utilisez 'required' si obligatoire)
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        // Utilise mb_strlen pour les caractères UTF-8
        return mb_strlen($value, 'UTF-8') >= $this->minLength;
    }

    /**
     * Retourne le message d'erreur.
     *
     * @return string Le message d'erreur
     */
    public function getMessage(): string
    {
        return "Ce champ doit contenir au moins {$this->minLength} caractères.";
    }

    /**
     * Retourne le nom de la règle.
     *
     * @return string Le nom de la règle
     */
    public function getName(): string
    {
        return 'min';
    }
}
