<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prospecting_card_statuses') || Schema::hasColumn('prospecting_card_statuses', 'appointment')) {
            return;
        }

        Schema::table('prospecting_card_statuses', function (Blueprint $table): void {
            $table->boolean('appointment')->default(false)->after('sent_text');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('prospecting_card_statuses') || ! Schema::hasColumn('prospecting_card_statuses', 'appointment')) {
            return;
        }

        Schema::table('prospecting_card_statuses', function (Blueprint $table): void {
            $table->dropColumn('appointment');
        });
    }
};