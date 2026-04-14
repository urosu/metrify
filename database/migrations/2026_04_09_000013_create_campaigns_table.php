<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained('ad_accounts')->cascadeOnDelete();
            $table->string('external_id');
            $table->string('name', 500);
            $table->string('status', 100)->nullable();
            $table->string('objective', 100)->nullable();

            // Budget fields for spend velocity calculation on the Campaigns page.
            // budget_type: 'daily' or 'lifetime'. See: PLANNING.md "campaigns" schema changes.
            $table->decimal('daily_budget', 12, 2)->nullable();
            $table->decimal('lifetime_budget', 12, 2)->nullable();
            $table->string('budget_type', 20)->nullable();
            $table->string('bid_strategy', 100)->nullable();
            $table->decimal('target_value', 12, 2)->nullable();

            // Campaign-level ROAS/CPO targets — override workspace/store defaults.
            // A retargeting campaign naturally has a higher target ROAS than prospecting.
            // Column exists now to avoid live-schema retrofit when Phase 1.2 UI ships.
            // See: PLANNING.md "campaigns — target_roas / target_cpo (Phase 1.1 schema)"
            $table->decimal('target_roas', 5, 2)->nullable();
            $table->decimal('target_cpo', 10, 2)->nullable();

            $table->timestamps();

            $table->unique(['ad_account_id', 'external_id']);
            $table->index(['workspace_id', 'ad_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
