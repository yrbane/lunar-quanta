<?php
/**
 * Lunar Quanta Framework - Contrôleur 2FA.
 *
 * =============================================================================
 * ROUTES 2FA
 * =============================================================================
 *
 * ```
 * ┌───────────────┬──────────────┬────────────────────────────────────────────┐
 * │ Route         │ Méthode      │ Description                                │
 * ├───────────────┼──────────────┼────────────────────────────────────────────┤
 * │ /2fa/setup    │ GET          │ Affiche le QR code pour activer 2FA       │
 * │ /2fa/setup    │ POST         │ Valide le code et active 2FA              │
 * │ /2fa/verify   │ GET          │ Formulaire de vérification 2FA            │
 * │ /2fa/verify   │ POST         │ Vérifie le code TOTP                      │
 * │ /2fa/disable  │ POST         │ Désactive le 2FA                          │
 * │ /2fa/recovery │ GET          │ Affiche les codes de récupération         │
 * │ /2fa/recovery │ POST         │ Régénère les codes de récupération        │
 * └───────────────┴──────────────┴────────────────────────────────────────────┘
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
use Lunar\Entity\TwoFactorSecret;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Security\Auth\Authenticator;
use Lunar\Service\Security\Auth\InMemoryUserProvider;
use Lunar\Service\Security\Auth\PasswordHasher;
use Lunar\Service\Security\TwoFactor\TotpService;
use Lunar\Service\Session\SessionService;

/**
 * Contrôleur pour la gestion de l'authentification à deux facteurs.
 */
class TwoFactorController extends BaseController
{
    private TotpService $totp;
    private SessionService $session;
    private Authenticator $auth;

    /**
     * Chemin de stockage des secrets 2FA.
     */
    private const SECRETS_PATH = 'data/2fa';

    public function __construct()
    {
        parent::__construct();

        $this->totp = new TotpService();
        $this->session = new SessionService();

        $userProvider = new InMemoryUserProvider();
        $this->auth = new Authenticator($userProvider, new PasswordHasher(), $this->session);
    }

    // =========================================================================
    // CONFIGURATION 2FA
    // =========================================================================

    /**
     * Affiche la page de configuration 2FA avec le QR code.
     */
    #[Route('/2fa/setup', methods: ['GET'], name: '2fa.setup')]
    public function showSetup(Request $request): Response
    {
        // Vérifie que l'utilisateur est connecté
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }

        $user = $this->auth->user();
        if ($user === null) {
            return $this->redirect('/login');
        }

        // Vérifie si 2FA déjà activé
        $existingSecret = $this->loadSecret($user->getId());
        if ($existingSecret !== null && $existingSecret->isEnabled()) {
            $this->session->setFlash('info', 'L\'authentification à deux facteurs est déjà activée.');

            return $this->redirect('/dashboard');
        }

        // Génère un nouveau secret
        $secret = $this->totp->generateSecret();
        $this->session->set('2fa_pending_secret', $secret);

        // Génère le QR code
        $qrCodeUrl = $this->totp->getQrCodeUrl($secret, $user->getIdentifier());

        return $this->renderView('2fa/setup', [
            'title' => 'Activer l\'authentification à deux facteurs',
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
            'error' => $this->session->getFlash('error'),
        ]);
    }

    /**
     * Valide le code et active le 2FA.
     */
    #[Route('/2fa/setup', methods: ['POST'], name: '2fa.setup.post')]
    public function setup(Request $request): Response
    {
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }

        $user = $this->auth->user();
        if ($user === null) {
            return $this->redirect('/login');
        }

        $data = $request->getPostParams();
        $code = $data['code'] ?? '';
        $secret = $this->session->get('2fa_pending_secret');

        if (empty($secret)) {
            $this->session->setFlash('error', 'Session expirée. Veuillez recommencer.');

            return $this->redirect('/2fa/setup');
        }

        // Vérifie le code
        if (!$this->totp->verifyCode($secret, $code)) {
            $this->session->setFlash('error', 'Code invalide. Veuillez réessayer.');

            return $this->redirect('/2fa/setup');
        }

        // Génère les codes de récupération
        $recoveryCodes = $this->totp->generateRecoveryCodes();
        $hashedCodes = array_map(
            fn($code) => $this->totp->hashRecoveryCode($code),
            $recoveryCodes
        );

        // Sauvegarde le secret
        $twoFactorSecret = new TwoFactorSecret($user->getId(), $secret);
        $twoFactorSecret->enable();
        $twoFactorSecret->setRecoveryCodes($hashedCodes);
        $this->saveSecret($twoFactorSecret);

        // Nettoie la session
        $this->session->remove('2fa_pending_secret');

        // Stocke les codes en clair pour affichage unique
        $this->session->set('2fa_recovery_codes', $recoveryCodes);
        $this->session->setFlash('success', 'L\'authentification à deux facteurs a été activée !');

        return $this->redirect('/2fa/recovery');
    }

    // =========================================================================
    // VÉRIFICATION 2FA (lors de la connexion)
    // =========================================================================

    /**
     * Affiche le formulaire de vérification 2FA.
     */
    #[Route('/2fa/verify', methods: ['GET'], name: '2fa.verify')]
    public function showVerify(Request $request): Response
    {
        // L'utilisateur doit être en "attente 2FA"
        $pendingUserId = $this->session->get('2fa_pending_user_id');

        if (empty($pendingUserId)) {
            return $this->redirect('/login');
        }

        return $this->renderView('2fa/verify', [
            'title' => 'Vérification en deux étapes',
            'error' => $this->session->getFlash('error'),
        ]);
    }

    /**
     * Vérifie le code TOTP.
     */
    #[Route('/2fa/verify', methods: ['POST'], name: '2fa.verify.post')]
    public function verify(Request $request): Response
    {
        $pendingUserId = $this->session->get('2fa_pending_user_id');

        if (empty($pendingUserId)) {
            return $this->redirect('/login');
        }

        $data = $request->getPostParams();
        $code = $data['code'] ?? '';

        $twoFactorSecret = $this->loadSecret($pendingUserId);

        if ($twoFactorSecret === null || !$twoFactorSecret->isEnabled()) {
            return $this->redirect('/login');
        }

        // Vérifie le code TOTP
        if ($this->totp->verifyCode($twoFactorSecret->getSecret(), $code)) {
            // Connexion réussie
            $this->session->remove('2fa_pending_user_id');
            $this->session->set('_auth_user_id', $pendingUserId);
            $this->session->setFlash('success', 'Connexion réussie !');

            return $this->redirect('/dashboard');
        }

        // Vérifie si c'est un code de récupération
        $recoveryIndex = $this->totp->verifyRecoveryCode($code, $twoFactorSecret->getRecoveryCodes());

        if ($recoveryIndex !== false) {
            // Invalide le code utilisé
            $twoFactorSecret->invalidateRecoveryCode($recoveryIndex);
            $this->saveSecret($twoFactorSecret);

            // Connexion réussie
            $this->session->remove('2fa_pending_user_id');
            $this->session->set('_auth_user_id', $pendingUserId);
            $this->session->setFlash(
                'warning',
                'Connexion avec un code de récupération. Il vous reste '
                . $twoFactorSecret->getRemainingRecoveryCodesCount() . ' codes.'
            );

            return $this->redirect('/dashboard');
        }

        $this->session->setFlash('error', 'Code invalide. Veuillez réessayer.');

        return $this->redirect('/2fa/verify');
    }

    // =========================================================================
    // CODES DE RÉCUPÉRATION
    // =========================================================================

    /**
     * Affiche les codes de récupération (une seule fois après activation).
     */
    #[Route('/2fa/recovery', methods: ['GET'], name: '2fa.recovery')]
    public function showRecovery(Request $request): Response
    {
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }

        // Récupère les codes en clair (uniquement disponibles juste après activation)
        $recoveryCodes = $this->session->get('2fa_recovery_codes');
        $this->session->remove('2fa_recovery_codes'); // Affichage unique

        $user = $this->auth->user();
        $twoFactorSecret = $user ? $this->loadSecret($user->getId()) : null;
        $remainingCount = $twoFactorSecret?->getRemainingRecoveryCodesCount() ?? 0;

        return $this->renderView('2fa/recovery', [
            'title' => 'Codes de récupération',
            'recoveryCodes' => $recoveryCodes,
            'remainingCount' => $remainingCount,
            'success' => $this->session->getFlash('success'),
        ]);
    }

    /**
     * Régénère les codes de récupération.
     */
    #[Route('/2fa/recovery', methods: ['POST'], name: '2fa.recovery.post')]
    public function regenerateRecovery(Request $request): Response
    {
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }

        $user = $this->auth->user();
        if ($user === null) {
            return $this->redirect('/login');
        }

        $twoFactorSecret = $this->loadSecret($user->getId());

        if ($twoFactorSecret === null || !$twoFactorSecret->isEnabled()) {
            return $this->redirect('/dashboard');
        }

        // Génère de nouveaux codes
        $recoveryCodes = $this->totp->generateRecoveryCodes();
        $hashedCodes = array_map(
            fn($code) => $this->totp->hashRecoveryCode($code),
            $recoveryCodes
        );

        $twoFactorSecret->setRecoveryCodes($hashedCodes);
        $this->saveSecret($twoFactorSecret);

        $this->session->set('2fa_recovery_codes', $recoveryCodes);
        $this->session->setFlash('success', 'Nouveaux codes de récupération générés.');

        return $this->redirect('/2fa/recovery');
    }

    // =========================================================================
    // DÉSACTIVATION
    // =========================================================================

    /**
     * Désactive le 2FA.
     */
    #[Route('/2fa/disable', methods: ['POST'], name: '2fa.disable')]
    public function disable(Request $request): Response
    {
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }

        $user = $this->auth->user();
        if ($user === null) {
            return $this->redirect('/login');
        }

        $data = $request->getPostParams();
        $password = $data['password'] ?? '';

        // Vérifie le mot de passe
        if (!password_verify($password, $user->getPassword())) {
            $this->session->setFlash('error', 'Mot de passe incorrect.');

            return $this->redirect('/2fa/recovery');
        }

        // Supprime le secret 2FA
        $this->deleteSecret($user->getId());
        $this->session->setFlash('success', 'L\'authentification à deux facteurs a été désactivée.');

        return $this->redirect('/dashboard');
    }

    // =========================================================================
    // PERSISTANCE
    // =========================================================================

    private function saveSecret(TwoFactorSecret $secret): void
    {
        $path = $this->getSecretPath($secret->getUserId());
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($secret->toArray(), JSON_PRETTY_PRINT));
    }

    private function loadSecret(string $userId): ?TwoFactorSecret
    {
        $path = $this->getSecretPath($userId);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        return TwoFactorSecret::fromArray($data);
    }

    private function deleteSecret(string $userId): void
    {
        $path = $this->getSecretPath($userId);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function getSecretPath(string $userId): string
    {
        $hash = hash('sha256', $userId);

        return getcwd() . '/' . self::SECRETS_PATH . '/' . $hash . '.json';
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function redirect(string $url): Response
    {
        return new Response('', 302, ['Location' => $url]);
    }

    private function renderView(string $view, array $variables = []): Response
    {
        return new Response($this->render($view, $variables));
    }
}
