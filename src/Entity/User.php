<?php
/**
 * Lunar Quanta Framework - Entité Utilisateur.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE ENTITÉ ?
 * =============================================================================
 *
 * Une ENTITÉ est un objet qui représente une "chose" de votre application.
 * Elle contient des DONNÉES et des COMPORTEMENTS associés à cette chose.
 *
 * ```
 * MONDE RÉEL                          CODE PHP
 *
 *    Utilisateur                       class User
 *    ├── ID unique                     ├── $id (UUID)
 *    ├── Email                         ├── $email
 *    ├── Nom                           ├── $name
 *    ├── Mot de passe                  ├── $password (hashé!)
 *    ├── Rôles                         ├── $roles
 *    └── Date d'inscription            └── $createdAt
 * ```
 *
 * =============================================================================
 * INTÉGRATION AVEC LE SYSTÈME D'AUTHENTIFICATION
 * =============================================================================
 *
 * Cette entité implémente `UserInterface` pour s'intégrer avec l'Authenticator :
 *
 * ```
 *                            ┌─────────────────────┐
 *                            │    UserInterface    │
 *                            │  getId()            │
 *                            │  getIdentifier()    │
 *                            │  getPassword()      │
 *                            │  getRoles()         │
 *                            └──────────┬──────────┘
 *                                       │
 *                                       │ implements
 *                                       ▼
 *                            ┌─────────────────────┐
 *                            │        User         │
 *                            │  + email, name...   │
 *                            │  + createdAt...     │
 *                            └─────────────────────┘
 * ```
 *
 * =============================================================================
 * POURQUOI HASHER LE MOT DE PASSE ?
 * =============================================================================
 *
 * Un HASH est une transformation IRRÉVERSIBLE du mot de passe :
 *
 * ```
 * "secret123"  ──► password_hash() ──► "$2y$10$xK8..."
 *                                            │
 *                                            └─ Impossible de retrouver
 *                                               "secret123" à partir du hash
 *
 * VÉRIFICATION :
 *    password_verify("secret123", "$2y$10$xK8...")  → true
 *    password_verify("mauvais",   "$2y$10$xK8...")  → false
 * ```
 *
 * =============================================================================
 * SYSTÈME DE RÔLES
 * =============================================================================
 *
 * Les rôles permettent de contrôler les permissions :
 *
 * ```
 * ROLE_USER          Utilisateur standard (par défaut)
 *    │
 *    └── ROLE_ADMIN  Administrateur (peut tout faire)
 *           │
 *           └── ROLE_SUPER_ADMIN  Super administrateur
 *
 * Vérification :
 *    if (in_array('ROLE_ADMIN', $user->getRoles())) {
 *        // Accès admin autorisé
 *    }
 * ```
 *
 * =============================================================================
 * STOCKAGE DES UTILISATEURS
 * =============================================================================
 *
 * Les utilisateurs sont stockés dans des fichiers JSON CHIFFRÉS :
 *
 * ```
 * data/
 * └── user/
 *     └── a3f/                         ← Préfixe du hash (3 premiers caractères)
 *         └── a3f8b2c...d5e.json       ← Fichier chiffré
 * ```
 *
 * @package    Lunar\Entity
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    2.0.0
 * @link       https://nethttp.net
 * @since      0.0.1
 *
 * @see JsonStorage Classe qui gère le stockage des utilisateurs
 * @see EncryptionService Classe qui chiffre les données
 * @see UserInterface Interface d'authentification
 */
declare(strict_types=1);

namespace Lunar\Entity;

use Lunar\Service\Security\Auth\UserInterface;

/**
 * Représente un utilisateur de l'application.
 *
 * Cette entité encapsule les données d'un utilisateur avec :
 * - ID unique généré automatiquement (UUID v4)
 * - Hashage automatique du mot de passe à la création
 * - Système de rôles pour les permissions
 * - Timestamps de création et mise à jour
 *
 * ==========================================================================
 * EXEMPLE D'UTILISATION
 * ==========================================================================
 *
 * ```php
 * // Créer un nouvel utilisateur
 * $user = new User('john@example.com', 'John Doe', 'monMotDePasse');
 *
 * // L'ID est généré automatiquement
 * echo $user->getId();  // "550e8400-e29b-41d4-a716-446655440000"
 *
 * // Le mot de passe est automatiquement hashé
 * echo $user->getPassword();  // "$2y$10$xK8..." (pas "monMotDePasse" !)
 *
 * // Rôles par défaut
 * print_r($user->getRoles());  // ['ROLE_USER']
 *
 * // Sauvegarder l'utilisateur
 * $storage = new JsonStorage();
 * $storage->saveUser($user);
 * ```
 *
 * ==========================================================================
 * IDENTIFIANT vs EMAIL
 * ==========================================================================
 *
 * ```
 * getIdentifier()  →  Email (utilisé pour la connexion)
 * getId()          →  UUID (utilisé pour les références internes)
 *
 * POURQUOI DEUX IDENTIFIANTS ?
 * - L'email peut changer (l'utilisateur le modifie)
 * - L'UUID reste fixe (références en base de données)
 * ```
 *
 * @package Lunar\Entity
 */
class User implements UserInterface
{
    /**
     * Identifiant unique de l'utilisateur (UUID v4).
     */
    private string $id;

    /**
     * Adresse email (utilisée comme identifiant de connexion).
     */
    private string $email;

    /**
     * Nom d'affichage de l'utilisateur.
     */
    private string $name;

    /**
     * Mot de passe hashé (bcrypt).
     */
    private string $password;

    /**
     * Rôles de l'utilisateur pour les permissions.
     *
     * @var array<int, string>
     */
    private array $roles;

    /**
     * Date de création du compte.
     */
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière mise à jour.
     */
    private \DateTimeImmutable $updatedAt;

    /**
     * Constructeur de l'entité User.
     *
     * Génère automatiquement un UUID v4 et hashe le mot de passe.
     *
     * @param string        $email    L'adresse email de l'utilisateur
     * @param string        $name     Le nom de l'utilisateur
     * @param string        $password Le mot de passe (sera hashé automatiquement)
     * @param array<string> $roles    Les rôles (défaut: ['ROLE_USER'])
     */
    public function __construct(
        string $email,
        string $name,
        string $password,
        array $roles = ['ROLE_USER']
    ) {
        $this->id = self::generateUuid();
        $this->email = $email;
        $this->name = $name;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->roles = $roles;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // =========================================================================
    // MÉTHODES DE UserInterface
    // =========================================================================

    /**
     * Retourne l'identifiant unique (UUID).
     *
     * L'UUID reste fixe même si l'email change.
     *
     * @return string L'UUID de l'utilisateur
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Retourne l'identifiant de connexion (email).
     *
     * C'est la valeur utilisée pour s'authentifier.
     *
     * @return string L'email de l'utilisateur
     */
    public function getIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Retourne les rôles de l'utilisateur.
     *
     * @return array<int, string> Les rôles (ex: ['ROLE_USER', 'ROLE_ADMIN'])
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    // =========================================================================
    // GETTERS
    // =========================================================================

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
     * Retourne le mot de passe hashé.
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

    // =========================================================================
    // SETTERS ET MUTATIONS
    // =========================================================================

    /**
     * Modifie le nom de l'utilisateur.
     *
     * @param string $name Le nouveau nom
     *
     * @return self Pour le chaînage
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updateTimestamp();

        return $this;
    }

    /**
     * Modifie l'email de l'utilisateur.
     *
     * @param string $email Le nouvel email
     *
     * @return self Pour le chaînage
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        $this->updateTimestamp();

        return $this;
    }

    /**
     * Modifie le mot de passe (sera hashé automatiquement).
     *
     * @param string $password Le nouveau mot de passe en clair
     *
     * @return self Pour le chaînage
     */
    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->updateTimestamp();

        return $this;
    }

    /**
     * Ajoute un rôle à l'utilisateur.
     *
     * @param string $role Le rôle à ajouter (ex: 'ROLE_ADMIN')
     *
     * @return self Pour le chaînage
     */
    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
            $this->updateTimestamp();
        }

        return $this;
    }

    /**
     * Retire un rôle à l'utilisateur.
     *
     * @param string $role Le rôle à retirer
     *
     * @return self Pour le chaînage
     */
    public function removeRole(string $role): self
    {
        $key = array_search($role, $this->roles, true);
        if ($key !== false) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
            $this->updateTimestamp();
        }

        return $this;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique.
     *
     * @param string $role Le rôle à vérifier
     *
     * @return bool true si l'utilisateur a ce rôle
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /**
     * Met à jour la date de mise à jour.
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // =========================================================================
    // SÉRIALISATION
    // =========================================================================

    /**
     * Retourne le hash de l'email (utilisé pour nommer le fichier).
     */
    public function getHash(): string
    {
        return hash('sha256', $this->email);
    }

    /**
     * Retourne un tableau associatif des données de l'utilisateur.
     *
     * Utilisé pour la persistance en JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'password' => $this->password,
            'roles' => $this->roles,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
        ];
    }

    /**
     * Crée un User à partir d'un tableau (hydratation depuis le stockage).
     *
     * Cette méthode permet de reconstruire un User depuis les données JSON
     * sans re-hasher le mot de passe.
     *
     * @param array<string, mixed> $data Les données de l'utilisateur
     *
     * @return self L'utilisateur reconstruit
     */
    public static function fromArray(array $data): self
    {
        $user = new self(
            $data['email'],
            $data['name'],
            'temp-password-will-be-replaced',
            $data['roles'] ?? ['ROLE_USER']
        );

        // Restaure les valeurs exactes (sans re-hasher)
        $user->id = $data['id'];
        $user->password = $data['password'];
        $user->createdAt = new \DateTimeImmutable($data['createdAt']);
        $user->updatedAt = new \DateTimeImmutable($data['updatedAt']);

        return $user;
    }

    // =========================================================================
    // MÉTHODES UTILITAIRES
    // =========================================================================

    /**
     * Génère un UUID v4 (identifiant unique universel).
     *
     * Format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     *
     * @return string L'UUID généré
     */
    private static function generateUuid(): string
    {
        $data = random_bytes(16);

        // Définit la version (4) et la variante (RFC 4122)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
