<?php
/**
 * Lunar Quanta Framework - Interface de Règle de Validation.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE RÈGLE DE VALIDATION ?
 * =============================================================================
 *
 * Une RÈGLE DE VALIDATION est une condition qu'une donnée doit respecter.
 * C'est comme un garde qui vérifie si vous avez le bon billet pour entrer.
 *
 * ```
 * DONNÉE                    RÈGLE                      RÉSULTAT
 *
 * "john@example.com"   ──► [Règle Email]    ──►  ✓ Valide
 * "not-an-email"       ──► [Règle Email]    ──►  ✗ Invalide
 * ""                   ──► [Règle Required] ──►  ✗ Invalide
 * "abc123"             ──► [Règle MinLength(6)] ►  ✓ Valide
 * ```
 *
 * =============================================================================
 * POURQUOI UNE INTERFACE ?
 * =============================================================================
 *
 * Une INTERFACE est un "contrat" que les classes doivent respecter.
 * Toute règle de validation DOIT avoir ces méthodes, garantissant
 * qu'elles fonctionnent toutes de la même manière.
 *
 * ```php
 * // Toutes ces règles respectent le même contrat :
 * $rules = [
 *     new RequiredRule(),
 *     new EmailRule(),
 *     new MinLengthRule(8),
 * ];
 *
 * foreach ($rules as $rule) {
 *     if (!$rule->validate($value)) {
 *         echo $rule->getMessage();  // Chaque règle a sa méthode getMessage()
 *     }
 * }
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
 * Interface pour les règles de validation.
 *
 * Toute règle de validation doit implémenter cette interface pour
 * garantir une API cohérente.
 *
 * @package Lunar\Service\Validation
 */
interface ValidationRuleInterface
{
    /**
     * Valide une valeur selon la règle.
     *
     * =========================================================================
     * RÔLE DE CETTE MÉTHODE
     * =========================================================================
     *
     * C'est le cœur de la règle. Elle reçoit une valeur et retourne :
     * - true  → La valeur respecte la règle
     * - false → La valeur ne respecte pas la règle
     *
     * @param mixed $value La valeur à valider
     *
     * @return bool true si valide, false sinon
     */
    public function validate(mixed $value): bool;

    /**
     * Retourne le message d'erreur si la validation échoue.
     *
     * =========================================================================
     * MESSAGES D'ERREUR
     * =========================================================================
     *
     * Chaque règle définit son propre message d'erreur, clair et compréhensible
     * pour l'utilisateur final.
     *
     * ```
     * Règle          →  Message
     * ────────────────────────────────────────────
     * RequiredRule   →  "Ce champ est requis."
     * EmailRule      →  "L'adresse email n'est pas valide."
     * MinLengthRule  →  "Ce champ doit contenir au moins 8 caractères."
     * ```
     *
     * @return string Le message d'erreur localisé
     */
    public function getMessage(): string;

    /**
     * Retourne le nom de la règle.
     *
     * =========================================================================
     * UTILITÉ DU NOM
     * =========================================================================
     *
     * Le nom permet d'identifier la règle pour :
     * - Le débogage
     * - L'affichage dans les logs
     * - La sérialisation des erreurs
     *
     * @return string Le nom de la règle (ex: "required", "email", "min_length")
     */
    public function getName(): string;
}
