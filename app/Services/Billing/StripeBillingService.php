<?php

namespace App\Services\Billing;

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Subscription;
use Stripe\StripeClient;

class StripeBillingService
{
    public function isConfigured(): bool
    {
        return class_exists(StripeClient::class)
            && filled(config('services.stripe.secret'))
            && filled(config('services.stripe.prices.single_agent'));
    }

    public function createCheckoutSession(Account $account, User $user): string
    {
        $this->guardConfigured();

        $customerId = $account->stripe_customer_id ?: $this->createCustomer($account, $user);
        $trialDays = (int) config('services.stripe.trial_days', 0);

        if (! $account->stripe_customer_id) {
            $account->forceFill(['stripe_customer_id' => $customerId])->save();
        }

        $sessionPayload = [
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [[
                'price' => config('services.stripe.prices.single_agent'),
                'quantity' => 1,
            ]],
            'metadata' => [
                'account_id' => (string) $account->id,
                'user_id' => (string) $user->id,
                'service_level' => $account->service_level,
            ],
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.collect', ['canceled' => 1]),
        ];

        if ($trialDays > 0) {
            $sessionPayload['subscription_data'] = [
                'trial_period_days' => $trialDays,
            ];
        }

        $session = $this->client()->checkout->sessions->create($sessionPayload);

        if (! is_string($session->url) || $session->url === '') {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        return $session->url;
    }

    public function completeCheckoutSession(Account $account, string $sessionId): void
    {
        $this->guardConfigured();

        $session = $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription'],
        ]);

        if (! $session instanceof Session) {
            throw new RuntimeException('Stripe did not return a checkout session.');
        }

        $metadataAccountId = (int) ($session->metadata->account_id ?? 0);

        if ($metadataAccountId > 0 && $metadataAccountId !== $account->id) {
            throw new RuntimeException('Stripe checkout session does not belong to this account.');
        }

        $customerId = is_string($session->customer ?? null) ? $session->customer : null;
        $subscription = $session->subscription ?? null;

        if (is_string($subscription) && $subscription !== '') {
            $subscription = $this->client()->subscriptions->retrieve($subscription, []);
        }

        if (! $subscription instanceof Subscription) {
            throw new RuntimeException('Stripe did not return a subscription for this checkout session.');
        }

        $trialEnd = $subscription->trial_end ?? null;
        $trialEndsAt = is_numeric($trialEnd)
            ? Carbon::createFromTimestamp((int) $trialEnd)
            : null;

        $account->forceFill([
            'stripe_customer_id' => $customerId ?: $account->stripe_customer_id,
            'stripe_subscription_id' => $subscription->id ?: $account->stripe_subscription_id,
            'billing_status' => $this->mapBillingStatus((string) ($subscription->status ?? '')),
            'trial_ends_at' => $trialEndsAt,
            'last_billing_sync_at' => now(),
            'last_billing_event_type' => 'checkout.session.completed',
            'last_billing_event_id' => $session->id,
        ])->save();
    }

    public function canOpenCustomerPortal(Account $account): bool
    {
        return class_exists(StripeClient::class)
            && filled(config('services.stripe.secret'))
            && filled($account->stripe_customer_id);
    }

    public function createCustomerPortalSession(Account $account, string $returnUrl): string
    {
        if (! $this->canOpenCustomerPortal($account)) {
            throw new RuntimeException('Stripe customer portal is not available for this account.');
        }

        $session = $this->client()->billingPortal->sessions->create([
            'customer' => $account->stripe_customer_id,
            'return_url' => $returnUrl,
        ]);

        if (! is_string($session->url) || $session->url === '') {
            throw new RuntimeException('Stripe did not return a customer portal URL.');
        }

        return $session->url;
    }

    private function createCustomer(Account $account, User $user): string
    {
        $customer = $this->client()->customers->create([
            'email' => $user->email,
            'name' => $account->name,
            'metadata' => [
                'account_id' => (string) $account->id,
                'user_id' => (string) $user->id,
            ],
        ]);

        if (! is_string($customer->id) || $customer->id === '') {
            throw new RuntimeException('Stripe did not return a customer ID.');
        }

        return $customer->id;
    }

    private function mapBillingStatus(string $status): string
    {
        return match ($status) {
            'active' => Account::BILLING_STATUS_ACTIVE,
            'trialing' => Account::BILLING_STATUS_TRIALING,
            'past_due', 'incomplete', 'incomplete_expired', 'unpaid' => Account::BILLING_STATUS_PAST_DUE,
            'canceled' => Account::BILLING_STATUS_CANCELED,
            default => Account::BILLING_STATUS_PENDING,
        };
    }

    private function guardConfigured(): void
    {
        if (! $this->isConfigured()) {
            if (! class_exists(StripeClient::class)) {
                throw new RuntimeException('Stripe SDK is not installed. Run composer install/deploy to include stripe/stripe-php.');
            }

            throw new RuntimeException('Stripe billing is not configured.');
        }
    }

    protected function client()
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }
}