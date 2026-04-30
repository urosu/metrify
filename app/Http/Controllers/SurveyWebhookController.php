<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Scopes\WorkspaceScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles POST /webhooks/survey/{workspace} — stubbed integration point for
 * KnoCommerce / Fairing-style post-purchase "How did you hear about us?" (HDYHAU)
 * survey webhooks.
 *
 * Authentication:
 *   Callers must pass `?token={workspace.survey_webhook_token}` in the query string.
 *   This is a per-workspace static token stored in the workspaces table, analogous
 *   to the HMAC secret used by the Shopify and WooCommerce webhook controllers.
 *   VerifyCsrfToken middleware is excluded via withoutMiddleware() on the route.
 *
 * Payload contract:
 *   {
 *     "order_id": 12345,      // integer — Nexstage orders.id or platform order reference
 *     "response": "Instagram", // string — the survey answer
 *     "source": "knocommerce" // string — originating provider (knocommerce | fairing | other)
 *   }
 *
 * On success, inserts a row into order_metafields with key = 'hdyhau_response'.
 *
 * Related: database/migrations/2026_04_29_000001_create_order_metafields_table.php
 * Related: app/Services/Attribution/AttributionDataService.php (surveyBreakdown)
 * Related: app/Http/Controllers/ShopifyWebhookController.php (auth pattern reference)
 * Route:   routes/web.php — Route::post('/webhooks/survey/{workspace}', ...)
 */
class SurveyWebhookController extends Controller
{
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        // ── Auth — token in query string ──────────────────────────────────────
        // Load workspace without WorkspaceScope; no session context on webhook routes.
        $workspace = Workspace::withoutGlobalScope(WorkspaceScope::class)
            ->where('id', $workspace->id)
            ->first();

        if ($workspace === null) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $token = (string) $request->query('token', '');

        if (
            empty($workspace->survey_webhook_token) ||
            ! hash_equals($workspace->survey_webhook_token, $token)
        ) {
            Log::warning('SurveyWebhookController: invalid token', [
                'workspace_id' => $workspace->id,
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ── Validation ────────────────────────────────────────────────────────
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
            'response' => ['required', 'string', 'max:1000'],
            'source'   => ['required', 'string', 'max:100'],
        ]);

        // ── Resolve order ─────────────────────────────────────────────────────
        // Verify the order belongs to this workspace before writing.
        // Use DB::table() to bypass WorkspaceScope — no session context here.
        $order = DB::table('orders')
            ->where('id', $validated['order_id'])
            ->where('workspace_id', $workspace->id)
            ->select(['id'])
            ->first();

        if ($order === null) {
            Log::warning('SurveyWebhookController: order not found or workspace mismatch', [
                'workspace_id' => $workspace->id,
                'order_id'     => $validated['order_id'],
            ]);

            // Return 200 so the caller stops retrying; we just don't write the row.
            return response()->json(['status' => 'skipped', 'reason' => 'order_not_found']);
        }

        // ── Upsert into order_metafields ──────────────────────────────────────
        // Use updateOrInsert so re-deliveries are idempotent.
        // workspace_id is denormalised here for the (workspace_id, key) index on
        // AttributionDataService::surveyBreakdown() to avoid a join to orders.
        DB::table('order_metafields')->updateOrInsert(
            [
                'order_id' => $order->id,
                'key'      => 'hdyhau_response',
            ],
            [
                'workspace_id' => $workspace->id,
                'value'        => $validated['response'],
                'updated_at'   => now()->toDateTimeString(),
                'created_at'   => now()->toDateTimeString(),
            ]
        );

        Log::info('SurveyWebhookController: hdyhau_response written', [
            'workspace_id' => $workspace->id,
            'order_id'     => $order->id,
            'source'       => $validated['source'],
        ]);

        return response()->json(['status' => 'ok']);
    }
}
