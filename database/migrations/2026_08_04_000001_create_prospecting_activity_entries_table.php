<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prospecting_activity_entries')) {
            return;
        }

        Schema::create('prospecting_activity_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('activity_date');
            $table->enum('activity_type', ['call', 'text', 'voicemail']);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'user_id', 'activity_date'], 'pae_acct_user_date_idx');
            $table->index(['account_id', 'user_id', 'activity_type'], 'pae_acct_user_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospecting_activity_entries');
    }
};