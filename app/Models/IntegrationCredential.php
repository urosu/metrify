<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypted OAuth/API credentials for integrations. Polymorphic: stores, ad_accounts,
 * search_console_properties can each have one credential row (unique on type+id).
 *
 * All secret columns are application-level encrypted (Laravel encrypt()); never stored in plaintext.
 * scopes holds the granted OAuth scopes as a JSON array for audit and permission checks.
 *
 * Reads: integration_credentials table (workspace-scoped).
 * Writes: OAuthCallbackController (token exchange); TokenRefreshJob (access token rotation).
 * Called by: FacebookAdsClient, GoogleAdsClient, SearchConsoleClient, WooConnector, ShopifyConnector.
 *
 * Note: migration uses 'integrationable_type'/'integrationable_id' (not 'integratable_*').
 * Spec doc says 'integratable' — migration spelling wins.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class IntegrationCredential extends Model
{
    protected $fillable = [
        'workspace_id',
        'integrationable_type',
        'integrationable_id',
        'auth_key_encrypted',
        'auth_secret_encrypted',
        'access_token_encrypted',
        'refresh_token_encrypted',
        'webhook_secret_encrypted',
        'token_expires_at',
        'scopes',
        'is_seeded',
    ];

    protected $hidden = [
        'auth_key_encrypted',
        'auth_secret_encrypted',
        'access_token_encrypted',
        'refresh_token_encrypted',
        'webhook_secret_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'scopes'           => 'array',
            'is_seeded'        => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Decrypt a named credential field (e.g. 'auth_key_encrypted').
     * Returns null when the field is null or empty rather than throwing.
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException  only for non-null garbled values
     */
    public function decrypt(string $field): ?string
    {
        $value = $this->getAttribute($field);

        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::decryptString((string) $value);
    }

    /**
     * Load the single IntegrationCredential row for any integrationable model.
     *
     * Must be called without WorkspaceScope active (e.g. from jobs). When
     * WorkspaceScope IS active (web requests), callers can use the normal relation.
     *
     * @param  class-string  $type  The fully-qualified model class (e.g. Store::class)
     * @param  int           $id    The integrationable_id
     *
     * @throws \RuntimeException  when no credential row exists for the given type+id
     */
    public static function mustForIntegrationable(string $type, int $id): self
    {
        $cred = self::withoutGlobalScopes()
            ->where('integrationable_type', $type)
            ->where('integrationable_id', $id)
            ->first();

        if ($cred === null) {
            throw new \RuntimeException(
                "No integration_credentials row found for {$type}#{$id}. " .
                'Ensure credentials were written during the connect flow.'
            );
        }

        return $cred;
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function integrationable(): MorphTo
    {
        return $this->morphTo();
    }
}
