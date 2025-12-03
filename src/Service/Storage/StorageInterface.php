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

namespace Lunar\Service\Storage;

use Lunar\Entity\User;

/**
 * Interface StorageInterface.
 *
 * Interface de stockage des entités.
 */
interface StorageInterface
{
    /**
     * Sauvegarde une entité User.
     *
     * @param User $user instance de l'utilisateur
     */
    public function saveUser(User $user): void;

    /**
     * Charge une entité User à partir d'un email.
     *
     * @param string $email email de l'utilisateur
     *
     * @return null|User L'utilisateur ou null si non trouvé
     */
    public function loadUser(string $email): ?User;
}
