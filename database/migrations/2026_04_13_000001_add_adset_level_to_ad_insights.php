<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extends ad_insights to support adset-level rows.
 *
 * The original table only allowed 'campaign' and 'ad' levels. Adding 'adset'
 * unlocks ad set performance pages without duplicating ad or campaign totals.
 *
 * Changes:
 *   1. Drop and recreate level_check — adds 'adset' to the allowed values.
 *   2. Drop and recreate level_fk_check — adset rows require adset_id NOT NULL,
 *      campaign_id NULL, ad_id NULL.
 *   3. New partial unique index: (adset_id, date) WHERE level='adset' AND hour IS NULL.
 *   4. New composite index: (workspace_id, adset_id, date) for controller queries.
 *
 * See: PLANNING.md "Ad Sets page" — Phase 1.5
 */
return new class extends Migration
{
    public function up(): void
    {
        // Extend the level enum
        DB::statement("ALTER TABLE ad_insights DROP CONSTRAINT ad_insights_level_check");
        DB::statement("ALTER TABLE ad_insights ADD CONSTRAINT ad_insights_level_check CHECK (level IN ('campaign','adset','ad'))");

        // Extend FK integrity rules — adset rows: adset_id set, campaign_id and ad_id NULL
        DB::statement("ALTER TABLE ad_insights DROP CONSTRAINT ad_insights_level_fk_check");
        DB::statement("ALTER TABLE ad_insights ADD CONSTRAINT ad_insights_level_fk_check CHECK (
            (level = 'campaign' AND campaign_id IS NOT NULL AND ad_id IS NULL)
            OR
            (level = 'adset' AND adset_id IS NOT NULL AND campaign_id IS NULL AND ad_id IS NULL)
            OR
            (level = 'ad' AND ad_id IS NOT NULL AND campaign_id IS NULL)
        )");

        // Partial unique index — one row per (adset, date) at daily granularity
        DB::statement("CREATE UNIQUE INDEX ai_adset_daily_unique ON ad_insights (adset_id, date) WHERE level='adset' AND hour IS NULL");

        // Composite index for controller queries: WHERE workspace_id=? AND adset_id=? AND date BETWEEN ? AND ?
        DB::statement("CREATE INDEX ad_insights_workspace_id_adset_id_date_index ON ad_insights (workspace_id, adset_id, date)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS ad_insights_workspace_id_adset_id_date_index");
        DB::statement("DROP INDEX IF EXISTS ai_adset_daily_unique");

        DB::statement("ALTER TABLE ad_insights DROP CONSTRAINT IF EXISTS ad_insights_level_fk_check");
        DB::statement("ALTER TABLE ad_insights ADD CONSTRAINT ad_insights_level_fk_check CHECK (
            (level = 'campaign' AND campaign_id IS NOT NULL AND ad_id IS NULL)
            OR
            (level = 'ad' AND ad_id IS NOT NULL AND campaign_id IS NULL)
        )");

        DB::statement("ALTER TABLE ad_insights DROP CONSTRAINT IF EXISTS ad_insights_level_check");
        DB::statement("ALTER TABLE ad_insights ADD CONSTRAINT ad_insights_level_check CHECK (level IN ('campaign','ad'))");
    }
};
