<?php

namespace App\Services\Billing;

use App\Models\Account;
use App\Models\User;
use RuntimeException;
use Stripe\Exception\InvalidRequestException;
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

        if (! $account->stripe_customer_id) {
            $account->forceFill(['stripe_customer_id' => $customerId])->save();
        }

        $session = $this->client()->checkout->sessions->create([
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
        ]);

        if (! is_string($session->url) || $session->url === '') {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        return $session->url;
    }

    public function completeCheckoutSession(Account $account, string $sessionId): void
    {
        $this->guardConfigured();

        try {
            $session = $this->client()->checkout->sessions->retrieve($sessionId, []);
        } catch (InvalidRequestException $exception) {
            throw new RuntimeException('Stripe checkout session could not be found. Please try checkout again.', previous: $exception);
        }

        $metadataAccountId = (int) ($session->metadata->account_id ?? 0);

        if ($metadataAccountId > 0 && $metadataAccountId !== (int) $account->id) {
            throw new RuntimeException('Stripe checkout session does not belong to this account.');
        }

        $customerId = is_string($session->customer) ? $session->customer : null;
        $subscriptionId = is_string($session->subscription) ? $session->subscription : null;

        if ($session->status !== 'complete' || ! $customerId || ! $subscriptionId) {
            throw new RuntimeException('Stripe checkout is not complete yet.');
        }

        $subscription = $this->client()->subscriptions->retrieve($subscriptionId, []);

        $account->forceFill([
            'stripe_customer_id' => $customerId,
            'stripe_subscription_id' => $subscriptionId,
            'billing_status' => $this->mapBillingStatus($subscription->status),
        ])->save();
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

    private function mapBillingStatus(?string $status): string
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

    private function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }
}