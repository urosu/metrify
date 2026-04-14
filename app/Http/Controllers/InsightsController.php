<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\AiSummary;
use App\Models\Alert;
use App\Models\DailyNote;
use App\Models\Store;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    public function index(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'severity' => 'nullable|string|in:all,info,warning,critical',
            'status'   => 'nullable|string|in:all,unread,unresolved',
            'page'     => 'nullable|integer|min:1',
        ]);

        $severity = $validated['severity'] ?? 'all';
        $status   = $validated['status']   ?? 'all';

        // AI summaries — last 7 days, latest first
        $aiSummaries = AiSummary::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('date', '>=', now()->subDays(6)->toDateString())
            ->select(['id', 'date', 'summary_text', 'model_used', 'generated_at'])
            ->orderByDesc('date')
            ->get()
            ->map(fn ($s) => [
                'id'           => $s->id,
                'date'         => $s->date->toDateString(),
                'summary_text' => $s->summary_text,
                'model_used'   => $s->model_used,
                'generated_at' => $s->generated_at->toISOString(),
            ]);

        // Daily notes — last 14 days, newest first
        // Why: notes appear in the Insights feed alongside AI summaries so users
        // have a unified view of what they recorded each day.
        $dailyNotes = DailyNote::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('date', '>=', now()->subDays(13)->toDateString())
            ->select(['id', 'date', 'note'])
            ->orderByDesc('date')
            ->get()
            ->map(fn ($n) => [
                'id'   => $n->id,
                'date' => $n->date->toDateString(),
                'note' => $n->note,
            ]);

        // Alert query — scoped to workspace, with optional filters
        $query = Alert::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with([
                'store:id,name',
                'adAccount:id,name',
            ])
            ->orderByDesc('created_at');

        if ($severity !== 'all') {
            $query->where('severity', $severity);
        }

        match ($status) {
            'unread'     => $query->whereNull('read_at'),
            'unresolved' => $query->whereNull('resolved_at'),
            default      => null,
        };

        $alerts = $query->paginate(30)->through(fn ($a) => [
            'id'              => $a->id,
            'type'            => $a->type,
            'severity'        => $a->severity,
            'store_name'      => $a->store?->name,
            'ad_account_name' => $a->adAccount?->name,
            'data'            => $a->data,
            'read_at'         => $a->read_at?->toISOString(),
            'resolved_at'     => $a->resolved_at?->toISOString(),
            'created_at'      => $a->created_at->toISOString(),
        ]);

        return Inertia::render('Insights', [
            'ai_summaries' => $aiSummaries,
            'daily_notes'  => $dailyNotes,
            'alerts'       => $alerts,
            'filters'      => [
                'severity' => $severity,
                'status'   => $status,
            ],
        ]);
    }

    public function dismiss(int $alert): RedirectResponse
    {
        // Why: avoid implicit route model binding which triggers WorkspaceScope before
        // WorkspaceContext is guaranteed to be set. Manual lookup with workspace check.
        $workspaceId = app(WorkspaceContext::class)->id();

        Alert::withoutGlobalScopes()
            ->where('id', $alert)
            ->where('workspace_id', $workspaceId)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'read_at'     => now(),
            ]);

        return back();
    }

    public function dismissAll(): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        Alert::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'read_at'     => now(),
            ]);

        return back();
    }
}
