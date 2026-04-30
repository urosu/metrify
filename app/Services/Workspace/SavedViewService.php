<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CRUD + pin ordering for workspace saved views.
 *
 * A saved view is a named (filter + sort + columns + date-range) combination
 * stored as `url_state` JSON. Views are either personal (`user_id` set) or
 * workspace-shared (`user_id` null). Shared views are visible to all members.
 *
 * Pin ordering uses `pin_order` for explicit position within a page's sidebar
 * pin section; re-pin calls compact the sequence to 0-based integers.
 *
 * Reads:  saved_views
 * Writes: saved_views
 * Called by: SavedViewController (new), sidebar Inertia shared props
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/schema.md §1.8 (saved_views table)
 * @see docs/UX.md §5.19 (SavedViewPanel primitive)
 */
class SavedViewService
{
    /**
     * Create a new saved view. If `userId` is null, the view is workspace-shared.
     *
     * @param  array<string, mixed>  $urlState  Serialised URL querystring state.
     */
    public function create(
        int $workspaceId,
        ?int $userId,
        string $page,
        string $name,
        array $urlState,
        int $createdBy,
    ): int {
        return DB::table('saved_views')->insertGetId([
            'workspace_id' => $workspaceId,
            'user_id'      => $userId,
            'page'         => $page,
            'name'         => $name,
            'url_state'    => json_encode($urlState),
            'is_pinned'    => false,
            'pin_order'    => 0,
            'created_by'   => $createdBy,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /**
     * Update the name and/or url_state of a saved view.
     *
     * Caller must verify ownership before invoking.
     *
     * @param  array<string, mixed>|null  $urlState
     */
    public function update(int $id, ?string $name, ?array $urlState): void
    {
        $data = ['updated_at' => now()];

        if ($name !== null) {
            $data['name'] = $name;
        }

        if ($urlState !== null) {
            $data['url_state'] = json_encode($urlState);
        }

        DB::table('saved_views')->where('id', $id)->update($data);
    }

    /**
     * Delete a saved view by ID.
     */
    public function delete(int $id): void
    {
        DB::table('saved_views')->where('id', $id)->delete();
    }

    /**
     * Toggle pinning for a view and reorder all pins for the page.
     *
     * When pinning: view is appended at the end of current pins.
     * When unpinning: gap in pin_order is compacted away.
     *
     * @param  bool  $pinned  True to pin, false to unpin.
     */
    public function pin(int $id, bool $pinned): void
    {
        $view = DB::table('saved_views')->where('id', $id)->first(['id', 'workspace_id', 'page']);

        if ($view === null) {
            return;
        }

        if ($pinned) {
            $maxOrder = DB::table('saved_views')
                ->where('workspace_id', $view->workspace_id)
                ->where('page', $view->page)
                ->where('is_pinned', true)
                ->max('pin_order');

            DB::table('saved_views')->where('id', $id)->update([
                'is_pinned'  => true,
                'pin_order'  => ($maxOrder ?? -1) + 1,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('saved_views')->where('id', $id)->update([
                'is_pinned'  => false,
                'pin_order'  => 0,
                'updated_at' => now(),
            ]);

            // Compact remaining pins so order is 0-based with no gaps.
            $this->compactPinOrder((int) $view->workspace_id, $view->page);
        }
    }

    /**
     * Return all saved views for a page in this workspace.
     *
     * Shared views (user_id IS NULL) are included for all users.
     * Personal views (user_id = $userId) are included only for the requesting user.
     * Pinned views are sorted by pin_order first, then alphabetically by name.
     *
     * @return Collection<int, object>
     */
    public function forPage(int $workspaceId, string $page, int $userId): Collection
    {
        return DB::table('saved_views')
            ->where('workspace_id', $workspaceId)
            ->where('page', $page)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            })
            ->orderByRaw('is_pinned DESC, pin_order ASC, name ASC')
            ->get(['id', 'user_id', 'name', 'url_state', 'is_pinned', 'pin_order', 'created_by', 'created_at']);
    }

    /**
     * Return all saved views created by a specific user across all pages.
     *
     * Used for user profile / settings page listing.
     *
     * @return Collection<int, object>
     */
    public function forUser(int $workspaceId, int $userId): Collection
    {
        return DB::table('saved_views')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->orderBy('page')
            ->orderBy('name')
            ->get(['id', 'page', 'name', 'url_state', 'is_pinned', 'pin_order', 'created_at']);
    }

    /**
     * Reassign pin_order values (0-based) for pinned views on a page after an unpin.
     */
    private function compactPinOrder(int $workspaceId, string $page): void
    {
        $pinned = DB::table('saved_views')
            ->where('workspace_id', $workspaceId)
            ->where('page', $page)
            ->where('is_pinned', true)
            ->orderBy('pin_order')
            ->pluck('id');

        foreach ($pinned as $index => $viewId) {
            DB::table('saved_views')->where('id', $viewId)->update(['pin_order' => $index]);
        }
    }
}
