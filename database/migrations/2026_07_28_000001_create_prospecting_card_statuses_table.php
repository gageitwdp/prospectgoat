<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospecting_card_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('card_key');
            $table->boolean('skipped')->default(false);
            $table->boolean('called')->default(false);
            $table->boolean('left_voicemail')->default(false);
            $table->boolean('sent_text')->default(false);
            $table->timestamps();

            $table->unique(['account_id', 'user_id', 'card_key']);
            $table->index(['account_id', 'user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospecting_card_statuses');
    }
};
