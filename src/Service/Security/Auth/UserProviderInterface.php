<?php
/**
 * Lunar Quanta Framework - Interface de Fournisseur d'Utilisateurs.
 *
 * =============================================================================
 * QU'EST-CE QU'UN "USER PROVIDER" (FOURNISSEUR D'UTILISATEURS) ?
 * =============================================================================
 *
 * Un UserProvider est une classe qui sait COMMENT et OÙ aller chercher
 * les informations d'un utilisateur.
 *
 * ANALOGIE : L'annuaire téléphonique
 *
 * Imaginez que vous cherchez le numéro de téléphone de quelqu'un.
 * L'annuaire (UserProvider) sait :
 * - OÙ sont stockées les informations (pages jaunes, internet, carnet d'adresses)
 * - COMMENT les chercher (par nom, par adresse, par numéro)
 *
 * ```
 * RÔLE DU USER PROVIDER
 *
 *    Authenticator : "J'ai besoin de l'utilisateur alice@email.com"
 *         │
 *         ▼
 *    ┌─────────────────────────────────────┐
 *    │         UserProvider                │
 *    │  "Je sais où chercher !"            │
 *    │                                     │
 *    │  - Base de données SQL ?            │
 *    │  - Fichier JSON ?                   │
 *    │  - API externe ?                    │
 *    │  - LDAP / Active Directory ?        │
 *    └─────────────────────────────────────┘
 *         │
 *         ▼
 *    Utilisateur trouvé (ou null si inexistant)
 * ```
 *
 * =============================================================================
 * POURQUOI CETTE ABSTRACTION ?
 * =============================================================================
 *
 * Le système d'authentification NE SAIT PAS (et ne doit pas savoir) d'où
 * viennent les utilisateurs. Il demande simplement au UserProvider.
 *
 * AVANTAGES :
 *
 * 1. FLEXIBILITÉ : Changez de source de données sans modifier l'authentification
 * 2. TESTABILITÉ : En tests, utilisez un faux provider (mock) avec des utilisateurs en mémoire
 * 3. ÉVOLUTIVITÉ : Ajoutez de nouvelles sources (API, LDAP...) facilement
 *
 * ```
 * DIFFÉRENTES IMPLÉMENTATIONS POSSIBLES
 *
 *    UserProviderInterface
 *            │
 *            ├── DatabaseUserProvider
 *            │   └── Cherche dans MySQL/PostgreSQL
 *            │
 *            ├── JsonFileUserProvider
 *            │   └── Cherche dans un fichier users.json
 *            │
 *            ├── LdapUserProvider
 *            │   └── Cherche dans Active Directory
 *            │
 *            ├── ApiUserProvider
 *            │   └── Cherche via une API externe
 *            │
 *            └── InMemoryUserProvider
 *                └── Utilisateurs codés en dur (pour les tests)
 * ```
 *
 * =============================================================================
 * LES DEUX MÉTHODES DE RECHERCHE
 * =============================================================================
 *
 * Cette interface définit DEUX façons de chercher un utilisateur :
 *
 * ┌─────────────────────────┬────────────────────────────────────────────────┐
 * │  Méthode                │  Utilisation                                   │
 * ├─────────────────────────┼────────────────────────────────────────────────┤
 * │  loadByIdentifier()     │  À LA CONNEXION                                │
 * │                         │  L'utilisateur tape son email/username         │
 * │                         │  → On cherche par ce qu'il a tapé              │
 * ├─────────────────────────┼────────────────────────────────────────────────┤
 * │  loadById()             │  APRÈS LA CONNEXION                            │
 * │                         │  L'ID est stocké en session                    │
 * │                         │  → On recharge l'utilisateur par son ID        │
 * └─────────────────────────┴────────────────────────────────────────────────┘
 *
 * ```
 * FLUX COMPLET
 *
 *    ┌─────────────────────────────────────────────────────────────────────┐
 *    │  CONNEXION (1ère requête)                                           │
 *    │  ──────────────────────                                             │
 *    │  Utilisateur tape : email = "alice@email.com", password = "secret"  │
 *    │                              │                                      │
 *    │                              ▼                                      │
 *    │              loadByIdentifier("alice@email.com")                    │
 *    │                              │                                      │
 *    │                              ▼                                      │
 *    │              SELECT * FROM users WHERE email = ?                    │
 *    │                              │                                      │
 *    │                              ▼                                      │
 *    │              Utilisateur trouvé, ID = 42                            │
 *    │              Mot de passe vérifié → OK !                            │
 *    │              $_SESSION['user_id'] = 42                              │
 *    └─────────────────────────────────────────────────────────────────────┘
 *
 *    ┌─────────────────────────────────────────────────────────────────────┐
 *    │  REQUÊTES SUIVANTES (utilisateur déjà connecté)                     │
 *    │  ────────────────────────────────────────────                       │
 *    │  Session contient : user_id = 42                                    │
 *    │                              │                                      │
 *    │                              ▼                                      │
 *    │                     loadById(42)                                    │
 *    │                              │                                      │
 *    │                              ▼                                      │
 *    │              SELECT * FROM users WHERE id = 42                      │
 *    │                              │                                      │
 *    │                              ▼                                      │
 *    │              Utilisateur rechargé depuis la BDD                     │
 *    └─────────────────────────────────────────────────────────────────────┘
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
 * @see UserInterface Interface des utilisateurs retournés
 * @see Authenticator Service qui utilise le provider
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

/**
 * Interface pour les fournisseurs d'utilisateurs.
 *
 * Les fournisseurs d'utilisateurs sont responsables de charger les utilisateurs
 * depuis une source de données (base de données, fichier, API, mémoire, etc.).
 *
 * =============================================================================
 * IMPLÉMENTATION MINIMALE (EXEMPLE AVEC BASE DE DONNÉES)
 * =============================================================================
 *
 * ```php
 * class DatabaseUserProvider implements UserProviderInterface
 * {
 *     private PDO $pdo;
 *
 *     public function __construct(PDO $pdo)
 *     {
 *         $this->pdo = $pdo;
 *     }
 *
 *     public function loadByIdentifier(string $identifier): ?UserInterface
 *     {
 *         $stmt = $this->pdo->prepare(
 *             'SELECT * FROM users WHERE email = :email LIMIT 1'
 *         );
 *         $stmt->execute(['email' => $identifier]);
 *         $row = $stmt->fetch(PDO::FETCH_ASSOC);
 *
 *         if (!$row) {
 *             return null;  // Utilisateur non trouvé
 *         }
 *
 *         return new User(
 *             id: (int) $row['id'],
 *             email: $row['email'],
 *             password: $row['password'],
 *             roles: json_decode($row['roles'], true)
 *         );
 *     }
 *
 *     public function loadById(string|int $id): ?UserInterface
 *     {
 *         $stmt = $this->pdo->prepare(
 *             'SELECT * FROM users WHERE id = :id LIMIT 1'
 *         );
 *         $stmt->execute(['id' => $id]);
 *         $row = $stmt->fetch(PDO::FETCH_ASSOC);
 *
 *         if (!$row) {
 *             return null;
 *         }
 *
 *         return new User(
 *             id: (int) $row['id'],
 *             email: $row['email'],
 *             password: $row['password'],
 *             roles: json_decode($row['roles'], true)
 *         );
 *     }
 * }
 * ```
 *
 * =============================================================================
 * IMPLÉMENTATION POUR LES TESTS (EN MÉMOIRE)
 * =============================================================================
 *
 * ```php
 * class InMemoryUserProvider implements UserProviderInterface
 * {
 *     /** @var array<string, UserInterface> Utilisateurs indexés par identifier * /
 *     private array $usersByIdentifier = [];
 *
 *     /** @var array<string|int, UserInterface> Utilisateurs indexés par ID * /
 *     private array $usersById = [];
 *
 *     public function addUser(UserInterface $user): void
 *     {
 *         $this->usersByIdentifier[$user->getIdentifier()] = $user;
 *         $this->usersById[$user->getId()] = $user;
 *     }
 *
 *     public function loadByIdentifier(string $identifier): ?UserInterface
 *     {
 *         return $this->usersByIdentifier[$identifier] ?? null;
 *     }
 *
 *     public function loadById(string|int $id): ?UserInterface
 *     {
 *         return $this->usersById[$id] ?? null;
 *     }
 * }
 *
 * // Utilisation dans un test
 * $provider = new InMemoryUserProvider();
 * $provider->addUser(new User(1, 'test@example.com', $hashedPassword, ['ROLE_USER']));
 *
 * $authenticator = new Authenticator($provider, $hasher, $session);
 * $user = $authenticator->attempt('test@example.com', 'password');
 * ```
 *
 * @package Lunar\Service\Security\Auth
 */
interface UserProviderInterface
{
    /**
     * Charge un utilisateur par son identifiant de connexion.
     *
     * =========================================================================
     * QUAND UTILISER CETTE MÉTHODE ?
     * =========================================================================
     *
     * Cette méthode est utilisée lors de la CONNEXION, quand l'utilisateur
     * tape son email ou son username dans le formulaire de login.
     *
     * ```
     * FORMULAIRE DE CONNEXION
     *
     *    ┌─────────────────────────────┐
     *    │ Email : alice@email.com    │  ← C'est ce qu'on passe
     *    │ Mot de passe : ********    │     à loadByIdentifier()
     *    │         [Se connecter]      │
     *    └─────────────────────────────┘
     *                  │
     *                  ▼
     *    loadByIdentifier("alice@email.com")
     *                  │
     *                  ▼
     *    Retourne l'utilisateur ou null
     * ```
     *
     * =========================================================================
     * IMPLÉMENTATION TYPIQUE (SQL)
     * =========================================================================
     *
     * ```php
     * public function loadByIdentifier(string $identifier): ?UserInterface
     * {
     *     // Prépare une requête SQL sécurisée (évite l'injection SQL)
     *     $stmt = $this->pdo->prepare(
     *         'SELECT id, email, password, roles
     *          FROM users
     *          WHERE email = :identifier
     *          LIMIT 1'
     *     );
     *
     *     // Exécute avec le paramètre
     *     $stmt->execute(['identifier' => $identifier]);
     *
     *     // Récupère le résultat
     *     $row = $stmt->fetch(PDO::FETCH_ASSOC);
     *
     *     // Si aucun résultat, retourne null
     *     if (!$row) {
     *         return null;
     *     }
     *
     *     // Crée et retourne l'objet User
     *     return new User(
     *         id: (int) $row['id'],
     *         email: $row['email'],
     *         password: $row['password'],
     *         roles: json_decode($row['roles'], true) ?? ['ROLE_USER']
     *     );
     * }
     * ```
     *
     * @param string $identifier L'identifiant de connexion (email, username...).
     *                           C'est ce que l'utilisateur tape pour se connecter.
     *
     * @return UserInterface|null L'utilisateur trouvé, ou null s'il n'existe pas.
     *                            Retourner null (et non lancer une exception)
     *                            permet de distinguer "utilisateur inexistant"
     *                            de "erreur technique".
     *
     * @example
     * ```php
     * // Dans l'Authenticator
     * $user = $this->userProvider->loadByIdentifier($email);
     *
     * if ($user === null) {
     *     // Utilisateur non trouvé
     *     return null;
     * }
     *
     * // Vérifier le mot de passe...
     * ```
     */
    public function loadByIdentifier(string $identifier): ?UserInterface;

    /**
     * Charge un utilisateur par son ID unique.
     *
     * =========================================================================
     * QUAND UTILISER CETTE MÉTHODE ?
     * =========================================================================
     *
     * Cette méthode est utilisée APRÈS la connexion, quand on recharge
     * l'utilisateur depuis son ID stocké en session.
     *
     * ```
     * REQUÊTE D'UN UTILISATEUR DÉJÀ CONNECTÉ
     *
     *    Session PHP contient : user_id = 42
     *                  │
     *                  ▼
     *    loadById(42)
     *                  │
     *                  ▼
     *    SELECT * FROM users WHERE id = 42
     *                  │
     *                  ▼
     *    Retourne l'utilisateur complet
     * ```
     *
     * =========================================================================
     * POURQUOI RECHARGER L'UTILISATEUR À CHAQUE REQUÊTE ?
     * =========================================================================
     *
     * On ne stocke que l'ID en session (pas tout l'objet) car :
     *
     * 1. Les données peuvent avoir changé (rôles modifiés, compte désactivé...)
     * 2. Les objets sérialisés en session peuvent causer des problèmes
     * 3. On garde la session légère
     *
     * ```
     * ❌ MAUVAISE PRATIQUE : Stocker tout l'utilisateur en session
     *
     *    $_SESSION['user'] = serialize($user);  // Risqué !
     *
     * ✅ BONNE PRATIQUE : Stocker uniquement l'ID
     *
     *    $_SESSION['user_id'] = $user->getId();  // Sûr et léger
     * ```
     *
     * =========================================================================
     * IMPLÉMENTATION TYPIQUE (SQL)
     * =========================================================================
     *
     * ```php
     * public function loadById(string|int $id): ?UserInterface
     * {
     *     $stmt = $this->pdo->prepare(
     *         'SELECT id, email, password, roles
     *          FROM users
     *          WHERE id = :id
     *          LIMIT 1'
     *     );
     *
     *     $stmt->execute(['id' => $id]);
     *     $row = $stmt->fetch(PDO::FETCH_ASSOC);
     *
     *     if (!$row) {
     *         return null;  // L'utilisateur a peut-être été supprimé
     *     }
     *
     *     return new User(
     *         id: (int) $row['id'],
     *         email: $row['email'],
     *         password: $row['password'],
     *         roles: json_decode($row['roles'], true) ?? ['ROLE_USER']
     *     );
     * }
     * ```
     *
     * @param string|int $id L'identifiant unique de l'utilisateur.
     *                       Généralement un entier (auto-increment) ou
     *                       une chaîne (UUID).
     *
     * @return UserInterface|null L'utilisateur trouvé, ou null s'il n'existe pas.
     *                            Retourner null si l'utilisateur a été supprimé
     *                            depuis sa connexion (le déconnectera proprement).
     *
     * @example
     * ```php
     * // Dans l'Authenticator, pour récupérer l'utilisateur courant
     * public function user(): ?UserInterface
     * {
     *     $userId = $this->session->get('_auth_user_id');
     *
     *     if ($userId === null) {
     *         return null;  // Pas connecté
     *     }
     *
     *     return $this->userProvider->loadById($userId);
     * }
     * ```
     */
    public function loadById(string|int $id): ?UserInterface;
}
