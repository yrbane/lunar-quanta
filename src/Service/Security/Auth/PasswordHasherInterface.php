<?php
/**
 * Lunar Quanta Framework - Interface de Hachage de Mots de Passe.
 *
 * =============================================================================
 * POURQUOI NE PAS STOCKER LES MOTS DE PASSE EN CLAIR ?
 * =============================================================================
 *
 * Imaginez que vous gardez les clés de tous vos clients dans un coffre.
 * Si un voleur accède au coffre, il peut ouvrir toutes les portes !
 *
 * C'est exactement pareil avec les mots de passe stockés "en clair" :
 * si un pirate accède à votre base de données, il connaît TOUS les mots de passe.
 *
 * ```
 * ❌ STOCKAGE EN CLAIR (DANGER !)
 *
 *    Base de données volée :
 *    ┌────────────────────────────────┐
 *    │  utilisateur  │  mot_de_passe  │
 *    ├───────────────┼────────────────┤
 *    │  alice        │  MonChat2024   │  ← Le pirate voit le mot de passe !
 *    │  bob          │  123456789     │  ← Peut se connecter à son compte !
 *    │  charlie      │  MotDePasse!   │  ← Et partout où il l'utilise...
 *    └────────────────────────────────┘
 *
 *    → Le pirate peut :
 *      1. Se connecter au compte
 *      2. Essayer ce mot de passe sur d'autres sites (beaucoup réutilisent)
 * ```
 *
 * =============================================================================
 * QU'EST-CE QUE LE HACHAGE ?
 * =============================================================================
 *
 * Le HACHAGE est une transformation IRRÉVERSIBLE d'une donnée.
 *
 * ANALOGIE : La viande hachée
 *
 * Quand vous hachez de la viande, vous ne pouvez pas revenir au steak initial !
 * C'est pareil avec un mot de passe haché : on ne peut pas retrouver l'original.
 *
 * ```
 * PRINCIPE DU HACHAGE
 *
 *    Entrée (mot de passe)          Sortie (hash)
 *    ─────────────────────          ────────────────────────────────────────
 *    "MonChat2024"        →         "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6..."
 *
 *    Propriétés importantes :
 *    ├── DÉTERMINISTE : même entrée → même sortie (toujours)
 *    ├── IRRÉVERSIBLE : impossible de retrouver "MonChat2024" depuis le hash
 *    ├── UNIQUE : deux mots de passe différents → deux hashs différents
 *    └── LONGUEUR FIXE : quelle que soit l'entrée, la sortie a la même taille
 * ```
 *
 * Pour vérifier un mot de passe, on hache ce que l'utilisateur tape
 * et on compare avec le hash stocké :
 *
 * ```
 * VÉRIFICATION D'UN MOT DE PASSE
 *
 *    1. L'utilisateur tape : "MonChat2024"
 *
 *    2. Le serveur hache cette entrée :
 *       hash("MonChat2024") → "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6..."
 *
 *    3. Le serveur compare avec le hash stocké :
 *       "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6..." === "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6..."
 *       → OUI ! Le mot de passe est correct.
 *
 *    Si l'utilisateur tape un mauvais mot de passe :
 *       hash("MauvasMDP") → "$2y$12$XXXXXXXXXXXXXXXXXXXXXXXXX..."
 *       "$2y$12$XXXXXXXXXXXXXXXXXXXXXXXXX..." === "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6..."
 *       → NON ! Mot de passe incorrect.
 * ```
 *
 * =============================================================================
 * LES ALGORITHMES DE HACHAGE : BCRYPT ET ARGON2
 * =============================================================================
 *
 * Il existe plusieurs algorithmes de hachage. Voici les deux recommandés :
 *
 * ┌─────────────┬─────────────────────────────────────────────────────────────┐
 * │  Algorithme │  Description                                                │
 * ├─────────────┼─────────────────────────────────────────────────────────────┤
 * │  bcrypt     │  Algorithme éprouvé depuis 1999. Très sûr.                  │
 * │             │  Paramètre : "cost" (coût de calcul, défaut = 10-12)        │
 * │             │  Plus le cost est élevé, plus c'est lent mais sécurisé.     │
 * ├─────────────┼─────────────────────────────────────────────────────────────┤
 * │  Argon2id   │  Algorithme moderne (2015), gagnant d'une compétition.      │
 * │             │  Résiste mieux aux attaques par GPU.                        │
 * │             │  Paramètres : mémoire, temps, threads (parallélisme).       │
 * │             │  Recommandé si disponible sur votre serveur.                │
 * └─────────────┴─────────────────────────────────────────────────────────────┘
 *
 * POURQUOI C'EST LENT ?
 *
 * C'est fait EXPRÈS ! Un hash lent empêche les attaques par "force brute"
 * où le pirate teste des millions de combinaisons par seconde.
 *
 * ```
 * COMPARAISON DE VITESSE
 *
 *    Algorithme faible (MD5) :
 *    → 10 milliards de tests/seconde
 *    → Mot de passe de 6 caractères cassé en... 0.5 seconde !
 *
 *    bcrypt (cost=12) :
 *    → 10 tests/seconde
 *    → Mot de passe de 6 caractères cassé en... 19 ans !
 * ```
 *
 * =============================================================================
 * LE "SEL" (SALT) : PROTECTION CONTRE LES TABLES ARC-EN-CIEL
 * =============================================================================
 *
 * Le SEL est une valeur aléatoire ajoutée au mot de passe avant le hachage.
 *
 * POURQUOI ?
 *
 * Sans sel, deux utilisateurs avec le même mot de passe auraient le même hash.
 * Un pirate pourrait précalculer les hashs de mots de passe courants
 * (c'est une "table arc-en-ciel" ou "rainbow table").
 *
 * ```
 * PROBLÈME SANS SEL
 *
 *    hash("123456") → "abc123..."  (toujours le même hash)
 *
 *    Si 1000 utilisateurs ont "123456", ils ont tous "abc123..."
 *    Le pirate a une liste : "abc123..." = "123456"
 *    → Il connaît le mot de passe de 1000 utilisateurs !
 *
 * SOLUTION AVEC SEL
 *
 *    sel_alice = "xyz789"
 *    hash("123456" + "xyz789") → "hash_unique_alice..."
 *
 *    sel_bob = "abc456"
 *    hash("123456" + "abc456") → "hash_unique_bob..."
 *
 *    Même mot de passe, mais hashs différents !
 *    → La table arc-en-ciel ne fonctionne plus.
 * ```
 *
 * BONNE NOUVELLE : bcrypt et Argon2 gèrent le sel automatiquement !
 * Vous n'avez rien à faire, c'est inclus dans le hash.
 *
 * =============================================================================
 * POURQUOI "REHACHER" UN MOT DE PASSE ?
 * =============================================================================
 *
 * Les ordinateurs deviennent plus puissants chaque année.
 * Un hash fait en 2015 peut être trop facile à casser en 2025.
 *
 * La méthode needsRehash() vérifie si le hash utilise les bons paramètres.
 * Si ce n'est pas le cas, il faut re-hacher le mot de passe avec les
 * nouveaux paramètres (plus sécurisés).
 *
 * ```
 * SCÉNARIO DE REHACHAGE
 *
 *    2015 : Vous utilisez bcrypt avec cost=10
 *           hash("MonMDP") → "$2y$10$..."
 *
 *    2025 : Vous passez à cost=12 (plus sécurisé)
 *           needsRehash("$2y$10$...") → true  (l'ancien hash est cost=10)
 *
 *    Action : Quand l'utilisateur se connecte, re-hacher son mot de passe
 *           hash("MonMDP") → "$2y$12$..."  (nouveau hash plus sécurisé)
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
 * @see PasswordHasher Implémentation de cette interface
 * @see Authenticator Service qui utilise le hasher pour l'authentification
 * @see https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html Guide OWASP
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

/**
 * Interface pour le hachage sécurisé des mots de passe.
 *
 * Cette interface définit le contrat pour hacher, vérifier et gérer
 * les mots de passe de manière sécurisée.
 *
 * =============================================================================
 * LES TROIS MÉTHODES ESSENTIELLES
 * =============================================================================
 *
 * 1. hash()       → Transforme un mot de passe en hash sécurisé
 * 2. verify()     → Vérifie si un mot de passe correspond à un hash
 * 3. needsRehash() → Vérifie si un hash doit être mis à jour
 *
 * =============================================================================
 * FLUX TYPIQUE
 * =============================================================================
 *
 * ```
 *  INSCRIPTION                          CONNEXION
 *  ───────────                          ─────────
 *  Mot de passe en clair                Mot de passe en clair
 *        │                                     │
 *        ▼                                     ▼
 *  ┌─────────────┐                       ┌─────────────┐
 *  │   hash()    │                       │  verify()   │
 *  └─────────────┘                       └─────────────┘
 *        │                                     │
 *        ▼                                     ▼
 *  Hash stocké en BDD                    true/false
 *  "$2y$12$LQv3c..."                          │
 *                                             │
 *                                        ┌────┴────┐
 *                                        │ Si true │
 *                                        │ + needs │
 *                                        │ Rehash? │
 *                                        └────┬────┘
 *                                             │
 *                                             ▼
 *                                     Rehacher si besoin
 * ```
 *
 * =============================================================================
 * EXEMPLE D'UTILISATION
 * =============================================================================
 *
 * ```php
 * // À l'inscription : hacher le mot de passe
 * $hash = $hasher->hash('MonMotDePasse123!');
 * // → "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X..."
 * // Stocker $hash en base de données, JAMAIS le mot de passe !
 *
 * // À la connexion : vérifier le mot de passe
 * $motDePasseTapé = $_POST['password'];
 * $hashStocké = $user->getPassword();  // Récupéré de la BDD
 *
 * if ($hasher->verify($motDePasseTapé, $hashStocké)) {
 *     echo "Bienvenue !";
 *
 *     // Vérifier si le hash doit être mis à jour
 *     if ($hasher->needsRehash($hashStocké)) {
 *         $nouveauHash = $hasher->hash($motDePasseTapé);
 *         // Mettre à jour le hash en BDD
 *     }
 * } else {
 *     echo "Mot de passe incorrect !";
 * }
 * ```
 *
 * @package Lunar\Service\Security\Auth
 */
interface PasswordHasherInterface
{
    /**
     * Hache un mot de passe en clair.
     *
     * =========================================================================
     * QUE FAIT CETTE MÉTHODE ?
     * =========================================================================
     *
     * Elle transforme un mot de passe lisible en une chaîne illisible
     * mais vérifiable. Cette transformation est IRRÉVERSIBLE.
     *
     * ```
     * TRANSFORMATION
     *
     *    Entrée : "MonSuperMotDePasse"
     *                    │
     *                    ▼
     *              ┌───────────┐
     *              │  hash()   │   Algorithme + Sel + Coût
     *              └───────────┘
     *                    │
     *                    ▼
     *    Sortie : "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X..."
     *              │    │
     *              │    └── Coût (12 tours)
     *              └── Algorithme (bcrypt)
     * ```
     *
     * =========================================================================
     * FORMAT DU HASH BCRYPT
     * =========================================================================
     *
     * ```
     * $2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X...
     * │││ ││ │
     * │││ ││ └── Hash + Sel (données cryptographiques)
     * │││ │└──── Coût (nombre de tours : 2^12 = 4096)
     * │││ └───── Séparateur
     * ││└─────── Version de l'algorithme (y = sécurisé)
     * │└──────── Type (2 = bcrypt)
     * └───────── Début du format
     * ```
     *
     * @param string $plainPassword Le mot de passe en clair à hacher.
     *                              NE JAMAIS stocker cette valeur !
     *
     * @return string Le hash du mot de passe.
     *                C'est cette valeur qu'il faut stocker en base de données.
     *
     * @throws \InvalidArgumentException Si le mot de passe est vide.
     *
     * @example
     * ```php
     * $hasher = new PasswordHasher();
     *
     * // Inscription d'un nouvel utilisateur
     * $motDePasse = $_POST['password'];
     * $hash = $hasher->hash($motDePasse);
     *
     * // Stocker $hash dans la base de données
     * $user->setPassword($hash);
     * $repository->save($user);
     * ```
     */
    public function hash(string $plainPassword): string;

    /**
     * Vérifie si un mot de passe correspond à un hash.
     *
     * =========================================================================
     * QUE FAIT CETTE MÉTHODE ?
     * =========================================================================
     *
     * Elle vérifie si le mot de passe tapé par l'utilisateur correspond
     * au hash stocké en base de données, SANS pouvoir décoder le hash.
     *
     * ```
     * VÉRIFICATION
     *
     *    Mot de passe tapé      Hash stocké en BDD
     *    "MonMotDePasse"        "$2y$12$LQv3c..."
     *           │                       │
     *           └───────────┬───────────┘
     *                       │
     *                       ▼
     *                 ┌──────────┐
     *                 │ verify() │
     *                 └──────────┘
     *                       │
     *                       ▼
     *              true (correspond)
     *              ou false (ne correspond pas)
     * ```
     *
     * =========================================================================
     * COMMENT ÇA MARCHE EN INTERNE ?
     * =========================================================================
     *
     * 1. Extrait le sel et les paramètres du hash stocké
     * 2. Hache le mot de passe tapé avec ces mêmes paramètres
     * 3. Compare les deux hashs
     *
     * ```
     * PROCESSUS INTERNE
     *
     *    Hash stocké : "$2y$12$SEL_ICI_____________HASH_RESULTAT"
     *                       │                    │
     *                       └──┐      ┌─────────┘
     *                          │      │
     *    Mot de passe tapé ────┤      │
     *                          ▼      │
     *              hash(mot_de_passe + sel)
     *                          │      │
     *                          ▼      ▼
     *                      Comparaison
     * ```
     *
     * =========================================================================
     * SÉCURITÉ : COMPARAISON EN TEMPS CONSTANT
     * =========================================================================
     *
     * La comparaison utilise une technique spéciale pour éviter les
     * "attaques par timing" (mesure du temps de réponse).
     *
     * @param string $plainPassword Le mot de passe en clair à vérifier.
     * @param string $hashedPassword Le hash stocké à comparer.
     *
     * @return bool true si le mot de passe correspond, false sinon.
     *
     * @example
     * ```php
     * // À la connexion
     * $motDePasseTapé = $_POST['password'];
     * $hashStocké = $user->getPassword();
     *
     * if ($hasher->verify($motDePasseTapé, $hashStocké)) {
     *     // Connexion réussie !
     *     $_SESSION['user_id'] = $user->getId();
     * } else {
     *     // Mot de passe incorrect
     *     echo "Identifiants invalides.";
     * }
     * ```
     */
    public function verify(string $plainPassword, string $hashedPassword): bool;

    /**
     * Vérifie si un hash doit être régénéré avec de nouveaux paramètres.
     *
     * =========================================================================
     * POURQUOI CETTE MÉTHODE ?
     * =========================================================================
     *
     * Au fil du temps, les ordinateurs deviennent plus puissants.
     * Un hash créé il y a 5 ans peut maintenant être trop facile à casser.
     *
     * Cette méthode vérifie si le hash a été créé avec les paramètres
     * actuels (algorithme, coût, etc.). Si ce n'est pas le cas,
     * il est recommandé de re-hacher le mot de passe.
     *
     * ```
     * ÉVOLUTION DE LA SÉCURITÉ
     *
     *    2015        2018        2021        2024
     *      │           │           │           │
     *      ▼           ▼           ▼           ▼
     *    cost=8     cost=10     cost=11     cost=12
     *   (rapide)   (modéré)   (sécurisé)  (recommandé)
     *
     *    Un hash de 2015 avec cost=8 doit être mis à jour !
     * ```
     *
     * =========================================================================
     * QUAND REHACHER ?
     * =========================================================================
     *
     * Le seul moment où vous connaissez le mot de passe en clair,
     * c'est quand l'utilisateur se connecte. C'est à ce moment
     * qu'il faut vérifier et éventuellement rehacher.
     *
     * ```
     * FLUX DE REHACHAGE
     *
     *    Utilisateur se connecte
     *            │
     *            ▼
     *    verify() → true ?
     *            │
     *            ├── NON → Refuser la connexion
     *            │
     *            └── OUI → needsRehash() ?
     *                       │
     *                       ├── NON → Continuer
     *                       │
     *                       └── OUI → Rehacher !
     *                                 hash(mot_de_passe)
     *                                 Sauvegarder en BDD
     * ```
     *
     * @param string $hashedPassword Le hash à vérifier.
     *
     * @return bool true si le hash doit être régénéré, false sinon.
     *
     * @example
     * ```php
     * // Après une connexion réussie
     * if ($hasher->verify($password, $user->getPassword())) {
     *     // Connexion OK, mais le hash est-il à jour ?
     *     if ($hasher->needsRehash($user->getPassword())) {
     *         // Le hash utilise d'anciens paramètres
     *         $nouveauHash = $hasher->hash($password);
     *         $user->setPassword($nouveauHash);
     *         $repository->save($user);
     *
     *         // L'utilisateur ne voit rien, mais son mot de passe
     *         // est maintenant protégé par un hash plus sécurisé !
     *     }
     * }
     * ```
     */
    public function needsRehash(string $hashedPassword): bool;
}
