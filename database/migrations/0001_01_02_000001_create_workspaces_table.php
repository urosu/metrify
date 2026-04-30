<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates workspaces table — the tenant root.
 *
 * Billable via Cashier (Stripe). Agency billing via billing_workspace_id self-FK.
 * state column powers DemoBanner (UX §5.11.1).
 * workspace_settings JSONB holds WorkspaceSettings VO (all user-tunable defaults).
 *
 * @see docs/planning/schema.md §1.1 workspaces
 * @see docs/UX.md §Settings workspace + notifications
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug')->unique();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            // Agency consolidated billing — child workspaces share billing owner's subscription.
            $table->foreignId('billing_workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            // NEW: workspace identity in WorkspaceSwitcher + portfolio contribution bar.
            $table->string('avatar_emoji', 8)->nullable();
            // NEW: powers DemoBanner. CHECK constraint below.
            $table->string('state', 16)->default('active');
            $table->char('reporting_currency', 3)->default('EUR');
            $table->string('reporting_timezone', 64)->default('UTC');
            // ISO-2. Used for country-level COALESCE chain (CLAUDE.md).
            $table->char('primary_country_code', 2)->nullable();
            // Shopify parity default (UX §5.3). CHECK constraint below.
            $table->string('week_start', 8)->default('sunday');
            $table->timestamp('trial_ends_at')->nullable();
            // NULL = subscription cancelled / trial expired with no active plan.
            // CHECK constraint below — 'standard' or 'enterprise' when not null.
            // Postgres allows NULL through IN checks so ->nullable() is safe here.
            $table->string('billing_plan', 16)->nullable()->default('standard');
            // Stripe Cashier columns
            $table->string('stripe_id', 255)->nullable();
            $table->string('pm_type', 16)->nullable();
            $table->char('pm_last_four', 4)->nullable();
            $table->string('billing_name', 255)->nullable();
            $table->string('billing_email', 255)->nullable();
            // Nexstage-owned JSONB (Stripe customer address shape). No api_version.
            $table->jsonb('billing_address')->default('{}');
            $table->string('vat_number', 64)->nullable();
            // WorkspaceSettings VO — Nexstage-owned. No api_version.
            $table->jsonb('workspace_settings')->default('{}');
            $table->boolean('is_orphaned')->default(false);
            $table->boolean('has_store')->default(false);
            $table->boolean('has_ads')->default(false);
            $table->boolean('has_gsc')->default(false);
            $table->boolean('has_psi')->default(false);
            // ISO-2 country code for billing address and holiday defaults.
            $table->char('country', 2)->nullable();
            // Sub-national region (e.g. state/canton) for billing/tax.
            $table->string('region', 64)->nullable();
            // Workspace-level display timezone (may differ from reporting_timezone).
            $table->string('timezone', 64)->nullable();
            // Performance targets for the KPI rail and goal-tracking alerts.
            $table->decimal('target_roas', 8, 2)->nullable();
            $table->decimal('target_cpo', 10, 2)->nullable();
            $table->decimal('target_marketing_pct', 5, 2)->nullable();
            // UTM coverage tracking — updated by UtmCoverageCheckJob.
            $table->decimal('utm_coverage_pct', 5, 2)->nullable();
            $table->string('utm_coverage_status', 16)->nullable();
            $table->timestamp('utm_coverage_checked_at')->nullable();
            $table->jsonb('utm_unrecognized_sources')->nullable();
            $table->string('survey_webhook_token', 64)->nullable()->unique();
            $table->string('pixel_tracking_token', 64)->nullable()->unique();
            $table->string('default_attribution_model')->default('last-click');
            $table->string('accounting_mode')->default('accrual');
            $table->string('vertical')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('billing_workspace_id');
        });

        DB::statement("ALTER TABLE workspaces ADD CONSTRAINT check_workspace_state CHECK (state IN ('onboarding','demo','active','suspended','archived'))");
        DB::statement("ALTER TABLE workspaces ADD CONSTRAINT check_workspace_week_start CHECK (week_start IN ('sunday','monday'))");
        DB::statement("ALTER TABLE workspaces ADD CONSTRAINT check_workspace_billing_plan CHECK (billing_plan IN ('standard','enterprise'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
