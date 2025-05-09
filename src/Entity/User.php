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

namespace App\Entity;

/**
 * Class User.
 *
 * Représente un utilisateur.
 *
 * Les instances de cette classe sont stockées sous forme de fichiers JSON chiffrés.
 */
class User
{
    private string $email;
    private string $name;
    private string $password;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    /**
     * Constructeur de l'entité User.
     *
     * @param string $email    L'adresse email de l'utilisateur
     * @param string $name     le nom de l'utilisateur
     * @param string $password le mot de passe de l'utilisateur
     */
    public function __construct(string $email, string $name, string $password)
    {
        $this->email = $email;
        $this->name = $name;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters et setters

    /**
     * Retourne l'email de l'utilisateur.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Retourne le nom de l'utilisateur.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retourne le mot de passe chiffré.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Retourne la date de mise à jour.
     */
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Met à jour la date de mise à jour.
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Retourne le hash de l'email (utilisé pour nommer le fichier).
     */
    public function getHash(): string
    {
        return hash('sha256', $this->email);
    }

    /**
     * Retourne un tableau associatif des données de l'utilisateur.
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'password' => $this->password,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }
}
