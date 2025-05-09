<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

namespace App\Service\Command;

/**
 * Classe abstraite pour les commandes de la console.
 * Gère la logique de base de parsing des arguments.
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * Vérifie si l'utilisateur a demandé l'aide via --help.
     *
     * @param string[] $args liste des arguments de la ligne de commande
     */
    protected function wantsHelp(array $args): bool
    {
        return in_array('--help', $args, true);
    }

    /**
     * Renvoie le premier argument "nu" (celui qui n'est pas un --key=value)
     * ou null si aucun argument nu n'est trouvé.
     *
     * @param string[] $args
     */
    protected function getFirstPositionalArgument(array $args): ?string
    {
        foreach ($args as $arg) {
            if (!str_starts_with($arg, '--')) {
                return $arg;
            }
        }

        return null;
    }

    /**
     * Parse les arguments nommés de style --key=value
     * et renvoie un tableau associatif [key => value].
     *
     * @param string[] $args
     *
     * @return array<string,string>
     */
    protected function parseNamedArgs(array $args): array
    {
        $parsed = [];
        foreach ($args as $arg) {
            if (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
                $parsed[$matches[1]] = $matches[2];
            }
        }

        return $parsed;
    }

    /**
     * Vérifie la présence d'une option nommée (ex: --some-flag).
     * Ici, on ne teste que la clé (pas la valeur).
     *
     * @param string[] $args
     * @param string   $option nom de l'option sans le "--"
     */
    protected function hasFlag(array $args, string $option): bool
    {
        foreach ($args as $arg) {
            if ($arg === '--'.$option) {
                return true;
            }
        }

        return false;
    }

    /**
     * Récupère la valeur d'une option nommée dans le tableau issu de parseNamedArgs().
     *
     * @param array<string,string> $namedArgs
     */
    protected function getOptionValue(array $namedArgs, string $key, mixed $default = null): mixed
    {
        return $namedArgs[$key] ?? $default;
    }
}
