<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->enum('seller_timeline', ['immediately_30_days', 'one_to_three_months', 'three_to_six_months', 'just_curious'])->nullable()->after('preferred_contact_method');
            $table->enum('seller_motivation', ['relocating_for_work', 'downsizing_upgrading', 'financial_reasons', 'estate_inheritance', 'testing_market'])->nullable()->after('seller_timeline');
            $table->string('seller_estimated_home_value')->nullable()->after('seller_motivation');
            $table->enum('seller_mortgage_status', ['yes', 'no'])->nullable()->after('seller_estimated_home_value');
            $table->enum('seller_needs_to_buy_another_home_after_selling', ['yes_local', 'yes_relocating', 'no'])->nullable()->after('seller_mortgage_status');
            $table->enum('seller_property_condition', ['excellent', 'minor_tlc', 'significant_repairs', 'fixer_upper'])->nullable()->after('seller_needs_to_buy_another_home_after_selling');
            $table->text('seller_major_upgrades')->nullable()->after('seller_property_condition');
            $table->enum('seller_agent_commitment', ['no', 'listed', 'fsbo'])->nullable()->after('seller_major_upgrades');
            $table->enum('seller_occupancy_status', ['primary_residence', 'vacant', 'rented_to_tenants'])->nullable()->after('seller_agent_commitment');
            $table->enum('seller_valuation_delivery_method', ['email', 'text', 'phone'])->nullable()->after('seller_occupancy_status');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'seller_timeline',
                'seller_motivation',
                'seller_estimated_home_value',
                'seller_mortgage_status',
                'seller_needs_to_buy_another_home_after_selling',
                'seller_property_condition',
                'seller_major_upgrades',
                'seller_agent_commitment',
                'seller_occupancy_status',
                'seller_valuation_delivery_method',
            ]);
        });
    }
};