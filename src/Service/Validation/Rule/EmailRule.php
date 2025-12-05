<?php
/**
 * Lunar Quanta Framework - Règle de Validation Email.
 *
 * =============================================================================
 * RÈGLE : EMAIL
 * =============================================================================
 *
 * Cette règle vérifie qu'une valeur est une adresse email valide.
 * Elle utilise la fonction PHP filter_var() avec le filtre FILTER_VALIDATE_EMAIL.
 *
 * ```
 * VALIDE                         INVALIDE
 *
 * "john@example.com"      ✓      "not-an-email"        ✗
 * "user.name@domain.fr"   ✓      "@domain.com"         ✗
 * "a@b.co"                ✓      "user@"               ✗
 *                                "user@domain"         ✗
 *                                ""                    ✓ (vide = valide, utilisez 'required' pour obliger)
 * ```
 *
 * NOTE : Une valeur vide est considérée valide. Utilisez 'required' si le champ
 * est obligatoire.
 *
 * @package Lunar\Service\Validation\Rule
 */
declare(strict_types=1);

namespace Lunar\Service\Validation\Rule;

use Lunar\Service\Validation\ValidationRuleInterface;

/**
 * Règle de validation : format email.
 */
class EmailRule implements ValidationRuleInterface
{
    /**
     * Valide que la valeur est un email valide.
     *
     * @param mixed $value La valeur à valider
     *
     * @return bool true si c'est un email valide ou une valeur vide
     */
    public function validate(mixed $value): bool
    {
        // Les valeurs vides sont considérées valides
        // (utilisez 'required' si le champ est obligatoire)
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Retourne le message d'erreur.
     *
     * @return string Le message d'erreur
     */
    public function getMessage(): string
    {
        return 'L\'adresse email n\'est pas valide.';
    }

    /**
     * Retourne le nom de la règle.
     *
     * @return string Le nom de la règle
     */
    public function getName(): string
    {
        return 'email';
    }
}
