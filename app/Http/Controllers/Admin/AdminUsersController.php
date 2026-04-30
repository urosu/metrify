<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkspaceUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin user management — list users, impersonate, stop impersonating.
 *
 * Purpose: Paginated user list with search. Impersonation writes to session
 *          and switches auth identity; stopImpersonating restores the admin.
 *
 * Reads:  users (with workspace count).
 * Writes: session (impersonating_admin_id, active_workspace_id).
 * Callers: routes/web.php admin group (/admin/users, /admin/users/{user}/impersonate,
 *          /admin/impersonation/stop).
 *
 * @see docs/planning/backend.md#6
 */
class AdminUsersController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $query = User::query()
            ->withCount('workspaces')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $users = $query->paginate(25)->through(fn ($u) => [
            'id'              => $u->id,
            'name'            => $u->name,
            'email'           => $u->email,
            'is_super_admin'  => $u->is_super_admin,
            'workspaces_count' => $u->workspaces_count,
            'last_login_at'   => $u->last_login_at?->toISOString(),
            'created_at'      => $u->created_at->toISOString(),
        ]);

        return Inertia::render('Admin/Users', [
            'users'   => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        // Store real admin's ID so we can restore later
        session(['impersonating_admin_id' => Auth::id()]);

        Log::info('Admin impersonation', [
            'admin_id'       => Auth::id(),
            'target_user_id' => $user->id,
        ]);

        Auth::loginUsingId($user->id);

        // Regenerate the session ID after switching identity to prevent session fixation.
        $request->session()->regenerate();

        // Set active workspace to the user's first workspace
        $firstWorkspaceId = WorkspaceUser::where('user_id', $user->id)
            ->orderBy('created_at')
            ->value('workspace_id');

        if ($firstWorkspaceId) {
            session(['active_workspace_id' => $firstWorkspaceId]);
        }

        return redirect('/onboarding');
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $adminId = session('impersonating_admin_id');

        if (! $adminId) {
            return redirect('/onboarding');
        }

        // Verify the stored ID actually belongs to a super admin before restoring.
        // Prevents session manipulation from elevating an arbitrary user to admin.
        $admin = User::find($adminId);

        if (! $admin || ! $admin->is_super_admin) {
            session()->forget(['impersonating_admin_id', 'active_workspace_id']);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        session()->forget(['impersonating_admin_id', 'active_workspace_id']);

        Auth::loginUsingId($adminId);

        // Regenerate the session ID after switching identity to prevent session fixation.
        $request->session()->regenerate();

        return redirect('/admin/workspaces');
    }
}
