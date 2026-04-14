<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->cascadeOnDelete();
            $table->foreignId('ad_account_id')->nullable()->constrained('ad_accounts')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('search_console_properties')->cascadeOnDelete();

            // source distinguishes system-generated, rule-based, and AI-generated alerts.
            // Phase 2: DetectAnomaliesJob sets source='system', AI narratives set source='ai'.
            $table->string('source', 50)->default('system');

            $table->string('type', 100);
            $table->string('severity', 50);
            $table->jsonb('data')->nullable();

            // Phase 2: silent mode — alerts are stored but not surfaced to users until
            // SILENT_MODE_GRADUATION criteria are met. See: PLANNING.md "Anomaly Detection System"
            $table->boolean('is_silent')->default(false);

            // review_status for tuning false positive rates during silent mode evaluation.
            $table->string('review_status', 50)->nullable();
            $table->timestamp('reviewed_at')->nullable();

            // Revenue impact estimates shown on alert cards — labeled "estimated", shown as range.
            $table->decimal('estimated_impact_low', 12, 2)->nullable();
            $table->decimal('estimated_impact_high', 12, 2)->nullable();

            // Audit trail for GSC-correlated revenue alerts.
            $table->decimal('gsc_conversion_rate_at_alert', 8, 6)->nullable();
            $table->decimal('store_aov_at_alert', 12, 2)->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'resolved_at', 'created_at']);
        });

        DB::statement("ALTER TABLE alerts ADD CONSTRAINT alerts_source_check CHECK (source IN ('system','rule','ai'))");
        DB::statement("ALTER TABLE alerts ADD CONSTRAINT alerts_severity_check CHECK (severity IN ('info','warning','high','critical'))");
        DB::statement("ALTER TABLE alerts ADD CONSTRAINT alerts_review_status_check CHECK (review_status IN ('unreviewed','true_positive','false_positive','correct_but_uninteresting'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
