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
        Schema::table('accounts', function (Blueprint $table): void {
            $table->timestamp('last_billing_sync_at')->nullable()->after('trial_ends_at');
            $table->string('last_billing_event_type', 120)->nullable()->after('last_billing_sync_at');
            $table->string('last_billing_event_id', 120)->nullable()->after('last_billing_event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn([
                'last_billing_sync_at',
                'last_billing_event_type',
                'last_billing_event_id',
            ]);
        });
    }
};