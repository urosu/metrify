<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates gsc_ctr_benchmarks table — static peer-bucket CTR benchmarks.
 *
 * Seeded by GscCtrBenchmarksSeeder. Global (no workspace_id).
 * Used by /seo ConfidenceChip and Opportunity badge "Leaking".
 *
 * @see docs/planning/schema.md §1.8 gsc_ctr_benchmarks
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gsc_ctr_benchmarks', function (Blueprint $table) {
            // Bucket labels: "1", "2", "3", "4-5", "6-10", "11-20", "21+"
            $table->string('position_bucket', 16)->primary();
            $table->decimal('expected_ctr', 6, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gsc_ctr_benchmarks');
    }
};
