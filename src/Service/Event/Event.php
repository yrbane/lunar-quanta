<?php
/**
 * Lunar Quanta Framework - Classe de Base pour les Événements.
 *
 * =============================================================================
 * UTILISATION
 * =============================================================================
 *
 * La classe Event peut être utilisée de deux façons :
 *
 * 1. DIRECTEMENT (événements simples) :
 * ```php
 * $event = new Event('user.registered');
 * $dispatcher->dispatch($event);
 * ```
 *
 * 2. PAR HÉRITAGE (événements avec données) :
 * ```php
 * class UserRegisteredEvent extends Event {
 *     public function __construct(private User $user) {
 *         parent::__construct('user.registered');
 *     }
 *
 *     public function getUser(): User {
 *         return $this->user;
 *     }
 * }
 *
 * $event = new UserRegisteredEvent($user);
 * $dispatcher->dispatch($event);
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
 * Classe de base pour les événements.
 *
 * Cette classe implémente StoppableEventInterface pour permettre
 * aux listeners d'interrompre la chaîne de traitement.
 *
 * @package Lunar\Service\Event
 */
class Event implements StoppableEventInterface
{
    /**
     * Le nom de l'événement.
     */
    private string $name;

    /**
     * Indique si la propagation a été stoppée.
     */
    private bool $propagationStopped = false;

    /**
     * Données supplémentaires de l'événement.
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Crée un nouvel événement.
     *
     * @param string               $name Le nom de l'événement
     * @param array<string, mixed> $data Données optionnelles
     */
    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Retourne le nom de l'événement.
     *
     * @return string Le nom de l'événement
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Vérifie si la propagation a été stoppée.
     *
     * @return bool true si stoppé
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stoppe la propagation de l'événement.
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Récupère une donnée de l'événement.
     *
     * ```php
     * $event = new Event('order.completed', ['orderId' => 42]);
     * $orderId = $event->get('orderId'); // 42
     * ```
     *
     * @param string $key     La clé de la donnée
     * @param mixed  $default Valeur par défaut si absent
     *
     * @return mixed La valeur ou le défaut
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Définit une donnée de l'événement.
     *
     * ```php
     * $event = new Event('user.registered');
     * $event->set('userId', 42);
     * $event->set('email', 'john@example.com');
     * ```
     *
     * @param string $key   La clé
     * @param mixed  $value La valeur
     *
     * @return self Pour le chaînage
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Vérifie si une donnée existe.
     *
     * @param string $key La clé
     *
     * @return bool true si la clé existe
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Retourne toutes les données de l'événement.
     *
     * @return array<string, mixed> Les données
     */
    public function getData(): array
    {
        return $this->data;
    }
}
