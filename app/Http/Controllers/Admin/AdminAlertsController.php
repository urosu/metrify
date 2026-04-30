<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TriageInboxItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin alerts read-only view.
 *
 * Purpose: Lists open/snoozed/dismissed triage inbox items across all workspaces.
 *          Status can only be changed from within each workspace's own UI.
 *          Graduation tracking (is_silent, review_status, estimated_impact_*)
 *          was dropped — those columns were never added to the schema.
 *
 * Reads:  triage_inbox_items (withoutGlobalScopes — all workspaces).
 * Writes: nothing (read-only).
 * Callers: routes/web.php admin group (GET /admin/alerts).
 *
 * @see docs/planning/schema.md#triage_inbox_items
 * @see docs/planning/backend.md#6
 */
class AdminAlertsController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->string('tab', 'snoozed')->toString();

        $baseQuery = TriageInboxItem::withoutGlobalScopes()
            ->with(['workspace:id,name,slug'])
            ->orderByDesc('created_at');

        $query = match ($tab) {
            'open'    => (clone $baseQuery)->where('status', 'open'),
            'snoozed' => (clone $baseQuery)->where('status', 'snoozed'),
            'dismissed' => (clone $baseQuery)->where('status', 'dismissed'),
            default     => (clone $baseQuery)->where('status', 'snoozed'),
        };

        $alerts = $query->paginate(50)->through(fn ($a) => [
            'id'           => $a->id,
            'workspace'    => $a->workspace ? ['id' => $a->workspace->id, 'name' => $a->workspace->name] : null,
            'severity'     => $a->severity,
            'title'        => $a->title,
            'context_text' => $a->context_text,
            'status'       => $a->status,
            'snoozed_until' => $a->snoozed_until?->toISOString(),
            'created_at'   => $a->created_at->toISOString(),
        ]);

        $counts = TriageInboxItem::withoutGlobalScopes()
            ->selectRaw("
                COUNT(*) FILTER (WHERE status = 'open')    AS open,
                COUNT(*) FILTER (WHERE status = 'snoozed') AS snoozed,
                COUNT(*) FILTER (WHERE status = 'dismissed') AS dismissed
            ")
            ->first();

        return Inertia::render('Admin/Alerts', [
            'alerts' => $alerts,
            'tab'    => $tab,
            'counts' => [
                'open'    => (int) ($counts->open    ?? 0),
                'snoozed' => (int) ($counts->snoozed ?? 0),
                'dismissed' => (int) ($counts->dismissed ?? 0),
            ],
        ]);
    }
}
