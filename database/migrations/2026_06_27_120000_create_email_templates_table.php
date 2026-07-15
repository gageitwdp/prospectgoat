<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $defaults = [
            [
                'key' => 'new_lead_default',
                'name' => 'Default Inquiry Confirmation',
                'subject' => 'Thanks for reaching out, {{first_name}}',
                'body' => <<<'BLADE'
Hi {{first_name}},

Thanks for reaching out — I just received your request and I’m already taking a look for you.

My name is Gage with ProspectGoat, and I help buyers and sellers here in the Woodstock / North Georgia area find the right opportunities without the pressure.

Here’s what happens next:
• I’m reviewing your request now
• I’ll follow up shortly with options/details tailored to what you're looking for
• If your situation is time-sensitive, feel free to call or text me directly at {{phone_number}}

In the meantime, if you want to speed things up, just reply and let me know:
• Timeline (ASAP / 30–60 days / just browsing)
• Price range (if you have one in mind)
• Must-haves (beds, location, etc.)

Looking forward to helping you out.

— Gage

ProspectGoat
{{phone_number}}
BLADE,
            ],
            [
                'key' => 'new_lead_buyer',
                'name' => 'Buyer Inquiry Confirmation',
                'subject' => 'Thanks for your buyer inquiry, {{first_name}}',
                'body' => <<<'BLADE'
Hi {{first_name}},

Thanks for reaching out — I just received your buyer request and I’m already taking a look for you.

My name is Gage with ProspectGoat, and I help buyers and sellers here in the Woodstock / North Georgia area find the right opportunities without the pressure.

Here’s what happens next:
• I’m reviewing your request now
• I’ll follow up shortly with options/details tailored to what you're looking for
• If your situation is time-sensitive, feel free to call or text me directly at {{phone_number}}

In the meantime, if you want to speed things up, just reply and let me know:
• Timeline (ASAP / 30–60 days / just browsing)
• Price range (if you have one in mind)
• Must-haves (beds, location, etc.)

Looking forward to helping you out.

— Gage

ProspectGoat
{{phone_number}}
BLADE,
            ],
            [
                'key' => 'new_lead_seller',
                'name' => 'Seller Inquiry Confirmation',
                'subject' => 'Thanks for your seller inquiry, {{first_name}}',
                'body' => <<<'BLADE'
Hi {{first_name}},

Thanks for reaching out — I just received your seller request and I’m already taking a look for you.

My name is Gage with ProspectGoat, and I help buyers and sellers here in the Woodstock / North Georgia area find the right opportunities without the pressure.

Here’s what happens next:
• I’m reviewing your request now
• I’ll follow up shortly with options/details tailored to what you're looking for
• If your situation is time-sensitive, feel free to call or text me directly at {{phone_number}}

In the meantime, if you want to speed things up, just reply and let me know:
• Timeline (ASAP / 30–60 days / just browsing)
• Price range (if you have one in mind)
• Must-haves (beds, location, etc.)

Looking forward to helping you out.

— Gage

ProspectGoat
{{phone_number}}
BLADE,
            ],
            [
                'key' => 'new_lead_home_value',
                'name' => 'Home Value Inquiry Confirmation',
                'subject' => 'Thanks for your home value request, {{first_name}}',
                'body' => <<<'BLADE'
Hi {{first_name}},

Thanks for reaching out — I just received your home value request and I’m already taking a look for you.

My name is Gage with ProspectGoat, and I help buyers and sellers here in the Woodstock / North Georgia area find the right opportunities without the pressure.

Here’s what happens next:
• I’m reviewing your request now
• I’ll follow up shortly with options/details tailored to what you're looking for
• If your situation is time-sensitive, feel free to call or text me directly at {{phone_number}}

In the meantime, if you want to speed things up, just reply and let me know:
• Timeline (ASAP / 30–60 days / just browsing)
• Price range (if you have one in mind)
• Must-haves (beds, location, etc.)

Looking forward to helping you out.

— Gage

ProspectGoat
{{phone_number}}
BLADE,
            ],
            [
                'key' => 'new_lead_generic_inquiry',
                'name' => 'General Inquiry Confirmation',
                'subject' => 'Thanks for reaching out, {{first_name}}',
                'body' => <<<'BLADE'
Hi {{first_name}},

Thanks for reaching out — I just received your request and I’m already taking a look for you.

My name is Gage with ProspectGoat, and I help buyers and sellers here in the Woodstock / North Georgia area find the right opportunities without the pressure.

Here’s what happens next:
• I’m reviewing your request now
• I’ll follow up shortly with options/details tailored to what you're looking for
• If your situation is time-sensitive, feel free to call or text me directly at {{phone_number}}

In the meantime, if you want to speed things up, just reply and let me know:
• Timeline (ASAP / 30–60 days / just browsing)
• Price range (if you have one in mind)
• Must-haves (beds, location, etc.)

Looking forward to helping you out.

— Gage

ProspectGoat
{{phone_number}}
BLADE,
            ],
        ];

        foreach ($defaults as $template) {
            DB::table('email_templates')->insert([
                ...$template,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};