<?php
/**
 * Lunar Quanta Framework - Contrôleur d'Authentification.
 *
 * =============================================================================
 * RESPONSABILITÉS
 * =============================================================================
 *
 * Ce contrôleur gère toutes les actions liées à l'authentification :
 *
 * ```
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │                      ACTIONS D'AUTHENTIFICATION                          │
 * ├──────────────┬──────────────┬────────────────────────────────────────────┤
 * │ Route        │ Méthode      │ Description                                │
 * ├──────────────┼──────────────┼────────────────────────────────────────────┤
 * │ /login       │ GET          │ Affiche le formulaire de connexion         │
 * │ /login       │ POST         │ Traite la connexion                        │
 * │ /logout      │ POST         │ Déconnecte l'utilisateur                   │
 * │ /register    │ GET          │ Affiche le formulaire d'inscription        │
 * │ /register    │ POST         │ Traite l'inscription                       │
 * └──────────────┴──────────────┴────────────────────────────────────────────┘
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
use Lunar\Service\Security\Auth\Authenticator;
use Lunar\Service\Security\Auth\InMemoryUserProvider;
use Lunar\Service\Security\Auth\PasswordHasher;
use Lunar\Service\Security\Auth\PasswordResetService;
use Lunar\Service\Session\SessionService;
use Lunar\Service\Storage\JsonStorage;
use Lunar\Service\Validation\Validator;

/**
 * Contrôleur pour l'authentification des utilisateurs.
 *
 * Gère la connexion, la déconnexion et l'inscription des utilisateurs.
 *
 * =============================================================================
 * FLUX D'AUTHENTIFICATION
 * =============================================================================
 *
 * ```
 * INSCRIPTION                           CONNEXION
 * ───────────                           ─────────
 *     │                                     │
 *     ▼                                     ▼
 * GET /register                         GET /login
 *     │                                     │
 *     ▼                                     ▼
 * ┌─────────────────┐                 ┌─────────────────┐
 * │  Formulaire     │                 │  Formulaire     │
 * │  d'inscription  │                 │  de connexion   │
 * └────────┬────────┘                 └────────┬────────┘
 *          │                                   │
 *          ▼                                   ▼
 * POST /register                        POST /login
 *          │                                   │
 *          ▼                                   ▼
 * ┌─────────────────┐                 ┌─────────────────┐
 * │ 1. Valider      │                 │ 1. Valider      │
 * │ 2. Créer user   │                 │ 2. Authentifier │
 * │ 3. Login auto   │                 │ 3. Session      │
 * └────────┬────────┘                 └────────┬────────┘
 *          │                                   │
 *          └───────────────┬───────────────────┘
 *                          │
 *                          ▼
 *                    /dashboard
 * ```
 *
 * @package Lunar\Controller
 */
class AuthController extends BaseController
{
    private Authenticator $auth;
    private SessionService $session;
    private JsonStorage $storage;
    private Validator $validator;
    private PasswordHasher $hasher;
    private PasswordResetService $passwordReset;

    public function __construct()
    {
        parent::__construct();

        $this->session = new SessionService();
        $this->storage = new JsonStorage();
        $this->hasher = new PasswordHasher();
        $this->validator = new Validator();
        $this->passwordReset = new PasswordResetService($this->storage);

        // Configure les messages de validation en français
        $this->validator->setMessages([
            'email' => [
                'required' => 'L\'email est obligatoire.',
                'email' => 'L\'adresse email n\'est pas valide.',
            ],
            'password' => [
                'required' => 'Le mot de passe est obligatoire.',
                'min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            ],
            'name' => [
                'required' => 'Le nom est obligatoire.',
                'min' => 'Le nom doit contenir au moins 2 caractères.',
            ],
            'password_confirm' => [
                'required' => 'La confirmation du mot de passe est obligatoire.',
            ],
        ]);

        // Crée l'authenticator avec un provider en mémoire pour cet exemple
        // En production, utilisez un DatabaseUserProvider
        $userProvider = new InMemoryUserProvider();
        $this->auth = new Authenticator($userProvider, $this->hasher, $this->session);
    }

    // =========================================================================
    // CONNEXION
    // =========================================================================

    /**
     * Affiche le formulaire de connexion.
     *
     * @param Request $request La requête HTTP
     *
     * @return Response Le formulaire de connexion
     */
    #[Route('/login', methods: ['GET'], name: 'auth.login')]
    public function showLogin(Request $request): Response
    {
        // Si déjà connecté, redirige vers le dashboard
        if ($this->auth->check()) {
            return $this->redirect('/dashboard');
        }

        return $this->renderView('auth/login', [
            'title' => 'Connexion',
            'error' => $this->session->getFlash('error'),
        ]);
    }

    /**
     * Traite la soumission du formulaire de connexion.
     *
     * @param Request $request La requête avec les données du formulaire
     *
     * @return Response Redirection ou erreur
     */
    #[Route('/login', methods: ['POST'], name: 'auth.login.post')]
    public function login(Request $request): Response
    {
        $data = $request->getPostParams();

        // Validation des données
        $result = $this->validator->validate($data, [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($result->hasErrors()) {
            $this->session->setFlash('error', $result->getFirstError('email') ?? $result->getFirstError('password'));

            return $this->redirect('/login');
        }

        // Tentative d'authentification
        $user = $this->auth->attempt($data['email'], $data['password']);

        if ($user === null) {
            $this->session->setFlash('error', 'Email ou mot de passe incorrect.');

            return $this->redirect('/login');
        }

        // Connexion réussie
        $this->session->setFlash('success', 'Bienvenue !');

        return $this->redirect('/dashboard');
    }

    // =========================================================================
    // DÉCONNEXION
    // =========================================================================

    /**
     * Déconnecte l'utilisateur.
     *
     * @param Request $request La requête HTTP
     *
     * @return Response Redirection vers l'accueil
     */
    #[Route('/logout', methods: ['POST', 'GET'], name: 'auth.logout')]
    public function logout(Request $request): Response
    {
        $this->auth->logout();
        $this->session->setFlash('success', 'Vous êtes déconnecté.');

        return $this->redirect('/');
    }

    // =========================================================================
    // INSCRIPTION
    // =========================================================================

    /**
     * Affiche le formulaire d'inscription.
     *
     * @param Request $request La requête HTTP
     *
     * @return Response Le formulaire d'inscription
     */
    #[Route('/register', methods: ['GET'], name: 'auth.register')]
    public function showRegister(Request $request): Response
    {
        // Si déjà connecté, redirige
        if ($this->auth->check()) {
            return $this->redirect('/dashboard');
        }

        return $this->renderView('auth/register', [
            'title' => 'Inscription',
            'errors' => $this->session->getFlash('errors') ?? [],
            'old' => $this->session->getFlash('old') ?? [],
        ]);
    }

    /**
     * Traite la soumission du formulaire d'inscription.
     *
     * @param Request $request La requête avec les données du formulaire
     *
     * @return Response Redirection ou erreurs
     */
    #[Route('/register', methods: ['POST'], name: 'auth.register.post')]
    public function register(Request $request): Response
    {
        $data = $request->getPostParams();

        // Validation des données
        $result = $this->validator->validate($data, [
            'name' => ['required', 'min:2', 'max:50'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
            'password_confirm' => ['required'],
        ]);

        // Vérification que les mots de passe correspondent
        if (($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
            $result->addError('password_confirm', 'Les mots de passe ne correspondent pas.');
        }

        // S'il y a des erreurs, redirige avec les erreurs
        if ($result->hasErrors()) {
            $this->session->setFlash('errors', $result->getAllErrors());
            $this->session->setFlash('old', [
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
            ]);

            return $this->redirect('/register');
        }

        // Création de l'utilisateur
        $user = new User($data['email'], $data['name'], $data['password']);
        $this->storage->saveUser($user);

        // Connexion automatique après inscription
        $this->auth->login($user);
        $this->session->setFlash('success', 'Votre compte a été créé avec succès !');

        return $this->redirect('/dashboard');
    }

    // =========================================================================
    // RÉCUPÉRATION DE MOT DE PASSE
    // =========================================================================

    /**
     * Affiche le formulaire de demande de reset.
     *
     * @param Request $request La requête HTTP
     *
     * @return Response Le formulaire
     */
    #[Route('/forgot-password', methods: ['GET'], name: 'auth.forgot')]
    public function showForgotPassword(Request $request): Response
    {
        // Si connecté, redirige
        if ($this->auth->check()) {
            return $this->redirect('/dashboard');
        }

        return $this->renderView('auth/forgot-password', [
            'title' => 'Mot de passe oublié',
            'error' => $this->session->getFlash('error'),
            'success' => $this->session->getFlash('success'),
        ]);
    }

    /**
     * Traite la demande de reset de mot de passe.
     *
     * Pour des raisons de sécurité, affiche toujours le même message
     * que l'email existe ou non (empêche l'énumération des utilisateurs).
     *
     * @param Request $request La requête avec l'email
     *
     * @return Response Redirection avec message
     */
    #[Route('/forgot-password', methods: ['POST'], name: 'auth.forgot.post')]
    public function forgotPassword(Request $request): Response
    {
        $data = $request->getPostParams();

        // Validation
        $result = $this->validator->validate($data, [
            'email' => ['required', 'email'],
        ]);

        if ($result->hasErrors()) {
            $this->session->setFlash('error', $result->getFirstError('email'));

            return $this->redirect('/forgot-password');
        }

        $email = $data['email'];

        // Génère le token (si l'utilisateur existe)
        $user = $this->storage->loadUser($email);

        if ($user !== null) {
            // Crée l'URL de reset
            $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $scheme . '://' . $host;

            $resetUrl = $this->passwordReset->createResetUrl($email, $baseUrl);

            // En production, envoyer un email ici
            // Pour le dev, on log l'URL
            error_log("Password reset URL for {$email}: {$resetUrl}");
        }

        // Message identique dans tous les cas (sécurité)
        $this->session->setFlash(
            'success',
            'Si cette adresse email est associée à un compte, vous recevrez un lien de réinitialisation.'
        );

        return $this->redirect('/forgot-password');
    }

    /**
     * Affiche le formulaire de nouveau mot de passe.
     *
     * @param Request $request La requête avec le token
     *
     * @return Response Le formulaire ou erreur
     */
    #[Route('/reset-password', methods: ['GET'], name: 'auth.reset')]
    public function showResetPassword(Request $request): Response
    {
        $token = $request->getQueryParams()['token'] ?? '';
        $email = $request->getQueryParams()['email'] ?? '';

        // Vérifie le token
        if (!$this->passwordReset->isTokenValid($email, $token)) {
            $this->session->setFlash('error', 'Ce lien de réinitialisation est invalide ou expiré.');

            return $this->redirect('/forgot-password');
        }

        return $this->renderView('auth/reset-password', [
            'title' => 'Nouveau mot de passe',
            'token' => $token,
            'email' => $email,
            'errors' => $this->session->getFlash('errors') ?? [],
        ]);
    }

    /**
     * Traite le changement de mot de passe.
     *
     * @param Request $request La requête avec le nouveau mot de passe
     *
     * @return Response Redirection
     */
    #[Route('/reset-password', methods: ['POST'], name: 'auth.reset.post')]
    public function resetPassword(Request $request): Response
    {
        $data = $request->getPostParams();

        $token = $data['token'] ?? '';
        $email = $data['email'] ?? '';

        // Validation
        $result = $this->validator->validate($data, [
            'password' => ['required', 'min:8'],
            'password_confirm' => ['required'],
        ]);

        if (($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
            $result->addError('password_confirm', 'Les mots de passe ne correspondent pas.');
        }

        if ($result->hasErrors()) {
            $this->session->setFlash('errors', $result->getAllErrors());

            return $this->redirect('/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email));
        }

        // Tente le reset
        $success = $this->passwordReset->resetPassword($email, $token, $data['password']);

        if (!$success) {
            $this->session->setFlash('error', 'Ce lien de réinitialisation est invalide ou expiré.');

            return $this->redirect('/forgot-password');
        }

        $this->session->setFlash('success', 'Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.');

        return $this->redirect('/login');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Redirige vers une URL.
     *
     * @param string $url L'URL de destination
     *
     * @return Response La réponse de redirection
     */
    private function redirect(string $url): Response
    {
        return new Response('', 302, ['Location' => $url]);
    }

    /**
     * Rendu d'une vue avec le layout.
     *
     * @param string               $view      Le nom de la vue
     * @param array<string, mixed> $variables Les variables à passer
     *
     * @return Response La réponse HTML
     */
    private function renderView(string $view, array $variables = []): Response
    {
        $html = $this->render($view, $variables);

        return new Response($html);
    }
}
