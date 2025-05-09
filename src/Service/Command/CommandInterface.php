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
 * Interface CommandInterface.
 *
 * Représente la forme minimale d’une commande du framework.
 */
interface CommandInterface
{
    /**
     * Exécute la commande avec les arguments passés en CLI.
     *
     * @param string[] $args
     *
     * @return int Code de sortie (0 = succès, >0 = code d’erreur)
     */
    public function execute(array $args): int;

    /**
     * Retourne une aide détaillée pour la commande (utilisée par --help).
     */
    public function getHelp(): string;
}
