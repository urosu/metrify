<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a new workspace and assigns the given user as owner.
 *
 * Called from OnboardingController when the user connects their first store.
 * Workspace is initially named after the store hostname; OnboardingController
 * renames it to the WooCommerce site title after a successful connection.
 */
class CreateWorkspaceAction
{
    public function handle(User $user, string $domain): Workspace
    {
        // Use just the hostname as the workspace name — OnboardingController will overwrite
        // this with the real WC site title on success.
        $hostname = parse_url($domain, PHP_URL_HOST) ?? $domain;
        $slug     = $this->generateUniqueSlug('');

        return DB::transaction(function () use ($user, $hostname, $slug): Workspace {
            $workspace = Workspace::create([
                'name'               => $hostname,
                'slug'               => $slug,
                'owner_id'           => $user->id,
                'reporting_currency' => 'EUR',
                'reporting_timezone' => 'Europe/Berlin',
                'trial_ends_at'      => now()->addDays(14),
            ]);

            WorkspaceUser::create([
                'workspace_id' => $workspace->id,
                'user_id'      => $user->id,
                'role'         => 'owner',
            ]);

            return $workspace;
        });
    }

    /**
     * Generate a random slug that is unique across all workspaces.
     *
     * Why: workspace slugs are global so name-derived slugs leak customer info
     * and still need a collision suffix anyway. Random 12-char IDs (Cloudflare-style)
     * are unguessable and collision-free in practice.
     *
     * @param  string    $name       Unused — kept for call-site compatibility.
     * @param  int|null  $excludeId  Exclude this workspace ID from the uniqueness check.
     */
    public function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        do {
            // 32 lowercase hex chars = 128 bits entropy, same as Cloudflare account IDs.
            $slug = bin2hex(random_bytes(16));
        } while ($this->slugExists($slug, $excludeId));

        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId): bool
    {
        $query = Workspace::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
