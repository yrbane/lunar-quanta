<?php
/**
 * Lunar Quanta Framework - Interface d'Événement.
 *
 * =============================================================================
 * QU'EST-CE QU'UN ÉVÉNEMENT ?
 * =============================================================================
 *
 * Un ÉVÉNEMENT est un signal qui indique que "quelque chose s'est passé"
 * dans l'application. D'autres parties du code peuvent écouter ces événements
 * et réagir en conséquence.
 *
 * ANALOGIE : Pensez à un journal télévisé
 * - Le journal (EventDispatcher) annonce les nouvelles (événements)
 * - Les téléspectateurs (listeners) écoutent et réagissent
 * - Le journal ne sait pas qui regarde ni comment ils réagissent
 *
 * ```
 * SANS ÉVÉNEMENTS                       AVEC ÉVÉNEMENTS
 *
 * class UserController {                class UserController {
 *   function register() {                 function register() {
 *     $user = createUser();                 $user = createUser();
 *     sendEmail($user);        ─┐           dispatch(new UserRegistered($user));
 *     logActivity($user);       │         }
 *     updateStats($user);       │ couplé }
 *     notifyAdmin($user);      ─┘
 *   }                                   class EmailListener {
 * }                                       function onUserRegistered($event) {
 *                                           sendEmail($event->getUser());
 * Le contrôleur connaît TOUT             }
 * → difficile à maintenir              }
 *                                       → Chaque listener est indépendant
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
 * Interface de base pour tous les événements.
 *
 * Un événement est un objet qui transporte des données sur
 * quelque chose qui s'est passé dans l'application.
 *
 * @package Lunar\Service\Event
 */
interface EventInterface
{
    /**
     * Retourne le nom de l'événement.
     *
     * Le nom permet d'identifier le type d'événement et de savoir
     * quels listeners doivent être notifiés.
     *
     * Convention de nommage : dot notation
     * - user.registered
     * - user.logged_in
     * - order.completed
     * - cache.cleared
     *
     * @return string Le nom de l'événement
     */
    public function getName(): string;
}
