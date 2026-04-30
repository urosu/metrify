<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates daily_source_disagreements table — per-channel, per-day reconciliation rows.
 *
 * One row per (workspace, store, date, channel). Captures the delta between what
 * the store recorded as attributed revenue and what the ad platform claimed as
 * conversion_value. Channels without a platform counterpart (gsc, direct, organic,
 * email) have a null platform_claim.
 *
 * Written by: App\Services\Reconciliation\SourceReconciliationService
 * Read by:    App\Services\Attribution\AttributionDataService::disagreementMatrix()
 * Populated by: App\Jobs\ReconcileSourceDisagreementsJob (nightly 03:30 UTC)
 *
 * @see docs/planning/schema.md §1.X daily_source_disagreements
 * @see docs/planning/backend.md §WS-A2c (reconciliation surface)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_source_disagreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('date');

            // Channel enum — CHECK constraint below.
            $table->string('channel', 32);

            // Store-attributed revenue for this channel (orders.total_in_reporting_currency
            // summed where attribution_source = channel).
            $table->decimal('store_claim', 14, 2)->default(0);

            // Platform-reported conversion_value (SUM ad_insights.platform_conversions_value
            // in reporting currency). Null for channels with no ad platform counterpart
            // (gsc, direct, organic, email).
            $table->decimal('platform_claim', 14, 2)->nullable();

            // Reconciled revenue. v1: equals store_claim (store is truth).
            // v2 may apply a smarter merge rule before anchoring.
            $table->decimal('real_revenue', 14, 2)->default(0);

            // platform_claim - store_claim. Positive = platform overclaims; negative = underclaims.
            $table->decimal('delta_abs', 14, 2)->nullable();

            // (platform_claim - store_claim) / NULLIF(store_claim, 0) * 100
            // Never stored if store_claim is zero — avoid misleading 0/0 rows.
            $table->decimal('delta_pct', 8, 2)->nullable();

            // Reserved for future match-confidence scoring (0-100). Not used in v1.
            $table->smallInteger('match_confidence')->nullable();

            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['workspace_id', 'store_id', 'date', 'channel'], 'dsd_workspace_store_date_channel_unique');
            $table->index(['workspace_id', 'date'], 'dsd_workspace_date_idx');
        });

        DB::statement("ALTER TABLE daily_source_disagreements ADD CONSTRAINT check_dsd_channel CHECK (channel IN ('facebook','google','gsc','direct','organic','email'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_source_disagreements');
    }
};
