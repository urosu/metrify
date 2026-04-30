<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateAnnotationAction;
use App\Models\Annotation;
use App\Models\Holiday;
use App\Models\Workspace;
use App\Services\Workspace\AnnotationService;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Annotation CRUD — workspace-scoped chart overlays.
 *
 * User-authored annotations (notes, promotions, spikes/drops) are
 * fully editable by the author. System-authored annotations cannot be
 * modified or deleted — users may hide them per-user via `hide()`.
 *
 * Reads: annotations
 * Writes: annotations (via CreateAnnotationAction for store; AnnotationService for the rest)
 * Called by: POST /annotations, PATCH /annotations/{id}, DELETE /annotations/{id},
 *            POST /annotations/{id}/hide
 *            Also aliased to /settings/events for the Settings/Events page.
 *
 * @see docs/planning/backend.md §14
 * @see app/Actions/CreateAnnotationAction.php
 * @see app/Services/Workspace/AnnotationService.php
 * @see docs/UX.md §5.6.1 (ChartAnnotationLayer)
 */
class AnnotationController extends Controller
{
    public function __construct(
        private readonly CreateAnnotationAction $createAnnotation,
        private readonly AnnotationService $annotations,
    ) {}

    /**
     * Create a new user-authored annotation.
     *
     * Accepted annotation_type values: user_note, promotion, expected_spike, expected_drop.
     * scope_type defaults to 'workspace'; scope_id is optional (e.g. a store_id).
     */
    public function store(Request $request): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'body'              => ['sometimes', 'nullable', 'string', 'max:5000'],
            'annotation_type'   => ['required', 'string', 'in:' . implode(',', AnnotationService::USER_TYPES)],
            'scope_type'        => ['sometimes', 'nullable', 'string', 'max:64'],
            'scope_id'          => ['sometimes', 'nullable', 'integer'],
            'starts_at'         => ['required', 'date'],
            'ends_at'           => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'suppress_anomalies' => ['sometimes', 'boolean'],
        ]);

        $this->createAnnotation->handle(
            workspace: $workspace,
            user: $request->user(),
            payload: $validated,
        );

        return back()->with('success', 'Annotation created.');
    }

    /**
     * Update a user-authored annotation.
     *
     * Only the original author may update. System annotations are read-only.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var Annotation $annotation */
        $annotation = Annotation::findOrFail($id);

        if ($annotation->author_type !== 'user') {
            abort(403, 'System annotations cannot be edited.');
        }

        if ($annotation->author_id !== $request->user()->id) {
            abort(403, 'You did not create this annotation.');
        }

        $validated = $request->validate([
            'title'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'body'              => ['sometimes', 'nullable', 'string', 'max:5000'],
            'starts_at'         => ['sometimes', 'nullable', 'date'],
            'ends_at'           => ['sometimes', 'nullable', 'date'],
            'suppress_anomalies' => ['sometimes', 'boolean'],
        ]);

        $this->annotations->update(
            id: $id,
            updatedBy: $request->user()->id,
            data: $validated,
        );

        return back()->with('success', 'Annotation updated.');
    }

    /**
     * Delete a user-authored annotation.
     *
     * Only the original author may delete. System annotations cannot be deleted;
     * use hide() instead.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        /** @var Annotation $annotation */
        $annotation = Annotation::findOrFail($id);

        if ($annotation->author_type !== 'user') {
            abort(403, 'System annotations cannot be deleted — use hide instead.');
        }

        if ($annotation->author_id !== $request->user()->id) {
            abort(403, 'You did not create this annotation.');
        }

        $this->annotations->delete($id);

        return back()->with('success', 'Annotation deleted.');
    }

    /**
     * Toggle chart-overlay visibility for a holiday.
     *
     * Persists the per-user preference in users.view_preferences['holiday_overlays']
     * as a JSON array of holiday IDs. Toggling: if present → remove, if absent → add.
     *
     * Routes: POST /{workspace}/holidays/{holiday}/overlay
     */
    public function overlayHoliday(Request $request, Holiday $holiday): JsonResponse
    {
        $user = $request->user();
        $prefs = $user->view_preferences ?? [];

        $overlays = $prefs['holiday_overlays'] ?? [];

        if (in_array($holiday->id, $overlays, true)) {
            $overlays = array_values(array_filter($overlays, fn ($id) => $id !== $holiday->id));
            $active   = false;
        } else {
            $overlays[] = $holiday->id;
            $active     = true;
        }

        $prefs['holiday_overlays'] = $overlays;
        $user->view_preferences    = $prefs;
        $user->save();

        return response()->json(['active' => $active, 'holiday_id' => $holiday->id]);
    }

    /**
     * Hide a system annotation for the requesting user.
     *
     * Adds the user's id to the `is_hidden_per_user` JSONB column.
     * No-op if the annotation is user-authored (users delete instead of hide).
     */
    public function hide(Request $request, int $id): RedirectResponse
    {
        /** @var Annotation $annotation */
        $annotation = Annotation::findOrFail($id);

        if ($annotation->author_type !== 'system') {
            abort(400, 'Only system annotations can be hidden; delete user annotations instead.');
        }

        $this->annotations->hideForUser($id, $request->user()->id);

        return back();
    }
}
