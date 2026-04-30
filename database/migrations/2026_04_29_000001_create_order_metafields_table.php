<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates order_metafields table for post-purchase survey responses
 * (Fairing / KnoCommerce "How did you hear about us?" integration).
 *
 * Each row is one metafield key→value pair for an order.
 * surveyBreakdown() in AttributionDataService queries key = 'hdyhau_response'.
 *
 * @see app/Services/Attribution/AttributionDataService.php surveyBreakdown()
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_metafields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('key', 255);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'key']);
            $table->index('key');
        });

        // Index for the primary query path (AttributionDataService::surveyBreakdown).
        DB::statement('CREATE INDEX idx_order_metafields_ws_key ON order_metafields (workspace_id, key)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_metafields');
    }
};
