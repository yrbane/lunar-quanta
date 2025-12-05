<?php
/**
 * Lunar Quanta Framework - Interface OAuth Provider.
 *
 * =============================================================================
 * QU'EST-CE QUE OAuth 2.0 ?
 * =============================================================================
 *
 * OAuth permet aux utilisateurs de se connecter via un service tiers
 * (Google, GitHub, etc.) sans partager leur mot de passe.
 *
 * ```
 * FLUX OAUTH 2.0 (Authorization Code Grant)
 *
 *     Utilisateur           Votre App            Provider (Google)
 *          │                    │                       │
 *    1. Clic "Login Google"     │                       │
 *          ├───────────────────►│                       │
 *          │                    │                       │
 *    2.    │    Redirige vers   │                       │
 *          │◄───────────────────┤                       │
 *          │                    │                       │
 *    3.    │─────────────────── Demande permission ────►│
 *          │                    │                       │
 *    4.    │◄─────────────────── Confirmation ──────────│
 *          │                    │                       │
 *    5.    │─── Callback avec code ────────────────────►│
 *          │                    │                       │
 *    6.    │                    ├── Échange code ──────►│
 *          │                    │   contre token        │
 *          │                    │◄── Access Token ──────│
 *          │                    │                       │
 *    7.    │                    ├── GET /userinfo ─────►│
 *          │                    │◄── Profil utilisateur │
 *          │                    │                       │
 *    8.    │◄── Connecté ! ─────┤                       │
 *          │                    │                       │
 * ```
 *
 * @package    Lunar\Service\Security\OAuth
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Security\OAuth;

/**
 * Interface pour les fournisseurs OAuth.
 */
interface OAuthProviderInterface
{
    /**
     * Retourne le nom du provider (google, github, etc.).
     */
    public function getName(): string;

    /**
     * Génère l'URL d'autorisation OAuth.
     *
     * @param string $state Token CSRF pour la sécurité
     *
     * @return string L'URL vers laquelle rediriger l'utilisateur
     */
    public function getAuthorizationUrl(string $state): string;

    /**
     * Échange le code d'autorisation contre un access token.
     *
     * @param string $code Le code reçu en callback
     *
     * @return OAuthToken Le token d'accès
     *
     * @throws OAuthException Si l'échange échoue
     */
    public function getAccessToken(string $code): OAuthToken;

    /**
     * Récupère les informations de l'utilisateur.
     *
     * @param OAuthToken $token Le token d'accès
     *
     * @return OAuthUser Les informations utilisateur
     *
     * @throws OAuthException Si la récupération échoue
     */
    public function getUser(OAuthToken $token): OAuthUser;

    /**
     * Rafraîchit un token expiré.
     *
     * @param string $refreshToken Le refresh token
     *
     * @return OAuthToken Le nouveau token
     *
     * @throws OAuthException Si le refresh échoue
     */
    public function refreshToken(string $refreshToken): OAuthToken;
}
