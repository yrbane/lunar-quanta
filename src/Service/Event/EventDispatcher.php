<?php
/**
 * Lunar Quanta Framework - Dispatcher d'Événements.
 *
 * =============================================================================
 * QU'EST-CE QU'UN DISPATCHER ?
 * =============================================================================
 *
 * Le DISPATCHER (répartiteur) est le chef d'orchestre du système d'événements.
 * Il maintient la liste des listeners et les notifie quand un événement arrive.
 *
 * ```
 * ARCHITECTURE DU SYSTÈME D'ÉVÉNEMENTS
 *
 *                        ┌─────────────────────┐
 *                        │   EventDispatcher   │
 *                        │                     │
 *    dispatch(event) ───▶│  listeners = [     │
 *                        │    'user.created': │
 *                        │      [listener1,   │
 *                        │       listener2],  │
 *                        │    'order.placed': │
 *                        │      [listener3]   │
 *                        │  ]                 │
 *                        └─────────┬──────────┘
 *                                  │
 *           ┌──────────────────────┼──────────────────────┐
 *           │                      │                      │
 *           ▼                      ▼                      ▼
 *    ┌─────────────┐        ┌─────────────┐       ┌─────────────┐
 *    │  Listener 1 │        │  Listener 2 │       │  Listener 3 │
 *    │ (EmailSend) │        │ (LogAction) │       │ (Webhook)   │
 *    └─────────────┘        └─────────────┘       └─────────────┘
 * ```
 *
 * =============================================================================
 * AVANTAGES DU PATTERN OBSERVER/EVENT
 * =============================================================================
 *
 * 1. DÉCOUPLAGE : Les composants ne se connaissent pas directement
 * 2. EXTENSIBILITÉ : Ajouter des fonctionnalités sans modifier l'existant
 * 3. TESTABILITÉ : Chaque listener peut être testé indépendamment
 * 4. MAINTENABILITÉ : Code plus organisé et modulaire
 *
 * ```php
 * // Ajouter une fonctionnalité = ajouter un listener
 * // Pas besoin de modifier le code existant !
 *
 * $dispatcher->addListener('user.registered', function($event) {
 *     // Nouvelle fonctionnalité : envoyer un SMS de bienvenue
 *     $this->smsService->sendWelcome($event->get('user'));
 * });
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
 * Dispatcher d'événements.
 *
 * Gère l'enregistrement des listeners et la distribution des événements.
 *
 * =============================================================================
 * EXEMPLE D'UTILISATION
 * =============================================================================
 *
 * ```php
 * $dispatcher = new EventDispatcher();
 *
 * // Enregistrer des listeners
 * $dispatcher->addListener('user.registered', function(Event $event) {
 *     $email = $event->get('email');
 *     sendWelcomeEmail($email);
 * });
 *
 * $dispatcher->addListener('user.registered', function(Event $event) {
 *     logActivity('User registered: ' . $event->get('email'));
 * });
 *
 * // Dispatcher un événement
 * $event = new Event('user.registered', [
 *     'email' => 'john@example.com',
 *     'name' => 'John Doe'
 * ]);
 *
 * $dispatcher->dispatch($event);
 * // → Les deux listeners sont appelés
 * ```
 *
 * @package Lunar\Service\Event
 */
class EventDispatcher
{
    /**
     * Les listeners enregistrés par événement.
     *
     * Structure : ['event.name' => [[callable, priority], ...], ...]
     *
     * @var array<string, array<int, array{callable: callable, priority: int}>>
     */
    private array $listeners = [];

    /**
     * Cache des listeners triés par priorité.
     *
     * @var array<string, array<int, callable>>
     */
    private array $sortedListeners = [];

    /**
     * Enregistre un listener pour un événement.
     *
     * =========================================================================
     * PRIORITÉS
     * =========================================================================
     *
     * Les listeners sont appelés par ordre de priorité DÉCROISSANTE :
     * - Priorité 100 : appelé en premier
     * - Priorité 0 (défaut) : appelé normalement
     * - Priorité -100 : appelé en dernier
     *
     * ```php
     * // Ce listener s'exécute en premier (priorité élevée)
     * $dispatcher->addListener('user.login', $securityCheck, 100);
     *
     * // Ce listener s'exécute ensuite (priorité normale)
     * $dispatcher->addListener('user.login', $logActivity, 0);
     *
     * // Ce listener s'exécute en dernier (priorité basse)
     * $dispatcher->addListener('user.login', $cleanup, -100);
     * ```
     *
     * @param string   $eventName Le nom de l'événement
     * @param callable $listener  La fonction/méthode à appeler
     * @param int      $priority  Priorité (plus élevé = appelé en premier)
     *
     * @return self Pour le chaînage
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): self
    {
        $this->listeners[$eventName][] = [
            'callable' => $listener,
            'priority' => $priority,
        ];

        // Invalide le cache pour cet événement
        unset($this->sortedListeners[$eventName]);

        return $this;
    }

    /**
     * Supprime un listener spécifique.
     *
     * @param string   $eventName Le nom de l'événement
     * @param callable $listener  Le listener à supprimer
     *
     * @return self Pour le chaînage
     */
    public function removeListener(string $eventName, callable $listener): self
    {
        if (!isset($this->listeners[$eventName])) {
            return $this;
        }

        foreach ($this->listeners[$eventName] as $key => $entry) {
            if ($entry['callable'] === $listener) {
                unset($this->listeners[$eventName][$key]);
            }
        }

        // Réindexe le tableau et invalide le cache
        $this->listeners[$eventName] = array_values($this->listeners[$eventName]);
        unset($this->sortedListeners[$eventName]);

        return $this;
    }

    /**
     * Vérifie si un événement a des listeners.
     *
     * @param string $eventName Le nom de l'événement
     *
     * @return bool true si au moins un listener existe
     */
    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * Retourne les listeners pour un événement (triés par priorité).
     *
     * @param string $eventName Le nom de l'événement
     *
     * @return array<int, callable> Les listeners
     */
    public function getListeners(string $eventName): array
    {
        if (!isset($this->listeners[$eventName])) {
            return [];
        }

        // Utilise le cache si disponible
        if (isset($this->sortedListeners[$eventName])) {
            return $this->sortedListeners[$eventName];
        }

        // Trie par priorité décroissante
        $sorted = $this->listeners[$eventName];
        usort($sorted, static function (array $a, array $b): int {
            return $b['priority'] <=> $a['priority'];
        });

        // Extrait les callables et met en cache
        $this->sortedListeners[$eventName] = array_map(
            static fn(array $entry): callable => $entry['callable'],
            $sorted
        );

        return $this->sortedListeners[$eventName];
    }

    /**
     * Dispatch (distribue) un événement à ses listeners.
     *
     * =========================================================================
     * PROCESSUS DE DISPATCH
     * =========================================================================
     *
     * ```
     * dispatch($event)
     *     │
     *     ▼
     * ┌───────────────────────────────────────┐
     * │ Pour chaque listener (par priorité) : │
     * │   1. Appeler le listener              │
     * │   2. Si l'événement est stoppé → fin  │
     * └───────────────────────────────────────┘
     *     │
     *     ▼
     * Retourne l'événement (potentiellement modifié)
     * ```
     *
     * @param EventInterface $event L'événement à dispatcher
     *
     * @return EventInterface L'événement (possiblement modifié par les listeners)
     */
    public function dispatch(EventInterface $event): EventInterface
    {
        $eventName = $event->getName();
        $listeners = $this->getListeners($eventName);

        foreach ($listeners as $listener) {
            // Vérifie si l'événement peut être stoppé
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            // Appelle le listener
            $listener($event);
        }

        return $event;
    }

    /**
     * Supprime tous les listeners d'un événement.
     *
     * @param string|null $eventName Le nom de l'événement (null = tous)
     *
     * @return self Pour le chaînage
     */
    public function clearListeners(?string $eventName = null): self
    {
        if ($eventName === null) {
            $this->listeners = [];
            $this->sortedListeners = [];
        } else {
            unset($this->listeners[$eventName]);
            unset($this->sortedListeners[$eventName]);
        }

        return $this;
    }

    /**
     * Retourne le nombre de listeners pour un événement.
     *
     * @param string $eventName Le nom de l'événement
     *
     * @return int Le nombre de listeners
     */
    public function countListeners(string $eventName): int
    {
        return count($this->listeners[$eventName] ?? []);
    }

    /**
     * Enregistre un listener pour plusieurs événements.
     *
     * ```php
     * $dispatcher->addListenerForEvents(
     *     ['user.created', 'user.updated', 'user.deleted'],
     *     function($event) {
     *         $this->cache->invalidate('users');
     *     }
     * );
     * ```
     *
     * @param array<int, string> $eventNames Les noms d'événements
     * @param callable           $listener   Le listener
     * @param int                $priority   La priorité
     *
     * @return self Pour le chaînage
     */
    public function addListenerForEvents(array $eventNames, callable $listener, int $priority = 0): self
    {
        foreach ($eventNames as $eventName) {
            $this->addListener($eventName, $listener, $priority);
        }

        return $this;
    }
}
