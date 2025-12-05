<?php

declare(strict_types=1);

namespace Lunar\Service\Logging\Formatter;

/**
 * Interface pour les formateurs de logs.
 *
 * Un formateur transforme un enregistrement de log (array) en une
 * représentation formatée (string) prête à être écrite.
 *
 * Le pattern Strategy est utilisé ici pour permettre différents
 * formats de sortie : texte, JSON, HTML, etc.
 */
interface LogFormatterInterface
{
    /**
     * Formate un enregistrement de log.
     *
     * @param array{
     *     datetime: \DateTimeImmutable,
     *     channel: string,
     *     level: string,
     *     message: string,
     *     context: array<string, mixed>
     * } $record L'enregistrement à formater
     *
     * @return string La représentation formatée
     */
    public function format(array $record): string;
}
