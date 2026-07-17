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
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('owner_2_full_name')->nullable()->after('name');
            $table->string('owner_2_phone', 30)->nullable()->after('phone');
            $table->string('owner_2_email')->nullable()->after('email');
            $table->text('prospecting_notes')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'owner_2_full_name',
                'owner_2_phone',
                'owner_2_email',
                'prospecting_notes',
            ]);
        });
    }
};
