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
        $response->assertSee('7-day trial');
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

    public function test_success_redirects_to_dashboard_after_syncing_billing_state(): void
    {
        $user = User::factory()->create();
        $account = $user->account()->getResults();
        $account->forceFill(['billing_status' => Account::BILLING_STATUS_PENDING])->save();

        $this->mock(StripeBillingService::class, function ($mock) use ($account): void {
            $mock->shouldReceive('completeCheckoutSession')
                ->once()
                ->withArgs(fn (Account $passedAccount, string $sessionId) => $passedAccount->is($account) && $sessionId === 'cs_test_123')
                ->andReturnUsing(function (Account $passedAccount): void {
                    $passedAccount->forceFill([
                        'billing_status' => Account::BILLING_STATUS_TRIALING,
                        'stripe_customer_id' => 'cus_test_123',
                        'stripe_subscription_id' => 'sub_test_123',
                    ])->save();
                });
        });

        $response = $this->actingAs($user)->get(route('billing.success', ['session_id' => 'cs_test_123']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', 'Billing confirmation received. You can now continue into Prospect GOAT.');
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'billing_status' => Account::BILLING_STATUS_TRIALING,
            'stripe_customer_id' => 'cus_test_123',
            'stripe_subscription_id' => 'sub_test_123',
        ]);
    }

    public function test_success_redirects_back_to_billing_when_session_placeholder_is_returned(): void
    {
        $user = User::factory()->create();
        $user->account()->update(['billing_status' => Account::BILLING_STATUS_PENDING]);

        $response = $this->actingAs($user)->get(route('billing.success', ['session_id' => 'CHECKOUT_SESSION_ID']));

        $response->assertRedirect(route('billing.collect'));
        $response->assertSessionHas('status', 'Billing session was not returned correctly. Please try checkout again.');
    }
}