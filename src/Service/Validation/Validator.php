<?php
/**
 * Lunar Quanta Framework - Validateur de Données.
 *
 * =============================================================================
 * QU'EST-CE QUE LA VALIDATION ?
 * =============================================================================
 *
 * La VALIDATION est le processus de vérification que les données reçues
 * respectent certaines règles avant de les traiter.
 *
 * POURQUOI VALIDER ?
 *
 * ```
 * DONNÉES NON VALIDÉES                 CONSÉQUENCES POSSIBLES
 *
 * email: "pas-un-email"        →  Email non livré, user frustré
 * age: -5                      →  Logique métier cassée
 * password: "abc"              →  Compte facilement piratable
 * sql: "'; DROP TABLE users;"  →  Base de données effacée !
 * ```
 *
 * LA RÈGLE D'OR : Ne JAMAIS faire confiance aux données utilisateur !
 *
 * =============================================================================
 * ARCHITECTURE
 * =============================================================================
 *
 * ```
 *                                    ┌────────────────┐
 *                                    │   Validator    │
 *                                    └───────┬────────┘
 *                                            │
 *           ┌────────────────────────────────┼────────────────────────────────┐
 *           │                                │                                │
 *           ▼                                ▼                                ▼
 *    ┌─────────────┐                  ┌─────────────┐                 ┌─────────────┐
 *    │ RequiredRule│                  │  EmailRule  │                 │ MinLenRule  │
 *    └─────────────┘                  └─────────────┘                 └─────────────┘
 *
 *                          ┌──────────────────────────┐
 *                          │   ValidationResult       │
 *                          │  - isValid()             │
 *                          │  - getErrors($field)     │
 *                          │  - getAllErrors()        │
 *                          └──────────────────────────┘
 * ```
 *
 * @package    Lunar\Service\Validation
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Validation;

use Lunar\Service\Validation\Rule\EmailRule;
use Lunar\Service\Validation\Rule\MaxLengthRule;
use Lunar\Service\Validation\Rule\MinLengthRule;
use Lunar\Service\Validation\Rule\NumericRule;
use Lunar\Service\Validation\Rule\PatternRule;
use Lunar\Service\Validation\Rule\RequiredRule;

/**
 * Validateur de données.
 *
 * Cette classe permet de valider un ensemble de données contre des règles
 * définies de façon déclarative.
 *
 * =============================================================================
 * EXEMPLE D'UTILISATION
 * =============================================================================
 *
 * ```php
 * $validator = new Validator();
 *
 * // Définir les règles
 * $rules = [
 *     'email' => ['required', 'email'],
 *     'password' => ['required', 'min:8'],
 *     'age' => ['numeric', 'min:18', 'max:120'],
 *     'username' => ['required', 'min:3', 'max:20', 'pattern:/^[a-z0-9_]+$/i'],
 * ];
 *
 * // Valider les données
 * $data = $_POST;
 * $result = $validator->validate($data, $rules);
 *
 * if ($result->isValid()) {
 *     // Tout est OK
 *     $this->createUser($data);
 * } else {
 *     // Afficher les erreurs
 *     foreach ($result->getAllErrors() as $field => $errors) {
 *         echo "<p>$field : " . implode(', ', $errors) . "</p>";
 *     }
 * }
 * ```
 *
 * @package Lunar\Service\Validation
 */
class Validator
{
    /**
     * Messages d'erreur personnalisés par champ et règle.
     *
     * @var array<string, array<string, string>>
     */
    private array $customMessages = [];

    /**
     * Valide des données contre un ensemble de règles.
     *
     * =========================================================================
     * FORMAT DES RÈGLES
     * =========================================================================
     *
     * Les règles sont définies sous forme de tableau :
     *
     * ```php
     * $rules = [
     *     'champ' => ['règle1', 'règle2:paramètre', ...],
     * ];
     *
     * // Exemples de règles :
     * 'required'      → Le champ est obligatoire
     * 'email'         → Doit être un email valide
     * 'min:8'         → Longueur minimum de 8 caractères
     * 'max:255'       → Longueur maximum de 255 caractères
     * 'numeric'       → Doit être un nombre
     * 'pattern:/...'  → Doit respecter l'expression régulière
     * ```
     *
     * @param array<string, mixed>               $data  Les données à valider
     * @param array<string, array<int, string>>  $rules Les règles par champ
     *
     * @return ValidationResult Le résultat de la validation
     */
    public function validate(array $data, array $rules): ValidationResult
    {
        $result = new ValidationResult();

        foreach ($rules as $field => $fieldRules) {
            // Récupère la valeur du champ (peut être null si absent)
            $value = $data[$field] ?? null;

            // Applique chaque règle au champ
            foreach ($fieldRules as $ruleDefinition) {
                $rule = $this->parseRule($ruleDefinition);

                // Vérifie la validité
                if (!$rule->validate($value)) {
                    // Récupère le message personnalisé ou par défaut
                    $message = $this->getErrorMessage($field, $rule);
                    $result->addError($field, $message);
                }
            }
        }

        return $result;
    }

    /**
     * Définit un message d'erreur personnalisé.
     *
     * =========================================================================
     * PERSONNALISATION DES MESSAGES
     * =========================================================================
     *
     * Par défaut, chaque règle a son message générique. Mais vous pouvez
     * les personnaliser pour un champ spécifique :
     *
     * ```php
     * $validator->setMessage('email', 'required', 'L\'email est obligatoire !');
     * $validator->setMessage('password', 'min', 'Le mot de passe est trop court.');
     * ```
     *
     * @param string $field   Le nom du champ
     * @param string $rule    Le nom de la règle (required, email, min, etc.)
     * @param string $message Le message personnalisé
     *
     * @return self Pour le chaînage
     */
    public function setMessage(string $field, string $rule, string $message): self
    {
        if (!isset($this->customMessages[$field])) {
            $this->customMessages[$field] = [];
        }

        $this->customMessages[$field][$rule] = $message;

        return $this;
    }

    /**
     * Définit plusieurs messages personnalisés en une fois.
     *
     * ```php
     * $validator->setMessages([
     *     'email' => [
     *         'required' => 'L\'email est obligatoire !',
     *         'email' => 'Cet email n\'est pas valide.',
     *     ],
     *     'password' => [
     *         'min' => 'Au moins 8 caractères SVP.',
     *     ],
     * ]);
     * ```
     *
     * @param array<string, array<string, string>> $messages Les messages par champ/règle
     *
     * @return self Pour le chaînage
     */
    public function setMessages(array $messages): self
    {
        foreach ($messages as $field => $fieldMessages) {
            foreach ($fieldMessages as $rule => $message) {
                $this->setMessage($field, $rule, $message);
            }
        }

        return $this;
    }

    /**
     * Parse une définition de règle en objet ValidationRule.
     *
     * =========================================================================
     * FORMAT DE PARSING
     * =========================================================================
     *
     * ```
     * 'required'       → RequiredRule()
     * 'email'          → EmailRule()
     * 'min:8'          → MinLengthRule(8)
     * 'max:255'        → MaxLengthRule(255)
     * 'numeric'        → NumericRule()
     * 'pattern:/^...'  → PatternRule('/^...')
     * ```
     *
     * @param string $ruleDefinition La définition de la règle (ex: "min:8")
     *
     * @return ValidationRuleInterface L'instance de la règle
     *
     * @throws \InvalidArgumentException Si la règle n'existe pas
     */
    private function parseRule(string $ruleDefinition): ValidationRuleInterface
    {
        // Sépare le nom de la règle et ses paramètres
        // "min:8" → ['min', '8']
        $parts = explode(':', $ruleDefinition, 2);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;

        return match ($ruleName) {
            'required' => new RequiredRule(),
            'email' => new EmailRule(),
            'min' => new MinLengthRule((int) ($parameter ?? 0)),
            'max' => new MaxLengthRule((int) ($parameter ?? 255)),
            'numeric' => new NumericRule(),
            'pattern' => new PatternRule($parameter ?? ''),
            default => throw new \InvalidArgumentException("Règle inconnue : $ruleName"),
        };
    }

    /**
     * Récupère le message d'erreur pour un champ et une règle.
     *
     * Cherche d'abord un message personnalisé, sinon utilise le message par défaut.
     *
     * @param string                  $field Le nom du champ
     * @param ValidationRuleInterface $rule  La règle
     *
     * @return string Le message d'erreur
     */
    private function getErrorMessage(string $field, ValidationRuleInterface $rule): string
    {
        // Message personnalisé ?
        $ruleName = $rule->getName();

        if (isset($this->customMessages[$field][$ruleName])) {
            return $this->customMessages[$field][$ruleName];
        }

        // Message par défaut de la règle
        return $rule->getMessage();
    }
}
