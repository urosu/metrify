<?php

namespace App\Integrations\Contracts\Support;

use App\Models\Workspace;

/**
 * Contract for OAuth flow handlers.
 *
 * Implementations: ShopifyOAuth, FacebookOAuth, GoogleOAuth, KlaviyoOAuth, TikTokOAuth.
 *
 * Standardizes the redirect-to-provider / handle-callback pattern across
 * all OAuth integrations. Each implementation knows its provider's specific
 * scopes, token endpoints, and token storage model.
 *
 * Callback routes are outside the workspace prefix (no CSRF on some):
 *   GET /oauth/{provider}          -> getRedirectUrl()
 *   GET /oauth/{provider}/callback -> handleCallback()
 */
interface OAuthHandler
{
    /**
     * Get the provider identifier.
     *
     * @return string  e.g., 'shopify', 'facebook', 'google', 'klaviyo', 'tiktok'
     */
    public function getProvider(): string;

    /**
     * Build the authorization redirect URL.
     *
     * Includes provider-specific scopes, state parameter (CSRF + workspace context),
     * and any additional parameters (Shopify: shop domain, Klaviyo: PKCE code_challenge).
     *
     * @param  array<string, mixed>  $params  Provider-specific params (e.g., ['shop' => 'mystore.myshopify.com']).
     * @return string  The full authorization URL to redirect the user to.
     */
    public function getRedirectUrl(Workspace $workspace, array $params = []): string;

    /**
     * Handle the OAuth callback and exchange the code for tokens.
     *
     * Validates the state parameter, exchanges the authorization code for
     * access + refresh tokens, and persists them to the appropriate model
     * (AdAccount, EmailAccount, AnalyticsProperty, Store, etc.).
     *
     * @param  string  $code   The authorization code from the callback query string.
     * @param  string  $state  The state parameter for CSRF validation.
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?\DateTimeInterface}
     *
     * @throws \App\Exceptions\OAuthException  When state validation fails or token exchange errors.
     */
    public function handleCallback(Workspace $workspace, string $code, string $state): array;

    /**
     * Refresh an expired access token.
     *
     * @param  string  $refreshToken  The stored refresh token.
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?\DateTimeInterface}
     *
     * @throws \App\Exceptions\OAuthTokenExpiredException  When the refresh token itself is expired.
     */
    public function refreshAccessToken(string $refreshToken): array;

    /**
     * Revoke the access and refresh tokens on the provider side.
     *
     * Called during disconnect. Not all providers support revocation
     * (return silently if unsupported).
     */
    public function revokeToken(string $accessToken): void;

    /**
     * Get the OAuth scopes required by this integration.
     *
     * @return list<string>  e.g., ['read_orders', 'read_products', 'read_inventory']
     */
    public function getScopes(): array;
}
