<?php
/**
 * Lunar Quanta Framework - Service d'Authentification.
 *
 * =============================================================================
 * QU'EST-CE QUE L'AUTHENTIFICATION ?
 * =============================================================================
 *
 * L'AUTHENTIFICATION est le processus qui vérifie l'identité d'un utilisateur.
 *
 * ANALOGIE : L'entrée dans un immeuble sécurisé
 *
 * Imaginez un immeuble avec un gardien à l'entrée :
 * 1. Vous présentez votre badge (identifiant)
 * 2. Le gardien vérifie que le badge est valide (mot de passe)
 * 3. Si OK, il vous donne un bracelet temporaire (session)
 * 4. Pour les prochaines entrées, montrez juste le bracelet
 *
 * ```
 * AUTHENTIFICATION vs AUTORISATION
 *
 *    AUTHENTIFICATION              AUTORISATION
 *    ────────────────              ────────────
 *    "Qui êtes-vous ?"             "Qu'avez-vous le droit de faire ?"
 *                │                              │
 *                ▼                              ▼
 *    Vérifier l'identité           Vérifier les permissions
 *    (email + mot de passe)        (rôles : admin, user, etc.)
 *
 *    Exemples :                    Exemples :
 *    - Se connecter                - Accéder au panneau admin
 *    - Prouver son identité        - Supprimer un article
 *                                  - Voir les factures
 * ```
 *
 * =============================================================================
 * QU'EST-CE QUE CETTE CLASSE FAIT ?
 * =============================================================================
 *
 * L'Authenticator est le SERVICE PRINCIPAL qui gère l'authentification.
 * Il coordonne tous les éléments :
 *
 * ```
 * ARCHITECTURE DE L'AUTHENTIFICATION
 *
 *                    ┌─────────────────────────────────────┐
 *                    │         Authenticator               │
 *                    │  (Chef d'orchestre)                 │
 *                    └───────────────┬─────────────────────┘
 *                                    │
 *          ┌─────────────────────────┼─────────────────────────┐
 *          │                         │                         │
 *          ▼                         ▼                         ▼
 *    ┌───────────────┐     ┌─────────────────┐      ┌────────────────┐
 *    │ UserProvider  │     │ PasswordHasher  │      │    Session     │
 *    │               │     │                 │      │                │
 *    │ Cherche les   │     │ Vérifie les     │      │ Stocke l'état  │
 *    │ utilisateurs  │     │ mots de passe   │      │ de connexion   │
 *    └───────────────┘     └─────────────────┘      └────────────────┘
 *          │                         │                         │
 *          ▼                         ▼                         ▼
 *    Base de données        Hachage bcrypt         $_SESSION
 * ```
 *
 * =============================================================================
 * LES MÉTHODES PRINCIPALES
 * =============================================================================
 *
 * ┌───────────────────┬───────────────────────────────────────────────────────┐
 * │  Méthode          │  Description                                          │
 * ├───────────────────┼───────────────────────────────────────────────────────┤
 * │  attempt()        │  Tente de connecter avec email + mot de passe         │
 * │  login()          │  Connecte un utilisateur (stocke en session)          │
 * │  logout()         │  Déconnecte l'utilisateur (vide la session)           │
 * │  user()           │  Retourne l'utilisateur connecté (ou null)            │
 * │  check()          │  Vérifie si quelqu'un est connecté (bool)             │
 * │  guest()          │  Vérifie si personne n'est connecté (bool)            │
 * │  id()             │  Retourne l'ID de l'utilisateur connecté              │
 * │  validate()       │  Vérifie des identifiants SANS connecter              │
 * └───────────────────┴───────────────────────────────────────────────────────┘
 *
 * =============================================================================
 * SÉCURITÉ : PROTECTION CONTRE LA FIXATION DE SESSION
 * =============================================================================
 *
 * La FIXATION DE SESSION est une attaque où un pirate :
 * 1. Crée une session (obtient un ID de session)
 * 2. Vous fait utiliser CET ID de session (lien piégé)
 * 3. Quand vous vous connectez, le pirate a accès à votre session !
 *
 * PROTECTION : Régénérer l'ID de session à la connexion et déconnexion.
 *
 * ```
 * AVANT (vulnérable)
 *
 *    Pirate : session_id = "abc123"
 *    Vous vous connectez avec session_id = "abc123"
 *    → Pirate peut accéder à votre session !
 *
 * APRÈS (protégé avec régénération)
 *
 *    Pirate : session_id = "abc123"
 *    Vous vous connectez
 *    → RÉGÉNÉRATION : nouveau session_id = "xyz789"
 *    → Pirate ne peut plus accéder (il a toujours "abc123")
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
 * @see UserProviderInterface Pour charger les utilisateurs
 * @see PasswordHasherInterface Pour vérifier les mots de passe
 * @see SessionInterface Pour stocker l'état de connexion
 * @see AuthMiddleware Middleware qui utilise l'Authenticator
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

use Lunar\Service\Session\SessionInterface;

/**
 * Service principal d'authentification.
 *
 * Cette classe coordonne l'authentification des utilisateurs :
 * connexion, déconnexion, vérification de l'état de connexion,
 * récupération de l'utilisateur courant.
 *
 * =============================================================================
 * INJECTION DE DÉPENDANCES
 * =============================================================================
 *
 * L'Authenticator a besoin de 3 dépendances :
 *
 * ```php
 * $authenticator = new Authenticator(
 *     $userProvider,     // Où trouver les utilisateurs (BDD, fichier...)
 *     $passwordHasher,   // Comment vérifier les mots de passe
 *     $session           // Où stocker l'état de connexion
 * );
 * ```
 *
 * QU'EST-CE QUE L'INJECTION DE DÉPENDANCES ?
 *
 * Au lieu que la classe crée elle-même ses dépendances, on les lui "injecte"
 * (passe) via le constructeur. Cela permet :
 * - De changer facilement de base de données
 * - De tester avec de faux services (mocks)
 * - De personnaliser le comportement
 *
 * =============================================================================
 * UTILISATION COMPLÈTE
 * =============================================================================
 *
 * ```php
 * // ─────────────────────────────────────────────────────────────────────────
 * // CONFIGURATION (une seule fois, au démarrage)
 * // ─────────────────────────────────────────────────────────────────────────
 * $userProvider = new DatabaseUserProvider($pdo);
 * $passwordHasher = PasswordHasher::bcrypt(12);
 * $session = new SessionService();
 * $session->start();
 *
 * $auth = new Authenticator($userProvider, $passwordHasher, $session);
 *
 * // ─────────────────────────────────────────────────────────────────────────
 * // CONNEXION (page de login)
 * // ─────────────────────────────────────────────────────────────────────────
 * $user = $auth->attempt($_POST['email'], $_POST['password']);
 *
 * if ($user !== null) {
 *     // Connexion réussie !
 *     header('Location: /dashboard');
 * } else {
 *     echo "Email ou mot de passe incorrect";
 * }
 *
 * // ─────────────────────────────────────────────────────────────────────────
 * // VÉRIFICATION (sur les pages protégées)
 * // ─────────────────────────────────────────────────────────────────────────
 * if ($auth->check()) {
 *     $user = $auth->user();
 *     echo "Bienvenue " . $user->getIdentifier();
 * } else {
 *     header('Location: /login');
 * }
 *
 * // ─────────────────────────────────────────────────────────────────────────
 * // DÉCONNEXION
 * // ─────────────────────────────────────────────────────────────────────────
 * $auth->logout();
 * header('Location: /');
 * ```
 *
 * @package Lunar\Service\Security\Auth
 */
class Authenticator
{
    /**
     * Clé de session pour stocker l'ID de l'utilisateur connecté.
     *
     * Le préfixe underscore indique une clé "système" (pas utilisateur).
     *
     * @var string
     */
    private const SESSION_USER_KEY = '_auth_user_id';

    /**
     * Service qui fournit les utilisateurs.
     *
     * C'est lui qui sait OÙ et COMMENT chercher les utilisateurs
     * (base de données, fichier, API, etc.).
     *
     * @var UserProviderInterface
     */
    private UserProviderInterface $userProvider;

    /**
     * Service de hachage de mots de passe.
     *
     * C'est lui qui sait COMMENT vérifier qu'un mot de passe en clair
     * correspond au hash stocké en base de données.
     *
     * @var PasswordHasherInterface
     */
    private PasswordHasherInterface $passwordHasher;

    /**
     * Service de gestion des sessions.
     *
     * C'est lui qui STOCKE l'état de connexion entre les requêtes.
     * Après connexion, l'ID de l'utilisateur est stocké en session.
     *
     * @var SessionInterface
     */
    private SessionInterface $session;

    /**
     * Crée un nouveau service d'authentification.
     *
     * =========================================================================
     * PARAMÈTRES
     * =========================================================================
     *
     * @param UserProviderInterface $userProvider Service pour charger les utilisateurs.
     *                                            Exemple : DatabaseUserProvider
     *
     * @param PasswordHasherInterface $passwordHasher Service pour vérifier les mots de passe.
     *                                                Exemple : PasswordHasher::bcrypt(12)
     *
     * @param SessionInterface $session Service de gestion des sessions.
     *                                  Doit être démarré avant utilisation.
     *
     * =========================================================================
     * EXEMPLE
     * =========================================================================
     *
     * ```php
     * // Création des dépendances
     * $pdo = new PDO('mysql:host=localhost;dbname=app', 'user', 'pass');
     * $userProvider = new DatabaseUserProvider($pdo);
     * $passwordHasher = PasswordHasher::bcrypt(12);
     *
     * $session = new SessionService();
     * $session->start();
     *
     * // Création de l'authenticator
     * $auth = new Authenticator($userProvider, $passwordHasher, $session);
     * ```
     */
    public function __construct(
        UserProviderInterface $userProvider,
        PasswordHasherInterface $passwordHasher,
        SessionInterface $session
    ) {
        $this->userProvider = $userProvider;
        $this->passwordHasher = $passwordHasher;
        $this->session = $session;
    }

    /**
     * Tente de connecter un utilisateur avec ses identifiants.
     *
     * =========================================================================
     * QUE FAIT CETTE MÉTHODE ?
     * =========================================================================
     *
     * 1. Cherche l'utilisateur par son identifiant (email/username)
     * 2. Vérifie que le mot de passe correspond au hash stocké
     * 3. Si OK, connecte l'utilisateur (stocke en session)
     * 4. Retourne l'utilisateur ou null si échec
     *
     * ```
     * FLUX DE attempt()
     *
     *    attempt("alice@email.com", "MonMDP123")
     *                    │
     *                    ▼
     *    ┌───────────────────────────────────────┐
     *    │  1. Cherche l'utilisateur             │
     *    │     userProvider->loadByIdentifier()  │
     *    └────────────────┬──────────────────────┘
     *                     │
     *           ┌─────────┴─────────┐
     *           │                   │
     *     Utilisateur          Pas trouvé
     *       trouvé                  │
     *           │                   ▼
     *           │              return null
     *           ▼
     *    ┌───────────────────────────────────────┐
     *    │  2. Vérifie le mot de passe           │
     *    │     passwordHasher->verify()          │
     *    └────────────────┬──────────────────────┘
     *                     │
     *           ┌─────────┴─────────┐
     *           │                   │
     *      Mot de passe        Mot de passe
     *        correct            incorrect
     *           │                   │
     *           │                   ▼
     *           │              return null
     *           ▼
     *    ┌───────────────────────────────────────┐
     *    │  3. Connecte l'utilisateur            │
     *    │     login($user)                      │
     *    └────────────────┬──────────────────────┘
     *                     │
     *                     ▼
     *              return $user
     * ```
     *
     * =========================================================================
     * REHACHAGE AUTOMATIQUE
     * =========================================================================
     *
     * Si le mot de passe est correct mais que le hash utilise d'anciens
     * paramètres, on détecte qu'il faut le rehacher. Le rehachage effectif
     * doit être fait par l'application (car elle connaît le UserRepository).
     *
     * @param string $identifier L'identifiant de connexion (email, username).
     * @param string $password Le mot de passe en clair.
     *
     * @return UserInterface|null L'utilisateur connecté, ou null si échec.
     *
     * @example
     * ```php
     * // Dans le contrôleur de login
     * public function login(Request $request): Response
     * {
     *     $email = $request->getPostParams()['email'] ?? '';
     *     $password = $request->getPostParams()['password'] ?? '';
     *
     *     $user = $this->auth->attempt($email, $password);
     *
     *     if ($user !== null) {
     *         return new Response('', 302, ['Location: /dashboard']);
     *     }
     *
     *     return new Response('Identifiants incorrects', 401);
     * }
     * ```
     */
    public function attempt(string $identifier, string $password): ?UserInterface
    {
        // 1. Cherche l'utilisateur par son identifiant (email, username...)
        $user = $this->userProvider->loadByIdentifier($identifier);

        // Si l'utilisateur n'existe pas, échec
        if (null === $user) {
            return null;
        }

        // 2. Vérifie que le mot de passe correspond au hash stocké
        if (!$this->passwordHasher->verify($password, $user->getPassword())) {
            return null;
        }

        // 3. Vérifie si le hash doit être mis à jour (paramètres obsolètes)
        // Note : L'application doit gérer le rehachage effectif
        // car nous n'avons pas accès au repository pour sauvegarder
        if ($this->passwordHasher->needsRehash($user->getPassword())) {
            // L'application peut :
            // 1. Récupérer le nouveau hash : $hasher->hash($password)
            // 2. Mettre à jour l'utilisateur : $user->setPassword($newHash)
            // 3. Sauvegarder : $repository->save($user)
        }

        // 4. Connecte l'utilisateur (stocke en session)
        $this->login($user);

        return $user;
    }

    /**
     * Connecte un utilisateur (sans vérifier le mot de passe).
     *
     * =========================================================================
     * QUAND UTILISER CETTE MÉTHODE ?
     * =========================================================================
     *
     * Cette méthode est utile pour :
     * - Connexion automatique après inscription
     * - Connexion via OAuth (Google, Facebook, etc.)
     * - Impersonation d'un utilisateur (admin qui teste)
     *
     * ```php
     * // Après inscription
     * $user = $userService->createUser($email, $password);
     * $auth->login($user);  // Connecte directement (pas besoin de mot de passe)
     *
     * // Connexion OAuth
     * $user = $userService->findOrCreateFromOAuth($oauthData);
     * $auth->login($user);
     * ```
     *
     * =========================================================================
     * SÉCURITÉ : RÉGÉNÉRATION DE L'ID DE SESSION
     * =========================================================================
     *
     * Cette méthode appelle session->regenerate() pour générer un nouvel
     * ID de session. C'est une protection contre la fixation de session.
     *
     * @param UserInterface $user L'utilisateur à connecter.
     *
     * @return void
     *
     * @example
     * ```php
     * // Connexion après inscription
     * public function register(Request $request): Response
     * {
     *     // Créer l'utilisateur...
     *     $user = $this->userService->create($data);
     *
     *     // Le connecter automatiquement
     *     $this->auth->login($user);
     *
     *     return new Response('', 302, ['Location: /welcome']);
     * }
     * ```
     */
    public function login(UserInterface $user): void
    {
        // Régénère l'ID de session pour prévenir la fixation de session
        // IMPORTANT pour la sécurité !
        $this->session->regenerate();

        // Stocke l'ID de l'utilisateur en session
        // On ne stocke QUE l'ID, pas tout l'objet (pour des raisons de sécurité et performance)
        $this->session->set(self::SESSION_USER_KEY, $user->getId());
    }

    /**
     * Déconnecte l'utilisateur courant.
     *
     * =========================================================================
     * QUE FAIT CETTE MÉTHODE ?
     * =========================================================================
     *
     * 1. Supprime l'ID utilisateur de la session
     * 2. Régénère l'ID de session (sécurité)
     *
     * ```
     * AVANT logout()                APRÈS logout()
     * ─────────────                ──────────────
     * $_SESSION = [                $_SESSION = []
     *   '_auth_user_id' => 42,     (vide, nouvel ID de session)
     *   'cart' => [...],
     * ]
     * ```
     *
     * La régénération de l'ID de session à la déconnexion empêche :
     * - La réutilisation de l'ancienne session
     * - Les attaques où un pirate aurait récupéré l'ancien ID
     *
     * @return void
     *
     * @example
     * ```php
     * // Dans le contrôleur de déconnexion
     * public function logout(Request $request): Response
     * {
     *     $this->auth->logout();
     *
     *     // Message flash pour la prochaine page
     *     $session = $request->getAttribute('session');
     *     $session->setFlash('success', 'Vous êtes déconnecté.');
     *
     *     return new Response('', 302, ['Location: /']);
     * }
     * ```
     */
    public function logout(): void
    {
        // Supprime l'ID utilisateur de la session
        $this->session->remove(self::SESSION_USER_KEY);

        // Régénère l'ID de session pour la sécurité
        $this->session->regenerate();
    }

    /**
     * Retourne l'utilisateur actuellement connecté.
     *
     * =========================================================================
     * COMMENT ÇA MARCHE ?
     * =========================================================================
     *
     * 1. Récupère l'ID stocké en session
     * 2. Charge l'utilisateur complet depuis le UserProvider
     * 3. Retourne l'utilisateur (ou null si pas connecté)
     *
     * ```
     * FLUX DE user()
     *
     *    user()
     *      │
     *      ▼
     *    Récupère $_SESSION['_auth_user_id']
     *      │
     *      ├── null → return null (pas connecté)
     *      │
     *      └── 42 → userProvider->loadById(42)
     *                    │
     *                    ▼
     *              return User (ou null si supprimé)
     * ```
     *
     * =========================================================================
     * POURQUOI RECHARGER L'UTILISATEUR ?
     * =========================================================================
     *
     * On recharge l'utilisateur depuis la BDD à chaque requête car :
     * - Les données peuvent avoir changé (rôles, email...)
     * - Le compte peut avoir été désactivé/supprimé
     * - On évite les problèmes de sérialisation d'objets en session
     *
     * @return UserInterface|null L'utilisateur connecté, ou null si pas de connexion.
     *
     * @example
     * ```php
     * // Dans un contrôleur
     * public function profile(Request $request): Response
     * {
     *     $user = $this->auth->user();
     *
     *     if ($user === null) {
     *         return new Response('', 302, ['Location: /login']);
     *     }
     *
     *     return $this->render('profile.html', [
     *         'user' => $user,
     *         'email' => $user->getIdentifier(),
     *         'roles' => $user->getRoles(),
     *     ]);
     * }
     * ```
     */
    public function user(): ?UserInterface
    {
        // Récupère l'ID de l'utilisateur depuis la session
        $userId = $this->session->get(self::SESSION_USER_KEY);

        // Si pas d'ID en session, personne n'est connecté
        if (null === $userId) {
            return null;
        }

        // Vérifie que l'ID est du bon type (string ou int)
        if (!is_string($userId) && !is_int($userId)) {
            return null;
        }

        // Charge et retourne l'utilisateur complet depuis le provider
        // Peut retourner null si l'utilisateur a été supprimé entre-temps
        return $this->userProvider->loadById($userId);
    }

    /**
     * Vérifie si un utilisateur est actuellement connecté.
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * C'est une méthode pratique qui retourne un booléen simple.
     * Plus lisible que `$auth->user() !== null`.
     *
     * ```php
     * // Au lieu de :
     * if ($auth->user() !== null) { ... }
     *
     * // Préférez :
     * if ($auth->check()) { ... }
     * ```
     *
     * @return bool true si quelqu'un est connecté, false sinon.
     *
     * @example
     * ```php
     * // Protection d'une page
     * if (!$auth->check()) {
     *     header('Location: /login');
     *     exit;
     * }
     *
     * // Affichage conditionnel dans un template
     * if ($auth->check()) {
     *     echo '<a href="/profile">Mon profil</a>';
     *     echo '<a href="/logout">Déconnexion</a>';
     * } else {
     *     echo '<a href="/login">Connexion</a>';
     *     echo '<a href="/register">Inscription</a>';
     * }
     * ```
     */
    public function check(): bool
    {
        // Vérifie si user() retourne un utilisateur (pas null)
        return null !== $this->user();
    }

    /**
     * Vérifie si aucun utilisateur n'est connecté (visiteur/invité).
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * C'est l'inverse de check(). Utile pour les pages réservées aux
     * visiteurs (login, register...).
     *
     * ```php
     * // Au lieu de :
     * if (!$auth->check()) { ... }
     *
     * // Préférez :
     * if ($auth->guest()) { ... }
     * ```
     *
     * @return bool true si personne n'est connecté, false sinon.
     *
     * @example
     * ```php
     * // Redirection si déjà connecté (page de login)
     * public function showLogin(Request $request): Response
     * {
     *     // Si déjà connecté, rediriger vers le dashboard
     *     if (!$this->auth->guest()) {
     *         return new Response('', 302, ['Location: /dashboard']);
     *     }
     *
     *     return $this->render('login.html');
     * }
     * ```
     */
    public function guest(): bool
    {
        // L'inverse de check()
        return !$this->check();
    }

    /**
     * Retourne l'ID de l'utilisateur connecté.
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * Cette méthode est utile quand on a besoin uniquement de l'ID,
     * sans charger tout l'objet utilisateur (performance).
     *
     * @return string|int|null L'ID de l'utilisateur, ou null si pas connecté.
     *
     * @example
     * ```php
     * // Récupérer les commandes de l'utilisateur connecté
     * $userId = $auth->id();
     *
     * if ($userId !== null) {
     *     $orders = $orderRepository->findByUserId($userId);
     * }
     *
     * // Journaliser une action
     * $logger->info('Action effectuée', ['user_id' => $auth->id()]);
     * ```
     */
    public function id(): string|int|null
    {
        // Récupère directement l'ID depuis la session
        // Sans passer par user() (évite le chargement complet)
        $userId = $this->session->get(self::SESSION_USER_KEY);

        // Vérifie le type
        if (!is_string($userId) && !is_int($userId)) {
            return null;
        }

        return $userId;
    }

    /**
     * Valide des identifiants SANS connecter l'utilisateur.
     *
     * =========================================================================
     * UTILISATION
     * =========================================================================
     *
     * Cette méthode est utile pour :
     * - Vérifier le mot de passe actuel avant un changement
     * - Valider des identifiants sans créer de session
     * - Authentification pour des opérations sensibles
     *
     * ```php
     * // Changement de mot de passe
     * // On demande l'ancien mot de passe pour confirmer l'identité
     * public function changePassword(Request $request): Response
     * {
     *     $oldPassword = $request->getPostParams()['old_password'];
     *     $newPassword = $request->getPostParams()['new_password'];
     *
     *     // Vérifie l'ancien mot de passe (sans déconnecter/reconnecter)
     *     if (!$this->auth->validate($user->getIdentifier(), $oldPassword)) {
     *         return new Response('Ancien mot de passe incorrect', 400);
     *     }
     *
     *     // Change le mot de passe...
     * }
     * ```
     *
     * @param string $identifier L'identifiant de connexion.
     * @param string $password Le mot de passe à vérifier.
     *
     * @return bool true si les identifiants sont valides, false sinon.
     *
     * @example
     * ```php
     * // Confirmation avant suppression de compte
     * public function deleteAccount(Request $request): Response
     * {
     *     $password = $request->getPostParams()['password'];
     *     $user = $this->auth->user();
     *
     *     if (!$this->auth->validate($user->getIdentifier(), $password)) {
     *         return new Response('Mot de passe incorrect', 403);
     *     }
     *
     *     // Supprimer le compte...
     * }
     * ```
     */
    public function validate(string $identifier, string $password): bool
    {
        // Cherche l'utilisateur
        $user = $this->userProvider->loadByIdentifier($identifier);

        // Si pas trouvé, invalide
        if (null === $user) {
            return false;
        }

        // Vérifie le mot de passe sans connecter
        return $this->passwordHasher->verify($password, $user->getPassword());
    }
}
