<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('email_templates')->where('key', 'new_lead_buyer_qualification')->exists()) {
            DB::table('email_templates')->insert([
                'key' => 'new_lead_buyer_qualification',
                'name' => 'Buyer Qualification Confirmation',
                'subject' => 'Thanks for completing your buyer profile, {{first_name}}',
                'body' => <<<'BLADE'
Hi {{first_name}},

Thanks for completing your buyer profile — I received your timeline, budget, and search preferences.

My name is Gage with ProspectGoat, and I help buyers and sellers here in the Woodstock / North Georgia area find the right opportunities without the pressure.

Here’s what happens next:
• I’m reviewing your buyer details now
• I’ll follow up shortly with homes and neighborhoods that fit your goals
• If your situation is time-sensitive, feel free to call or text me directly at {{phone_number}}

Here’s the quick snapshot I’m using:
• Timeline: {{move_timeline}}
• Budget: {{price_range}}
• Preferred contact: {{preferred_contact_method}}

Looking forward to helping you out.

— Gage

ProspectGoat
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
        DB::table('email_templates')->where('key', 'new_lead_buyer_qualification')->delete();
    }
};