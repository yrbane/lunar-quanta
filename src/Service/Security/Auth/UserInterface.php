<?php
/**
 * Lunar Quanta Framework - Interface Utilisateur pour l'Authentification.
 *
 * =============================================================================
 * QU'EST-CE QU'UN UTILISATEUR EN PROGRAMMATION ?
 * =============================================================================
 *
 * Un UTILISATEUR est une personne qui se connecte à votre application.
 * En programmation, on le représente par un OBJET qui contient ses informations.
 *
 * ANALOGIE : La carte d'identité
 *
 * Pensez à une carte d'identité qui contient des informations sur une personne :
 * - Numéro unique (ID)
 * - Nom (identifiant)
 * - Photo (mot de passe... en quelque sorte !)
 * - Droits (permis de conduire, accès...)
 *
 * ```
 * REPRÉSENTATION D'UN UTILISATEUR
 *
 *    ┌─────────────────────────────────────────┐
 *    │              UTILISATEUR                │
 *    ├─────────────────────────────────────────┤
 *    │  ID : 42                                │  ← Numéro unique
 *    │  Identifiant : "alice@email.com"        │  ← Email/Username
 *    │  Mot de passe : "$2y$12$hashé..."       │  ← HACHÉ, jamais en clair !
 *    │  Rôles : ["ROLE_USER", "ROLE_ADMIN"]    │  ← Permissions
 *    └─────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * POURQUOI UNE INTERFACE ?
 * =============================================================================
 *
 * Cette interface définit le CONTRAT minimum qu'un utilisateur doit respecter
 * pour fonctionner avec le système d'authentification.
 *
 * QU'EST-CE QU'UNE INTERFACE ?
 *
 * Une interface est une liste de méthodes que les classes doivent implémenter.
 * C'est comme un "formulaire à remplir" : l'interface dit quoi fournir,
 * chaque classe décide COMMENT le fournir.
 *
 * ```
 * ANALOGIE : Le contrat de travail
 *
 *    Interface (contrat) :              Implémentation (employé) :
 *    ────────────────────              ─────────────────────────
 *    "Tu dois pouvoir :"               "Voici comment je fais :"
 *    - Donner ton ID                   - return $this->id;
 *    - Donner ton email                - return $this->email;
 *    - Donner ton mot de passe hashé   - return $this->password;
 *    - Donner tes rôles                - return $this->roles;
 * ```
 *
 * AVANTAGE : Le système d'authentification peut fonctionner avec N'IMPORTE
 * QUELLE classe qui implémente cette interface, pas une classe spécifique.
 *
 * =============================================================================
 * LES QUATRE MÉTHODES REQUISES
 * =============================================================================
 *
 * ┌───────────────────┬───────────────────────────────────────────────────────┐
 * │  Méthode          │  Description                                          │
 * ├───────────────────┼───────────────────────────────────────────────────────┤
 * │  getId()          │  Retourne l'identifiant unique (clé primaire BDD)     │
 * │                   │  Ex: 42, "uuid-abc-123"                               │
 * ├───────────────────┼───────────────────────────────────────────────────────┤
 * │  getIdentifier()  │  Retourne ce qu'on tape pour se connecter             │
 * │                   │  Ex: "alice@email.com", "alice_92"                    │
 * ├───────────────────┼───────────────────────────────────────────────────────┤
 * │  getPassword()    │  Retourne le MOT DE PASSE HACHÉ (jamais en clair !)   │
 * │                   │  Ex: "$2y$12$LQv3c1yqBWVHxkd0LH..."                   │
 * ├───────────────────┼───────────────────────────────────────────────────────┤
 * │  getRoles()       │  Retourne les rôles/permissions de l'utilisateur      │
 * │                   │  Ex: ["ROLE_USER"], ["ROLE_USER", "ROLE_ADMIN"]       │
 * └───────────────────┴───────────────────────────────────────────────────────┘
 *
 * =============================================================================
 * IDENTIFIER vs ID : QUELLE DIFFÉRENCE ?
 * =============================================================================
 *
 * Ces deux concepts sont souvent confondus :
 *
 * ```
 * ID (Identifiant technique)           IDENTIFIER (Identifiant métier)
 * ─────────────────────────           ───────────────────────────────
 * Numéro unique en base de données    Ce que l'utilisateur CONNAÎT
 * Utilisé par le programme            Utilisé pour SE CONNECTER
 * Jamais modifié                      Peut être changé (email, username)
 *
 * Exemples :                          Exemples :
 * - 42                                - "alice@email.com"
 * - "uuid-abc-123-def"                - "alice_92"
 * - Auto-incrémenté                   - "0612345678"
 * ```
 *
 * ```
 * FLUX DE CONNEXION
 *
 *    Formulaire de login
 *    ┌─────────────────────────────┐
 *    │ Email : alice@email.com    │  ← IDENTIFIER (ce qu'on tape)
 *    │ Mot de passe : ********    │
 *    │         [Se connecter]      │
 *    └─────────────────────────────┘
 *                  │
 *                  ▼
 *    Le système cherche l'utilisateur par son IDENTIFIER
 *    SELECT * FROM users WHERE email = "alice@email.com"
 *                  │
 *                  ▼
 *    Utilisateur trouvé → ID = 42
 *    L'ID est stocké en session : $_SESSION['user_id'] = 42
 *                  │
 *                  ▼
 *    Pour les requêtes suivantes, on charge l'utilisateur par ID
 *    SELECT * FROM users WHERE id = 42
 * ```
 *
 * =============================================================================
 * LES RÔLES : SYSTÈME DE PERMISSIONS
 * =============================================================================
 *
 * Les RÔLES déterminent ce qu'un utilisateur peut faire dans l'application.
 *
 * CONVENTION : Les rôles commencent par "ROLE_" (recommandation Symfony).
 *
 * ```
 * HIÉRARCHIE DES RÔLES TYPIQUE
 *
 *    ROLE_SUPER_ADMIN
 *         │
 *         ├── Peut tout faire
 *         │   ├── Gérer les administrateurs
 *         │   └── Accéder à toutes les fonctions
 *         │
 *    ROLE_ADMIN
 *         │
 *         ├── Peut modérer
 *         │   ├── Supprimer des contenus
 *         │   └── Bannir des utilisateurs
 *         │
 *    ROLE_MODERATOR
 *         │
 *         ├── Peut éditer
 *         │   └── Modifier les contenus signalés
 *         │
 *    ROLE_USER
 *         │
 *         └── Peut utiliser l'app
 *             ├── Créer son contenu
 *             └── Modifier son profil
 *
 *    ANONYMOUS (pas connecté)
 *         │
 *         └── Peut seulement voir le contenu public
 * ```
 *
 * VÉRIFICATION DES RÔLES :
 *
 * ```php
 * // L'utilisateur a-t-il le droit d'accéder ?
 * if (in_array('ROLE_ADMIN', $user->getRoles())) {
 *     // Afficher le panneau d'administration
 * } else {
 *     // Accès refusé !
 * }
 * ```
 *
 * @package    Lunar\Service\Security\Auth
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see Authenticator Service qui utilise cette interface
 * @see UserProviderInterface Interface pour charger les utilisateurs
 * @see AuthMiddleware Middleware de protection des routes
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

/**
 * Interface pour les utilisateurs authentifiés.
 *
 * Toute classe représentant un utilisateur dans le système d'authentification
 * DOIT implémenter cette interface. Elle définit les 4 informations essentielles
 * que le système a besoin pour gérer l'authentification et l'autorisation.
 *
 * =============================================================================
 * IMPLÉMENTATION MINIMALE
 * =============================================================================
 *
 * ```php
 * class User implements UserInterface
 * {
 *     private int $id;
 *     private string $email;
 *     private string $password;
 *     /** @var array<string> * /
 *     private array $roles;
 *
 *     public function __construct(
 *         int $id,
 *         string $email,
 *         string $password,
 *         array $roles = ['ROLE_USER']
 *     ) {
 *         $this->id = $id;
 *         $this->email = $email;
 *         $this->password = $password;
 *         $this->roles = $roles;
 *     }
 *
 *     public function getId(): int
 *     {
 *         return $this->id;
 *     }
 *
 *     public function getIdentifier(): string
 *     {
 *         return $this->email;  // On se connecte avec l'email
 *     }
 *
 *     public function getPassword(): string
 *     {
 *         return $this->password;  // Le hash, JAMAIS le clair !
 *     }
 *
 *     public function getRoles(): array
 *     {
 *         return $this->roles;
 *     }
 * }
 * ```
 *
 * =============================================================================
 * UTILISATION AVEC LE SYSTÈME D'AUTHENTIFICATION
 * =============================================================================
 *
 * ```php
 * // Le système d'authentification utilise cette interface
 * $authenticator = new Authenticator($userProvider, $hasher, $session);
 *
 * // Connexion
 * $user = $authenticator->attempt($email, $password);
 * if ($user !== null) {
 *     // $user est un objet qui implémente UserInterface
 *     echo "Bienvenue " . $user->getIdentifier();
 *     echo "Votre ID est " . $user->getId();
 *     echo "Vos rôles sont : " . implode(', ', $user->getRoles());
 * }
 *
 * // Vérification des droits
 * if (in_array('ROLE_ADMIN', $user->getRoles())) {
 *     // Afficher le menu admin
 * }
 * ```
 *
 * @package Lunar\Service\Security\Auth
 */
interface UserInterface
{
    /**
     * Retourne l'identifiant unique de l'utilisateur.
     *
     * =========================================================================
     * QU'EST-CE QUE L'ID ?
     * =========================================================================
     *
     * L'ID est le numéro UNIQUE qui identifie l'utilisateur en base de données.
     * C'est généralement la CLÉ PRIMAIRE de la table "users".
     *
     * CARACTÉRISTIQUES :
     * - UNIQUE : Deux utilisateurs ne peuvent pas avoir le même ID
     * - IMMUTABLE : L'ID ne change jamais (contrairement à l'email)
     * - TECHNIQUE : L'utilisateur ne le connaît pas forcément
     *
     * ```
     * TABLE users
     * ┌────────────┬─────────────────────┬──────────────────┐
     * │ id (PK)    │ email               │ password         │
     * ├────────────┼─────────────────────┼──────────────────┤
     * │ 1          │ alice@email.com     │ $2y$12$...       │
     * │ 2          │ bob@email.com       │ $2y$12$...       │
     * │ 3          │ charlie@email.com   │ $2y$12$...       │
     * └────────────┴─────────────────────┴──────────────────┘
     *   │
     *   └── C'est cette colonne qui est retournée par getId()
     * ```
     *
     * =========================================================================
     * POURQUOI STOCKER L'ID EN SESSION ?
     * =========================================================================
     *
     * Après la connexion, on stocke l'ID (pas l'email) en session car :
     * 1. L'ID ne change jamais
     * 2. L'email pourrait être modifié par l'utilisateur
     * 3. L'ID est plus rapide à rechercher (index primaire)
     *
     * ```php
     * // À la connexion
     * $_SESSION['user_id'] = $user->getId();  // Stocke 42, pas "alice@email.com"
     *
     * // Pour les requêtes suivantes
     * $user = $userProvider->loadById($_SESSION['user_id']);  // Recherche par ID
     * ```
     *
     * @return string|int L'identifiant unique de l'utilisateur.
     *                    Peut être un entier (auto-increment) ou une chaîne (UUID).
     *
     * @example
     * ```php
     * $user = $authenticator->user();
     * $id = $user->getId();
     *
     * // Utilisation pour une requête SQL
     * $sql = "SELECT * FROM orders WHERE user_id = ?";
     * $orders = $db->query($sql, [$id]);
     * ```
     */
    public function getId(): string|int;

    /**
     * Retourne l'identifiant de connexion (username ou email).
     *
     * =========================================================================
     * QU'EST-CE QUE L'IDENTIFIER ?
     * =========================================================================
     *
     * L'IDENTIFIER est ce que l'utilisateur TAPE pour se connecter.
     * C'est généralement l'email ou un nom d'utilisateur.
     *
     * ```
     * FORMULAIRE DE CONNEXION
     *
     *    ┌─────────────────────────────────┐
     *    │        Connexion                │
     *    ├─────────────────────────────────┤
     *    │                                 │
     *    │  Email ou username :            │
     *    │  ┌───────────────────────────┐  │
     *    │  │ alice@email.com           │  │  ← C'est l'IDENTIFIER
     *    │  └───────────────────────────┘  │
     *    │                                 │
     *    │  Mot de passe :                 │
     *    │  ┌───────────────────────────┐  │
     *    │  │ ••••••••••                │  │
     *    │  └───────────────────────────┘  │
     *    │                                 │
     *    │         [Se connecter]          │
     *    └─────────────────────────────────┘
     * ```
     *
     * =========================================================================
     * DIFFÉRENCE AVEC getId()
     * =========================================================================
     *
     * ```
     * getId()                           getIdentifier()
     * ──────                           ───────────────
     * Numéro technique                 Texte humainement lisible
     * Utilisé par le PROGRAMME         Utilisé par l'UTILISATEUR
     * Jamais affiché                   Affiché ("Bienvenue alice@...")
     * Immuable                         Peut être modifié
     *
     * Exemples :                       Exemples :
     * - 42                             - "alice@email.com"
     * - "550e8400-e29b..."             - "alice_wonderland"
     * ```
     *
     * @return string L'identifiant de connexion (email, username, téléphone...).
     *
     * @example
     * ```php
     * // Affichage du nom de l'utilisateur connecté
     * $user = $authenticator->user();
     * echo "Bienvenue " . $user->getIdentifier();
     * // → "Bienvenue alice@email.com"
     *
     * // Recherche d'un utilisateur pour la connexion
     * $user = $userProvider->loadByIdentifier($_POST['email']);
     * ```
     */
    public function getIdentifier(): string;

    /**
     * Retourne le mot de passe HACHÉ.
     *
     * =========================================================================
     * ATTENTION : HASH UNIQUEMENT !
     * =========================================================================
     *
     * Cette méthode retourne le mot de passe HACHÉ, jamais en clair !
     *
     * ```
     * ✅ BON : Retourne le hash
     * return "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X4bN1..."
     *
     * ❌ MAUVAIS : Retourne en clair (DANGER !)
     * return "MonMotDePasse123"
     * ```
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * Cette méthode est utilisée par le système d'authentification pour
     * comparer avec le mot de passe tapé :
     *
     * ```php
     * // Dans l'Authenticator (simplifié)
     * public function attempt(string $identifier, string $password): ?UserInterface
     * {
     *     $user = $this->userProvider->loadByIdentifier($identifier);
     *
     *     // Compare le mot de passe tapé avec le hash stocké
     *     if ($this->hasher->verify($password, $user->getPassword())) {
     *         return $user;  // Connexion réussie !
     *     }
     *
     *     return null;  // Mot de passe incorrect
     * }
     * ```
     *
     * @return string Le mot de passe HACHÉ (bcrypt ou Argon2id).
     *                Format typique : "$2y$12$..." ou "$argon2id$..."
     *
     * @example
     * ```php
     * // Vérification du mot de passe
     * $user = $userProvider->loadByIdentifier($email);
     * $hashStocké = $user->getPassword();  // "$2y$12$LQv3c..."
     *
     * if ($hasher->verify($motDePasseTapé, $hashStocké)) {
     *     echo "Mot de passe correct !";
     * }
     * ```
     */
    public function getPassword(): string;

    /**
     * Retourne les rôles assignés à cet utilisateur.
     *
     * =========================================================================
     * QU'EST-CE QU'UN RÔLE ?
     * =========================================================================
     *
     * Un RÔLE est une permission qui détermine ce que l'utilisateur peut faire.
     *
     * ANALOGIE : Les badges d'accès
     *
     * Imaginez un immeuble de bureaux avec différents badges :
     * - Badge "EMPLOYÉ" : accès au hall et aux toilettes
     * - Badge "MANAGER" : + accès aux salles de réunion
     * - Badge "DIRECTEUR" : + accès au bureau de direction
     *
     * ```
     * EXEMPLE DE RÔLES
     *
     *    Utilisateur Alice :
     *    ├── ROLE_USER        → Peut utiliser l'app normalement
     *    └── ROLE_ADMIN       → Peut accéder au panneau d'admin
     *
     *    Utilisateur Bob :
     *    └── ROLE_USER        → Peut seulement utiliser l'app
     *
     *    Utilisateur Charlie (super admin) :
     *    ├── ROLE_USER
     *    ├── ROLE_ADMIN
     *    └── ROLE_SUPER_ADMIN → Peut tout faire
     * ```
     *
     * =========================================================================
     * VÉRIFICATION DES RÔLES
     * =========================================================================
     *
     * ```php
     * $roles = $user->getRoles();
     *
     * // Vérification simple
     * if (in_array('ROLE_ADMIN', $roles)) {
     *     // Afficher le menu admin
     * }
     *
     * // Plusieurs rôles possibles
     * if (in_array('ROLE_ADMIN', $roles) || in_array('ROLE_MODERATOR', $roles)) {
     *     // Afficher le bouton "Supprimer"
     * }
     *
     * // Vérification avec le middleware
     * #[Route('/admin', middlewares: [AuthMiddleware::class])]
     * public function admin(): Response
     * {
     *     // Seuls les utilisateurs connectés arrivent ici
     *     // On vérifie ensuite le rôle
     *     if (!in_array('ROLE_ADMIN', $this->user->getRoles())) {
     *         return new Response('Accès refusé', 403);
     *     }
     *     // ...
     * }
     * ```
     *
     * =========================================================================
     * RÔLES RECOMMANDÉS
     * =========================================================================
     *
     * ┌───────────────────┬───────────────────────────────────────────────────┐
     * │  Rôle             │  Utilisation typique                              │
     * ├───────────────────┼───────────────────────────────────────────────────┤
     * │  ROLE_USER        │  Utilisateur standard (minimum pour tous)         │
     * │  ROLE_ADMIN       │  Administrateur de l'application                  │
     * │  ROLE_SUPER_ADMIN │  Super administrateur (gère les admins)           │
     * │  ROLE_MODERATOR   │  Modérateur (peut éditer/supprimer du contenu)    │
     * │  ROLE_EDITOR      │  Éditeur (peut créer/modifier du contenu)         │
     * │  ROLE_API         │  Accès API (pour les services externes)           │
     * └───────────────────┴───────────────────────────────────────────────────┘
     *
     * @return array<string> Tableau des rôles assignés à l'utilisateur.
     *                       Par convention, chaque rôle commence par "ROLE_".
     *
     * @example
     * ```php
     * // Création d'un utilisateur avec des rôles
     * $user = new User(
     *     id: 42,
     *     email: 'alice@email.com',
     *     password: $hash,
     *     roles: ['ROLE_USER', 'ROLE_ADMIN']
     * );
     *
     * // Récupération des rôles
     * $roles = $user->getRoles();
     * // → ['ROLE_USER', 'ROLE_ADMIN']
     *
     * // Affichage conditionnel
     * if (in_array('ROLE_ADMIN', $roles)) {
     *     echo '<a href="/admin">Administration</a>';
     * }
     * ```
     */
    public function getRoles(): array;
}
