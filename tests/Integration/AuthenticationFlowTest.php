<?php
/**
 * Tests d'intégration du flux d'authentification complet.
 *
 * =============================================================================
 * QU'EST-CE QU'UN TEST D'INTÉGRATION ?
 * =============================================================================
 *
 * Un test d'intégration vérifie que plusieurs composants fonctionnent
 * ENSEMBLE correctement. Contrairement aux tests unitaires qui testent
 * une classe en isolation, les tests d'intégration testent des flux complets.
 *
 * ```
 * TEST UNITAIRE                       TEST D'INTÉGRATION
 *
 *    ┌─────────┐                      ┌─────────┐
 *    │ Classe  │                      │ Classe A│
 *    │  seule  │                      └────┬────┘
 *    └─────────┘                           │
 *        │                            ┌────▼────┐
 *        ▼                            │ Classe B│
 *    Assertions                       └────┬────┘
 *                                          │
 *                                     ┌────▼────┐
 *                                     │ Classe C│
 *                                     └────┬────┘
 *                                          │
 *                                          ▼
 *                                     Assertions
 * ```
 *
 * =============================================================================
 * FLUX D'AUTHENTIFICATION
 * =============================================================================
 *
 * Ce test vérifie le flux complet :
 *
 * ```
 * 1. Création d'un utilisateur (UserProvider)
 * 2. Tentative de connexion (Authenticator)
 * 3. Vérification de session (SessionService)
 * 4. Protection de routes (AuthMiddleware)
 * 5. Déconnexion (Authenticator)
 * ```
 *
 * @package Tests\Integration
 */
declare(strict_types=1);

namespace Tests\Integration;

use Lunar\Service\Security\Auth\Authenticator;
use Lunar\Service\Security\Auth\AuthMiddleware;
use Lunar\Service\Security\Auth\GuestMiddleware;
use Lunar\Service\Security\Auth\InMemoryUser;
use Lunar\Service\Security\Auth\InMemoryUserProvider;
use Lunar\Service\Security\Auth\PasswordHasher;
use Lunar\Service\Security\Auth\RoleMiddleware;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Session\SessionService;
use PHPUnit\Framework\TestCase;

class AuthenticationFlowTest extends TestCase
{
    private InMemoryUserProvider $userProvider;
    private PasswordHasher $hasher;
    private SessionService $session;
    private Authenticator $auth;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialiser les composants
        $this->hasher = new PasswordHasher();
        $this->userProvider = new InMemoryUserProvider();
        $this->session = new SessionService(true); // Mode test

        // Créer quelques utilisateurs de test
        $this->userProvider->createUser(
            1,
            'admin@example.com',
            'admin123',
            $this->hasher,
            ['ROLE_ADMIN', 'ROLE_USER']
        );
        $this->userProvider->createUser(
            2,
            'user@example.com',
            'user123',
            $this->hasher,
            ['ROLE_USER']
        );

        // Créer l'authenticateur (ordre: provider, hasher, session)
        $this->auth = new Authenticator(
            $this->userProvider,
            $this->hasher,
            $this->session
        );
    }

    // =========================================================================
    // TESTS DU FLUX DE CONNEXION
    // =========================================================================

    public function testCompleteLoginFlow(): void
    {
        // 1. Initialement, l'utilisateur n'est pas connecté
        $this->assertFalse($this->auth->check());
        $this->assertTrue($this->auth->guest());

        // 2. Tentative de connexion avec les bons identifiants
        $user = $this->auth->attempt('admin@example.com', 'admin123');

        // 3. L'utilisateur est maintenant connecté
        $this->assertNotNull($user);
        $this->assertTrue($this->auth->check());
        $this->assertFalse($this->auth->guest());

        // 4. Les informations utilisateur sont accessibles
        $this->assertSame('admin@example.com', $this->auth->user()?->getIdentifier());
        $this->assertSame(1, $this->auth->id());
    }

    public function testLoginWithWrongPassword(): void
    {
        // Tentative avec mauvais mot de passe
        $user = $this->auth->attempt('admin@example.com', 'wrongpassword');

        // Connexion échouée
        $this->assertNull($user);
        $this->assertFalse($this->auth->check());
        $this->assertTrue($this->auth->guest());
    }

    public function testLoginWithNonExistentUser(): void
    {
        // Tentative avec utilisateur inexistant
        $user = $this->auth->attempt('nonexistent@example.com', 'password');

        // Connexion échouée
        $this->assertNull($user);
        $this->assertFalse($this->auth->check());
    }

    public function testLogoutFlow(): void
    {
        // 1. Connexion
        $this->auth->attempt('user@example.com', 'user123');
        $this->assertTrue($this->auth->check());

        // 2. Déconnexion
        $this->auth->logout();

        // 3. Vérification
        $this->assertFalse($this->auth->check());
        $this->assertTrue($this->auth->guest());
        $this->assertNull($this->auth->user());
    }

    // =========================================================================
    // TESTS DE VALIDATION SANS CONNEXION
    // =========================================================================

    public function testValidateWithoutLogin(): void
    {
        // Valider les identifiants sans se connecter
        $this->assertTrue($this->auth->validate('admin@example.com', 'admin123'));

        // L'utilisateur ne doit PAS être connecté
        $this->assertFalse($this->auth->check());
    }

    public function testValidateWithWrongCredentials(): void
    {
        $this->assertFalse($this->auth->validate('admin@example.com', 'wrong'));
        $this->assertFalse($this->auth->validate('wrong@example.com', 'admin123'));
    }

    // =========================================================================
    // TESTS DES MIDDLEWARES D'AUTHENTIFICATION
    // =========================================================================

    public function testAuthMiddlewareBlocksUnauthenticated(): void
    {
        $middleware = new AuthMiddleware($this->auth);
        $request = $this->createMockRequest();

        $response = $middleware->process($request, function () {
            return new Response('Protected content');
        });

        // Devrait être redirigé ou bloqué (status 401 ou 302)
        $this->assertNotSame('Protected content', $response->getBody());
    }

    public function testAuthMiddlewareAllowsAuthenticated(): void
    {
        // Connecter l'utilisateur
        $this->auth->attempt('user@example.com', 'user123');

        $middleware = new AuthMiddleware($this->auth);
        $request = $this->createMockRequest();

        $response = $middleware->process($request, function () {
            return new Response('Protected content');
        });

        $this->assertSame('Protected content', $response->getBody());
    }

    public function testGuestMiddlewareAllowsGuests(): void
    {
        $middleware = new GuestMiddleware($this->auth);
        $request = $this->createMockRequest();

        $response = $middleware->process($request, function () {
            return new Response('Guest content');
        });

        $this->assertSame('Guest content', $response->getBody());
    }

    public function testGuestMiddlewareBlocksAuthenticated(): void
    {
        // Connecter l'utilisateur
        $this->auth->attempt('user@example.com', 'user123');

        $middleware = new GuestMiddleware($this->auth);
        $request = $this->createMockRequest();

        $response = $middleware->process($request, function () {
            return new Response('Guest content');
        });

        // Devrait être redirigé
        $this->assertNotSame('Guest content', $response->getBody());
    }

    // =========================================================================
    // TESTS DES RÔLES
    // =========================================================================

    public function testRoleMiddlewareAllowsCorrectRole(): void
    {
        // Connecter l'admin
        $this->auth->attempt('admin@example.com', 'admin123');

        $middleware = new RoleMiddleware($this->auth, ['ROLE_ADMIN']);
        $request = $this->createMockRequest();

        $response = $middleware->process($request, function () {
            return new Response('Admin content');
        });

        $this->assertSame('Admin content', $response->getBody());
    }

    public function testRoleMiddlewareBlocksWrongRole(): void
    {
        // Connecter un utilisateur normal
        $this->auth->attempt('user@example.com', 'user123');

        $middleware = new RoleMiddleware($this->auth, ['ROLE_ADMIN']);
        $request = $this->createMockRequest();

        $response = $middleware->process($request, function () {
            return new Response('Admin content');
        });

        // Ne devrait pas avoir accès
        $this->assertNotSame('Admin content', $response->getBody());
    }

    public function testRoleMiddlewareBlocksUnauthenticated(): void
    {
        $middleware = new RoleMiddleware($this->auth, ['ROLE_USER']);
        $request = $this->createMockRequest();

        $response = $middleware->process($request, function () {
            return new Response('User content');
        });

        // Devrait être bloqué
        $this->assertNotSame('User content', $response->getBody());
    }

    // =========================================================================
    // TESTS DE PERSISTANCE DE SESSION
    // =========================================================================

    public function testSessionPersistenceAfterLogin(): void
    {
        // Connexion
        $this->auth->attempt('admin@example.com', 'admin123');

        // Créer un nouvel authenticateur avec la même session
        $newAuth = new Authenticator(
            $this->userProvider,
            $this->hasher,
            $this->session
        );

        // L'utilisateur devrait toujours être connecté
        $this->assertTrue($newAuth->check());
        $this->assertSame(1, $newAuth->id());
    }

    public function testMultipleLoginAttempts(): void
    {
        // Plusieurs tentatives échouées
        $this->auth->attempt('admin@example.com', 'wrong1');
        $this->auth->attempt('admin@example.com', 'wrong2');
        $this->auth->attempt('admin@example.com', 'wrong3');

        // L'utilisateur ne devrait pas être connecté
        $this->assertFalse($this->auth->check());

        // Une tentative réussie devrait fonctionner
        $this->auth->attempt('admin@example.com', 'admin123');
        $this->assertTrue($this->auth->check());
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function createMockRequest(): Request
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/protected';

        return new Request();
    }
}
