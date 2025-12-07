<?php

declare(strict_types=1);

namespace Lunar\Service\Validation\Rule;

/**
 * Règle de validation pour les URLs.
 *
 * @example
 * ```php
 * $validator->field('website')->url();
 * $validator->field('image')->url(['schemes' => ['https']]);
 * ```
 */
final class UrlRule implements ValidationRuleInterface
{
    /** @var string[] Schémas autorisés */
    private array $schemes = ['http', 'https'];

    /** @var bool Autoriser les URLs relatives */
    private bool $allowRelative = false;

    /** @var bool Vérifier si le domaine existe (DNS) */
    private bool $checkDns = false;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['schemes'])) {
            $this->schemes = (array) $options['schemes'];
        }
        if (isset($options['allowRelative'])) {
            $this->allowRelative = (bool) $options['allowRelative'];
        }
        if (isset($options['checkDns'])) {
            $this->checkDns = (bool) $options['checkDns'];
        }
    }

    public function validate(mixed $value, array $context = []): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        // URL relative
        if ($this->allowRelative && str_starts_with($value, '/')) {
            return $this->isValidPath($value);
        }

        // Validation de base
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Vérifier le schéma
        $parsed = parse_url($value);
        if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), $this->schemes, true)) {
            return false;
        }

        // Vérifier le domaine si demandé
        if ($this->checkDns && isset($parsed['host'])) {
            if (!checkdnsrr($parsed['host'], 'A') && !checkdnsrr($parsed['host'], 'AAAA')) {
                return false;
            }
        }

        return true;
    }

    public function getMessage(): string
    {
        $schemes = implode(', ', $this->schemes);
        return "L'URL n'est pas valide. Schémas autorisés : {$schemes}.";
    }

    /**
     * Vérifie si un chemin relatif est valide.
     */
    private function isValidPath(string $path): bool
    {
        // Doit commencer par /
        if (!str_starts_with($path, '/')) {
            return false;
        }

        // Pas de double slashes
        if (str_contains($path, '//')) {
            return false;
        }

        // Pas de path traversal
        if (str_contains($path, '..')) {
            return false;
        }

        // Caractères autorisés
        return (bool) preg_match('#^[a-zA-Z0-9/_.-]+$#', $path);
    }
}
