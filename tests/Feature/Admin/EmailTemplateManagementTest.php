<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\EmailTemplateController;
use App\Mail\LeadInquiryConfirmationMail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_email_template_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.email-templates.index'));

        $response->assertOk();
        $response->assertSee('Inquiry Confirmation Templates');
    }

    public function test_admin_can_update_email_template_content_and_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = EmailTemplate::query()->where('key', 'new_lead_buyer_qualification')->firstOrFail();

        $body = "Hello {{first_name}},\n\nThanks for your inquiry.\n\n- Gage";

        $request = Request::create('/', 'PUT', [
            'name' => 'Updated Buyer Confirmation',
            'subject' => 'We received your buyer request, {{first_name}}',
            'body' => $body,
            'is_active' => '1',
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(EmailTemplateController::class)->update($request, $template);

        $this->assertSame(route('admin.email-templates.edit', $template), $response->headers->get('Location'));
        $this->assertSame('Template updated successfully.', $response->getSession()->get('status'));

        $template->refresh();

        $this->assertSame('Updated Buyer Confirmation', $template->name);
        $this->assertSame('We received your buyer request, {{first_name}}', $template->subject);
        $this->assertSame($body, $template->body);
        $this->assertTrue($template->is_active);
    }

    public function test_admin_can_send_a_template_test_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $template = EmailTemplate::query()->where('key', 'new_lead_seller')->firstOrFail();

        $request = Request::create(route('admin.email-templates.test', $template), 'POST', [
            'recipient_email' => 'template-test@example.com',
            'first_name' => 'Jordan',
            'lead_type' => 'seller',
            'source' => 'homepage',
        ], [], [], ['HTTP_REFERER' => route('admin.email-templates.edit', $template)]);
        $request->setUserResolver(fn () => $admin);

        $response = app(EmailTemplateController::class)->test($request, $template);

        $this->assertSame(url('/'), $response->headers->get('Location'));
        $this->assertSame('Test email sent successfully.', $response->getSession()->get('status'));

        Mail::assertSent(LeadInquiryConfirmationMail::class, function (LeadInquiryConfirmationMail $mail): bool {
            return $mail->hasTo('template-test@example.com')
                && $mail->lead->name === 'Jordan Test'
                && $mail->template->key === 'new_lead_seller';
        });
    }

    public function test_admin_can_edit_seller_profile_email_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = EmailTemplate::query()->where('key', 'new_lead_seller_profile')->firstOrFail();

        $request = Request::create('/', 'PUT', [
            'name' => 'Updated Seller Profile Confirmation',
            'subject' => 'Your seller profile is in, {{first_name}}',
            'body' => 'Hello {{first_name}}',
            'is_active' => '1',
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(EmailTemplateController::class)->update($request, $template);

        $this->assertSame(route('admin.email-templates.edit', $template), $response->headers->get('Location'));
        $this->assertSame('Template updated successfully.', $response->getSession()->get('status'));

        $template->refresh();

        $this->assertSame('Updated Seller Profile Confirmation', $template->name);
        $this->assertSame('Your seller profile is in, {{first_name}}', $template->subject);
        $this->assertSame('Hello {{first_name}}', $template->body);
        $this->assertTrue($template->is_active);
    }

    public function test_agent_cannot_access_email_template_module(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $response = $this->actingAs($agent)->get(route('admin.email-templates.index'));

        $response->assertForbidden();
    }
}
