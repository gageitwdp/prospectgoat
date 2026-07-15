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

    public function test_billing_screen_can_be_rendered_for_pending_account(): void
    {
        $user = User::factory()->create();
        $user->account()->update(['billing_status' => Account::BILLING_STATUS_PENDING]);

        $response = $this->actingAs($user)->get(route('billing.collect'));

        $response->assertOk();
        $response->assertSee('Finish your account setup');
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

    public function test_success_marks_account_active_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();
        $user->account()->update(['billing_status' => Account::BILLING_STATUS_PENDING]);

        $this->mock(StripeBillingService::class, function ($mock) use ($user): void {
            $mock->shouldReceive('completeCheckoutSession')
                ->once()
                ->withArgs(fn (Account $account, string $sessionId) => $account->is($user->account) && $sessionId === 'cs_test_123');
        });

        $response = $this->actingAs($user)->get(route('billing.success', ['session_id' => 'cs_test_123']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', 'Billing setup complete.');
    }
}