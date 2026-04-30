<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SaveFilterAsViewAction;
use App\Models\SavedView;
use App\Models\Workspace;
use App\Services\Workspace\SavedViewService;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Saved view CRUD — named TopBar filter presets per workspace user.
 *
 * Reads: saved_views
 * Writes: saved_views (via SaveFilterAsViewAction for store, SavedViewService for update/delete/pin)
 * Called by: POST /saved-views, PATCH /saved-views/{id}, DELETE /saved-views/{id},
 *            PATCH /saved-views/{id}/pin
 *
 * Authorization: personal views are owned by the creating user; workspace-shared
 * views (user_id IS NULL) may be deleted/updated by any workspace member.
 *
 * @see docs/planning/backend.md §15
 * @see app/Services/Workspace/SavedViewService.php
 */
class SavedViewController extends Controller
{
    public function __construct(
        private readonly SaveFilterAsViewAction $saveView,
        private readonly SavedViewService $savedViews,
    ) {}

    /**
     * Create a new saved view for the authenticated user.
     *
     * Accepts shared=true to create a workspace-shared view (user_id = null).
     * Returns a redirect with flash data (Inertia pattern) so TopBar can
     * reload the saved-views list via partial props.
     */
    public function store(Request $request): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'page'      => ['required', 'string', 'max:255'],
            'url_state' => ['required', 'array'],
            'shared'    => ['sometimes', 'boolean'],
        ]);

        $user   = $request->user();
        // null user passed to action means workspace-shared view
        $owner  = ($validated['shared'] ?? false) ? null : $user;

        $id = $this->saveView->handle(
            workspace: $workspace,
            user: $owner,
            page: $validated['page'],
            name: $validated['name'],
            urlState: $validated['url_state'],
        );

        return back()->with('success', 'View saved.')->with('savedViewId', $id);
    }

    /**
     * Update a saved view's name and/or url_state.
     *
     * Only the owning user may update a personal view.
     * Any workspace member may update a shared view (user_id IS NULL).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        /** @var SavedView $view */
        $view = SavedView::findOrFail($id);

        // Personal view — only the owner may update it
        if ($view->user_id !== null && $view->user_id !== $request->user()->id) {
            abort(403, 'You do not own this saved view.');
        }

        $validated = $request->validate([
            'name'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'url_state' => ['sometimes', 'nullable', 'array'],
        ]);

        $this->savedViews->update(
            id: $id,
            name: $validated['name'] ?? null,
            urlState: $validated['url_state'] ?? null,
        );

        return back()->with('success', 'View updated.');
    }

    /**
     * Delete a saved view.
     *
     * Only the owning user may delete a personal view.
     * Any workspace member may delete a shared view (user_id IS NULL).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        /** @var SavedView $view */
        $view = SavedView::findOrFail($id);

        // Personal view — only the owner may delete it
        if ($view->user_id !== null && $view->user_id !== $request->user()->id) {
            abort(403, 'You do not own this saved view.');
        }

        $this->savedViews->delete($id);

        return back()->with('success', 'View deleted.');
    }

    /**
     * Toggle the pinned state of a saved view.
     *
     * Returns JSON { is_pinned: bool } so the frontend can apply an optimistic UI
     * update without a full page reload.
     *
     * Only the owning user may pin/unpin a personal view.
     * Any workspace member may pin/unpin a shared view (user_id IS NULL).
     */
    public function pin(Request $request, int $id): JsonResponse
    {
        /** @var SavedView $view */
        $view = SavedView::findOrFail($id);

        // Personal view — only the owner may toggle pinning
        if ($view->user_id !== null && $view->user_id !== $request->user()->id) {
            abort(403, 'You do not own this saved view.');
        }

        $newPinned = ! $view->is_pinned;

        $this->savedViews->pin($id, $newPinned);

        return response()->json(['is_pinned' => $newPinned]);
    }
}
