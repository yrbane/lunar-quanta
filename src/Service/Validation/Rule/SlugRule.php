<?php

declare(strict_types=1);

namespace Lunar\Service\Validation\Rule;

/**
 * Règle de validation pour les slugs.
 *
 * Un slug valide contient uniquement des lettres minuscules,
 * des chiffres et des tirets. Il ne peut pas commencer ou
 * finir par un tiret.
 *
 * @example
 * ```php
 * $validator->field('slug')->slug();
 * $validator->field('slug')->slug(['minLength' => 3, 'maxLength' => 100]);
 * ```
 */
final class SlugRule implements ValidationRuleInterface
{
    private int $minLength;
    private int $maxLength;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->minLength = (int) ($options['minLength'] ?? 1);
        $this->maxLength = (int) ($options['maxLength'] ?? 255);
    }

    public function validate(mixed $value, array $context = []): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $length = strlen($value);

        // Vérifier la longueur
        if ($length < $this->minLength || $length > $this->maxLength) {
            return false;
        }

        // Pattern: lettres minuscules, chiffres, tirets
        // Ne peut pas commencer ou finir par un tiret
        // Pas de tirets consécutifs
        if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $value)) {
            return false;
        }

        return true;
    }

    public function getMessage(): string
    {
        return "Le slug n'est pas valide. Utilisez uniquement des lettres minuscules, des chiffres et des tirets.";
    }
}
