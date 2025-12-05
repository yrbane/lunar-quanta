<?php
/**
 * Lunar Quanta Framework - Contrôleur OAuth.
 *
 * =============================================================================
 * ROUTES OAUTH
 * =============================================================================
 *
 * ```
 * ┌─────────────────────────┬─────────┬─────────────────────────────────────┐
 * │ Route                   │ Méthode │ Description                         │
 * ├─────────────────────────┼─────────┼─────────────────────────────────────┤
 * │ /oauth/{provider}       │ GET     │ Redirige vers le provider OAuth     │
 * │ /oauth/{provider}/callback │ GET  │ Callback après autorisation         │
 * └─────────────────────────┴─────────┴─────────────────────────────────────┘
 * ```
 *
 * @package    Lunar\Controller
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Controller;

use Lunar\Attribute\Route;
use Lunar\Entity\User;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Security\OAuth\GitHubProvider;
use Lunar\Service\Security\OAuth\GoogleProvider;
use Lunar\Service\Security\OAuth\OAuthException;
use Lunar\Service\Security\OAuth\OAuthProviderInterface;
use Lunar\Service\Security\OAuth\OAuthUser;
use Lunar\Service\Session\SessionService;
use Lunar\Service\Storage\JsonStorage;

/**
 * Contrôleur pour l'authentification OAuth.
 */
class OAuthController extends BaseController
{
    private SessionService $session;
    private JsonStorage $storage;

    /** @var array<string, OAuthProviderInterface> */
    private array $providers = [];

    /**
     * Chemin de stockage des liaisons OAuth.
     */
    private const OAUTH_LINKS_PATH = 'data/oauth_links';

    public function __construct()
    {
        parent::__construct();

        $this->session = new SessionService();
        $this->storage = new JsonStorage();

        // Initialise les providers depuis la configuration
        $this->initializeProviders();
    }

    /**
     * Initialise les providers OAuth depuis les variables d'environnement.
     */
    private function initializeProviders(): void
    {
        $baseUrl = $this->getBaseUrl();

        // Google
        $googleClientId = getenv('GOOGLE_CLIENT_ID');
        $googleClientSecret = getenv('GOOGLE_CLIENT_SECRET');

        if ($googleClientId && $googleClientSecret) {
            $this->providers['google'] = new GoogleProvider(
                $googleClientId,
                $googleClientSecret,
                $baseUrl . '/oauth/google/callback'
            );
        }

        // GitHub
        $githubClientId = getenv('GITHUB_CLIENT_ID');
        $githubClientSecret = getenv('GITHUB_CLIENT_SECRET');

        if ($githubClientId && $githubClientSecret) {
            $this->providers['github'] = new GitHubProvider(
                $githubClientId,
                $githubClientSecret,
                $baseUrl . '/oauth/github/callback'
            );
        }
    }

    // =========================================================================
    // ROUTES OAUTH
    // =========================================================================

    /**
     * Initie le flux OAuth vers Google.
     */
    #[Route('/oauth/google', methods: ['GET'], name: 'oauth.google')]
    public function googleAuth(Request $request): Response
    {
        return $this->initiateOAuth('google');
    }

    /**
     * Callback OAuth Google.
     */
    #[Route('/oauth/google/callback', methods: ['GET'], name: 'oauth.google.callback')]
    public function googleCallback(Request $request): Response
    {
        return $this->handleCallback('google', $request);
    }

    /**
     * Initie le flux OAuth vers GitHub.
     */
    #[Route('/oauth/github', methods: ['GET'], name: 'oauth.github')]
    public function githubAuth(Request $request): Response
    {
        return $this->initiateOAuth('github');
    }

    /**
     * Callback OAuth GitHub.
     */
    #[Route('/oauth/github/callback', methods: ['GET'], name: 'oauth.github.callback')]
    public function githubCallback(Request $request): Response
    {
        return $this->handleCallback('github', $request);
    }

    // =========================================================================
    // LOGIQUE OAUTH
    // =========================================================================

    /**
     * Initie le flux OAuth.
     *
     * @param string $providerName Le nom du provider
     */
    private function initiateOAuth(string $providerName): Response
    {
        $provider = $this->providers[$providerName] ?? null;

        if ($provider === null) {
            $this->session->setFlash('error', "Le provider OAuth '{$providerName}' n'est pas configuré.");

            return $this->redirect('/login');
        }

        // Génère un state CSRF
        $state = bin2hex(random_bytes(16));
        $this->session->set('oauth_state', $state);
        $this->session->set('oauth_provider', $providerName);

        // Redirige vers le provider
        $authUrl = $provider->getAuthorizationUrl($state);

        return $this->redirect($authUrl);
    }

    /**
     * Gère le callback OAuth.
     *
     * @param string  $providerName Le nom du provider
     * @param Request $request      La requête
     */
    private function handleCallback(string $providerName, Request $request): Response
    {
        $query = $request->getQueryParams();

        // Vérifie les erreurs OAuth
        if (isset($query['error'])) {
            $error = $query['error_description'] ?? $query['error'];
            $this->session->setFlash('error', "Erreur OAuth : {$error}");

            return $this->redirect('/login');
        }

        // Vérifie le state CSRF
        $expectedState = $this->session->get('oauth_state');
        $receivedState = $query['state'] ?? '';

        if (!hash_equals($expectedState ?? '', $receivedState)) {
            $this->session->setFlash('error', 'Erreur de sécurité : state invalide.');

            return $this->redirect('/login');
        }

        // Vérifie le code
        $code = $query['code'] ?? '';
        if (empty($code)) {
            $this->session->setFlash('error', 'Code d\'autorisation manquant.');

            return $this->redirect('/login');
        }

        $provider = $this->providers[$providerName] ?? null;
        if ($provider === null) {
            $this->session->setFlash('error', 'Provider non configuré.');

            return $this->redirect('/login');
        }

        try {
            // Échange le code contre un token
            $token = $provider->getAccessToken($code);

            // Récupère les infos utilisateur
            $oauthUser = $provider->getUser($token);

            // Nettoie la session OAuth
            $this->session->remove('oauth_state');
            $this->session->remove('oauth_provider');

            // Trouve ou crée l'utilisateur
            return $this->handleOAuthUser($oauthUser);
        } catch (OAuthException $e) {
            $this->session->setFlash('error', 'Erreur lors de l\'authentification : ' . $e->getMessage());

            return $this->redirect('/login');
        }
    }

    /**
     * Gère l'utilisateur OAuth (connexion ou création).
     *
     * @param OAuthUser $oauthUser Les infos OAuth
     */
    private function handleOAuthUser(OAuthUser $oauthUser): Response
    {
        // Cherche une liaison existante
        $userId = $this->findLinkedUser($oauthUser);

        if ($userId !== null) {
            // Utilisateur existant → connexion
            $this->session->set('_auth_user_id', $userId);
            $this->session->setFlash('success', 'Connexion réussie !');

            return $this->redirect('/dashboard');
        }

        // Cherche un utilisateur avec le même email
        $existingUser = $this->storage->loadUser($oauthUser->getEmail());

        if ($existingUser !== null) {
            // Lie le compte OAuth à l'utilisateur existant
            $this->linkOAuthToUser($oauthUser, $existingUser->getId());
            $this->session->set('_auth_user_id', $existingUser->getId());
            $this->session->setFlash(
                'success',
                "Votre compte {$oauthUser->getProvider()} a été lié à votre compte existant."
            );

            return $this->redirect('/dashboard');
        }

        // Crée un nouvel utilisateur
        $newUser = new User(
            $oauthUser->getEmail(),
            $oauthUser->getName() ?? $oauthUser->getEmail(),
            bin2hex(random_bytes(16)) // Mot de passe aléatoire (login OAuth uniquement)
        );

        $this->storage->saveUser($newUser);
        $this->linkOAuthToUser($oauthUser, $newUser->getId());

        $this->session->set('_auth_user_id', $newUser->getId());
        $this->session->setFlash('success', 'Compte créé avec succès via ' . ucfirst($oauthUser->getProvider()) . ' !');

        return $this->redirect('/dashboard');
    }

    // =========================================================================
    // GESTION DES LIAISONS OAUTH
    // =========================================================================

    /**
     * Trouve l'utilisateur lié à un compte OAuth.
     *
     * @param OAuthUser $oauthUser L'utilisateur OAuth
     *
     * @return string|null L'ID utilisateur ou null
     */
    private function findLinkedUser(OAuthUser $oauthUser): ?string
    {
        $path = $this->getLinkPath($oauthUser->getUniqueKey());

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        return $data['userId'] ?? null;
    }

    /**
     * Lie un compte OAuth à un utilisateur.
     *
     * @param OAuthUser $oauthUser L'utilisateur OAuth
     * @param string    $userId    L'ID de l'utilisateur local
     */
    private function linkOAuthToUser(OAuthUser $oauthUser, string $userId): void
    {
        $path = $this->getLinkPath($oauthUser->getUniqueKey());
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = [
            'userId' => $userId,
            'provider' => $oauthUser->getProvider(),
            'providerId' => $oauthUser->getProviderId(),
            'email' => $oauthUser->getEmail(),
            'linkedAt' => (new \DateTimeImmutable())->format('c'),
        ];

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Retourne le chemin du fichier de liaison.
     *
     * @param string $uniqueKey La clé unique OAuth (provider:providerId)
     */
    private function getLinkPath(string $uniqueKey): string
    {
        $hash = hash('sha256', $uniqueKey);

        return getcwd() . '/' . self::OAUTH_LINKS_PATH . '/' . $hash . '.json';
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function getBaseUrl(): string
    {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }

    private function redirect(string $url): Response
    {
        return new Response('', 302, ['Location' => $url]);
    }
}
