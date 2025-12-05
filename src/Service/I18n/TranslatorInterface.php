<?php

declare(strict_types=1);

namespace Lunar\Service\I18n;

/**
 * Interface pour le service de traduction.
 *
 * Cette interface définit le contrat minimal pour un traducteur.
 * Elle est inspirée de Symfony Translator mais simplifiée.
 */
interface TranslatorInterface
{
    /**
     * Traduit un message.
     *
     * @param string $id La clé de traduction (ex: "messages.hello")
     * @param array<string, mixed> $parameters Les paramètres à interpoler
     * @param string|null $locale La locale à utiliser (null = locale courante)
     * @return string Le message traduit ou la clé si non trouvé
     */
    public function trans(string $id, array $parameters = [], ?string $locale = null): string;

    /**
     * Traduit un message avec pluralisation.
     *
     * @param string $id La clé de traduction
     * @param int $count Le nombre pour la pluralisation
     * @param array<string, mixed> $parameters Les paramètres à interpoler
     * @param string|null $locale La locale à utiliser
     * @return string Le message traduit
     */
    public function transChoice(string $id, int $count, array $parameters = [], ?string $locale = null): string;

    /**
     * Retourne la locale courante.
     */
    public function getLocale(): string;

    /**
     * Définit la locale courante.
     */
    public function setLocale(string $locale): void;
}
