<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Store;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;

/**
 * Provides a fully-onboarded workspace fixture: user + owner workspace + store
 * with historical_import_status='completed' to satisfy EnsureOnboardingComplete.
 *
 * Usage in a test class:
 *
 *   use Tests\Concerns\WithOnboardedWorkspace;
 *
 *   protected function setUp(): void
 *   {
 *       parent::setUp();
 *       $this->setUpOnboardedWorkspace();
 *   }
 *
 * Properties available after calling setUpOnboardedWorkspace():
 *   $this->user       — workspace owner
 *   $this->workspace  — workspace (slug-addressable, reporting_currency=EUR)
 *   $this->store      — store belonging to $this->workspace
 */
trait WithOnboardedWorkspace
{
    protected User $user;
    protected Workspace $workspace;
    protected Store $store;

    protected function setUpOnboardedWorkspace(array $workspaceOverrides = []): void
    {
        $this->user = User::factory()->create();

        $this->workspace = Workspace::factory()->create(array_merge([
            'owner_id'           => $this->user->id,
            'reporting_currency' => 'EUR',
        ], $workspaceOverrides));

        WorkspaceUser::factory()->owner()->create([
            'user_id'      => $this->user->id,
            'workspace_id' => $this->workspace->id,
        ]);

        // historical_import_status='completed' satisfies EnsureOnboardingComplete (Path 1).
        $this->store = Store::factory()->create([
            'workspace_id'             => $this->workspace->id,
            'historical_import_status' => 'completed',
        ]);
    }
}
