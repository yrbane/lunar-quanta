<?php
/**
 * Lunar Quanta Framework - Interface de Gestion des Sessions.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE SESSION ? (Session HTTP)
 * =============================================================================
 *
 * HTTP est un protocole SANS ÉTAT (stateless). Cela signifie que le serveur
 * "oublie" tout entre chaque requête. Chaque requête est indépendante.
 *
 * PROBLÈME : Comment se souvenir qu'un utilisateur est connecté ?
 * SOLUTION : Les SESSIONS !
 *
 * Une SESSION est un mécanisme qui permet de conserver des données
 * entre plusieurs requêtes du même utilisateur.
 *
 * ANALOGIE : Pensez à un vestiaire
 *
 * ```
 *  CLIENT (Navigateur)                    SERVEUR (PHP)
 *
 *  1. Première visite
 *     ──────────────────────────────────►
 *                                         "Je crée un casier #ABC123"
 *                                         "Je donne le ticket au client"
 *     ◄── Cookie: PHPSESSID=ABC123 ─────
 *
 *  2. Deuxième visite
 *     ── Cookie: PHPSESSID=ABC123 ─────►
 *                                         "Le client a le ticket #ABC123"
 *                                         "Je retrouve son casier"
 *                                         "Je lis ses affaires (données)"
 *     ◄─────────────────────────────────
 * ```
 *
 * Le COOKIE contient l'identifiant de session (ticket).
 * Le SERVEUR stocke les vraies données (casier).
 *
 * =============================================================================
 * FONCTIONNEMENT TECHNIQUE
 * =============================================================================
 *
 * 1. L'utilisateur visite le site
 * 2. PHP génère un identifiant unique (ex: "abc123xyz")
 * 3. PHP envoie un cookie au navigateur : PHPSESSID=abc123xyz
 * 4. PHP crée un fichier sur le serveur : /tmp/sess_abc123xyz
 * 5. Les données de session sont stockées dans ce fichier
 *
 * À chaque requête suivante :
 * - Le navigateur envoie le cookie PHPSESSID
 * - PHP retrouve le fichier correspondant
 * - PHP charge les données en mémoire ($_SESSION)
 *
 * ```
 *  ┌─────────────────────────────────────────────────────────────────────────┐
 *  │                    CYCLE DE VIE D'UNE SESSION                           │
 *  │                                                                         │
 *  │  ┌──────────────┐     Cookie      ┌──────────────┐                     │
 *  │  │  NAVIGATEUR  │ ◄─────────────► │   SERVEUR    │                     │
 *  │  │              │   PHPSESSID     │              │                     │
 *  │  │              │   =abc123       │              │                     │
 *  │  └──────────────┘                 └──────┬───────┘                     │
 *  │                                          │                              │
 *  │                                          │ Lit/écrit                    │
 *  │                                          ▼                              │
 *  │                                   ┌──────────────┐                     │
 *  │                                   │ Fichier      │                     │
 *  │                                   │ sess_abc123  │                     │
 *  │                                   │ ─────────────│                     │
 *  │                                   │ user_id=42   │                     │
 *  │                                   │ username=Jean│                     │
 *  │                                   └──────────────┘                     │
 *  └─────────────────────────────────────────────────────────────────────────┘
 * ```
 *
 * =============================================================================
 * QU'EST-CE QU'UN MESSAGE FLASH ?
 * =============================================================================
 *
 * Un MESSAGE FLASH est un message qui n'existe que pour UNE SEULE requête.
 * Après avoir été lu, il disparaît automatiquement.
 *
 * CAS D'UTILISATION TYPIQUE :
 * 1. L'utilisateur soumet un formulaire
 * 2. Le serveur traite le formulaire
 * 3. Le serveur stocke un message flash "Formulaire enregistré !"
 * 4. Le serveur redirige vers une autre page
 * 5. La nouvelle page affiche le message flash
 * 6. Le message est automatiquement supprimé
 *
 * ```
 *  POST /inscription
 *       │
 *       ▼
 *  ┌─────────────────────────────┐
 *  │ Inscription réussie !       │
 *  │ $session->flash('success',  │
 *  │   'Bienvenue parmi nous !');│
 *  │ Redirect → /accueil         │
 *  └─────────────────────────────┘
 *       │
 *       ▼
 *  GET /accueil
 *       │
 *       ▼
 *  ┌─────────────────────────────┐
 *  │ $msg = $session->getFlash(  │
 *  │   'success');               │
 *  │ → "Bienvenue parmi nous !"  │
 *  │                             │
 *  │ (message supprimé après     │
 *  │  lecture)                   │
 *  └─────────────────────────────┘
 *       │
 *       ▼
 *  GET /accueil (refresh)
 *       │
 *       ▼
 *  ┌─────────────────────────────┐
 *  │ $msg = $session->getFlash(  │
 *  │   'success');               │
 *  │ → null (plus de message)    │
 *  └─────────────────────────────┘
 * ```
 *
 * Sans messages flash, si l'utilisateur rafraîchit la page, il verrait
 * le même message plusieurs fois, ce qui serait confus.
 *
 * =============================================================================
 * SÉCURITÉ DES SESSIONS
 * =============================================================================
 *
 * Les sessions sont une cible privilégiée des attaquants. Voici les risques :
 *
 * 1. SESSION HIJACKING (vol de session)
 *    L'attaquant vole l'identifiant de session d'un utilisateur légitime.
 *    PROTECTION : HttpOnly (empêche JavaScript de lire le cookie)
 *
 * 2. SESSION FIXATION
 *    L'attaquant force un utilisateur à utiliser un ID de session connu.
 *    PROTECTION : Régénérer l'ID après la connexion (regenerate())
 *
 * 3. CSRF (Cross-Site Request Forgery)
 *    L'attaquant fait exécuter des actions au nom de l'utilisateur.
 *    PROTECTION : Tokens CSRF (voir CsrfMiddleware)
 *
 * @package    Lunar\Service\Session
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see SessionService Implémentation concrète de cette interface
 * @see SessionMiddleware Middleware qui démarre la session automatiquement
 */
declare(strict_types=1);

namespace Lunar\Service\Session;

/**
 * Interface pour la gestion des sessions HTTP.
 *
 * Cette interface définit le CONTRAT que tout service de session doit respecter.
 * Elle garantit une API cohérente pour stocker et récupérer des données
 * entre les requêtes.
 *
 * =============================================================================
 * POURQUOI UNE INTERFACE ?
 * =============================================================================
 *
 * En définissant une interface, on permet :
 *
 * 1. INTERCHANGEABILITÉ
 *    On peut remplacer SessionService par une autre implémentation
 *    (Redis, Memcached, base de données...) sans changer le reste du code.
 *
 * 2. TESTABILITÉ
 *    On peut créer une implémentation "mock" pour les tests,
 *    sans démarrer de vraie session PHP.
 *
 * 3. DOCUMENTATION
 *    L'interface documente clairement les méthodes disponibles.
 *
 * ```php
 * // Le contrôleur ne dépend que de l'interface
 * class UserController
 * {
 *     public function __construct(private SessionInterface $session) { }
 *
 *     public function profile(Request $request): Response
 *     {
 *         $userId = $this->session->get('user_id');
 *         // ...
 *     }
 * }
 *
 * // En production : vraie session PHP
 * new UserController(new SessionService());
 *
 * // En test : session en mémoire
 * new UserController(new SessionService(testMode: true));
 *
 * // Alternative : session Redis (hypothétique)
 * new UserController(new RedisSessionService($redis));
 * ```
 *
 * @package Lunar\Service\Session
 */
interface SessionInterface
{
    /**
     * Démarre la session.
     *
     * =========================================================================
     * QUE FAIT start() ?
     * =========================================================================
     *
     * Cette méthode initialise la session PHP :
     *
     * 1. Vérifie si une session est déjà active
     * 2. Lit le cookie PHPSESSID du navigateur (s'il existe)
     * 3. Charge les données de session existantes
     * 4. Si nouveau visiteur : génère un nouvel ID et crée le fichier session
     *
     * IMPORTANT : start() doit être appelé AVANT d'utiliser la session !
     *
     * En pratique, le SessionMiddleware appelle start() automatiquement
     * au début de chaque requête.
     *
     * @return void Cette méthode ne retourne rien.
     *
     * @example
     * ```php
     * $session = new SessionService();
     * $session->start();  // Initialise la session
     *
     * // Maintenant on peut utiliser get/set
     * $session->set('user_id', 42);
     * ```
     */
    public function start(): void;

    /**
     * Récupère une valeur de la session.
     *
     * =========================================================================
     * LECTURE DES DONNÉES
     * =========================================================================
     *
     * Retourne la valeur associée à la clé donnée.
     * Si la clé n'existe pas, retourne la valeur par défaut.
     *
     * ```php
     * // Avec valeur par défaut
     * $theme = $session->get('theme', 'light');
     * // → 'dark' si défini, sinon 'light'
     *
     * // Sans valeur par défaut (retourne null)
     * $userId = $session->get('user_id');
     * // → null si non défini
     * ```
     *
     * @param string $key     La clé de la donnée à récupérer.
     *                        Ex: 'user_id', 'cart', 'preferences'
     *
     * @param mixed  $default Valeur retournée si la clé n'existe pas.
     *                        Par défaut : null.
     *
     * @return mixed La valeur stockée, ou $default si absente.
     *               Le type dépend de ce qui a été stocké.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Stocke une valeur dans la session.
     *
     * =========================================================================
     * ÉCRITURE DES DONNÉES
     * =========================================================================
     *
     * Enregistre une valeur associée à une clé.
     * Si la clé existe déjà, sa valeur est remplacée.
     *
     * Les données peuvent être de n'importe quel type sérialisable :
     * - Scalaires : int, float, string, bool
     * - Tableaux : array
     * - Objets : (si sérialisables)
     *
     * ```php
     * // Stocker un ID utilisateur
     * $session->set('user_id', 42);
     *
     * // Stocker un tableau
     * $session->set('cart', [
     *     ['product_id' => 1, 'quantity' => 2],
     *     ['product_id' => 5, 'quantity' => 1],
     * ]);
     *
     * // Stocker un objet (si sérialisable)
     * $session->set('preferences', new UserPreferences());
     * ```
     *
     * @param string $key   La clé sous laquelle stocker la valeur.
     * @param mixed  $value La valeur à stocker (tout type sérialisable).
     *
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Vérifie si une clé existe dans la session.
     *
     * =========================================================================
     * VÉRIFICATION D'EXISTENCE
     * =========================================================================
     *
     * Retourne true si la clé existe, même si sa valeur est null ou false.
     *
     * DIFFÉRENCE IMPORTANTE :
     *
     * ```php
     * $session->set('key1', null);  // Existe avec valeur null
     * $session->set('key2', false); // Existe avec valeur false
     * // 'key3' n'est pas défini
     *
     * $session->has('key1');  // true (la clé existe)
     * $session->has('key2');  // true (la clé existe)
     * $session->has('key3');  // false (la clé n'existe pas)
     *
     * $session->get('key1');  // null
     * $session->get('key2');  // false
     * $session->get('key3');  // null (valeur par défaut)
     * ```
     *
     * @param string $key La clé à vérifier.
     *
     * @return bool true si la clé existe, false sinon.
     */
    public function has(string $key): bool;

    /**
     * Supprime une valeur de la session.
     *
     * =========================================================================
     * SUPPRESSION DE DONNÉES
     * =========================================================================
     *
     * Retire la clé et sa valeur de la session.
     * Si la clé n'existe pas, aucune erreur n'est levée.
     *
     * ```php
     * // Déconnexion partielle : supprime les infos utilisateur
     * $session->remove('user_id');
     * $session->remove('user_name');
     * $session->remove('user_permissions');
     * ```
     *
     * @param string $key La clé à supprimer.
     *
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Définit un message flash (disponible uniquement pour la prochaine requête).
     *
     * =========================================================================
     * MESSAGES ÉPHÉMÈRES
     * =========================================================================
     *
     * Les messages flash sont automatiquement supprimés après avoir été lus.
     * Ils sont parfaits pour :
     * - Messages de succès après un formulaire
     * - Messages d'erreur après une validation
     * - Notifications diverses après une redirection
     *
     * ```php
     * // Après un enregistrement réussi
     * $session->flash('success', 'Votre compte a été créé !');
     *
     * // Après une erreur
     * $session->flash('error', 'Email déjà utilisé');
     *
     * // Plusieurs messages
     * $session->flash('info', 'Vous avez 3 nouveaux messages');
     * $session->flash('warning', 'Votre abonnement expire bientôt');
     * ```
     *
     * @param string $key   La clé du message flash (ex: 'success', 'error', 'info').
     * @param mixed  $value Le contenu du message (généralement une string).
     *
     * @return void
     */
    public function flash(string $key, mixed $value): void;

    /**
     * Récupère un message flash et le supprime.
     *
     * =========================================================================
     * LECTURE DESTRUCTRICE
     * =========================================================================
     *
     * Cette méthode :
     * 1. Récupère la valeur du message flash
     * 2. Supprime automatiquement le message
     *
     * Un message flash ne peut être lu qu'UNE SEULE fois.
     *
     * ```php
     * // Dans le template après une redirection
     *
     * $success = $session->getFlash('success');
     * if ($success) {
     *     echo '<div class="alert-success">' . htmlspecialchars($success) . '</div>';
     * }
     *
     * $error = $session->getFlash('error');
     * if ($error) {
     *     echo '<div class="alert-error">' . htmlspecialchars($error) . '</div>';
     * }
     *
     * // Si l'utilisateur rafraîchit la page, les messages ont disparu
     * ```
     *
     * @param string $key     La clé du message flash à récupérer.
     * @param mixed  $default Valeur par défaut si le message n'existe pas.
     *
     * @return mixed Le message flash, ou $default s'il n'existe pas.
     */
    public function getFlash(string $key, mixed $default = null): mixed;

    /**
     * Régénère l'identifiant de session.
     *
     * =========================================================================
     * PROTECTION CONTRE LE SESSION FIXATION
     * =========================================================================
     *
     * Le SESSION FIXATION est une attaque où :
     * 1. L'attaquant crée une session et obtient un ID
     * 2. L'attaquant force la victime à utiliser cet ID
     * 3. La victime se connecte
     * 4. L'attaquant utilise le même ID pour accéder au compte
     *
     * PROTECTION : Régénérer l'ID après la connexion !
     *
     * ```php
     * // Lors de la connexion
     * public function login(string $email, string $password): bool
     * {
     *     if ($this->authenticate($email, $password)) {
     *         // IMPORTANT : régénérer l'ID APRÈS authentification
     *         $session->regenerate();
     *
     *         $session->set('user_id', $user->getId());
     *         return true;
     *     }
     *     return false;
     * }
     * ```
     *
     * Cette méthode :
     * - Génère un nouvel identifiant de session
     * - Garde les données de session existantes
     * - Supprime l'ancien fichier de session
     *
     * @return void
     */
    public function regenerate(): void;

    /**
     * Détruit complètement la session.
     *
     * =========================================================================
     * NETTOYAGE COMPLET
     * =========================================================================
     *
     * Cette méthode effectue une déconnexion complète :
     *
     * 1. Vide toutes les données de session ($_SESSION = [])
     * 2. Supprime le cookie de session du navigateur
     * 3. Détruit le fichier de session sur le serveur
     *
     * Utilisez cette méthode pour :
     * - La déconnexion utilisateur
     * - La suppression de compte
     * - Le nettoyage après une action sensible
     *
     * ```php
     * public function logout(): Response
     * {
     *     // Détruit toute la session
     *     $session->destroy();
     *
     *     // Redirige vers l'accueil
     *     return new Response('', 302, ['Location: /']);
     * }
     * ```
     *
     * @return void
     */
    public function destroy(): void;

    /**
     * Retourne toutes les données de session.
     *
     * =========================================================================
     * ACCÈS COMPLET AUX DONNÉES
     * =========================================================================
     *
     * Retourne un tableau avec toutes les données de session,
     * SAUF les données internes (messages flash).
     *
     * Utile pour :
     * - Débogage
     * - Export des données utilisateur
     * - Affichage dans un panneau d'administration
     *
     * ```php
     * $data = $session->all();
     * // [
     * //     'user_id' => 42,
     * //     'user_name' => 'Jean',
     * //     'cart' => [...],
     * //     'preferences' => [...],
     * // ]
     * ```
     *
     * @return array<string, mixed> Toutes les données de session (sauf les internes).
     */
    public function all(): array;
}
