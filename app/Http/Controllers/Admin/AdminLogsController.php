<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationEvent;
use App\Models\IntegrationRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin sync and webhook log viewer.
 *
 * Purpose: Renders paginated sync logs (integration_runs) and webhook logs
 *          (integration_events) with filter/search. Also handles log truncation.
 *
 * Reads:  integration_runs, integration_events.
 * Writes: TRUNCATE integration_runs or integration_events (clearLogs action).
 * Callers: routes/web.php admin group (GET /admin/logs, DELETE /admin/logs).
 *
 * @see docs/planning/backend.md#6
 */
class AdminLogsController extends Controller
{
    public function index(Request $request): Response
    {
        $tab    = $request->string('tab', 'sync')->toString();
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        // ── Sync logs ─────────────────────────────────────────────────────────
        $syncQuery = IntegrationRun::withoutGlobalScopes()
            ->with('workspace:id,name,slug')
            ->orderByDesc('created_at');

        if ($status !== '') {
            $syncQuery->where('status', $status);
        }
        if ($search !== '') {
            $syncQuery->where(function ($q) use ($search): void {
                $q->where('job_type', 'ilike', "%{$search}%")
                  ->orWhere('error_message', 'ilike', "%{$search}%");
            });
        }

        $syncLogs = $syncQuery->simplePaginate(50, ['*'], 'sync_page')->through(fn ($l) => [
            'id'                => $l->id,
            'workspace'         => $l->workspace ? ['id' => $l->workspace->id, 'name' => $l->workspace->name] : null,
            'job_type'          => $l->job_type,
            'status'            => $l->status,
            'records_processed' => $l->records_processed,
            'error_message'     => $l->error_message,
            'duration_seconds'  => $l->duration_seconds,
            'started_at'        => $l->started_at?->toISOString(),
            'completed_at'      => $l->completed_at?->toISOString(),
            'scheduled_at'      => $l->scheduled_at?->toISOString(),
            'queue'             => $l->queue,
            'attempt'           => $l->attempt,
            'created_at'        => $l->created_at->toISOString(),
        ]);

        // ── Integration events (replaces webhook_logs) ────────────────────────
        // Table columns: event_type, error_code, received_at (no event/signature_valid/error_message).
        $webhookQuery = IntegrationEvent::withoutGlobalScopes()
            ->with('workspace:id,name')
            ->orderByDesc('created_at');

        if ($status !== '') {
            $webhookQuery->where('status', $status);
        }
        if ($search !== '') {
            $webhookQuery->where(function ($q) use ($search): void {
                $q->where('event_type', 'ilike', "%{$search}%")
                  ->orWhere('error_code', 'ilike', "%{$search}%")
                  ->orWhere('external_ref', 'ilike', "%{$search}%");
            });
        }

        $webhookLogs = $webhookQuery->simplePaginate(50, ['*'], 'webhook_page')->through(fn ($l) => [
            'id'                   => $l->id,
            'workspace'            => $l->workspace ? ['id' => $l->workspace->id, 'name' => $l->workspace->name] : null,
            'event_type'           => $l->event_type,
            'direction'            => $l->direction,
            'destination_platform' => $l->destination_platform,
            'external_ref'         => $l->external_ref,
            'status'               => $l->status,
            'error_code'           => $l->error_code,
            'error_category'       => $l->error_category,
            'match_quality'        => $l->match_quality,
            'received_at'          => $l->received_at?->toISOString(),
            'created_at'           => $l->created_at->toISOString(),
        ]);

        return Inertia::render('Admin/Logs', [
            'sync_logs'    => $syncLogs,
            'webhook_logs' => $webhookLogs,
            'filters'      => ['tab' => $tab, 'status' => $status, 'search' => $search],
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $type = $request->validate([
            'type' => 'required|in:sync,webhook',
        ])['type'];

        // TRUNCATE is orders-of-magnitude faster than DELETE on large tables
        // and avoids WAL bloat. Both tables are append-only operational logs
        // with no FK children, so truncation is safe.
        if ($type === 'sync') {
            DB::statement('TRUNCATE TABLE integration_runs RESTART IDENTITY');
        } else {
            DB::statement('TRUNCATE TABLE integration_events RESTART IDENTITY');
        }

        Log::info('Admin cleared logs', ['type' => $type, 'admin' => Auth::id()]);

        return back()->with('success', ucfirst($type) . ' logs cleared.');
    }
}
