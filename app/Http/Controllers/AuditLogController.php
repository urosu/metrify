<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AuditLogController — renders the Settings / Audit page.
 *
 * In v1 the audit log is frontend-rendered from mock data embedded in the page.
 * In v2 this controller will query an `audit_log` table and pass real entries.
 *
 * Reads: nothing (mock data in the Inertia page for now).
 * Writes: nothing.
 * Called by: GET /settings/audit (route: settings.audit).
 *
 * @see docs/pages/settings.md — "Audit log panel"
 * @see resources/js/Pages/Settings/Audit.tsx
 */
class AuditLogController extends Controller
{
    public function index(): Response
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $this->authorize('viewSettings', $workspace);

        return Inertia::render('Settings/Audit', [
            // v1: audit_log entries are mock data in the page component itself.
            // v2: pass real entries from an audit_log table here.
            'audit_log' => [],
        ]);
    }
}
