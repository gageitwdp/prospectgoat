<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prospecting_scripts', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('account_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['account_id', 'user_id', 'is_active', 'sort_order'], 'prospecting_scripts_scope_active_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospecting_scripts', function (Blueprint $table) {
            $table->dropIndex('prospecting_scripts_scope_active_sort_idx');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
