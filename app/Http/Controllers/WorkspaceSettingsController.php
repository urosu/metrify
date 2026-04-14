<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteWorkspaceAction;
use App\Actions\UpdateWorkspaceAction;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $this->authorize('viewSettings', $workspace);

        return Inertia::render('Settings/Workspace', [
            'workspace' => [
                'id'                   => $workspace->id,
                'name'                 => $workspace->name,
                'slug'                 => $workspace->slug,
                'reporting_currency'   => $workspace->reporting_currency,
                'reporting_timezone'   => $workspace->reporting_timezone,
                'billing_plan'         => $workspace->billing_plan,
                'trial_ends_at'        => $workspace->trial_ends_at,
                'target_roas'          => $workspace->target_roas ? (float) $workspace->target_roas : null,
                'target_cpo'           => $workspace->target_cpo  ? (float) $workspace->target_cpo  : null,
                'has_store'            => $workspace->has_store,
                'has_ads'              => $workspace->has_ads,
            ],
            'userRole' => $this->resolveUserRole($request, $workspace),
        ]);
    }

    public function update(Request $request, UpdateWorkspaceAction $action): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $this->authorize('update', $workspace);

        $isOwner = $workspace->workspaceUsers()
            ->where('user_id', $request->user()->id)
            ->where('role', 'owner')
            ->exists();

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            // Slug may only be changed by the owner. The regex mirrors store slug rules.
            'slug'                => $isOwner
                ? ['required', 'string', 'min:4', 'max:64', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', Rule::unique('workspaces', 'slug')->ignore($workspace->id)]
                : ['prohibited'],
            'reporting_currency'  => ['required', 'string', 'size:3'],
            'reporting_timezone'  => ['required', 'string', 'max:100', Rule::in(\DateTimeZone::listIdentifiers())],
            // Performance targets — used for Winners/Losers filter chips. Null = use break-even defaults (1.0× ROAS).
            'target_roas'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'target_cpo'          => ['nullable', 'numeric', 'min:0'],
        ]);

        $slugChanged = $isOwner
            && isset($validated['slug'])
            && $validated['slug'] !== $workspace->slug;

        $action->handle($workspace, $validated);

        if ($slugChanged) {
            // Redirect to the settings page under the new slug so the URL stays valid.
            return redirect("/{$validated['slug']}/settings/workspace")
                ->with('success', 'Workspace settings updated.');
        }

        return back()->with('success', 'Workspace settings updated.');
    }

    public function destroy(Request $request, DeleteWorkspaceAction $action): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $this->authorize('delete', $workspace);

        $request->validate([
            'confirmation' => ['required', 'string', Rule::in([$workspace->name])],
        ]);

        try {
            $action->handle($workspace);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['deletion' => $e->getMessage()]);
        }

        $request->session()->forget('active_workspace_id');

        return redirect('/onboarding')
            ->with('success', 'Workspace deleted. You have 30 days to restore it.');
    }

    private function resolveUserRole(Request $request, Workspace $workspace): string
    {
        return $workspace->workspaceUsers()
            ->where('user_id', $request->user()->id)
            ->value('role') ?? 'member';
    }
}
