<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 3: keyword cannibalization detection (GSC top-10 keywords also in Google Ads).
        // Table created now per Phase 0 data-capture strategy — capture all data, build features later.
        // Related: PLANNING.md "google_ads_keywords" and "Cross-Channel Page Enhancements — Campaigns page"
        Schema::create('google_ads_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained('ad_accounts')->cascadeOnDelete();
            $table->string('ad_group_id', 255);
            $table->string('keyword_text', 500);
            $table->string('match_type', 50);  // BROAD, PHRASE, EXACT
            $table->string('status', 100)->nullable();
            $table->timestamps();

            $table->unique(['ad_account_id', 'ad_group_id', 'keyword_text', 'match_type']);
            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ads_keywords');
    }
};
