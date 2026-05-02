# GA4 OAuth Integration Research

## Required OAuth Scope

The Google Analytics Data API v1beta (runReport, batchRunReports) requires:

```
https://www.googleapis.com/auth/analytics.readonly
```

This is distinct from the Google Ads scope (`adwords`) and the Search Console scope
(`webmasters.readonly`), but all three share the same Google OAuth 2.0 infrastructure
— same `client_id`, `client_secret`, and `redirect_uri`. Nexstage reuses the existing
`services.google.*` config block; no separate GA4_OAUTH_* credentials are needed.

## GA4 Property Selection Pattern

A Google account can own many GA4 accounts, each with many properties. The canonical
listing endpoint is the Google Analytics Admin API v1alpha:

```
GET https://analyticsadmin.googleapis.com/v1alpha/accountSummaries
```

Response shape:
```json
{
  "accountSummaries": [
    {
      "account": "accounts/123",
      "displayName": "Acme Corp",
      "propertySummaries": [
        {
          "property": "properties/456",
          "displayName": "Main Website",
          "propertyType": "PROPERTY_TYPE_ORDINARY"
        }
      ]
    }
  ]
}
```

This endpoint requires scope `analytics.readonly` (same as Data API) — no extra
permissions needed.

**Property ID format used by the Data API:** `properties/456` (the full resource name).
Store this as `ga4_properties.property_id`.

## Token Handling

- GA4 uses standard Google OAuth 2.0 refresh tokens (same flow as Google Ads / GSC).
- `access_type=offline` and `prompt=consent` are required on the initial auth URL to
  force issuance of a refresh token (otherwise re-auth does not return one).
- Access tokens expire in 3600 s. GA4Client already handles auto-refresh via
  `refreshIfNeeded()` before each sync job run.
- Tokens are encrypted at rest in `integration_credentials.access_token_encrypted`
  and `refresh_token_encrypted` (using Laravel `Crypt::encryptString`).

## Property Selection UI Patterns

**Triple Whale** — Modal dialog on first connect. Shows GA4 account name + property
name in a flat list. No web stream selection (stream ID is optional; TW focuses on
property-level data).

**Polar Analytics** — Inline card in the integrations panel. Lists properties in a
searchable dropdown. Suggests the property whose domain matches the connected store.

**Klaviyo** — Separate wizard page for integrations that require extra setup. Shows
progress steps: "Authorize" → "Select property" → "Done". Clean step-indicator at top.

## Key Observations for Nexstage Implementation

1. **Admin API scope**: `analyticsadmin.googleapis.com/v1alpha/accountSummaries`
   uses the same `analytics.readonly` scope — no extra consent needed.
2. **Property matching**: Auto-suggest the property whose web stream URL contains
   the workspace's store domain (Polar pattern).
3. **Pending key pattern**: Mirror GSC flow — stash encrypted tokens in cache under
   `ga4_pending_{uuid}`, redirect to property-select page, persist on form POST.
4. **`ga4_properties` table**: Already exists (`id`, `workspace_id`, `property_id`,
   `property_name`, `measurement_id`, `status`). `measurement_id` is optional (not
   exposed by accountSummaries; the Data API doesn't need it).
5. **`integration_credentials` table**: Polymorphic store for encrypted tokens, keyed
   on `integrationable_type = App\Models\Ga4Property`, `integrationable_id`.
6. **Post-connect trigger**: Dispatch `SyncGA4SessionsJob` (already exists) immediately
   after property is persisted, same as GSC dispatches `SyncSearchConsoleJob`.
7. **Disconnect**: Revoke via `https://oauth2.googleapis.com/revoke?token=...`, then
   soft-delete or mark `status = 'disconnected'` on the `ga4_properties` row and
   delete the `integration_credentials` row.
8. **Env vars**: No new vars needed — reuse `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`,
   `GOOGLE_REDIRECT_URI` (GA4 callback flows through the same redirect URI as Ads/GSC;
   the `state.type = 'ga4'` field routes the shared callback handler).

## Admin API — listing properties (real implementation feasibility)

The call is one unauthenticated-format GET with Bearer token:

```
Authorization: Bearer {access_token}
GET https://analyticsadmin.googleapis.com/v1alpha/accountSummaries
```

This is lightweight enough to do synchronously in the OAuth callback, same as
GSC's `SearchConsoleClient::listProperties()`. The `Ga4OAuthController` therefore
makes a real Admin API call in the callback — no mocking needed.

## Error States

- No properties found → redirect to integrations with `oauth_error` flash.
- Token exchange failed → same.
- Token expired on reconnect → `Ga4OAuthController::reconnectToken()` updates creds
  in-place, no picker shown.
- Admin API 403 → user revoked access in Google Account settings; surface clear message.
