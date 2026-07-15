<?php

namespace Tests\Feature\Auth;

use App\Models\Account;
use App\Models\User;
use App\Services\Billing\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_billing_user_is_redirected_from_dashboard_to_billing_screen(): void
    {
        $user = User::factory()->create();
        $user->account()->update(['billing_status' => Account::BILLING_STATUS_PENDING]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('billing.collect'));
    }

    public function test_global_admin_is_not_redirected_to_billing_when_account_is_pending(): void
    {
        $user = User::factory()->create(['role' => 'global_admin']);
        $user->account()->update(['billing_status' => Account::BILLING_STATUS_PENDING]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_billing_screen_can_be_rendered_for_pending_account(): void
    {
        $user = User::factory()->create();
        $user->account()->update(['billing_status' => Account::BILLING_STATUS_PENDING]);

        $response = $this->actingAs($user)->get(route('billing.collect'));

        $response->assertOk();
        $response->assertSee('Finish your account setup');
        $response->assertSee('7-day free trial');
    }

    public function test_checkout_redirects_to_stripe_url(): void
    {
        $user = User::factory()->create();
        $user->account()->update(['billing_status' => Account::BILLING_STATUS_PENDING]);

        $this->mock(StripeBillingService::class, function ($mock) use ($user): void {
            $mock->shouldReceive('isConfigured')->once()->andReturn(true);
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->withArgs(fn (Account $account, User $authUser) => $account->is($user->account) && $authUser->is($user))
                ->andReturn('https://checkout.stripe.com/test-session');
        });

        $response = $this->actingAs($user)->post(route('billing.checkout'));

        $response->assertRedirect('https://checkout.stripe.com/test-session');
    }

    public function test_success_redirects_to_dashboard_and_defers_activation_to_webhook(): void
    {
        $user = User::factory()->create();
        $user->account()->update(['billing_status' => Account::BILLING_STATUS_PENDING]);

        $this->mock(StripeBillingService::class, function ($mock): void {
            $mock->shouldNotReceive('completeCheckoutSession');
        });

        $response = $this->actingAs($user)->get(route('billing.success', ['session_id' => 'cs_test_123']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', 'Billing confirmation received. Access will update once Stripe finalizes your payment.');
        $this->assertDatabaseHas('accounts', [
            'id' => $user->account->id,
            'billing_status' => Account::BILLING_STATUS_PENDING,
        ]);
    }
}