<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates campaigns table.
 *
 * convention_version smallint tracks parser output shape changes to enable re-parse jobs.
 * target_roas / target_cpo removed — moved to workspace_targets.
 * parsed_convention is Nexstage-owned JSONB — no api_version.
 *
 * @see docs/planning/schema.md §1.3 campaigns
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained('ad_accounts')->cascadeOnDelete();
            $table->string('external_id', 255);
            $table->string('name', 500);
            // Array of {name, observed_at} for historical name matching. Nexstage-owned.
            $table->jsonb('previous_names')->default('[]');
            // Nexstage-owned: {country, audience, offer, angle, creative_iteration, product_sku, placement, funnel_stage}
            $table->jsonb('parsed_convention')->nullable();
            // Bumped when the parser output shape changes. Enables re-parse jobs.
            $table->smallInteger('convention_version')->default(1);
            $table->string('status', 100)->nullable();
            $table->string('objective', 100)->nullable();
            $table->decimal('daily_budget', 12, 2)->nullable();
            $table->decimal('lifetime_budget', 12, 2)->nullable();
            $table->string('budget_type', 20)->nullable();
            $table->string('bid_strategy', 100)->nullable();
            $table->decimal('target_value', 12, 2)->nullable();
            // Per-campaign ROAS / CPO targets (override workspace defaults).
            $table->decimal('target_roas', 8, 2)->nullable();
            $table->decimal('target_cpo', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['ad_account_id', 'external_id']);
            $table->index(['workspace_id', 'ad_account_id']);
        });

        // Functional index on lower-cased name — used by buildUtmAttributionMap case-insensitive join.
        // LOWER(c.name) = LOWER(o.attribution_last_touch->>'campaign') needs an index scan, not SeqScan.
        DB::statement("CREATE INDEX idx_campaigns_ws_name_lower ON campaigns (workspace_id, LOWER(name))");
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
