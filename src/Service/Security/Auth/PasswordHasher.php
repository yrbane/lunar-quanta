<?php
/**
 * Lunar Quanta Framework - Service de Hachage de Mots de Passe.
 *
 * =============================================================================
 * QU'EST-CE QUE CETTE CLASSE ?
 * =============================================================================
 *
 * PasswordHasher est l'IMPLÉMENTATION CONCRÈTE de PasswordHasherInterface.
 * C'est la classe qui fait réellement le travail de hachage en utilisant
 * les fonctions natives de PHP.
 *
 * RAPPEL : INTERFACE vs CLASSE CONCRÈTE
 *
 * - Interface : dit CE QU'ON PEUT FAIRE (le contrat)
 * - Classe concrète : dit COMMENT ON LE FAIT (l'implémentation)
 *
 * ```
 * PasswordHasherInterface            PasswordHasher
 * ─────────────────────              ─────────────
 * "On peut hacher"                   "On utilise bcrypt"
 * "On peut vérifier"                 "On utilise password_hash()"
 * "On peut check rehash"             "On utilise password_verify()"
 * ```
 *
 * =============================================================================
 * LES FONCTIONS PHP UTILISÉES
 * =============================================================================
 *
 * PHP fournit 3 fonctions natives pour gérer les mots de passe :
 *
 * ┌─────────────────────────┬─────────────────────────────────────────────────┐
 * │  Fonction               │  Description                                    │
 * ├─────────────────────────┼─────────────────────────────────────────────────┤
 * │  password_hash()        │  Crée un hash à partir d'un mot de passe.       │
 * │                         │  Génère automatiquement un sel aléatoire.       │
 * ├─────────────────────────┼─────────────────────────────────────────────────┤
 * │  password_verify()      │  Vérifie si un mot de passe correspond à        │
 * │                         │  un hash. Utilise une comparaison sécurisée.    │
 * ├─────────────────────────┼─────────────────────────────────────────────────┤
 * │  password_needs_rehash()│  Vérifie si un hash doit être mis à jour        │
 * │                         │  (paramètres obsolètes).                        │
 * └─────────────────────────┴─────────────────────────────────────────────────┘
 *
 * Ces fonctions sont recommandées par PHP et par l'OWASP (organisation
 * de sécurité web) car elles gèrent automatiquement les détails complexes.
 *
 * =============================================================================
 * LES ALGORITHMES DISPONIBLES
 * =============================================================================
 *
 * 1. BCRYPT (PASSWORD_BCRYPT)
 * ---------------------------
 *
 * Algorithme par défaut, utilisé depuis 1999. Très éprouvé et sûr.
 *
 * ```php
 * // Utilisation basique (cost par défaut = 10)
 * $hash = password_hash("MonMDP", PASSWORD_BCRYPT);
 *
 * // Avec un coût personnalisé
 * $hash = password_hash("MonMDP", PASSWORD_BCRYPT, ['cost' => 12]);
 * ```
 *
 * Paramètres bcrypt :
 * - cost : Nombre de tours (2^cost itérations). Plus c'est élevé, plus c'est
 *          lent mais sécurisé. Valeurs recommandées : 10-13.
 *
 * ```
 * TEMPS DE HACHAGE SELON LE COÛT
 *
 *    Cost    Temps approximatif
 *    ────    ──────────────────
 *    8       ~0.03 seconde
 *    10      ~0.1 seconde
 *    12      ~0.4 seconde
 *    14      ~1.5 seconde
 *
 *    Règle : chaque +1 double le temps !
 * ```
 *
 * 2. ARGON2ID (PASSWORD_ARGON2ID)
 * -------------------------------
 *
 * Algorithme moderne, gagnant de la compétition Password Hashing Competition
 * en 2015. Résiste mieux aux attaques par GPU et ASIC.
 *
 * ```php
 * // Utilisation avec paramètres par défaut
 * $hash = password_hash("MonMDP", PASSWORD_ARGON2ID);
 *
 * // Avec paramètres personnalisés
 * $hash = password_hash("MonMDP", PASSWORD_ARGON2ID, [
 *     'memory_cost' => 65536,  // Mémoire en kilo-octets
 *     'time_cost' => 4,        // Nombre d'itérations
 *     'threads' => 3,          // Parallélisme
 * ]);
 * ```
 *
 * Paramètres Argon2 :
 * - memory_cost : Quantité de mémoire utilisée (empêche les attaques GPU)
 * - time_cost : Nombre d'itérations (ralentit le calcul)
 * - threads : Nombre de threads parallèles
 *
 * =============================================================================
 * QUAND CHOISIR BCRYPT vs ARGON2ID ?
 * =============================================================================
 *
 * ┌───────────────┬────────────────────────────────────────────────────────────┐
 * │  Utilisez     │  Quand ?                                                   │
 * ├───────────────┼────────────────────────────────────────────────────────────┤
 * │  bcrypt       │  - Compatibilité maximale (fonctionne partout)             │
 * │               │  - Serveurs avec peu de mémoire                            │
 * │               │  - Valeur sûre, éprouvée depuis 25 ans                     │
 * ├───────────────┼────────────────────────────────────────────────────────────┤
 * │  Argon2id     │  - PHP 7.3+ disponible                                     │
 * │               │  - Serveurs avec suffisamment de RAM                       │
 * │               │  - Protection maximale contre attaques GPU                 │
 * │               │  - Applications haute sécurité                             │
 * └───────────────┴────────────────────────────────────────────────────────────┘
 *
 * =============================================================================
 * PATTERN "FACTORY METHOD" : bcrypt() et argon2id()
 * =============================================================================
 *
 * Cette classe utilise le pattern "Factory Method" pour créer des instances
 * pré-configurées facilement.
 *
 * QU'EST-CE QU'UNE FACTORY METHOD ?
 *
 * C'est une méthode STATIQUE qui crée et retourne un nouvel objet.
 * C'est plus lisible que d'appeler new avec plein de paramètres.
 *
 * ```php
 * // Sans factory (verbeux et moins clair)
 * $hasher = new PasswordHasher(PASSWORD_ARGON2ID, [
 *     'memory_cost' => 65536,
 *     'time_cost' => 4,
 *     'threads' => 3,
 * ]);
 *
 * // Avec factory (simple et lisible)
 * $hasher = PasswordHasher::argon2id();
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
 * @see PasswordHasherInterface Interface implémentée
 * @see Authenticator Service qui utilise le hasher
 * @see https://www.php.net/manual/fr/function.password-hash.php Documentation PHP
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

/**
 * Service de hachage de mots de passe utilisant l'API password_hash de PHP.
 *
 * Cette classe encapsule les fonctions natives de PHP pour le hachage
 * sécurisé des mots de passe. Elle supporte bcrypt (par défaut) et Argon2id.
 *
 * =============================================================================
 * CRÉATION D'UNE INSTANCE
 * =============================================================================
 *
 * Il y a trois façons de créer un PasswordHasher :
 *
 * ```php
 * // 1. Via le constructeur (bcrypt par défaut)
 * $hasher = new PasswordHasher();
 *
 * // 2. Via la factory bcrypt() avec coût personnalisé
 * $hasher = PasswordHasher::bcrypt(12);  // cost = 12
 *
 * // 3. Via la factory argon2id() pour plus de sécurité
 * $hasher = PasswordHasher::argon2id();
 * ```
 *
 * =============================================================================
 * UTILISATION COMPLÈTE
 * =============================================================================
 *
 * ```php
 * // Configuration (une seule fois, au démarrage)
 * $hasher = PasswordHasher::bcrypt(12);
 *
 * // ─────────────────────────────────────────────────────────────────────────
 * // INSCRIPTION : Hacher le mot de passe avant de le stocker
 * // ─────────────────────────────────────────────────────────────────────────
 * $motDePasse = $_POST['password'];  // "MonSuperMDP123!"
 * $hash = $hasher->hash($motDePasse);
 * // → "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X4bN1...."
 *
 * // Stocker $hash en base de données (JAMAIS le mot de passe en clair !)
 * $sql = "INSERT INTO users (email, password_hash) VALUES (?, ?)";
 * $db->execute($sql, [$email, $hash]);
 *
 * // ─────────────────────────────────────────────────────────────────────────
 * // CONNEXION : Vérifier le mot de passe
 * // ─────────────────────────────────────────────────────────────────────────
 * $motDePasseTapé = $_POST['password'];
 * $hashStocké = $user->getPassword();  // Récupéré de la BDD
 *
 * if ($hasher->verify($motDePasseTapé, $hashStocké)) {
 *     // Mot de passe correct !
 *
 *     // Vérifier si le hash doit être mis à jour
 *     if ($hasher->needsRehash($hashStocké)) {
 *         $nouveauHash = $hasher->hash($motDePasseTapé);
 *         $user->setPassword($nouveauHash);
 *         $repository->save($user);
 *     }
 *
 *     // Démarrer la session
 *     $_SESSION['user_id'] = $user->getId();
 * } else {
 *     echo "Mot de passe incorrect !";
 * }
 * ```
 *
 * @package Lunar\Service\Security\Auth
 */
class PasswordHasher implements PasswordHasherInterface
{
    /**
     * L'algorithme de hachage à utiliser.
     *
     * C'est une constante PHP qui identifie l'algorithme :
     * - PASSWORD_BCRYPT : Algorithme bcrypt
     * - PASSWORD_ARGON2ID : Algorithme Argon2id
     *
     * @var string
     */
    private string $algorithm;

    /**
     * Options de l'algorithme.
     *
     * Les options varient selon l'algorithme :
     *
     * Pour bcrypt :
     * - 'cost' => int (nombre de tours, défaut 10)
     *
     * Pour Argon2id :
     * - 'memory_cost' => int (mémoire en ko)
     * - 'time_cost' => int (nombre d'itérations)
     * - 'threads' => int (parallélisme)
     *
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * Crée un service de hachage de mots de passe.
     *
     * =========================================================================
     * PARAMÈTRES
     * =========================================================================
     *
     * @param string $algorithm L'algorithme de hachage à utiliser.
     *                          Valeurs possibles :
     *                          - PASSWORD_BCRYPT (recommandé par défaut)
     *                          - PASSWORD_ARGON2ID (plus sécurisé si disponible)
     *
     * @param array<string, mixed> $options Options spécifiques à l'algorithme.
     *                                      Si vide, utilise les valeurs par défaut de PHP.
     *
     * =========================================================================
     * EXEMPLES
     * =========================================================================
     *
     * ```php
     * // bcrypt avec les options par défaut
     * $hasher = new PasswordHasher();
     *
     * // bcrypt avec un coût personnalisé
     * $hasher = new PasswordHasher(PASSWORD_BCRYPT, ['cost' => 13]);
     *
     * // Argon2id avec options personnalisées
     * $hasher = new PasswordHasher(PASSWORD_ARGON2ID, [
     *     'memory_cost' => 65536,  // 64 Mo de RAM
     *     'time_cost' => 4,
     *     'threads' => 3,
     * ]);
     * ```
     *
     * =========================================================================
     * CONSEIL : UTILISEZ LES FACTORY METHODS
     * =========================================================================
     *
     * Pour plus de lisibilité, préférez les méthodes statiques :
     *
     * ```php
     * // Plus lisible !
     * $hasher = PasswordHasher::bcrypt(12);
     * $hasher = PasswordHasher::argon2id();
     * ```
     */
    public function __construct(
        string $algorithm = PASSWORD_BCRYPT,
        array $options = []
    ) {
        $this->algorithm = $algorithm;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     *
     * Hache un mot de passe avec l'algorithme configuré.
     *
     * =========================================================================
     * FONCTIONNEMENT INTERNE
     * =========================================================================
     *
     * Cette méthode utilise password_hash() de PHP qui :
     * 1. Génère un sel aléatoire unique
     * 2. Combine le mot de passe avec le sel
     * 3. Applique l'algorithme de hachage
     * 4. Retourne une chaîne contenant tout (algo, sel, hash)
     *
     * ```
     * password_hash("MonMDP", PASSWORD_BCRYPT, ['cost' => 12])
     *                   │
     *                   ▼
     *         ┌─────────────────┐
     *         │ Génère un sel   │  (ex: "randomSalt123...")
     *         └────────┬────────┘
     *                  │
     *                  ▼
     *         ┌─────────────────┐
     *         │ Combine avec    │  "MonMDP" + "randomSalt123..."
     *         │ le mot de passe │
     *         └────────┬────────┘
     *                  │
     *                  ▼
     *         ┌─────────────────┐
     *         │ Applique bcrypt │  2^12 = 4096 itérations
     *         │ avec cost=12    │
     *         └────────┬────────┘
     *                  │
     *                  ▼
     *    "$2y$12$randomSalt123...hashResultat..."
     * ```
     *
     * @throws \InvalidArgumentException Si le mot de passe est vide.
     *                                   On refuse de hacher une chaîne vide
     *                                   car ce serait une faille de sécurité.
     */
    public function hash(string $plainPassword): string
    {
        // Refuse de hacher un mot de passe vide
        // Un mot de passe vide stocké en BDD serait une faille de sécurité !
        if ('' === $plainPassword) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }

        // password_hash() gère tout automatiquement :
        // - Génération du sel
        // - Application de l'algorithme
        // - Formatage du résultat
        return password_hash($plainPassword, $this->algorithm, $this->options);
    }

    /**
     * {@inheritdoc}
     *
     * Vérifie si un mot de passe correspond à un hash.
     *
     * =========================================================================
     * FONCTIONNEMENT INTERNE
     * =========================================================================
     *
     * password_verify() :
     * 1. Parse le hash pour extraire l'algorithme, le sel et les options
     * 2. Rehache le mot de passe soumis avec ces mêmes paramètres
     * 3. Compare les deux hashs de manière sécurisée (temps constant)
     *
     * ```
     * EXEMPLE
     *
     *    Hash stocké : "$2y$12$SEL_ALEATOIRE__________HASH_FINAL"
     *                   │   │  │                     │
     *                   │   │  │                     └── Hash à comparer
     *                   │   │  └── Sel extrait
     *                   │   └── Cost extrait (12)
     *                   └── Algo extrait (bcrypt)
     *
     *    Mot de passe tapé : "MonMDP"
     *                            │
     *                            ▼
     *    hash("MonMDP" + sel_extrait, cost=12) → "HASH_CALCULE"
     *
     *    Comparaison :
     *    "HASH_FINAL" === "HASH_CALCULE" ?
     *    → OUI = mot de passe correct
     *    → NON = mot de passe incorrect
     * ```
     *
     * =========================================================================
     * SÉCURITÉ
     * =========================================================================
     *
     * password_verify() utilise une comparaison "en temps constant" qui
     * prend toujours le même temps, que le mot de passe soit correct ou non.
     * Cela empêche les attaques par timing.
     */
    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        // Refuse de vérifier si l'un des deux est vide
        // Évite les comparaisons inutiles et potentiellement dangereuses
        if ('' === $plainPassword || '' === $hashedPassword) {
            return false;
        }

        // password_verify() fait tout le travail :
        // - Extrait le sel et les paramètres du hash
        // - Rehache le mot de passe soumis
        // - Compare en temps constant
        return password_verify($plainPassword, $hashedPassword);
    }

    /**
     * {@inheritdoc}
     *
     * Vérifie si un hash doit être régénéré.
     *
     * =========================================================================
     * QUAND CETTE MÉTHODE RETOURNE-T-ELLE TRUE ?
     * =========================================================================
     *
     * Elle retourne true si le hash a été créé avec des paramètres différents
     * de ceux configurés dans ce hasher. Par exemple :
     *
     * ```
     * SCÉNARIO 1 : Changement de coût
     *
     *    Ancien hasher : cost=10
     *    Hash stocké : "$2y$10$..."
     *
     *    Nouveau hasher : cost=12
     *    needsRehash("$2y$10$...") → true !
     *    (le hash est cost=10, mais on veut cost=12)
     *
     * SCÉNARIO 2 : Changement d'algorithme
     *
     *    Ancien hash : bcrypt
     *    "$2y$10$..."
     *
     *    Nouveau hasher : Argon2id
     *    needsRehash("$2y$10$...") → true !
     *    (le hash est bcrypt, mais on veut Argon2id)
     *
     * SCÉNARIO 3 : Paramètres identiques
     *
     *    Hasher : bcrypt cost=12
     *    Hash : "$2y$12$..."
     *
     *    needsRehash("$2y$12$...") → false
     *    (le hash utilise déjà les bons paramètres)
     * ```
     */
    public function needsRehash(string $hashedPassword): bool
    {
        // password_needs_rehash() compare les paramètres du hash
        // avec les paramètres configurés et retourne true
        // s'ils sont différents
        return password_needs_rehash($hashedPassword, $this->algorithm, $this->options);
    }

    /**
     * Crée un hasher utilisant l'algorithme Argon2id.
     *
     * =========================================================================
     * POURQUOI ARGON2ID ?
     * =========================================================================
     *
     * Argon2id est le gagnant de la Password Hashing Competition (2015).
     * Il est conçu pour résister aux attaques modernes :
     *
     * - ATTAQUES GPU : Argon2id nécessite beaucoup de mémoire, ce qui rend
     *   les attaques par GPU très coûteuses (les GPU ont peu de mémoire).
     *
     * - ATTAQUES ASIC : Les circuits spécialisés sont inefficaces contre
     *   Argon2id à cause des accès mémoire aléatoires.
     *
     * =========================================================================
     * PARAMÈTRES EXPLIQUÉS
     * =========================================================================
     *
     * ```
     * Argon2id(memory_cost, time_cost, threads)
     *            │            │          │
     *            │            │          └── Parallélisme
     *            │            │              Combien de coeurs CPU utiliser
     *            │            │              Plus = plus rapide mais plus de ressources
     *            │            │
     *            │            └── Nombre d'itérations
     *            │                Combien de fois répéter l'algorithme
     *            │                Plus = plus lent mais plus sécurisé
     *            │
     *            └── Mémoire en kilo-octets
     *                Combien de RAM utiliser
     *                Plus = plus sécurisé contre les attaques GPU
     *                Défaut: 65536 Ko = 64 Mo
     * ```
     *
     * @param int $memoryCost Mémoire à utiliser en Ko. Défaut : 64 Mo.
     *                        Plus c'est élevé, plus c'est sécurisé mais gourmand.
     *
     * @param int $timeCost Nombre d'itérations. Défaut : 4.
     *                      Plus c'est élevé, plus c'est lent mais sécurisé.
     *
     * @param int $threads Nombre de threads. Défaut : 1.
     *                     Augmente le parallélisme (si votre serveur le permet).
     *
     * @return self Une nouvelle instance configurée pour Argon2id.
     *
     * @example
     * ```php
     * // Argon2id avec les paramètres par défaut (recommandé)
     * $hasher = PasswordHasher::argon2id();
     *
     * // Argon2id avec paramètres personnalisés (plus sécurisé, plus lent)
     * $hasher = PasswordHasher::argon2id(
     *     memoryCost: 131072,  // 128 Mo
     *     timeCost: 6,
     *     threads: 4
     * );
     * ```
     */
    public static function argon2id(
        int $memoryCost = PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
        int $timeCost = PASSWORD_ARGON2_DEFAULT_TIME_COST,
        int $threads = PASSWORD_ARGON2_DEFAULT_THREADS
    ): self {
        return new self(PASSWORD_ARGON2ID, [
            'memory_cost' => $memoryCost,
            'time_cost' => $timeCost,
            'threads' => $threads,
        ]);
    }

    /**
     * Crée un hasher utilisant l'algorithme bcrypt.
     *
     * =========================================================================
     * POURQUOI BCRYPT ?
     * =========================================================================
     *
     * bcrypt est l'algorithme de hachage de mots de passe le plus utilisé.
     * Créé en 1999, il est éprouvé et considéré comme très sûr.
     *
     * Avantages :
     * - Fonctionne partout (PHP 5.5+)
     * - Peu gourmand en mémoire
     * - Paramètre simple (juste le coût)
     * - 25 ans de sécurité prouvée
     *
     * =========================================================================
     * LE PARAMÈTRE "COST"
     * =========================================================================
     *
     * Le "cost" détermine combien d'itérations sont effectuées (2^cost).
     *
     * ```
     * RELATION COST / TEMPS
     *
     *    Cost   Itérations   Temps approximatif
     *    ────   ──────────   ──────────────────
     *     8     256          ~30 ms
     *     10    1024         ~100 ms       ← défaut PHP
     *     12    4096         ~400 ms       ← recommandé 2024
     *     14    16384        ~1.5 s
     *
     *    Chaque +1 DOUBLE le temps !
     * ```
     *
     * COMMENT CHOISIR LE COST ?
     *
     * Visez un temps de hachage entre 250ms et 1s sur votre serveur.
     * Testez avec ce code :
     *
     * ```php
     * for ($cost = 8; $cost <= 14; $cost++) {
     *     $start = microtime(true);
     *     password_hash("test", PASSWORD_BCRYPT, ['cost' => $cost]);
     *     $end = microtime(true);
     *     printf("Cost %d : %.3f secondes\n", $cost, $end - $start);
     * }
     * ```
     *
     * @param int $cost Le facteur de coût (4-31). Défaut : 10.
     *                  Recommandé : 12 en 2024.
     *
     * @return self Une nouvelle instance configurée pour bcrypt.
     *
     * @example
     * ```php
     * // bcrypt avec le coût par défaut de PHP (10)
     * $hasher = PasswordHasher::bcrypt();
     *
     * // bcrypt avec un coût recommandé pour 2024
     * $hasher = PasswordHasher::bcrypt(12);
     *
     * // bcrypt avec un coût élevé (pour les applications critiques)
     * $hasher = PasswordHasher::bcrypt(13);
     * ```
     */
    public static function bcrypt(int $cost = PASSWORD_BCRYPT_DEFAULT_COST): self
    {
        return new self(PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}
