<?php

namespace Tests\Feature\Billing;

use App\Models\Account;
use App\Services\Billing\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session;
use Stripe\Subscription;
use Tests\TestCase;

class StripeBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_checkout_session_syncs_trialing_subscription_state(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_123',
            'services.stripe.prices.single_agent' => 'price_test_123',
        ]);

        $account = Account::factory()->create([
            'billing_status' => Account::BILLING_STATUS_PENDING,
            'stripe_customer_id' => null,
            'stripe_subscription_id' => null,
            'trial_ends_at' => null,
        ]);

        $subscription = new Subscription('sub_test_123');
        $subscription->status = 'trialing';
        $subscription->trial_end = now()->addDays(7)->timestamp;

        $session = new Session('cs_test_123');
        $session->customer = 'cus_test_123';
        $session->subscription = $subscription;
        $session->metadata = [
            'account_id' => (string) $account->id,
        ];

        $checkoutSessions = \Mockery::mock();
        $checkoutSessions->shouldReceive('retrieve')
            ->once()
            ->with('cs_test_123', ['expand' => ['subscription']])
            ->andReturn($session);

        $service = new class($checkoutSessions) extends StripeBillingService {
            public function __construct(private readonly object $fakeCheckoutSessions)
            {
            }

            protected function client(): object
            {
                return (object) [
                    'checkout' => (object) [
                        'sessions' => $this->fakeCheckoutSessions,
                    ],
                ];
            }
        };

        $service->completeCheckoutSession($account, 'cs_test_123');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'billing_status' => Account::BILLING_STATUS_TRIALING,
            'stripe_customer_id' => 'cus_test_123',
            'stripe_subscription_id' => 'sub_test_123',
        ]);
        $this->assertNotNull($account->fresh()->trial_ends_at);
    }
}