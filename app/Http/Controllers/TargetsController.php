<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings/Targets page — workspace-level ROAS, CPO, and marketing spend % targets.
 *
 * Reads: workspace_targets, workspaces
 * Writes: workspace_targets
 * Called by: GET /settings/targets, POST /settings/targets, PATCH /settings/targets/{id}, DELETE /settings/targets/{id}
 *
 * @see docs/planning/backend.md §17
 */
class TargetsController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $this->authorize('viewSettings', $workspace);

        $targets = DB::table('workspace_targets')
            ->where('workspace_id', $workspace->id)
            ->orderBy('metric')
            ->get()
            ->map(fn ($t) => [
                'id'            => $t->id,
                'metric'        => $t->metric,
                'period'        => $t->period,
                'target_value'  => (float) $t->target_value_reporting,
                'current_value' => null, // computed at query time; not stored
                'unit'          => $this->unitForMetric($t->metric),
            ])
            ->all();

        return Inertia::render('Settings/Targets', [
            'targets'  => $targets,
            'currency' => $workspace->reporting_currency ?? 'EUR',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'metric'       => ['required', 'string', 'max:100'],
            'period'       => ['required', 'in:monthly,quarterly,annual'],
            'target_value' => ['required', 'numeric', 'min:0'],
            'unit'         => ['required', 'in:currency,pct,ratio'],
        ]);

        DB::table('workspace_targets')->insert([
            'workspace_id'            => $workspace->id,
            'metric'                  => $validated['metric'],
            'period'                  => $validated['period'],
            'target_value_reporting'  => $validated['target_value'],
            'currency'                => $workspace->reporting_currency ?? 'EUR',
            'created_by'              => $request->user()->id,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        return back()->with('success', 'Target saved.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'target_value' => ['required', 'numeric', 'min:0'],
        ]);

        DB::table('workspace_targets')
            ->where('id', $id)
            ->where('workspace_id', $workspace->id)
            ->update([
                'target_value_reporting' => $validated['target_value'],
                'updated_at'             => now(),
            ]);

        return back()->with('success', 'Target updated.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $this->authorize('update', $workspace);

        DB::table('workspace_targets')
            ->where('id', $id)
            ->where('workspace_id', $workspace->id)
            ->delete();

        return back()->with('success', 'Target removed.');
    }

    /**
     * Infer display unit from metric name.
     * Revenue/spend/cost metrics → currency; rate/pct metrics → pct; ratio metrics → ratio.
     */
    private function unitForMetric(string $metric): string
    {
        $lower = strtolower($metric);

        if (str_contains($lower, 'roas')) {
            return 'ratio';
        }

        if (str_contains($lower, 'rate') || str_contains($lower, 'pct') || str_contains($lower, 'ctr') || str_contains($lower, 'cvr')) {
            return 'pct';
        }

        return 'currency';
    }
}
