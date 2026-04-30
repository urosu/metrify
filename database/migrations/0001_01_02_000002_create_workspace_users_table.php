<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates workspace_users membership pivot table.
 *
 * @see docs/planning/schema.md §1.1 workspace_users
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 16);
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE workspace_users ADD CONSTRAINT check_workspace_users_role CHECK (role IN ('owner','admin','member'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_users');
    }
};
