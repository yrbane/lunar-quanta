<?php
/**
 * Lunar Quanta Framework - Règle de Validation Numeric.
 *
 * =============================================================================
 * RÈGLE : NUMERIC
 * =============================================================================
 *
 * Cette règle vérifie qu'une valeur est numérique (entier ou décimal).
 *
 * ```
 * VALIDE                    INVALIDE
 *
 * 42               ✓        "abc"           ✗
 * 3.14             ✓        "12abc"         ✗
 * "123"            ✓        "1.2.3"         ✗
 * "-5"             ✓        ""              ✓ (vide = valide)
 * "0"              ✓
 * ```
 *
 * UTILITÉ :
 * - Valider les champs de prix, quantités, âges
 * - Éviter les injections SQL dans les champs numériques
 * - S'assurer que les calculs seront possibles
 *
 * @package Lunar\Service\Validation\Rule
 */
declare(strict_types=1);

namespace Lunar\Service\Validation\Rule;

use Lunar\Service\Validation\ValidationRuleInterface;

/**
 * Règle de validation : valeur numérique.
 */
class NumericRule implements ValidationRuleInterface
{
    /**
     * Valide que la valeur est numérique.
     *
     * @param mixed $value La valeur à valider
     *
     * @return bool true si la valeur est numérique
     */
    public function validate(mixed $value): bool
    {
        // Les valeurs vides sont valides
        if ($value === null || $value === '') {
            return true;
        }

        // is_numeric() accepte les entiers, flottants et strings numériques
        return is_numeric($value);
    }

    /**
     * Retourne le message d'erreur.
     *
     * @return string Le message d'erreur
     */
    public function getMessage(): string
    {
        return 'Ce champ doit être un nombre.';
    }

    /**
     * Retourne le nom de la règle.
     *
     * @return string Le nom de la règle
     */
    public function getName(): string
    {
        return 'numeric';
    }
}
