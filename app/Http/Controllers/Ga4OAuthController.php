<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\GoogleApiException;
use App\Models\Ga4Property;
use App\Models\IntegrationCredential;
use App\Models\WorkspaceUser;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles the Google Analytics 4 OAuth 2.0 flow.
 *
 * GA4 uses the same Google OAuth platform as Google Ads and GSC — same
 * GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / GOOGLE_REDIRECT_URI. The `state.type`
 * field routes the shared /oauth/google/callback to the correct handler, but GA4
 * has its own /oauth/ga4/callback to keep the controller surface clean.
 *
 * Flow:
 *   1. GET  /oauth/ga4/initiate          → initiate()       → Google consent screen
 *   2. GET  /oauth/ga4/callback           → callback()       → token exchange + list properties
 *   3. GET  /oauth/ga4/select-property    → selectProperty() → user picks a GA4 property
 *   4. POST /oauth/ga4/select-property    → persistProperty()→ upsert ga4_properties + integration_credentials
 *   5. POST /oauth/ga4/disconnect         → disconnect()     → revoke token + soft-delete property
 *
 * Token storage: `integration_credentials` polymorphic on `Ga4Property`.
 * Property rows: `ga4_properties` table (already migrated).
 *
 * Properties are listed via the Google Analytics Admin API v1alpha (accountSummaries).
 * This uses the same `analytics.readonly` scope as the Data API — no extra consent.
 *
 * Coordination note: a sibling agent owns the /integrations page connection card.
 * The initiate URL exposed here (`/oauth/ga4/initiate`) is what that card links to.
 * The `has_ga4` workspace flag is set on successful persist so the card shows as connected.
 *
 * @see docs/competitors/_research_ga4_oauth_integration.md
 * @see docs/planning/backend.md §4 (GA4 connector spec)
 */
class Ga4OAuthController extends Controller
{
    private const AUTH_URL        = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL       = 'https://oauth2.googleapis.com/token';
    private const REVOKE_URL      = 'https://oauth2.googleapis.com/revoke';
    private const ADMIN_API_BASE  = 'https://analyticsadmin.googleapis.com/v1alpha';

    // GA4 Data API requires analytics.readonly.
    // The Admin API accountSummaries endpoint accepts the same scope.
    // @see docs/competitors/_research_ga4_oauth_integration.md §Required OAuth Scope
    private const SCOPE_GA4 = 'https://www.googleapis.com/auth/analytics.readonly';

    // -------------------------------------------------------------------------
    // Step 1 — redirect to Google OAuth consent screen
    // -------------------------------------------------------------------------

    public function initiate(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        if ($workspaceId === null) {
            return redirect('/onboarding');
        }

        $this->authorizeWorkspaceAccess($request, $workspaceId);

        $returnTo = $request->query('from') === 'onboarding' ? 'onboarding' : 'integrations';

        $nonce = Str::random(32);

        // Nonce consumed in callback to prevent state replay within the 15-minute window.
        cache()->put("oauth_nonce_{$nonce}", true, now()->addMinutes(15));

        $payload = [
            'workspace_id'   => $workspaceId,
            'workspace_slug' => app(WorkspaceContext::class)->slug(),
            'type'           => 'ga4',
            'nonce'          => $nonce,
            'expires_at'     => now()->addMinutes(15)->timestamp,
            'return_to'      => $returnTo,
        ];

        // reconnect_id: if set, callback updates tokens in-place (no picker shown).
        $reconnectId = $request->query('reconnect_id');
        if ($reconnectId !== null) {
            $payload['reconnect_id'] = (int) $reconnectId;
        }

        $state = $this->encodeState($payload);

        return redirect($this->buildAuthUrl(self::SCOPE_GA4, $state));
    }

    // -------------------------------------------------------------------------
    // Step 2 — handle OAuth callback from Google
    // -------------------------------------------------------------------------

    public function callback(Request $request): RedirectResponse
    {
        $stateRaw = (string) $request->query('state', '');
        $state    = $this->decodeState($stateRaw);

        if ($state === null || ($state['type'] ?? '') !== 'ga4') {
            Log::warning('GA4 OAuth: invalid or expired state', [
                'state_type' => $state['type'] ?? null,
            ]);
            return redirect()->away(
                rtrim(config('app.url'), '/') . $this->oauthDest('integrations')
                . '?oauth_error=' . urlencode('GA4 connection failed: invalid or expired link. Please try again.')
                . '&oauth_platform=ga4'
            );
        }

        $nonce = $state['nonce'] ?? '';
        if (! $nonce || ! cache()->pull("oauth_nonce_{$nonce}")) {
            Log::warning('GA4 OAuth: nonce already used or missing', [
                'workspace_id' => $state['workspace_id'] ?? null,
            ]);
            return redirect()->away(
                rtrim(config('app.url'), '/') . $this->oauthDest('integrations')
                . '?oauth_error=' . urlencode('GA4 connection failed: link already used. Please try again.')
                . '&oauth_platform=ga4'
            );
        }

        $workspaceId   = (int) $state['workspace_id'];
        $workspaceSlug = $state['workspace_slug'] ?? null;
        $returnTo      = $state['return_to'] ?? 'integrations';
        $reconnectId   = isset($state['reconnect_id']) ? (int) $state['reconnect_id'] : null;

        if ($request->query('error')) {
            return redirect()->away(
                rtrim(config('app.url'), '/') . $this->oauthDest($returnTo, $workspaceSlug)
                . '?oauth_error=' . urlencode('GA4 connection was cancelled.')
                . '&oauth_platform=ga4'
            );
        }

        $code = (string) $request->query('code', '');

        try {
            $tokens = $this->exchangeCode($code);
        } catch (GoogleApiException $e) {
            Log::error('GA4 OAuth: token exchange failed', [
                'workspace_id' => $workspaceId,
                'error'        => $e->getMessage(),
            ]);
            return redirect()->away(
                rtrim(config('app.url'), '/') . $this->oauthDest($returnTo, $workspaceSlug)
                . '?oauth_error=' . urlencode('GA4 connection failed: ' . $e->getMessage())
                . '&oauth_platform=ga4'
            );
        }

        // Reconnect path — update tokens in-place, no picker.
        if ($reconnectId !== null) {
            return $this->reconnectToken($workspaceId, $tokens, $reconnectId, $returnTo, $workspaceSlug);
        }

        // List GA4 properties via the Admin API.
        try {
            $properties = $this->listProperties($tokens['access_token']);
        } catch (GoogleApiException $e) {
            Log::error('GA4 OAuth: failed to list properties', [
                'workspace_id' => $workspaceId,
                'error'        => $e->getMessage(),
            ]);
            return redirect()->away(
                rtrim(config('app.url'), '/') . $this->oauthDest($returnTo, $workspaceSlug)
                . '?oauth_error=' . urlencode('Could not retrieve GA4 properties: ' . $e->getMessage())
                . '&oauth_platform=ga4'
            );
        }

        if (empty($properties)) {
            return redirect()->away(
                rtrim(config('app.url'), '/') . $this->oauthDest($returnTo, $workspaceSlug)
                . '?oauth_error=' . urlencode('No GA4 properties were found for this Google account.')
                . '&oauth_platform=ga4'
            );
        }

        // Stash tokens + property list in cache; redirect to the property-selection page.
        $pendingKey = 'ga4_pending_' . Str::uuid();

        cache()->put($pendingKey, [
            'workspace_id'   => $workspaceId,
            'workspace_slug' => $workspaceSlug,
            'access_token'   => Crypt::encryptString($tokens['access_token']),
            'refresh_token'  => Crypt::encryptString($tokens['refresh_token']),
            'expires_at'     => $tokens['expires_at']->timestamp,
            'properties'     => $properties,
            'return_to'      => $returnTo,
        ], now()->addMinutes(15));

        $selectUrl = rtrim(config('app.url'), '/')
            . '/oauth/ga4/select-property?pending=' . urlencode($pendingKey);

        return redirect()->away($selectUrl);
    }

    // -------------------------------------------------------------------------
    // Step 3 — display property-selection page (GET)
    // -------------------------------------------------------------------------

    public function selectProperty(Request $request): Response|RedirectResponse
    {
        $pendingKey = (string) $request->query('pending', '');

        if ($pendingKey === '') {
            return redirect($this->oauthDest('integrations'))
                ->with('error', 'Missing session key. Please try connecting GA4 again.');
        }

        $pending = cache()->get($pendingKey);

        if ($pending === null) {
            return redirect($this->oauthDest('integrations'))
                ->with('error', 'Session expired. Please try connecting GA4 again.');
        }

        return Inertia::render('Ga4/PropertySelect', [
            'pending_key' => $pendingKey,
            'properties'  => $pending['properties'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Step 4 — persist chosen property (POST)
    // -------------------------------------------------------------------------

    public function persistProperty(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pending_key' => ['required', 'string'],
            'property_id' => ['required', 'string', 'max:100'],
        ]);

        $workspaceId = app(WorkspaceContext::class)->id();

        if ($workspaceId === null) {
            return redirect('/onboarding');
        }

        $this->authorizeWorkspaceAccess($request, $workspaceId);

        $pending = cache()->get($validated['pending_key']);

        if (
            $pending === null
            || (int) ($pending['workspace_id'] ?? 0) !== $workspaceId
        ) {
            return redirect($this->oauthDest('integrations'))
                ->with('error', 'Link expired. Please re-connect Google Analytics 4.');
        }

        $returnTo      = $pending['return_to'] ?? 'integrations';
        $workspaceSlug = $pending['workspace_slug'] ?? null;

        cache()->forget($validated['pending_key']);

        // Find the selected property in the cached list.
        $selectedPropertyId = $validated['property_id'];
        $propertyData       = collect($pending['properties'])
            ->firstWhere('property_id', $selectedPropertyId);

        if ($propertyData === null) {
            return redirect($this->oauthDest($returnTo, $workspaceSlug))
                ->with('error', 'Selected property not found. Please try connecting again.');
        }

        $accessToken  = Crypt::decryptString($pending['access_token']);
        $refreshToken = Crypt::decryptString($pending['refresh_token']);
        $expiresAt    = \Carbon\Carbon::createFromTimestamp($pending['expires_at']);

        // Upsert the ga4_properties row — workspace_id + property_id is the unique key.
        /** @var Ga4Property $property */
        $property = Ga4Property::withoutGlobalScopes()->updateOrCreate(
            [
                'workspace_id' => $workspaceId,
                'property_id'  => $selectedPropertyId,
            ],
            [
                'property_name'             => $propertyData['property_name'],
                'measurement_id'            => $propertyData['measurement_id'] ?? null,
                'status'                    => 'active',
                'consecutive_sync_failures' => 0,
            ]
        );

        // Upsert integration_credentials — polymorphic on Ga4Property.
        IntegrationCredential::withoutGlobalScopes()->updateOrCreate(
            [
                'workspace_id'         => $workspaceId,
                'integrationable_type' => Ga4Property::class,
                'integrationable_id'   => $property->id,
            ],
            [
                'access_token_encrypted'  => Crypt::encryptString($accessToken),
                'refresh_token_encrypted' => Crypt::encryptString($refreshToken),
                'token_expires_at'        => $expiresAt,
                'scopes'                  => [self::SCOPE_GA4],
            ]
        );

        // Mark workspace as having GA4, driving nav visibility.
        // @see docs/planning/schema.md §workspaces.has_ga4
        if (! DB::table('workspaces')->where('id', $workspaceId)->value('has_ga4')) {
            DB::table('workspaces')->where('id', $workspaceId)->update(['has_ga4' => true]);
        }

        // TODO (backend-pass agent): dispatch SyncGA4SessionsJob after credentials exist.
        // Shape: SyncGA4SessionsJob::dispatch($property->id, $workspaceId);
        // Blocked on: job needs the property ID to call GA4Client::forProperty().

        Log::info('GA4: property connected', [
            'workspace_id' => $workspaceId,
            'property_id'  => $property->id,
            'ga4_property' => $selectedPropertyId,
        ]);

        return redirect($this->oauthDest($returnTo, $workspaceSlug))
            ->with('success', 'Google Analytics 4 property connected successfully.');
    }

    // -------------------------------------------------------------------------
    // Disconnect — POST /oauth/ga4/disconnect
    // -------------------------------------------------------------------------

    public function disconnect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer'],
        ]);

        $workspaceId = app(WorkspaceContext::class)->id();

        if ($workspaceId === null) {
            return redirect('/onboarding');
        }

        $this->authorizeWorkspaceAccess($request, $workspaceId);

        $property = Ga4Property::withoutGlobalScopes()
            ->where('id', $validated['property_id'])
            ->where('workspace_id', $workspaceId)
            ->first();

        if ($property === null) {
            return redirect($this->oauthDest('integrations'))
                ->with('error', 'Property not found or access denied.');
        }

        // Revoke the access token with Google so the permission is fully removed.
        $cred = IntegrationCredential::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('integrationable_type', Ga4Property::class)
            ->where('integrationable_id', $property->id)
            ->first();

        if ($cred !== null) {
            $accessToken = $cred->decrypt('access_token_encrypted');

            if ($accessToken !== null) {
                // Best-effort revoke — don't block disconnect on network failure.
                Http::timeout(5)->post(self::REVOKE_URL, ['token' => $accessToken]);
            }

            $cred->delete();
        }

        $property->update(['status' => 'disconnected']);

        // Clear has_ga4 flag if no active properties remain.
        $remaining = Ga4Property::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('status', 'active')
            ->exists();

        if (! $remaining) {
            DB::table('workspaces')->where('id', $workspaceId)->update(['has_ga4' => false]);
        }

        Log::info('GA4: property disconnected', [
            'workspace_id' => $workspaceId,
            'property_id'  => $property->id,
        ]);

        return redirect($this->oauthDest('integrations'))
            ->with('success', 'Google Analytics 4 disconnected.');
    }

    // -------------------------------------------------------------------------
    // Reconnect — update tokens in-place (no property picker)
    // -------------------------------------------------------------------------

    /**
     * @param  array{access_token: string, refresh_token: string, expires_at: \Carbon\Carbon} $tokens
     */
    private function reconnectToken(
        int $workspaceId,
        array $tokens,
        int $propertyId,
        string $returnTo,
        ?string $workspaceSlug,
    ): RedirectResponse {
        $property = Ga4Property::withoutGlobalScopes()
            ->where('id', $propertyId)
            ->where('workspace_id', $workspaceId)
            ->first();

        if ($property === null) {
            return redirect($this->oauthDest($returnTo, $workspaceSlug))
                ->with('error', 'Property not found or access denied.');
        }

        $property->update([
            'status'                    => 'active',
            'consecutive_sync_failures' => 0,
        ]);

        IntegrationCredential::withoutGlobalScopes()->updateOrCreate(
            [
                'workspace_id'         => $workspaceId,
                'integrationable_type' => Ga4Property::class,
                'integrationable_id'   => $property->id,
            ],
            [
                'access_token_encrypted'  => Crypt::encryptString($tokens['access_token']),
                'refresh_token_encrypted' => Crypt::encryptString($tokens['refresh_token']),
                'token_expires_at'        => $tokens['expires_at'],
                'scopes'                  => [self::SCOPE_GA4],
            ]
        );

        Log::info('GA4: property token reconnected', [
            'workspace_id' => $workspaceId,
            'property_id'  => $property->id,
        ]);

        return redirect($this->oauthDest($returnTo, $workspaceSlug))
            ->with('success', 'Google Analytics 4 reconnected successfully.');
    }

    // -------------------------------------------------------------------------
    // Google Analytics Admin API — list properties
    // -------------------------------------------------------------------------

    /**
     * List all GA4 properties accessible to the authenticated user via the
     * Analytics Admin API v1alpha accountSummaries endpoint.
     *
     * Same `analytics.readonly` scope as the Data API — no additional consent.
     * Filters out sub-properties (PROPERTY_TYPE_SUBPROPERTY) which cannot be
     * queried independently via the Data API.
     *
     * @return array<int, array{property_id: string, property_name: string, account_name: string, measurement_id: null}>
     *
     * @throws GoogleApiException
     *
     * @see docs/competitors/_research_ga4_oauth_integration.md §Admin API
     */
    private function listProperties(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->get(self::ADMIN_API_BASE . '/accountSummaries');

        if ($response->failed()) {
            throw new GoogleApiException(
                'GA4 Admin API failed: HTTP ' . $response->status()
            );
        }

        $body     = $response->json();
        $accounts = $body['accountSummaries'] ?? [];

        $properties = [];

        foreach ($accounts as $account) {
            $accountName = $account['displayName'] ?? $account['account'] ?? 'Unknown Account';

            foreach ($account['propertySummaries'] ?? [] as $prop) {
                // Property resource name e.g. "properties/456".
                $resourceName = $prop['property'] ?? null;

                if ($resourceName === null) {
                    continue;
                }

                // Skip sub-properties — they cannot be queried independently.
                $propType = $prop['propertyType'] ?? 'PROPERTY_TYPE_ORDINARY';
                if ($propType === 'PROPERTY_TYPE_SUBPROPERTY') {
                    continue;
                }

                $properties[] = [
                    'property_id'   => $resourceName,                             // "properties/456"
                    'property_name' => $prop['displayName'] ?? $resourceName,
                    'account_name'  => $accountName,
                    'measurement_id' => null, // Not available via accountSummaries; optional column.
                ];
            }
        }

        return $properties;
    }

    // -------------------------------------------------------------------------
    // Token exchange
    // -------------------------------------------------------------------------

    /**
     * Exchange an authorization code for access + refresh tokens.
     *
     * @return array{access_token: string, refresh_token: string, expires_at: \Carbon\Carbon}
     *
     * @throws GoogleApiException
     */
    private function exchangeCode(string $code): array
    {
        $response = Http::timeout(15)->post(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri'  => config('services.ga4.redirect'),
            'grant_type'    => 'authorization_code',
        ]);

        if ($response->failed()) {
            throw new GoogleApiException(
                'Token exchange failed: HTTP ' . $response->status()
            );
        }

        $body = $response->json();

        if (isset($body['error'])) {
            throw new GoogleApiException(
                'Token exchange error: ' . ($body['error_description'] ?? $body['error'])
            );
        }

        $accessToken  = (string) ($body['access_token'] ?? '');
        $refreshToken = (string) ($body['refresh_token'] ?? '');
        $expiresIn    = (int) ($body['expires_in'] ?? 3600);

        if ($accessToken === '' || $refreshToken === '') {
            throw new GoogleApiException('Token exchange returned empty access or refresh token.');
        }

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at'    => now()->addSeconds($expiresIn),
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildAuthUrl(string $scope, string $state): string
    {
        $params = http_build_query([
            'client_id'     => config('services.google.client_id'),
            'redirect_uri'  => config('services.ga4.redirect'),
            'response_type' => 'code',
            'scope'         => $scope,
            'access_type'   => 'offline',
            'prompt'        => 'consent',  // force refresh_token issuance
            'state'         => $state,
        ]);

        return self::AUTH_URL . '?' . $params;
    }

    /**
     * base64url-encode a JSON payload and append an HMAC-SHA256 signature.
     *
     * @param  array<string, mixed> $data
     */
    private function encodeState(array $data): string
    {
        $payload   = rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return $payload . '.' . $signature;
    }

    /**
     * Verify the HMAC signature and decode the state payload.
     *
     * @return array<string, mixed>|null
     */
    private function decodeState(string $state): ?array
    {
        if ($state === '') {
            return null;
        }

        $parts = explode('.', $state, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;

        $expected = hash_hmac('sha256', $payload, config('app.key'));

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $json = base64_decode(strtr($payload, '-_', '+/'), strict: false);

        if ($json === false) {
            return null;
        }

        $decoded = json_decode($json, associative: true);

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['expires_at']) && now()->timestamp > (int) $decoded['expires_at']) {
            return null;
        }

        return $decoded;
    }

    /**
     * Return the destination path after OAuth, depending on where the flow started.
     */
    private function oauthDest(string $returnTo, ?string $workspaceSlug = null): string
    {
        if ($returnTo === 'onboarding') {
            return '/onboarding';
        }

        if ($workspaceSlug === null) {
            $workspaceSlug = app(WorkspaceContext::class)->slug();
        }

        return $workspaceSlug
            ? "/{$workspaceSlug}/settings/integrations"
            : '/settings/integrations';
    }

    /**
     * Abort 403 unless the authenticated user is an owner or admin of the workspace.
     */
    private function authorizeWorkspaceAccess(Request $request, int $workspaceId): void
    {
        $allowed = WorkspaceUser::where('user_id', $request->user()?->id)
            ->where('workspace_id', $workspaceId)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        abort_unless($allowed, 403, 'You do not have permission to connect integrations for this workspace.');
    }
}
