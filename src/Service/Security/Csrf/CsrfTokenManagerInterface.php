<?php
/**
 * Lunar Quanta Framework - Interface de Gestion des Tokens CSRF.
 *
 * =============================================================================
 * QU'EST-CE QU'UNE ATTAQUE CSRF ?
 * =============================================================================
 *
 * CSRF signifie "Cross-Site Request Forgery" (Falsification de Requête
 * Inter-Sites). C'est une attaque où un site malveillant fait exécuter
 * des actions sur un autre site au nom d'un utilisateur connecté.
 *
 * ANALOGIE : Imaginez une signature électronique
 *
 * Vous êtes connecté à votre banque (site légitime).
 * Un attaquant vous envoie un email avec un lien vers son site.
 * Ce site contient un formulaire caché qui fait un virement vers son compte.
 * Votre navigateur envoie la requête À VOTRE BANQUE avec VOS cookies !
 * La banque pense que c'est vous → le virement est effectué.
 *
 * ```
 *  ATTAQUE CSRF - SCÉNARIO
 *
 *  1. L'utilisateur est connecté à sa banque (cookie de session actif)
 *
 *     ┌──────────────┐                   ┌──────────────┐
 *     │  Navigateur  │ ── Session ─────► │   Banque     │
 *     │  (Cookie     │    Cookie         │   (Confie    │
 *     │   actif)     │                   │    le cookie)│
 *     └──────────────┘                   └──────────────┘
 *
 *  2. L'utilisateur visite un site malveillant
 *
 *     ┌──────────────┐                   ┌──────────────┐
 *     │  Navigateur  │ ──────────────►   │   Site       │
 *     │              │                   │   Malveillant│
 *     └──────────────┘                   └──────────────┘
 *                                              │
 *                                              ▼
 *                                        ┌──────────────────────┐
 *                                        │ <form action=        │
 *                                        │ "banque.com/virement"│
 *                                        │ method="POST">       │
 *                                        │ <input name="montant"│
 *                                        │    value="10000">    │
 *                                        │ <input name="dest"   │
 *                                        │    value="attaquant">│
 *                                        │ </form>              │
 *                                        │ <script>submit()</..>│
 *                                        └──────────────────────┘
 *
 *  3. Le formulaire est soumis automatiquement vers la banque
 *     AVEC le cookie de session de l'utilisateur !
 *
 *     ┌──────────────┐                   ┌──────────────┐
 *     │  Navigateur  │ ── POST ────────► │   Banque     │
 *     │  (Cookie     │   + Cookie !      │   (Exécute   │
 *     │   envoyé)    │                   │   l'action)  │
 *     └──────────────┘                   └──────────────┘
 *
 *  → La banque voit une requête valide avec un cookie valide
 *  → Elle exécute le virement !
 * ```
 *
 * =============================================================================
 * COMMENT SE PROTÉGER ? LES TOKENS CSRF
 * =============================================================================
 *
 * La solution : un TOKEN CSRF (jeton anti-falsification).
 *
 * PRINCIPE :
 * 1. Le serveur génère un token UNIQUE pour chaque session/formulaire
 * 2. Le token est inclus dans le formulaire (champ caché)
 * 3. Lors de la soumission, le serveur vérifie que le token est valide
 * 4. Le site malveillant NE PEUT PAS connaître ce token !
 *
 * ```
 *  PROTECTION CSRF
 *
 *  1. Le serveur génère un token unique
 *
 *     ┌──────────────┐                   ┌──────────────┐
 *     │  Serveur     │ ── Token ─────►   │  Session     │
 *     │  génère      │   stocké          │  (Token =    │
 *     │  "abc123"    │                   │   "abc123")  │
 *     └──────────────┘                   └──────────────┘
 *
 *  2. Le token est inclus dans le formulaire
 *
 *     <form method="POST">
 *         <input type="hidden" name="_csrf_token" value="abc123">
 *         ... autres champs ...
 *     </form>
 *
 *  3. Lors de la soumission, le serveur vérifie
 *
 *     Token reçu : "abc123"
 *     Token en session : "abc123"
 *     → Identiques → Requête légitime ✓
 *
 *  4. Un site malveillant ne peut pas deviner le token
 *
 *     <form action="banque.com/virement">
 *         <input name="_csrf_token" value="???">  ← Ne connaît pas !
 *     </form>
 *
 *     Token reçu : "" ou faux token
 *     Token en session : "abc123"
 *     → Différents → Requête rejetée ✗
 * ```
 *
 * =============================================================================
 * POURQUOI LE SITE MALVEILLANT NE PEUT PAS CONNAÎTRE LE TOKEN ?
 * =============================================================================
 *
 * 1. SAME-ORIGIN POLICY
 *    Le navigateur empêche un site de lire le contenu d'un autre site.
 *    Le site malveillant ne peut pas lire le HTML de votre banque.
 *
 * 2. TOKEN ALÉATOIRE
 *    Le token est généré aléatoirement et change à chaque session.
 *    Impossible à deviner.
 *
 * 3. STOCKÉ EN SESSION
 *    Le token est stocké côté serveur, dans la session de l'utilisateur.
 *    Le site malveillant n'a pas accès à la session.
 *
 * @package    Lunar\Service\Security\Csrf
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see CsrfTokenManager Implémentation de cette interface
 * @see CsrfMiddleware Middleware qui vérifie les tokens CSRF
 * @see https://owasp.org/www-community/attacks/csrf Documentation OWASP sur CSRF
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Csrf;

/**
 * Interface pour la gestion des tokens CSRF.
 *
 * Cette interface définit le contrat pour créer, valider et supprimer
 * des tokens de protection CSRF.
 *
 * =============================================================================
 * UTILISATION TYPIQUE
 * =============================================================================
 *
 * ```php
 * // Dans un contrôleur - affichage du formulaire
 * public function showForm(Request $request): Response
 * {
 *     $csrf = $request->getAttribute('csrf');  // CsrfTokenManagerInterface
 *     $token = $csrf->generate('contact_form');
 *
 *     $html = '
 *         <form method="POST">
 *             <input type="hidden" name="_csrf_token" value="' . $token . '">
 *             <input type="text" name="message">
 *             <button type="submit">Envoyer</button>
 *         </form>
 *     ';
 *
 *     return new Response($html);
 * }
 *
 * // Le CsrfMiddleware vérifie automatiquement le token lors du POST
 * ```
 *
 * @package Lunar\Service\Security\Csrf
 */
interface CsrfTokenManagerInterface
{
    /**
     * Génère un nouveau token CSRF pour un identifiant donné.
     *
     * =========================================================================
     * FONCTIONNEMENT
     * =========================================================================
     *
     * 1. Génère une chaîne aléatoire cryptographiquement sûre
     * 2. Stocke le token en session, associé à l'identifiant
     * 3. Retourne le token pour l'inclure dans le formulaire
     *
     * L'IDENTIFIANT ($tokenId) permet d'avoir plusieurs tokens différents
     * pour différents formulaires. Exemples :
     * - 'login_form' pour le formulaire de connexion
     * - 'contact_form' pour le formulaire de contact
     * - 'delete_account' pour la suppression de compte
     *
     * ```php
     * // Génère un token pour le formulaire de contact
     * $token = $csrf->generate('contact_form');
     *
     * // Le token est stocké en session sous cette clé
     * // $_SESSION['_csrf_tokens']['contact_form'] = 'abc123...'
     *
     * // Utilisation dans le HTML
     * echo '<input type="hidden" name="_csrf_token" value="' . $token . '">';
     * ```
     *
     * @param string $tokenId Identifiant unique du token.
     *                        Ex: 'login_form', 'delete_user_42'
     *
     * @return string Le token généré (chaîne hexadécimale de 64 caractères).
     *
     * @example
     * ```php
     * $token = $csrf->generate('my_form');
     * // Retourne quelque chose comme :
     * // "a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef12345678"
     * ```
     */
    public function generate(string $tokenId): string;

    /**
     * Valide un token CSRF.
     *
     * =========================================================================
     * FONCTIONNEMENT
     * =========================================================================
     *
     * 1. Récupère le token stocké en session pour cet identifiant
     * 2. Compare avec le token fourni (comparaison en temps constant)
     * 3. Retourne true si les tokens correspondent
     *
     * COMPARAISON EN TEMPS CONSTANT (hash_equals)
     * -------------------------------------------
     * La comparaison utilise hash_equals() au lieu de === pour éviter
     * les attaques par timing. Une comparaison classique s'arrête
     * dès qu'un caractère diffère, ce qui permet de deviner le token
     * caractère par caractère en mesurant le temps de réponse.
     *
     * ```php
     * // ❌ Vulnérable aux attaques timing
     * if ($stored === $submitted) { ... }
     *
     * // ✅ Sécurisé contre les attaques timing
     * if (hash_equals($stored, $submitted)) { ... }
     * ```
     *
     * @param string $tokenId L'identifiant du token à vérifier.
     * @param string $token   Le token soumis à valider.
     *
     * @return bool true si le token est valide, false sinon.
     *
     * @example
     * ```php
     * // Le middleware fait cette vérification automatiquement
     * $submittedToken = $_POST['_csrf_token'] ?? '';
     * if (!$csrf->isValid('contact_form', $submittedToken)) {
     *     // Token invalide → requête rejetée !
     *     return new Response('Accès refusé', 403);
     * }
     * ```
     */
    public function isValid(string $tokenId, string $token): bool;

    /**
     * Supprime un token du stockage.
     *
     * =========================================================================
     * QUAND UTILISER ?
     * =========================================================================
     *
     * Appelez cette méthode pour :
     *
     * 1. NETTOYAGE après une action réussie (optionnel)
     *    Évite l'accumulation de tokens en session.
     *
     * 2. INVALIDATION après utilisation unique
     *    Pour certaines actions critiques (changement de mot de passe),
     *    on peut vouloir invalider le token après une seule utilisation.
     *
     * ```php
     * // Après un changement de mot de passe
     * if ($csrf->isValid('change_password', $token)) {
     *     $this->changePassword($newPassword);
     *     $csrf->remove('change_password');  // Invalide le token
     * }
     * ```
     *
     * NOTE : Cette méthode n'est généralement pas nécessaire car les tokens
     * sont régénérés à chaque affichage du formulaire.
     *
     * @param string $tokenId L'identifiant du token à supprimer.
     *
     * @return void
     */
    public function remove(string $tokenId): void;
}
