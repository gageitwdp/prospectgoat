<?php

namespace Tests\Feature\Billing;

use App\Jobs\ProcessStripeWebhookPayloadJob;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_webhook_dispatches_processing_job(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        Bus::fake();

        $payload = json_encode([
            'id' => 'evt_dispatch_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_dispatch_1',
                    'customer' => 'cus_dispatch_1',
                    'subscription' => 'sub_dispatch_1',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->signedHeader($payload, 'whsec_test');

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        );

        $response->assertOk();

        Bus::assertDispatched(ProcessStripeWebhookPayloadJob::class);
    }

    public function test_checkout_session_completed_webhook_syncs_stripe_ids_without_forcing_active_status(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $account = Account::factory()->create([
            'billing_status' => Account::BILLING_STATUS_PENDING,
            'stripe_customer_id' => null,
            'stripe_subscription_id' => null,
        ]);

        $payload = json_encode([
            'id' => 'evt_checkout_completed_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1',
                    'customer' => 'cus_test_1',
                    'subscription' => 'sub_test_1',
                    'metadata' => [
                        'account_id' => (string) $account->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->signedHeader($payload, 'whsec_test');

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        );

        $response->assertOk();
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'stripe_customer_id' => 'cus_test_1',
            'stripe_subscription_id' => 'sub_test_1',
            'billing_status' => Account::BILLING_STATUS_PENDING,
            'last_billing_event_type' => 'checkout.session.completed',
            'last_billing_event_id' => 'evt_checkout_completed_1',
        ]);
        $this->assertNotNull($account->fresh()->last_billing_sync_at);
        $this->assertDatabaseHas('stripe_webhook_events', [
            'event_id' => 'evt_checkout_completed_1',
            'event_type' => 'checkout.session.completed',
        ]);
    }

    public function test_subscription_updated_webhook_sets_trialing_status_and_trial_end_date(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $account = Account::factory()->create([
            'billing_status' => Account::BILLING_STATUS_PENDING,
            'stripe_customer_id' => 'cus_trial_1',
            'stripe_subscription_id' => 'sub_trial_1',
            'trial_ends_at' => null,
        ]);

        $trialEnd = now()->addDays(7)->timestamp;

        $payload = json_encode([
            'id' => 'evt_sub_updated_1',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_trial_1',
                    'customer' => 'cus_trial_1',
                    'status' => 'trialing',
                    'trial_end' => $trialEnd,
                    'metadata' => [
                        'account_id' => (string) $account->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->signedHeader($payload, 'whsec_test');

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        );

        $response->assertOk();

        $freshAccount = $account->fresh();
        $this->assertNotNull($freshAccount?->trial_ends_at);
        $this->assertSame($trialEnd, $freshAccount?->trial_ends_at?->timestamp);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'billing_status' => Account::BILLING_STATUS_TRIALING,
            'last_billing_event_type' => 'customer.subscription.updated',
            'last_billing_event_id' => 'evt_sub_updated_1',
        ]);
    }

    public function test_invoice_payment_failed_webhook_sets_account_past_due(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $account = Account::factory()->create([
            'billing_status' => Account::BILLING_STATUS_ACTIVE,
            'stripe_customer_id' => 'cus_test_2',
            'stripe_subscription_id' => 'sub_test_2',
        ]);

        $payload = json_encode([
            'id' => 'evt_invoice_failed_1',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test_1',
                    'customer' => 'cus_test_2',
                    'subscription' => 'sub_test_2',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->signedHeader($payload, 'whsec_test');

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        );

        $response->assertOk();
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'billing_status' => Account::BILLING_STATUS_PAST_DUE,
            'last_billing_event_type' => 'invoice.payment_failed',
            'last_billing_event_id' => 'evt_invoice_failed_1',
        ]);
        $this->assertNotNull($account->fresh()->last_billing_sync_at);
    }

    public function test_duplicate_webhook_event_is_processed_once(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $account = Account::factory()->create([
            'billing_status' => Account::BILLING_STATUS_PENDING,
        ]);

        $payload = json_encode([
            'id' => 'evt_duplicate_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_duplicate_1',
                    'customer' => 'cus_dup_1',
                    'subscription' => 'sub_dup_1',
                    'metadata' => [
                        'account_id' => (string) $account->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->signedHeader($payload, 'whsec_test');

        $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        )
            ->assertOk();

        $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        )
            ->assertOk();

        $this->assertDatabaseCount('stripe_webhook_events', 1);
    }

    public function test_invalid_webhook_signature_returns_bad_request(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        Bus::fake();

        $payload = json_encode([
            'id' => 'evt_bad_sig',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_bad_sig',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't=1,v1=invalid',
            ],
            $payload,
        );

        $response->assertStatus(400);
        Bus::assertNotDispatched(ProcessStripeWebhookPayloadJob::class);
    }

    private function signedHeader(string $payload, string $secret): string
    {
        $timestamp = (string) time();
        $signedPayload = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return 't='.$timestamp.',v1='.$signature;
    }
}