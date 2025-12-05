<?php
/**
 * Lunar Quanta Framework - Résultat de Validation.
 *
 * =============================================================================
 * QU'EST-CE QU'UN RÉSULTAT DE VALIDATION ?
 * =============================================================================
 *
 * Après avoir validé des données, on a besoin de savoir :
 * - Est-ce que TOUT est valide ?
 * - Quels champs ont des erreurs ?
 * - Quels sont les messages d'erreur pour chaque champ ?
 *
 * ```
 * DONNÉES                              RÉSULTAT DE VALIDATION
 *
 * [                                    ValidationResult {
 *   'email' => 'invalid',                isValid: false
 *   'password' => 'abc',                 errors: [
 *   'name' => 'John'                       'email' => ['L\'email n\'est pas valide'],
 * ]                                        'password' => ['Minimum 8 caractères']
 *                                        ]
 *                                      }
 * ```
 *
 * @package    Lunar\Service\Validation
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Validation;

/**
 * Résultat d'une opération de validation.
 *
 * Cette classe encapsule le résultat de la validation de plusieurs champs,
 * permettant d'accéder facilement aux erreurs par champ.
 *
 * @package Lunar\Service\Validation
 */
class ValidationResult
{
    /**
     * Les erreurs de validation par champ.
     *
     * Structure : ['champ' => ['erreur1', 'erreur2', ...], ...]
     *
     * @var array<string, array<int, string>>
     */
    private array $errors = [];

    /**
     * Ajoute une erreur pour un champ.
     *
     * =========================================================================
     * ACCUMULATION DES ERREURS
     * =========================================================================
     *
     * Un champ peut avoir PLUSIEURS erreurs. Par exemple, un mot de passe
     * peut être "trop court" ET "ne pas contenir de chiffres".
     *
     * ```php
     * $result->addError('password', 'Minimum 8 caractères');
     * $result->addError('password', 'Doit contenir un chiffre');
     *
     * // getErrors('password') retourne :
     * // ['Minimum 8 caractères', 'Doit contenir un chiffre']
     * ```
     *
     * @param string $field   Le nom du champ
     * @param string $message Le message d'erreur
     */
    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Vérifie si la validation a réussi (aucune erreur).
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * ```php
     * $result = $validator->validate($data);
     *
     * if ($result->isValid()) {
     *     // Tout est OK, on peut traiter les données
     *     $this->userService->create($data);
     * } else {
     *     // Il y a des erreurs, on les affiche
     *     foreach ($result->getAllErrors() as $field => $errors) {
     *         echo "$field : " . implode(', ', $errors);
     *     }
     * }
     * ```
     *
     * @return bool true si aucune erreur, false sinon
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Vérifie si la validation a échoué (au moins une erreur).
     *
     * C'est l'inverse de isValid(), pour une syntaxe plus naturelle.
     *
     * ```php
     * if ($result->hasErrors()) {
     *     return $this->redirectWithErrors($result);
     * }
     * ```
     *
     * @return bool true si au moins une erreur, false sinon
     */
    public function hasErrors(): bool
    {
        return !$this->isValid();
    }

    /**
     * Vérifie si un champ spécifique a des erreurs.
     *
     * ```php
     * if ($result->hasError('email')) {
     *     echo '<span class="error">Email invalide</span>';
     * }
     * ```
     *
     * @param string $field Le nom du champ
     *
     * @return bool true si le champ a au moins une erreur
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Récupère les erreurs d'un champ spécifique.
     *
     * ```php
     * $errors = $result->getErrors('password');
     * // ['Minimum 8 caractères', 'Doit contenir un chiffre']
     *
     * // Afficher la première erreur seulement
     * echo $result->getFirstError('password');
     * ```
     *
     * @param string $field Le nom du champ
     *
     * @return array<int, string> Liste des messages d'erreur (vide si pas d'erreur)
     */
    public function getErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Récupère la première erreur d'un champ.
     *
     * Utile pour n'afficher qu'une seule erreur par champ (UX plus simple).
     *
     * ```php
     * <input type="email" name="email">
     * <span class="error"><?= $result->getFirstError('email') ?></span>
     * ```
     *
     * @param string $field Le nom du champ
     *
     * @return string|null Le premier message d'erreur ou null
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Récupère toutes les erreurs de tous les champs.
     *
     * ```php
     * $allErrors = $result->getAllErrors();
     * // [
     * //     'email' => ['Email invalide'],
     * //     'password' => ['Trop court', 'Manque un chiffre']
     * // ]
     * ```
     *
     * @return array<string, array<int, string>> Toutes les erreurs par champ
     */
    public function getAllErrors(): array
    {
        return $this->errors;
    }

    /**
     * Récupère tous les champs en erreur.
     *
     * ```php
     * $fieldsWithErrors = $result->getErrorFields();
     * // ['email', 'password']
     * ```
     *
     * @return array<int, string> Liste des noms de champs en erreur
     */
    public function getErrorFields(): array
    {
        return array_keys($this->errors);
    }

    /**
     * Compte le nombre total d'erreurs.
     *
     * ```php
     * echo "Il y a " . $result->count() . " erreurs.";
     * ```
     *
     * @return int Le nombre total d'erreurs
     */
    public function count(): int
    {
        $count = 0;

        foreach ($this->errors as $fieldErrors) {
            $count += count($fieldErrors);
        }

        return $count;
    }

    /**
     * Fusionne les erreurs d'un autre ValidationResult.
     *
     * Utile pour combiner les validations de plusieurs formulaires
     * ou de plusieurs étapes de validation.
     *
     * ```php
     * $result1 = $validator->validate($userData);
     * $result2 = $validator->validate($addressData);
     *
     * $result1->merge($result2);
     * // $result1 contient maintenant toutes les erreurs
     * ```
     *
     * @param ValidationResult $other L'autre résultat à fusionner
     */
    public function merge(ValidationResult $other): void
    {
        foreach ($other->getAllErrors() as $field => $errors) {
            foreach ($errors as $error) {
                $this->addError($field, $error);
            }
        }
    }

    /**
     * Convertit le résultat en tableau (pour JSON ou template).
     *
     * ```php
     * // Pour une API JSON
     * return new JsonResponse([
     *     'success' => false,
     *     'errors' => $result->toArray()
     * ]);
     * ```
     *
     * @return array{valid: bool, errors: array<string, array<int, string>>}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'errors' => $this->errors,
        ];
    }
}
