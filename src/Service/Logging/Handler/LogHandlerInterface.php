<?php

declare(strict_types=1);

namespace Lunar\Service\Logging\Handler;

/**
 * Interface pour les handlers de logs.
 *
 * Un handler est responsable de l'écriture des logs vers une destination :
 * - Fichier (FileHandler)
 * - Mémoire/Array (ArrayHandler - pour les tests)
 * - Stream (StreamHandler)
 * - Email, Slack, base de données, etc.
 *
 * Le pattern Chain of Responsibility permet d'avoir plusieurs handlers
 * qui traitent le même log (ex: fichier + email pour les erreurs critiques).
 */
interface LogHandlerInterface
{
    /**
     * Gère un enregistrement de log.
     *
     * @param array{
     *     datetime: \DateTimeImmutable,
     *     channel: string,
     *     level: string,
     *     message: string,
     *     context: array<string, mixed>
     * } $record L'enregistrement à traiter
     */
    public function handle(array $record): void;

    /**
     * Vérifie si le handler gère ce niveau de log.
     *
     * @param string $level Le niveau de log à vérifier
     * @return bool True si le handler doit traiter ce niveau
     */
    public function isHandling(string $level): bool;
}
