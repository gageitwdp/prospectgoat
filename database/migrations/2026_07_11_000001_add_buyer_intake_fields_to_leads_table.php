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
        Schema::table('leads', function (Blueprint $table) {
            $table->enum('move_timeline', ['immediately_30_days', 'one_to_three_months', 'three_to_six_months', 'just_browsing'])->nullable()->after('working_with_agent');
            $table->enum('move_if_not_found', ['must_move', 'stay_where_i_am', 'continue_renting'])->nullable()->after('move_timeline');
            $table->string('price_range')->nullable()->after('move_if_not_found');
            $table->enum('mortgage_preapproval_status', ['pre_approved', 'ready_to_talk', 'cash', 'not_ready'])->nullable()->after('price_range');
            $table->enum('need_to_sell_current_home', ['yes', 'no', 'renting'])->nullable()->after('mortgage_preapproval_status');
            $table->enum('agent_relationship', ['exclusive', 'none', 'open_houses'])->nullable()->after('need_to_sell_current_home');
            $table->enum('purchase_reason', ['first_time_homebuyer', 'relocating_for_work', 'upgrading_downsizing', 'investing'])->nullable()->after('agent_relationship');
            $table->string('target_areas')->nullable()->after('purchase_reason');
            $table->unsignedTinyInteger('min_bedrooms')->nullable()->after('target_areas');
            $table->decimal('min_bathrooms', 3, 1)->nullable()->after('min_bedrooms');
            $table->enum('preferred_contact_method', ['email', 'text', 'phone'])->nullable()->after('min_bathrooms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'move_timeline',
                'move_if_not_found',
                'price_range',
                'mortgage_preapproval_status',
                'need_to_sell_current_home',
                'agent_relationship',
                'purchase_reason',
                'target_areas',
                'min_bedrooms',
                'min_bathrooms',
                'preferred_contact_method',
            ]);
        });
    }
};