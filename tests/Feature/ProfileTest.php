<?php

namespace Tests\Feature;

use App\Mail\AdminEmailTestMail;
use App\Models\User;
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
