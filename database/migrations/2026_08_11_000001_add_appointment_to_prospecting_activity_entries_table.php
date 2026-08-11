<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prospecting_activity_entries')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE prospecting_activity_entries MODIFY COLUMN activity_type ENUM('call','text','voicemail','appointment') NOT NULL");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('prospecting_activity_entries')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE prospecting_activity_entries MODIFY COLUMN activity_type ENUM('call','text','voicemail') NOT NULL");
        }
    }
};