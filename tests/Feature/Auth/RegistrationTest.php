<?php

namespace Tests\Feature\Auth;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_not_available_when_public_signup_is_disabled(): void
    {
        config(['auth.enable_public_signup' => false]);

        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_new_users_cannot_register_through_public_endpoint_when_public_signup_is_disabled(): void
    {
        config(['auth.enable_public_signup' => false]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertNotFound();
    }

    public function test_guest_can_register_when_public_signup_is_enabled(): void
    {
        config(['auth.enable_public_signup' => true]);

        $response = $this->post('/register', [
            'name' => 'Single Agent Owner',
            'email' => 'owner@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $account = Account::query()->findOrFail($user->account_id);

        $this->assertSame('owner', $user->role);
        $this->assertSame(Account::SERVICE_LEVEL_SINGLE_AGENT, $account->service_level);
        $this->assertSame(Account::BILLING_STATUS_PENDING, $account->billing_status);
        $this->assertNotEmpty($account->slug);
    }

    public function test_public_signup_creates_unique_account_slug_for_same_name(): void
    {
        config(['auth.enable_public_signup' => true]);

        $this->post('/register', [
            'name' => 'Gage Team',
            'email' => 'first@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        auth()->logout();

        $this->post('/register', [
            'name' => 'Gage Team',
            'email' => 'second@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        $slugs = Account::query()->where('name', 'Gage Team')->pluck('slug');

        $this->assertCount(2, $slugs);
        $this->assertNotSame($slugs->first(), $slugs->last());
    }
}
