<?php
/**
 * Lunar Quanta Framework - Interface d'Événement Stoppable.
 *
 * =============================================================================
 * QU'EST-CE QU'UN ÉVÉNEMENT STOPPABLE ?
 * =============================================================================
 *
 * Parfois, un listener veut empêcher les autres listeners de s'exécuter.
 * C'est utile pour :
 *
 * - Valider des données et annuler l'opération si invalide
 * - Gérer un cas spécial et éviter le traitement normal
 * - Optimiser en court-circuitant le reste du traitement
 *
 * ```
 * EXEMPLE : Validation avant création
 *
 * Événement: user.creating
 *
 * Listener 1 (ValidationListener):
 *   Si l'email existe déjà → stopPropagation()
 *   → Les autres listeners ne s'exécutent pas
 *
 * Listener 2 (CreateUserListener):
 *   Ne s'exécute que si Listener 1 n'a pas stoppé
 * ```
 *
 * @package    Lunar\Service\Event
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Event;

/**
 * Interface pour les événements qui peuvent être stoppés.
 *
 * Un événement stoppable peut interrompre la chaîne de listeners.
 * Une fois stoppé, plus aucun listener ne sera notifié.
 *
 * @package Lunar\Service\Event
 */
interface StoppableEventInterface extends EventInterface
{
    /**
     * Indique si la propagation a été stoppée.
     *
     * Si true, le dispatcher ne doit pas appeler les autres listeners.
     *
     * @return bool true si stoppé, false sinon
     */
    public function isPropagationStopped(): bool;

    /**
     * Stoppe la propagation de l'événement.
     *
     * Une fois appelée, aucun autre listener ne sera notifié.
     * Cette action est irréversible pour cet événement.
     */
    public function stopPropagation(): void;
}
