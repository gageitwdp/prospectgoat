<?php

namespace App\Services\Billing;

use App\Models\Account;
use Illuminate\Support\Collection;
use Stripe\StripeClient;
use Throwable;

class GlobalAdminBillingOverviewService
{
    /**
     * @return array{isStripeConfigured: bool, accounts: array<int, array<string, mixed>>}
     */
    public function buildOverview(): array
    {
        $isStripeConfigured = $this->isStripeConfigured();
        $accounts = Account::query()
            ->withCount('users')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Account $account): array => $this->mapAccount($account, $isStripeConfigured))
            ->all();

        return [
            'isStripeConfigured' => $isStripeConfigured,
            'accounts' => $accounts,
        ];
    }

    public function isStripeConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAccount(Account $account, bool $isStripeConfigured): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'slug' => $account->slug,
            'service_level' => $account->service_level,
            'billing_status' => $account->billing_status,
            'users_count' => $account->users_count,
            'stripe_customer_id' => $account->stripe_customer_id,
            'stripe_subscription_id' => $account->stripe_subscription_id,
            'created_at' => $account->created_at,
            'payment_history' => $this->paymentHistoryForAccount($account, $isStripeConfigured),
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, error: string|null}
     */
    private function paymentHistoryForAccount(Account $account, bool $isStripeConfigured): array
    {
        if (! $isStripeConfigured) {
            return [
                'items' => [],
                'error' => 'Stripe is not configured for this environment.',
            ];
        }

        if (! $account->stripe_customer_id) {
            return [
                'items' => [],
                'error' => 'No Stripe customer is linked to this account.',
            ];
        }

        try {
            $invoices = $this->client()->invoices->all([
                'customer' => $account->stripe_customer_id,
                'limit' => 8,
            ]);

            $items = Collection::make($invoices->data)
                ->map(function ($invoice): array {
                    return [
                        'id' => (string) ($invoice->id ?? ''),
                        'status' => (string) ($invoice->status ?? 'unknown'),
                        'amount_paid' => (int) ($invoice->amount_paid ?? 0),
                        'currency' => strtoupper((string) ($invoice->currency ?? 'USD')),
                        'created_at' => isset($invoice->created) ? now()->createFromTimestamp((int) $invoice->created) : null,
                        'hosted_invoice_url' => is_string($invoice->hosted_invoice_url ?? null) ? $invoice->hosted_invoice_url : null,
                    ];
                })
                ->all();

            return [
                'items' => $items,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            return [
                'items' => [],
                'error' => 'Unable to load Stripe invoices right now.',
            ];
        }
    }

    private function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }
}