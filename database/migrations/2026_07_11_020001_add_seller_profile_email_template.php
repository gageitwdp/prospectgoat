<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('email_templates')->where('key', 'new_lead_seller_profile')->exists()) {
            DB::table('email_templates')->insert([
                'key' => 'new_lead_seller_profile',
                'name' => 'Seller Profile Confirmation',
                'subject' => 'Thanks for sharing your seller profile, {{first_name}}',
                'body' => <<<'BLADE'
Hi {{first_name}},

Thanks for sharing your seller profile — I received your timeline, home details, and next-step preferences.

My name is Gage with Lezin Properties, and I help buyers and sellers here in the Woodstock / North Georgia area find the right opportunities without the pressure.

Here’s what happens next:
• I’m reviewing your seller profile now
• I’ll follow up shortly with a valuation approach and next steps tailored to your home
• If your situation is time-sensitive, feel free to call or text me directly at {{phone_number}}

Here’s the quick snapshot I’m using:
• Timeline: {{seller_timeline}}
• Motivation: {{seller_motivation}}
• Valuation delivery: {{seller_valuation_delivery_method}}

Looking forward to helping you out.

— Gage

Lezin Properties
{{phone_number}}
BLADE,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('email_templates')->where('key', 'new_lead_seller_profile')->delete();
    }
};