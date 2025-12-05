<?php
/**
 * Tests des classes OAuth.
 */
declare(strict_types=1);

namespace Tests\Service\Security\OAuth;

use Lunar\Service\Security\OAuth\GitHubProvider;
use Lunar\Service\Security\OAuth\GoogleProvider;
use Lunar\Service\Security\OAuth\OAuthException;
use Lunar\Service\Security\OAuth\OAuthToken;
use Lunar\Service\Security\OAuth\OAuthUser;
use PHPUnit\Framework\TestCase;

class OAuthTest extends TestCase
{
    // =========================================================================
    // TESTS OAuthToken
    // =========================================================================

    public function testOAuthTokenConstruction(): void
    {
        $token = new OAuthToken('access123', 'refresh456', 3600, 'Bearer', ['email', 'profile']);

        $this->assertSame('access123', $token->getAccessToken());
        $this->assertSame('refresh456', $token->getRefreshToken());
        $this->assertSame(3600, $token->getExpiresIn());
        $this->assertSame('Bearer', $token->getTokenType());
        $this->assertSame(['email', 'profile'], $token->getScopes());
    }

    public function testOAuthTokenFromResponse(): void
    {
        $data = [
            'access_token' => 'access123',
            'refresh_token' => 'refresh456',
            'expires_in' => 7200,
            'token_type' => 'Bearer',
            'scope' => 'email profile',
        ];

        $token = OAuthToken::fromResponse($data);

        $this->assertSame('access123', $token->getAccessToken());
        $this->assertSame('refresh456', $token->getRefreshToken());
        $this->assertSame(7200, $token->getExpiresIn());
        $this->assertSame(['email', 'profile'], $token->getScopes());
    }

    public function testOAuthTokenFromResponseMinimal(): void
    {
        $data = [
            'access_token' => 'access123',
        ];

        $token = OAuthToken::fromResponse($data);

        $this->assertSame('access123', $token->getAccessToken());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getExpiresIn());
        $this->assertSame('Bearer', $token->getTokenType());
        $this->assertSame([], $token->getScopes());
    }

    public function testOAuthTokenIsExpired(): void
    {
        // Token non expiré
        $token = new OAuthToken('access', null, 3600);
        $this->assertFalse($token->isExpired());

        // Token sans expiration
        $tokenNoExpiry = new OAuthToken('access', null, null);
        $this->assertFalse($tokenNoExpiry->isExpired());

        // Token expiré (0 secondes)
        $expiredToken = new OAuthToken('access', null, 0);
        usleep(1000);
        $this->assertTrue($expiredToken->isExpired());
    }

    public function testOAuthTokenAuthorizationHeader(): void
    {
        $token = new OAuthToken('mytoken', null, null, 'Bearer');

        $this->assertSame('Bearer mytoken', $token->getAuthorizationHeader());
    }

    // =========================================================================
    // TESTS OAuthUser
    // =========================================================================

    public function testOAuthUserConstruction(): void
    {
        $user = new OAuthUser(
            provider: 'google',
            providerId: '12345',
            email: 'john@example.com',
            name: 'John Doe',
            avatar: 'https://example.com/avatar.jpg',
            rawData: ['custom' => 'data']
        );

        $this->assertSame('google', $user->getProvider());
        $this->assertSame('12345', $user->getProviderId());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('https://example.com/avatar.jpg', $user->getAvatar());
        $this->assertSame(['custom' => 'data'], $user->getRawData());
    }

    public function testOAuthUserParsesName(): void
    {
        $user = new OAuthUser('google', '123', 'test@example.com', 'John Doe');

        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
    }

    public function testOAuthUserSingleName(): void
    {
        $user = new OAuthUser('google', '123', 'test@example.com', 'Madonna');

        $this->assertSame('Madonna', $user->getFirstName());
        $this->assertNull($user->getLastName());
    }

    public function testOAuthUserNoName(): void
    {
        $user = new OAuthUser('google', '123', 'test@example.com');

        $this->assertNull($user->getName());
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
    }

    public function testOAuthUserUniqueKey(): void
    {
        $user = new OAuthUser('google', '12345', 'test@example.com');

        $this->assertSame('google:12345', $user->getUniqueKey());
    }

    public function testOAuthUserToArray(): void
    {
        $user = new OAuthUser('github', '789', 'dev@example.com', 'Jane Dev');
        $user->setProfileUrl('https://github.com/janedev');

        $array = $user->toArray();

        $this->assertSame('github', $array['provider']);
        $this->assertSame('789', $array['providerId']);
        $this->assertSame('dev@example.com', $array['email']);
        $this->assertSame('Jane Dev', $array['name']);
        $this->assertSame('https://github.com/janedev', $array['profileUrl']);
    }

    // =========================================================================
    // TESTS OAuthException
    // =========================================================================

    public function testOAuthExceptionFromResponse(): void
    {
        $data = [
            'error' => 'invalid_grant',
            'error_description' => 'The authorization code has expired',
        ];

        $exception = OAuthException::fromResponse($data);

        $this->assertSame('invalid_grant', $exception->getErrorCode());
        $this->assertSame('The authorization code has expired', $exception->getErrorDescription());
        $this->assertStringContainsString('invalid_grant', $exception->getMessage());
    }

    // =========================================================================
    // TESTS GoogleProvider
    // =========================================================================

    public function testGoogleProviderName(): void
    {
        $provider = new GoogleProvider('client-id', 'client-secret', 'https://example.com/callback');

        $this->assertSame('google', $provider->getName());
    }

    public function testGoogleProviderAuthorizationUrl(): void
    {
        $provider = new GoogleProvider('my-client-id', 'secret', 'https://example.com/callback');
        $url = $provider->getAuthorizationUrl('test-state');

        $this->assertStringContainsString('accounts.google.com', $url);
        $this->assertStringContainsString('client_id=my-client-id', $url);
        $this->assertStringContainsString('state=test-state', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function testGoogleProviderAuthUrlWithOptions(): void
    {
        $provider = new GoogleProvider('client-id', 'secret', 'https://example.com/callback');
        $url = $provider->getAuthorizationUrlWithOptions('state', true, 'user@example.com');

        $this->assertStringContainsString('prompt=consent', $url);
        $this->assertStringContainsString('login_hint=user%40example.com', $url);
        $this->assertStringContainsString('access_type=offline', $url);
    }

    // =========================================================================
    // TESTS GitHubProvider
    // =========================================================================

    public function testGitHubProviderName(): void
    {
        $provider = new GitHubProvider('client-id', 'client-secret', 'https://example.com/callback');

        $this->assertSame('github', $provider->getName());
    }

    public function testGitHubProviderAuthorizationUrl(): void
    {
        $provider = new GitHubProvider('my-gh-client', 'secret', 'https://example.com/callback');
        $url = $provider->getAuthorizationUrl('state123');

        $this->assertStringContainsString('github.com/login/oauth/authorize', $url);
        $this->assertStringContainsString('client_id=my-gh-client', $url);
        $this->assertStringContainsString('state=state123', $url);
    }
}
