<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use App\Services\Workspace\SavedViewService;

/**
 * Persist the current page filter+sort+columns combination as a named saved view.
 *
 * If `$user` is null, creates a workspace-shared view (visible to all members).
 * Otherwise creates a personal view for that user.
 *
 * Input:  Workspace, User|null, page slug, name, url_state array
 * Output: int  saved_views.id
 * Writes: saved_views
 *
 * @see docs/planning/backend.md §2.2 (action spec)
 * @see app/Services/Workspace/SavedViewService.php
 */
class SaveFilterAsViewAction
{
    public function __construct(private readonly SavedViewService $savedViews) {}

    /**
     * @param  array<string, mixed>  $urlState
     */
    public function handle(
        Workspace $workspace,
        ?User $user,
        string $page,
        string $name,
        array $urlState,
    ): int {
        return $this->savedViews->create(
            workspaceId: $workspace->id,
            userId: $user?->id,
            page: $page,
            name: $name,
            urlState: $urlState,
            createdBy: $user?->id ?? 0,
        );
    }
}
