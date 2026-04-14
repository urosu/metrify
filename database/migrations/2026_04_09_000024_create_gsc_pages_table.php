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
        Schema::create('gsc_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('search_console_properties')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->date('date');

            // page is TEXT (URLs can exceed 2000 chars). page_hash is SHA-256 of the URL,
            // used in the unique constraint instead of the full URL to avoid massive indexes.
            // Why: VARCHAR(2000) unique constraint creates a huge index that degrades write performance.
            // See: PLANNING.md "Problems in Current Schema — gsc_pages.page"
            $table->text('page');
            $table->char('page_hash', 64);

            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('ctr', 8, 6)->nullable();
            $table->decimal('position', 6, 2)->nullable();

            // NOT NULL with sentinel values — see gsc_daily_stats for why.
            $table->string('device', 10)->default('all');
            $table->char('country', 3)->default('ZZ');

            $table->timestamps();

            $table->index(['property_id', 'date']);
            $table->index(['workspace_id', 'date']);
        });

        DB::statement('CREATE UNIQUE INDEX gsc_pages_upsert_key ON gsc_pages (property_id, date, page_hash, device, country)');
    }

    public function down(): void
    {
        Schema::dropIfExists('gsc_pages');
    }
};
