<?php
/**
 * Lunar Quanta Framework - Règle de Validation MaxLength.
 *
 * =============================================================================
 * RÈGLE : MAX (Longueur Maximale)
 * =============================================================================
 *
 * Cette règle vérifie qu'une chaîne ne dépasse pas N caractères.
 *
 * ```
 * Exemple : max:10 (maximum 10 caractères)
 *
 * VALIDE                    INVALIDE
 *
 * "hello"             ✓      "hello world!"    ✗ (12 > 10)
 * "1234567890"        ✓      "12345678901"     ✗ (11 > 10)
 * ""                  ✓
 * ```
 *
 * UTILITÉ :
 * - Éviter les attaques par déni de service (données trop volumineuses)
 * - Respecter les limites de la base de données (VARCHAR(255) par exemple)
 * - Améliorer l'UX (noms d'utilisateur raisonnables)
 *
 * @package Lunar\Service\Validation\Rule
 */
declare(strict_types=1);

namespace Lunar\Service\Validation\Rule;

use Lunar\Service\Validation\ValidationRuleInterface;

/**
 * Règle de validation : longueur maximale.
 */
class MaxLengthRule implements ValidationRuleInterface
{
    /**
     * La longueur maximale autorisée.
     */
    private int $maxLength;

    /**
     * Crée une règle de longueur maximale.
     *
     * @param int $maxLength La longueur maximale autorisée
     */
    public function __construct(int $maxLength)
    {
        $this->maxLength = $maxLength;
    }

    /**
     * Valide que la valeur ne dépasse pas N caractères.
     *
     * @param mixed $value La valeur à valider
     *
     * @return bool true si la longueur est acceptable
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

        // Utilise mb_strlen pour les caractères UTF-8
        return mb_strlen($value, 'UTF-8') <= $this->maxLength;
    }

    /**
     * Retourne le message d'erreur.
     *
     * @return string Le message d'erreur
     */
    public function getMessage(): string
    {
        return "Ce champ ne doit pas dépasser {$this->maxLength} caractères.";
    }

    /**
     * Retourne le nom de la règle.
     *
     * @return string Le nom de la règle
     */
    public function getName(): string
    {
        return 'max';
    }
}
