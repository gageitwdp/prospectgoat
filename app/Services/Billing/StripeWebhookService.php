<?php

namespace App\Services\Billing;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\StripeObject;
use Stripe\Webhook;
use Throwable;

class StripeWebhookService
{
    public function handleSignedPayload(string $payload, string $signature): void
    {
        $this->guardConfigured();

        $event = Webhook::constructEvent(
            $payload,
            $signature,
            (string) config('services.stripe.webhook_secret'),
        );

        $eventId = (string) ($event->id ?? '');
        $eventType = (string) ($event->type ?? 'unknown');

        if ($eventId === '') {
            throw new RuntimeException('Stripe webhook event id is missing.');
        }

        if (! $this->reserveEvent($eventId, $eventType, $payload)) {
            return;
        }

        try {
            $this->processEvent($eventType, $eventId, $event->data?->object);
            $this->markProcessed($eventId);
        } catch (Throwable $exception) {
            $this->releaseReservation($eventId);

            throw $exception;
        }
    }

    private function processEvent(string $eventType, string $eventId, mixed $object): void
    {
        if (! $object instanceof StripeObject) {
            return;
        }

        match ($eventType) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($object, $eventType, $eventId),
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted' => $this->handleSubscriptionUpdated($object, $eventType, $eventId),
            'invoice.paid' => $this->handleInvoicePaid($object, $eventType, $eventId),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object, $eventType, $eventId),
            default => null,
        };
    }

    private function handleCheckoutSessionCompleted(StripeObject $session, string $eventType, string $eventId): void
    {
        $account = $this->resolveAccount(
            accountId: (int) ($session->metadata->account_id ?? 0),
            customerId: is_string($session->customer ?? null) ? $session->customer : null,
            subscriptionId: is_string($session->subscription ?? null) ? $session->subscription : null,
        );

        if (! $account) {
            return;
        }

        $account->forceFill([
            'stripe_customer_id' => is_string($session->customer ?? null) ? $session->customer : $account->stripe_customer_id,
            'stripe_subscription_id' => is_string($session->subscription ?? null) ? $session->subscription : $account->stripe_subscription_id,
            'billing_status' => Account::BILLING_STATUS_ACTIVE,
            'last_billing_sync_at' => now(),
            'last_billing_event_type' => $eventType,
            'last_billing_event_id' => $eventId,
        ])->save();
    }

    private function handleSubscriptionUpdated(StripeObject $subscription, string $eventType, string $eventId): void
    {
        $subscriptionId = is_string($subscription->id ?? null) ? $subscription->id : null;
        $customerId = is_string($subscription->customer ?? null) ? $subscription->customer : null;
        $metadataAccountId = (int) ($subscription->metadata->account_id ?? 0);

        $account = $this->resolveAccount(
            accountId: $metadataAccountId,
            customerId: $customerId,
            subscriptionId: $subscriptionId,
        );

        if (! $account) {
            return;
        }

        $account->forceFill([
            'stripe_customer_id' => $customerId ?: $account->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId ?: $account->stripe_subscription_id,
            'billing_status' => $this->mapBillingStatus((string) ($subscription->status ?? '')),
            'last_billing_sync_at' => now(),
            'last_billing_event_type' => $eventType,
            'last_billing_event_id' => $eventId,
        ])->save();
    }

    private function handleInvoicePaid(StripeObject $invoice, string $eventType, string $eventId): void
    {
        $subscriptionId = is_string($invoice->subscription ?? null) ? $invoice->subscription : null;
        $customerId = is_string($invoice->customer ?? null) ? $invoice->customer : null;

        $account = $this->resolveAccount(
            accountId: 0,
            customerId: $customerId,
            subscriptionId: $subscriptionId,
        );

        if (! $account) {
            return;
        }

        $account->forceFill([
            'stripe_customer_id' => $customerId ?: $account->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId ?: $account->stripe_subscription_id,
            'billing_status' => Account::BILLING_STATUS_ACTIVE,
            'last_billing_sync_at' => now(),
            'last_billing_event_type' => $eventType,
            'last_billing_event_id' => $eventId,
        ])->save();
    }

    private function handleInvoicePaymentFailed(StripeObject $invoice, string $eventType, string $eventId): void
    {
        $subscriptionId = is_string($invoice->subscription ?? null) ? $invoice->subscription : null;
        $customerId = is_string($invoice->customer ?? null) ? $invoice->customer : null;

        $account = $this->resolveAccount(
            accountId: 0,
            customerId: $customerId,
            subscriptionId: $subscriptionId,
        );

        if (! $account) {
            return;
        }

        $account->forceFill([
            'stripe_customer_id' => $customerId ?: $account->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId ?: $account->stripe_subscription_id,
            'billing_status' => Account::BILLING_STATUS_PAST_DUE,
            'last_billing_sync_at' => now(),
            'last_billing_event_type' => $eventType,
            'last_billing_event_id' => $eventId,
        ])->save();
    }

    private function resolveAccount(int $accountId, ?string $customerId, ?string $subscriptionId): ?Account
    {
        if ($accountId > 0) {
            $account = Account::query()->find($accountId);
            if ($account) {
                return $account;
            }
        }

        if ($subscriptionId) {
            $account = Account::query()->where('stripe_subscription_id', $subscriptionId)->first();
            if ($account) {
                return $account;
            }
        }

        if ($customerId) {
            return Account::query()->where('stripe_customer_id', $customerId)->first();
        }

        return null;
    }

    private function mapBillingStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active' => Account::BILLING_STATUS_ACTIVE,
            'trialing' => Account::BILLING_STATUS_TRIALING,
            'past_due', 'incomplete', 'incomplete_expired', 'unpaid' => Account::BILLING_STATUS_PAST_DUE,
            'canceled' => Account::BILLING_STATUS_CANCELED,
            default => Account::BILLING_STATUS_PENDING,
        };
    }

    private function reserveEvent(string $eventId, string $eventType, string $payload): bool
    {
        try {
            DB::table('stripe_webhook_events')->insert([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => $payload,
                'processed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function markProcessed(string $eventId): void
    {
        DB::table('stripe_webhook_events')
            ->where('event_id', $eventId)
            ->update([
                'processed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function releaseReservation(string $eventId): void
    {
        DB::table('stripe_webhook_events')
            ->where('event_id', $eventId)
            ->whereNull('processed_at')
            ->delete();
    }

    private function guardConfigured(): void
    {
        if (! class_exists(Webhook::class)) {
            throw new RuntimeException('Stripe SDK is not installed.');
        }

        if (! filled(config('services.stripe.webhook_secret'))) {
            throw new RuntimeException('Stripe webhook secret is not configured.');
        }
    }
}