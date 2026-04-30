<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benchmark_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('vertical')->index();
            $table->string('metric');
            $table->string('period');         // e.g. 'last_30d'
            $table->decimal('p25', 12, 4)->nullable();
            $table->decimal('p50', 12, 4)->nullable();
            $table->decimal('p75', 12, 4)->nullable();
            $table->unsignedInteger('sample_size');
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['vertical', 'metric', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benchmark_snapshots');
    }
};
