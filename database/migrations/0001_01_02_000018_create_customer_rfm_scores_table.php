<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates customer_rfm_scores and customer_ltv_overrides tables.
 *
 * customer_rfm_scores: nightly RFM + segment assignment per customer.
 * customer_ltv_overrides: admin-authored overrides on Predicted LTV.
 *
 * @see docs/planning/schema.md §1.4 customer_rfm_scores, customer_ltv_overrides
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_rfm_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('computed_for');
            $table->smallInteger('recency_score');
            $table->smallInteger('frequency_score');
            $table->smallInteger('monetary_score');
            // CHECK constraint below.
            $table->string('segment', 32);
            // 0-100
            $table->smallInteger('churn_risk')->nullable();
            $table->timestamp('predicted_next_order_at')->nullable();
            $table->decimal('predicted_ltv_reporting', 14, 2)->nullable();
            // 0-100
            $table->smallInteger('predicted_ltv_confidence')->nullable();
            // Track retrains.
            $table->string('model_version', 16);
            // Days from acquisition until cumulative revenue ≥ acquisition CAC.
            // Computed by future ComputePaybackDaysJob using cac_at_acquisition below.
            $table->integer('payback_days')->nullable();
            // Frozen CAC for the customer's acquisition channel/period.
            // Captured at first-order time; does not change when ad spend is retroactively recomputed.
            $table->decimal('cac_at_acquisition', 12, 2)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['workspace_id', 'customer_id', 'computed_for']);
            $table->index(['workspace_id', 'segment', 'computed_for'], 'idx_rfm_ws_segment_date');
        });

        DB::statement("ALTER TABLE customer_rfm_scores ADD CONSTRAINT check_rfm_segment CHECK (segment IN ('champions','loyal','potential_loyalists','at_risk','about_to_sleep','needs_attention','hibernating'))");

        // Fast "who needs attention" queries.
        DB::statement("CREATE INDEX idx_rfm_workspace_date ON customer_rfm_scores (workspace_id, computed_for) WHERE segment = 'at_risk'");

        Schema::create('customer_ltv_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('overridden_ltv_reporting', 14, 2);
            $table->string('reason', 255);
            $table->foreignId('overridden_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('overridden_at');
            $table->timestamps();

            $table->unique(['workspace_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ltv_overrides');
        Schema::dropIfExists('customer_rfm_scores');
    }
};
