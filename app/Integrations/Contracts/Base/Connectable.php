<?php

namespace App\Integrations\Contracts\Base;

use App\Models\Workspace;

/**
 * Base connectivity contract shared by every integration.
 *
 * Handles OAuth lifecycle, connection health checks, and token management.
 * Every integration (Shopify, Meta, GA4, Klaviyo, etc.) implements this.
 */
interface Connectable
{
    /**
     * Establish the connection to the external platform.
     *
     * For OAuth providers: exchanges the authorization code for tokens and
     * persists credentials. For key-based (WooCommerce): validates and stores keys.
     *
     * @param  array<string, mixed>  $credentials  Provider-specific credentials
     *                                              (code, shop domain, consumer keys, etc.)
     * @return void
     *
     * @throws \App\Exceptions\IntegrationConnectionException
     */
    public function connect(Workspace $workspace, array $credentials): void;

    /**
     * Disconnect the integration and clean up stored credentials.
     *
     * Revokes tokens where the platform supports it, removes webhook
     * subscriptions, and soft-deletes the local integration record.
     */
    public function disconnect(Workspace $workspace): void;

    /**
     * Get the current connection status.
     *
     * @return 'connected'|'disconnected'|'expired'|'error'
     */
    public function getStatus(Workspace $workspace): string;

    /**
     * Perform a lightweight health check against the platform API.
     *
     * Makes a minimal API call (e.g., fetch account info) to verify
     * the token is valid and the connection is functional. Used by
     * the Settings page status indicators and DetectStuckImportsJob.
     */
    public function isHealthy(Workspace $workspace): bool;

    /**
     * Refresh the OAuth access token using the stored refresh token.
     *
     * Called by RefreshOAuthTokensJob (daily 05:00) for tokens expiring
     * within 48 hours, and on-demand when a 401 is received during sync.
     *
     * @return bool  True if refresh succeeded, false if re-authorization is needed.
     */
    public function refreshToken(Workspace $workspace): bool;
}
