<?php
/**
 * Lunar Quanta Framework - Gestionnaire de Tokens CSRF.
 *
 * =============================================================================
 * QU'EST-CE QUE CETTE CLASSE ?
 * =============================================================================
 *
 * CsrfTokenManager est l'implémentation concrète de CsrfTokenManagerInterface.
 * C'est la classe qui fait réellement le travail de :
 *
 * 1. GÉNÉRATION de tokens cryptographiquement sûrs
 * 2. STOCKAGE des tokens en session
 * 3. VALIDATION avec comparaison en temps constant
 *
 * =============================================================================
 * CARACTÉRISTIQUES DE SÉCURITÉ
 * =============================================================================
 *
 * 1. ENTROPIE ÉLEVÉE
 *    Les tokens font 64 caractères hexadécimaux (256 bits d'entropie).
 *    C'est cryptographiquement impossible à deviner.
 *
 * 2. GÉNÉRATION SÉCURISÉE
 *    Utilise random_bytes() qui génère des octets cryptographiquement sûrs.
 *    C'est la méthode recommandée en PHP pour la génération aléatoire.
 *
 * 3. COMPARAISON EN TEMPS CONSTANT
 *    Utilise hash_equals() pour éviter les attaques par timing.
 *
 * 4. STOCKAGE EN SESSION
 *    Les tokens sont stockés côté serveur, inaccessibles aux attaquants.
 *
 * =============================================================================
 * QU'EST-CE QUE random_bytes() ?
 * =============================================================================
 *
 * random_bytes() est une fonction PHP qui génère des octets aléatoires
 * de qualité cryptographique. Elle utilise les sources d'entropie du
 * système d'exploitation :
 *
 * - Linux : /dev/urandom
 * - Windows : CryptGenRandom()
 *
 * ```php
 * // Génère 32 octets aléatoires
 * $bytes = random_bytes(32);
 *
 * // Convertit en hexadécimal (64 caractères)
 * $hex = bin2hex($bytes);
 * // Ex: "a1b2c3d4e5f6789012345678901234567890abcdef..."
 * ```
 *
 * @package    Lunar\Service\Security\Csrf
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see CsrfTokenManagerInterface Interface implémentée
 * @see CsrfMiddleware Middleware qui utilise ce gestionnaire
 * @see SessionInterface Session utilisée pour le stockage
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Csrf;

use Lunar\Service\Session\SessionInterface;

/**
 * Gestionnaire de tokens CSRF utilisant le stockage en session.
 *
 * Cette classe génère des tokens cryptographiquement sécurisés et les valide
 * en utilisant une comparaison à temps constant pour protéger contre les
 * attaques CSRF.
 *
 * =============================================================================
 * UTILISATION
 * =============================================================================
 *
 * ```php
 * // Création avec une session
 * $session = new SessionService();
 * $session->start();
 * $manager = new CsrfTokenManager($session);
 *
 * // Génération d'un token pour un formulaire
 * $token = $manager->generate('contact_form');
 * // → "a1b2c3d4e5f6789012345678901234567890abcdef..."
 *
 * // Affichage dans le formulaire HTML
 * echo '<input type="hidden" name="_csrf_token" value="' . $token . '">';
 *
 * // Validation lors de la soumission
 * $submittedToken = $_POST['_csrf_token'] ?? '';
 * if ($manager->isValid('contact_form', $submittedToken)) {
 *     // Token valide → traiter le formulaire
 * } else {
 *     // Token invalide → rejeter la requête
 * }
 * ```
 *
 * =============================================================================
 * INTÉGRATION AVEC LE MIDDLEWARE
 * =============================================================================
 *
 * En pratique, vous n'appelez pas directement isValid(). Le CsrfMiddleware
 * s'en charge automatiquement pour les requêtes POST/PUT/PATCH/DELETE.
 *
 * Vous n'avez qu'à :
 * 1. Générer le token pour vos formulaires
 * 2. L'inclure dans un champ caché nommé "_csrf_token"
 *
 * @package Lunar\Service\Security\Csrf
 */
class CsrfTokenManager implements CsrfTokenManagerInterface
{
    /**
     * Clé de session où sont stockés les tokens.
     *
     * Les tokens sont stockés dans un tableau sous cette clé :
     * $_SESSION['_csrf_tokens'] = [
     *     'form1' => 'token1...',
     *     'form2' => 'token2...',
     * ]
     *
     * @var string
     */
    private const SESSION_KEY = '_csrf_tokens';

    /**
     * Longueur du token en octets.
     *
     * 32 octets = 256 bits = 64 caractères hexadécimaux.
     * C'est une entropie suffisante pour résister aux attaques par force brute.
     *
     * Temps pour deviner par force brute (1 milliard de tentatives/seconde) :
     * 2^256 / 10^9 = environ 10^68 secondes
     * → Plus que l'âge de l'univers !
     *
     * @var int
     */
    private const TOKEN_LENGTH = 32;

    /**
     * Service de session pour le stockage des tokens.
     *
     * @var SessionInterface
     */
    private SessionInterface $session;

    /**
     * Crée un nouveau gestionnaire de tokens CSRF.
     *
     * @param SessionInterface $session Le service de session à utiliser.
     *                                  Doit être démarré avant utilisation.
     *
     * @example
     * ```php
     * $session = new SessionService();
     * $session->start();
     * $csrf = new CsrfTokenManager($session);
     * ```
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     *
     * Génère un token cryptographiquement sûr.
     */
    public function generate(string $tokenId): string
    {
        // 1. Génère 32 octets aléatoires (256 bits)
        //    random_bytes() utilise une source d'entropie sécurisée
        $randomBytes = random_bytes(self::TOKEN_LENGTH);

        // 2. Convertit en hexadécimal (lisible et safe pour HTML)
        //    32 octets → 64 caractères hex
        $token = bin2hex($randomBytes);

        // 3. Stocke en session
        $this->storeToken($tokenId, $token);

        return $token;
    }

    /**
     * {@inheritdoc}
     *
     * Valide un token avec comparaison en temps constant.
     */
    public function isValid(string $tokenId, string $token): bool
    {
        // Token vide = invalide (évite les comparaisons inutiles)
        if ('' === $token) {
            return false;
        }

        // Récupère le token stocké
        $storedToken = $this->getStoredToken($tokenId);
        if (null === $storedToken) {
            return false;  // Pas de token stocké pour cet ID
        }

        // COMPARAISON EN TEMPS CONSTANT
        // hash_equals() compare les chaînes en temps constant
        // → Évite les attaques par timing
        return hash_equals($storedToken, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $tokenId): void
    {
        /** @var array<string, string> $tokens */
        $tokens = $this->session->get(self::SESSION_KEY, []);
        unset($tokens[$tokenId]);
        $this->session->set(self::SESSION_KEY, $tokens);
    }

    /**
     * Stocke un token en session.
     *
     * @param string $tokenId L'identifiant du token.
     * @param string $token   Le token à stocker.
     *
     * @return void
     */
    private function storeToken(string $tokenId, string $token): void
    {
        /** @var array<string, string> $tokens */
        $tokens = $this->session->get(self::SESSION_KEY, []);
        $tokens[$tokenId] = $token;
        $this->session->set(self::SESSION_KEY, $tokens);
    }

    /**
     * Récupère un token stocké en session.
     *
     * @param string $tokenId L'identifiant du token.
     *
     * @return string|null Le token ou null s'il n'existe pas.
     */
    private function getStoredToken(string $tokenId): ?string
    {
        /** @var array<string, string> $tokens */
        $tokens = $this->session->get(self::SESSION_KEY, []);
        return $tokens[$tokenId] ?? null;
    }
}
