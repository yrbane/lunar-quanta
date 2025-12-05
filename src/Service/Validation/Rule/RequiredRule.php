<?php
/**
 * Lunar Quanta Framework - Règle de Validation Required.
 *
 * =============================================================================
 * RÈGLE : REQUIRED (Obligatoire)
 * =============================================================================
 *
 * Cette règle vérifie qu'un champ n'est pas vide.
 *
 * ```
 * VALIDE                    INVALIDE
 *
 * "John"          ✓         ""              ✗
 * "0"             ✓         "   "           ✗ (espaces seulement)
 * 0               ✓         null            ✗
 * false           ✓         []              ✗ (tableau vide)
 * [1, 2]          ✓
 * ```
 *
 * @package Lunar\Service\Validation\Rule
 */
declare(strict_types=1);

namespace Lunar\Service\Validation\Rule;

use Lunar\Service\Validation\ValidationRuleInterface;

/**
 * Règle de validation : champ obligatoire.
 */
class RequiredRule implements ValidationRuleInterface
{
    /**
     * Valide que la valeur n'est pas vide.
     *
     * @param mixed $value La valeur à valider
     *
     * @return bool true si la valeur est présente et non vide
     */
    public function validate(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            // Une chaîne vide ou ne contenant que des espaces est invalide
            return trim($value) !== '';
        }

        if (is_array($value)) {
            // Un tableau vide est invalide
            return !empty($value);
        }

        // Pour les autres types (int, bool, object), la valeur est considérée présente
        return true;
    }

    /**
     * Retourne le message d'erreur.
     *
     * @return string Le message d'erreur
     */
    public function getMessage(): string
    {
        return 'Ce champ est requis.';
    }

    /**
     * Retourne le nom de la règle.
     *
     * @return string Le nom de la règle
     */
    public function getName(): string
    {
        return 'required';
    }
}
