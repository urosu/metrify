<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates ad_insights table — daily/hourly ad performance per campaign/adset/ad.
 *
 * No country column on purpose — CLAUDE.md gotcha. Country attribution uses:
 * COALESCE(campaigns.parsed_convention->>'country', stores.primary_country_code, 'UNKNOWN')
 *
 * Never SUM across multiple levels. Always filter level to a single value.
 *
 * raw_insights is platform-owned JSONB — paired with raw_insights_api_version.
 *
 * @see docs/planning/schema.md §1.3 ad_insights
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('ad_account_id')->nullable()->constrained('ad_accounts')->nullOnDelete();
            // CHECK constraint below.
            $table->string('level', 20);
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('adset_id')->nullable()->constrained('adsets')->nullOnDelete();
            $table->foreignId('ad_id')->nullable()->constrained('ads')->nullOnDelete();
            $table->date('date');
            $table->smallInteger('hour')->nullable();
            $table->decimal('spend', 12, 4)->default(0);
            $table->decimal('spend_in_reporting_currency', 12, 4)->nullable();
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->bigInteger('reach')->nullable();
            // Average times a person saw the ad in the reporting period (Facebook).
            $table->decimal('frequency', 5, 2)->nullable();
            // Platform-reported conversions (may differ from actual orders).
            $table->decimal('platform_conversions', 12, 2)->nullable();
            $table->decimal('platform_conversions_value', 14, 4)->nullable();
            // NEW: enables FX-safe Purchase-ROAS aggregation.
            $table->decimal('platform_conversions_value_in_reporting_currency', 14, 2)->nullable();
            // Google Ads: fraction of eligible impressions actually received (0.0-1.0).
            $table->decimal('search_impression_share', 5, 4)->nullable();
            $table->decimal('platform_roas', 10, 4)->nullable();
            $table->char('currency', 3);
            // Platform-owned: Facebook actions array, social_spend, placement breakdowns.
            // Paired with api_version per CLAUDE.md gotcha.
            $table->jsonb('raw_insights')->nullable();
            $table->string('raw_insights_api_version', 16)->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'ad_account_id', 'date']);
            $table->index(['workspace_id', 'campaign_id', 'date']);
            $table->index(['workspace_id', 'adset_id', 'date']);
            $table->index(['workspace_id', 'ad_id', 'date']);
            $table->index(['workspace_id', 'date']);
        });

        // Level/FK integrity — prevents double-counting spend across levels.
        DB::statement("ALTER TABLE ad_insights ADD CONSTRAINT check_ad_insights_level CHECK (level IN ('campaign','adset','ad'))");
        DB::statement("ALTER TABLE ad_insights ADD CONSTRAINT check_ad_insights_level_fk CHECK (
            (level = 'campaign' AND campaign_id IS NOT NULL AND ad_id IS NULL)
            OR (level = 'adset' AND adset_id IS NOT NULL AND campaign_id IS NULL AND ad_id IS NULL)
            OR (level = 'ad' AND ad_id IS NOT NULL AND campaign_id IS NULL)
        )");

        // Partial unique indexes per level — one row per (entity, date[, hour]).
        DB::statement("CREATE UNIQUE INDEX ai_campaign_daily_unique  ON ad_insights (campaign_id, date)       WHERE level='campaign' AND hour IS NULL");
        DB::statement("CREATE UNIQUE INDEX ai_campaign_hourly_unique ON ad_insights (campaign_id, date, hour) WHERE level='campaign' AND hour IS NOT NULL");
        DB::statement("CREATE UNIQUE INDEX ai_adset_daily_unique     ON ad_insights (adset_id, date)          WHERE level='adset' AND hour IS NULL");
        DB::statement("CREATE UNIQUE INDEX ai_ad_daily_unique        ON ad_insights (ad_id, date)             WHERE level='ad' AND hour IS NULL");
        DB::statement("CREATE UNIQUE INDEX ai_ad_hourly_unique       ON ad_insights (ad_id, date, hour)       WHERE level='ad' AND hour IS NOT NULL");

        // Partial index for the standard campaign-level daily aggregation path.
        DB::statement("CREATE INDEX idx_ad_insights_ws_campaign_daily ON ad_insights (workspace_id, date) WHERE level = 'campaign' AND hour IS NULL");

        // Partial indexes for adset-level and ad-level daily queries (no hour partition).
        // AdsQueryService::computeAdsetRows() and computeAdRows() filter level + date range + hour IS NULL.
        DB::statement("CREATE INDEX idx_ai_ws_adset_daily ON ad_insights (workspace_id, date) WHERE level = 'adset' AND hour IS NULL");
        DB::statement("CREATE INDEX idx_ai_ws_ad_daily ON ad_insights (workspace_id, date) WHERE level = 'ad' AND hour IS NULL");

        // Composite covering index for account-filtered aggregations (buildPlatformPurchasesMap, buildCampaignSpendMap).
        DB::statement("CREATE INDEX idx_ai_ws_account_level_date ON ad_insights (workspace_id, ad_account_id, level, date) WHERE hour IS NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_insights');
    }
};
