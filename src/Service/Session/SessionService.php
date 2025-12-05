<?php
/**
 * Lunar Quanta Framework - Service de Session.
 *
 * =============================================================================
 * QU'EST-CE QUE CE SERVICE ?
 * =============================================================================
 *
 * SessionService est l'IMPLÉMENTATION concrète de SessionInterface.
 * C'est la classe qui fait réellement le travail de gestion des sessions.
 *
 * Elle utilise les sessions PHP natives ($_SESSION, session_start(), etc.)
 * mais les encapsule dans une API propre et orientée objet.
 *
 * =============================================================================
 * CARACTÉRISTIQUES PRINCIPALES
 * =============================================================================
 *
 * 1. SÉCURITÉ PAR DÉFAUT
 *    Configure automatiquement les paramètres de sécurité des cookies :
 *    - HttpOnly : empêche JavaScript de lire le cookie
 *    - SameSite : protège contre certaines attaques CSRF
 *    - Secure : utilise HTTPS si disponible
 *
 * 2. MESSAGES FLASH
 *    Support intégré des messages flash (messages temporaires).
 *
 * 3. MODE TEST
 *    Peut fonctionner sans vraie session PHP pour les tests unitaires.
 *
 * =============================================================================
 * PARAMÈTRES DE SÉCURITÉ DES COOKIES
 * =============================================================================
 *
 * ┌───────────────────────────────────────────────────────────────────────────┐
 * │ PARAMÈTRE           │ VALEUR │ EXPLICATION                               │
 * ├───────────────────────────────────────────────────────────────────────────┤
 * │ cookie_httponly     │ true   │ Le cookie ne peut pas être lu par         │
 * │                     │        │ JavaScript. Protège contre le vol de      │
 * │                     │        │ session via XSS (Cross-Site Scripting).   │
 * ├───────────────────────────────────────────────────────────────────────────┤
 * │ cookie_samesite     │ Lax    │ Le cookie n'est envoyé qu'aux requêtes    │
 * │                     │        │ du même site (protège contre CSRF).       │
 * │                     │        │ "Lax" permet les liens normaux,           │
 * │                     │        │ "Strict" serait plus restrictif.          │
 * ├───────────────────────────────────────────────────────────────────────────┤
 * │ use_strict_mode     │ true   │ N'accepte pas les ID de session fournis   │
 * │                     │        │ par le client. Protège contre le          │
 * │                     │        │ Session Fixation.                         │
 * ├───────────────────────────────────────────────────────────────────────────┤
 * │ cookie_secure       │ auto   │ Si HTTPS est détecté, le cookie n'est     │
 * │                     │        │ transmis que sur connexions sécurisées.   │
 * └───────────────────────────────────────────────────────────────────────────┘
 *
 * =============================================================================
 * QU'EST-CE QUE $_SESSION ?
 * =============================================================================
 *
 * $_SESSION est une SUPERGLOBALE PHP (comme $_GET, $_POST).
 * C'est un tableau associatif qui contient les données de la session active.
 *
 * ```php
 * // Écrire dans la session
 * $_SESSION['user_id'] = 42;
 * $_SESSION['cart'] = ['item1', 'item2'];
 *
 * // Lire depuis la session
 * $userId = $_SESSION['user_id'];  // 42
 *
 * // Supprimer une clé
 * unset($_SESSION['cart']);
 *
 * // Vider toute la session
 * $_SESSION = [];
 * ```
 *
 * ATTENTION : $_SESSION ne fonctionne qu'après session_start() !
 *
 * @package    Lunar\Service\Session
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 * @version    1.1.0
 * @link       https://nethttp.net
 * @since      1.1.0
 *
 * @see SessionInterface Interface que ce service implémente
 * @see SessionMiddleware Middleware qui utilise ce service
 */
declare(strict_types=1);

namespace Lunar\Service\Session;

/**
 * Service de session avec paramètres sécurisés par défaut.
 *
 * Cette classe implémente la gestion complète des sessions PHP,
 * incluant le support des messages flash et les bonnes pratiques
 * de sécurité.
 *
 * =============================================================================
 * UTILISATION NORMALE
 * =============================================================================
 *
 * ```php
 * $session = new SessionService();
 * $session->start();  // Démarre la session PHP
 *
 * // Stocker des données
 * $session->set('user_id', 42);
 * $session->set('preferences', ['theme' => 'dark', 'lang' => 'fr']);
 *
 * // Récupérer des données
 * $userId = $session->get('user_id');  // 42
 * $theme = $session->get('theme', 'light');  // 'light' (valeur par défaut)
 *
 * // Messages flash (notifications temporaires)
 * $session->flash('success', 'Profil mis à jour !');
 * // ... redirection ...
 * $message = $session->getFlash('success');  // "Profil mis à jour !"
 * $message = $session->getFlash('success');  // null (consommé)
 *
 * // Déconnexion sécurisée
 * $session->regenerate();  // Nouvel ID (après login)
 * $session->destroy();     // Tout supprimer (logout)
 * ```
 *
 * =============================================================================
 * MODE TEST (pour les tests unitaires)
 * =============================================================================
 *
 * ```php
 * // En mode test, les données sont stockées en mémoire
 * // au lieu d'utiliser les vraies sessions PHP
 * $session = new SessionService(testMode: true);
 *
 * $session->start();
 * $session->set('user_id', 42);
 * $this->assertEquals(42, $session->get('user_id'));
 *
 * // Pas de fichier de session créé, pas de cookie envoyé
 * // Idéal pour les tests unitaires !
 * ```
 *
 * @package Lunar\Service\Session
 */
class SessionService implements SessionInterface
{
    /**
     * Clé interne pour stocker les messages flash.
     *
     * Les messages flash sont stockés dans un tableau sous cette clé.
     *
     * @var string
     */
    private const FLASH_KEY = '_flash';

    /**
     * Clé interne pour les messages flash de la requête courante.
     *
     * Cette clé permet de distinguer les messages créés dans
     * la requête actuelle de ceux des requêtes précédentes.
     *
     * @var string
     */
    private const FLASH_NEW_KEY = '_flash_new';

    /**
     * Données de session en mémoire (mode test uniquement).
     *
     * =========================================================================
     * STOCKAGE EN MÉMOIRE POUR LES TESTS
     * =========================================================================
     *
     * En mode test, on n'utilise pas $_SESSION (qui nécessite une vraie
     * session PHP). À la place, on stocke tout dans ce tableau.
     *
     * Avantages :
     * - Pas besoin de session_start()
     * - Pas de fichiers créés
     * - Isolation parfaite entre les tests
     * - Rapide
     *
     * @var array<string, mixed>
     */
    private array $testData = [];

    /**
     * Indique si le service est en mode test.
     *
     * Si true : utilise $testData au lieu de $_SESSION.
     * Si false : utilise les vraies sessions PHP.
     *
     * @var bool
     */
    private bool $testMode;

    /**
     * Indique si la session a été démarrée.
     *
     * Évite de démarrer la session plusieurs fois.
     *
     * @var bool
     */
    private bool $started = false;

    /**
     * Crée un nouveau service de session.
     *
     * @param bool $testMode Si true, utilise le stockage en mémoire.
     *                       Utile pour les tests unitaires.
     *                       Par défaut : false (vraie session PHP).
     *
     * @example Production
     * ```php
     * $session = new SessionService();  // Vraie session PHP
     * ```
     *
     * @example Tests unitaires
     * ```php
     * $session = new SessionService(testMode: true);  // Mémoire uniquement
     * ```
     */
    public function __construct(bool $testMode = false)
    {
        $this->testMode = $testMode;

        // Configure les paramètres de sécurité (sauf en mode test)
        if (!$testMode) {
            $this->configureSecureSession();
        }
    }

    /**
     * Configure les paramètres de sécurité de la session.
     *
     * =========================================================================
     * PARAMÈTRES CONFIGURÉS
     * =========================================================================
     *
     * Cette méthode configure PHP pour des sessions plus sécurisées :
     *
     * 1. HttpOnly : Le cookie ne peut pas être lu par JavaScript
     *    → Protège contre les attaques XSS
     *
     * 2. SameSite=Lax : Le cookie n'est envoyé qu'aux requêtes du même site
     *    → Protège contre certaines attaques CSRF
     *
     * 3. Strict Mode : N'accepte pas les ID fournis par le client
     *    → Protège contre le Session Fixation
     *
     * 4. Secure (si HTTPS) : Cookie transmis uniquement sur HTTPS
     *    → Empêche l'interception du cookie
     *
     * QU'EST-CE QUE ini_set() ?
     * -------------------------
     * ini_set() modifie les paramètres de configuration PHP à l'exécution.
     * Ces paramètres sont définis dans php.ini mais peuvent être changés
     * dynamiquement par le code.
     *
     * @return void
     */
    private function configureSecureSession(): void
    {
        // Ne pas configurer si une session est déjà active
        if (PHP_SESSION_ACTIVE === session_status()) {
            return;
        }

        // =====================================================================
        // Paramètres de sécurité
        // =====================================================================

        // HttpOnly : empêche JavaScript de lire le cookie PHPSESSID
        // Protège contre les attaques XSS (vol de session)
        ini_set('session.cookie_httponly', '1');

        // SameSite : le cookie n'est pas envoyé lors de requêtes cross-site
        // "Lax" est un bon compromis entre sécurité et utilisabilité
        ini_set('session.cookie_samesite', 'Lax');

        // Strict Mode : n'accepte pas les ID de session fournis par le client
        // Protège contre le Session Fixation
        ini_set('session.use_strict_mode', '1');

        // =====================================================================
        // Cookie Secure (HTTPS uniquement)
        // =====================================================================

        // Si le site utilise HTTPS, le cookie n'est transmis que sur HTTPS
        // Empêche l'interception du cookie sur des connexions non sécurisées
        if (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']) {
            ini_set('session.cookie_secure', '1');
        }
    }

    /**
     * {@inheritdoc}
     *
     * Démarre la session PHP et initialise les messages flash.
     */
    public function start(): void
    {
        // Évite de démarrer plusieurs fois
        if ($this->started) {
            return;
        }

        // En mode production, démarre la vraie session PHP
        if (!$this->testMode) {
            // session_status() retourne :
            // - PHP_SESSION_DISABLED (0) : sessions désactivées
            // - PHP_SESSION_NONE (1) : sessions activées mais pas démarrées
            // - PHP_SESSION_ACTIVE (2) : session active
            if (PHP_SESSION_ACTIVE !== session_status()) {
                session_start();
            }
            // Nettoie les anciens messages flash
            $this->ageFlashData();
        }

        $this->started = true;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        if ($this->testMode) {
            // Mode test : lit depuis la mémoire
            return $this->testData[$key] ?? $default;
        }

        // Mode production : lit depuis $_SESSION
        return $_SESSION[$key] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();

        if ($this->testMode) {
            // Mode test : écrit en mémoire
            $this->testData[$key] = $value;
        } else {
            // Mode production : écrit dans $_SESSION
            $_SESSION[$key] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();

        if ($this->testMode) {
            return array_key_exists($key, $this->testData);
        }

        return array_key_exists($key, $_SESSION);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): void
    {
        $this->ensureStarted();

        if ($this->testMode) {
            unset($this->testData[$key]);
        } else {
            unset($_SESSION[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flash(string $key, mixed $value): void
    {
        $this->ensureStarted();

        if ($this->testMode) {
            // Stocke le message flash en mémoire
            /** @var array<string, mixed> $flash */
            $flash = $this->testData[self::FLASH_KEY] ?? [];
            $flash[$key] = $value;
            $this->testData[self::FLASH_KEY] = $flash;

            // Marque comme "nouveau" (créé dans cette requête)
            /** @var array<string> $newFlash */
            $newFlash = $this->testData[self::FLASH_NEW_KEY] ?? [];
            $newFlash[] = $key;
            $this->testData[self::FLASH_NEW_KEY] = array_unique($newFlash);
        } else {
            // Mode production
            /** @var array<string, mixed> $flash */
            $flash = $_SESSION[self::FLASH_KEY] ?? [];
            $flash[$key] = $value;
            $_SESSION[self::FLASH_KEY] = $flash;

            /** @var array<string> $newFlash */
            $newFlash = $_SESSION[self::FLASH_NEW_KEY] ?? [];
            $newFlash[] = $key;
            $_SESSION[self::FLASH_NEW_KEY] = array_unique($newFlash);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        if ($this->testMode) {
            /** @var array<string, mixed> $flash */
            $flash = $this->testData[self::FLASH_KEY] ?? [];
            $value = $flash[$key] ?? $default;
            // Supprime après lecture
            unset($flash[$key]);
            $this->testData[self::FLASH_KEY] = $flash;
            return $value;
        }

        /** @var array<string, mixed> $flash */
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        $value = $flash[$key] ?? $default;
        // Supprime après lecture
        unset($flash[$key]);
        $_SESSION[self::FLASH_KEY] = $flash;

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate(): void
    {
        // session_regenerate_id() génère un nouvel ID tout en gardant les données
        // Le paramètre true supprime l'ancien fichier de session
        if (!$this->testMode && PHP_SESSION_ACTIVE === session_status()) {
            session_regenerate_id(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(): void
    {
        if ($this->testMode) {
            // Mode test : vide la mémoire
            $this->testData = [];
            $this->started = false;
            return;
        }

        // Mode production : destruction complète
        if (PHP_SESSION_ACTIVE === session_status()) {
            // 1. Vide les données
            $_SESSION = [];

            // 2. Supprime le cookie de session
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                $sessionName = session_name();
                if (false !== $sessionName) {
                    setcookie(
                        $sessionName,
                        '',
                        time() - 42000,  // Expiration dans le passé = suppression
                        $params['path'],
                        $params['domain'],
                        $params['secure'],
                        $params['httponly']
                    );
                }
            }

            // 3. Détruit le fichier de session sur le serveur
            session_destroy();
        }

        $this->started = false;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        $this->ensureStarted();

        if ($this->testMode) {
            $data = $this->testData;
            // Exclut les données internes des messages flash
            unset($data[self::FLASH_KEY], $data[self::FLASH_NEW_KEY]);
            /** @var array<string, mixed> $data */
            return $data;
        }

        $data = $_SESSION;
        unset($data[self::FLASH_KEY], $data[self::FLASH_NEW_KEY]);
        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * Nettoie les anciens messages flash.
     *
     * =========================================================================
     * CYCLE DE VIE DES MESSAGES FLASH
     * =========================================================================
     *
     * Les messages flash créés dans une requête doivent être disponibles
     * dans la requête SUIVANTE, puis supprimés.
     *
     * Cette méthode est appelée au début de chaque requête pour :
     * 1. Supprimer les messages des requêtes précédentes
     * 2. Garder les messages créés dans cette requête
     *
     * ```
     *  Requête 1 :
     *  - $session->flash('success', 'Message A')
     *  - _flash = { 'success' => 'Message A' }
     *  - _flash_new = ['success']
     *
     *  Requête 2 :
     *  - ageFlashData() garde 'success' car il est dans _flash_new
     *  - getFlash('success') retourne 'Message A'
     *  - _flash = {} (vidé après lecture)
     *
     *  Requête 3 :
     *  - ageFlashData() n'a rien à faire
     *  - getFlash('success') retourne null
     * ```
     *
     * @return void
     */
    private function ageFlashData(): void
    {
        if ($this->testMode) {
            return;
        }

        /** @var array<string, mixed> $flash */
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        /** @var array<string> $newKeys */
        $newKeys = $_SESSION[self::FLASH_NEW_KEY] ?? [];

        // Supprime les messages flash qui n'ont pas été créés dans cette requête
        foreach (array_keys($flash) as $key) {
            if (!in_array($key, $newKeys, true)) {
                unset($flash[$key]);
            }
        }

        $_SESSION[self::FLASH_KEY] = $flash;
        // Réinitialise la liste des nouveaux flash pour la prochaine requête
        $_SESSION[self::FLASH_NEW_KEY] = [];
    }

    /**
     * S'assure que la session est démarrée.
     *
     * Cette méthode est appelée automatiquement avant chaque opération
     * pour garantir que la session est active.
     *
     * @return void
     */
    private function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }
}
