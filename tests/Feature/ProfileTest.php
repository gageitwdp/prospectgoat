<?php

namespace Tests\Feature;

use App\Mail\AdminEmailTestMail;
use App\Models\Account;
use App\Models\User;
use App\Services\Billing\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_admin_profile_page_displays_email_test_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Email Test');
    }

    public function test_non_admin_profile_page_does_not_display_email_test_section(): void
    {
        $user = User::factory()->create(['role' => 'agent']);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertDontSee('Send Test Email');
    }

    public function test_global_admin_profile_shows_account_plan_oversight_section(): void
    {
        $account = Account::factory()->create([
            'name' => 'Atlas Realty',
            'service_level' => Account::SERVICE_LEVEL_SINGLE_AGENT,
            'billing_status' => Account::BILLING_STATUS_ACTIVE,
            'stripe_customer_id' => null,
        ]);

        $globalAdmin = User::factory()->create([
            'role' => 'global_admin',
            'account_id' => $account->id,
        ]);

        $response = $this
            ->actingAs($globalAdmin)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Global Account Oversight');
        $response->assertSee('Atlas Realty');
        $response->assertSee('Single Agent');
    }

    public function test_non_global_admin_profile_does_not_show_account_oversight_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->get('/profile');

        $response->assertOk();
        $response->assertDontSee('Global Account Oversight');
    }

    public function test_owner_profile_shows_subscription_management_section(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $response = $this
            ->actingAs($owner)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Subscription Management');
    }

    public function test_owner_profile_shows_trial_end_date_when_account_is_trialing(): void
    {
        $trialEnd = now()->addDays(7)->startOfDay();

        $owner = User::factory()->create([
            'role' => 'owner',
            'account_id' => Account::factory()->create([
                'billing_status' => Account::BILLING_STATUS_TRIALING,
                'trial_ends_at' => $trialEnd,
            ])->id,
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Trial Ends');
        $response->assertSee($trialEnd->format('M d, Y'));
    }

    public function test_agent_profile_does_not_show_subscription_management_section(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $response = $this
            ->actingAs($agent)
            ->get('/profile');

        $response->assertOk();
        $response->assertDontSee('Subscription Management');
    }

    public function test_owner_can_open_stripe_subscription_portal(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'account_id' => Account::factory()->create([
                'stripe_customer_id' => 'cus_portal_test_1',
            ])->id,
        ]);

        $this->mock(StripeBillingService::class, function ($mock) use ($owner): void {
            $mock->shouldReceive('canOpenCustomerPortal')
                ->once()
                ->withArgs(fn (Account $account) => $account->id === $owner->account_id)
                ->andReturn(true);

            $mock->shouldReceive('createCustomerPortalSession')
                ->once()
                ->withArgs(fn (Account $account, string $returnUrl) => $account->id === $owner->account_id && str_contains($returnUrl, '/profile'))
                ->andReturn('https://billing.stripe.com/p/session_test_1');
        });

        $response = $this
            ->actingAs($owner)
            ->post(route('profile.subscription-portal'));

        $response->assertRedirect('https://billing.stripe.com/p/session_test_1');
    }

    public function test_agent_cannot_open_subscription_portal(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $response = $this
            ->actingAs($agent)
            ->post(route('profile.subscription-portal'));

        $response->assertForbidden();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->notify_on_new_lead_intake);
        $this->assertFalse($user->notify_on_lead_assignment);
    }

    public function test_profile_notification_preferences_can_be_updated(): void
    {
        $user = User::factory()->create([
            'notify_on_new_lead_intake' => true,
            'notify_on_lead_assignment' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'notify_on_new_lead_intake' => '0',
                'notify_on_lead_assignment' => '1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertFalse($user->notify_on_new_lead_intake);
        $this->assertTrue($user->notify_on_lead_assignment);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_admin_can_send_immediate_test_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->post('/profile/email-test', [
                'email' => 'mail-test@example.com',
                'delivery' => 'immediate',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('email_test_status', 'Sent test email immediately.');

        Mail::assertSent(AdminEmailTestMail::class, function (AdminEmailTestMail $mail): bool {
            return $mail->hasTo('mail-test@example.com') && $mail->deliveryMode === 'immediate';
        });
    }

    public function test_admin_can_queue_test_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->post('/profile/email-test', [
                'email' => 'mail-queue@example.com',
                'delivery' => 'queued',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('email_test_status', 'Queued test email request successfully.');

        Mail::assertQueued(AdminEmailTestMail::class, function (AdminEmailTestMail $mail): bool {
            return $mail->hasTo('mail-queue@example.com')
                && $mail->deliveryMode === 'queued'
                && $mail->queue === 'notifications';
        });
    }

    public function test_non_admin_cannot_send_test_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => 'agent']);

        $response = $this
            ->actingAs($user)
            ->post('/profile/email-test', [
                'email' => 'blocked@example.com',
                'delivery' => 'immediate',
            ]);

        $response->assertForbidden();

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }
}
