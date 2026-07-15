<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Global Account Oversight') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Monitor all tenant accounts, active pricing plans, and recent Stripe payment history from one view.') }}
        </p>
    </header>

    @if (! ($globalAccountOverview['isStripeConfigured'] ?? false))
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            {{ __('Stripe is not configured in this environment. Payment history will be unavailable until Stripe keys are set.') }}
        </div>
    @endif

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600">
                <tr>
                    <th class="px-3 py-2">Account</th>
                    <th class="px-3 py-2">Plan</th>
                    <th class="px-3 py-2">Billing Status</th>
                    <th class="px-3 py-2">Last Sync</th>
                    <th class="px-3 py-2">Last Event</th>
                    <th class="px-3 py-2">Users</th>
                    <th class="px-3 py-2">Stripe Customer</th>
                    <th class="px-3 py-2">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse (($globalAccountOverview['accounts'] ?? []) as $account)
                    <tr>
                        <td class="px-3 py-3 align-top">
                            <p class="font-medium text-gray-900">{{ $account['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $account['slug'] }}</p>
                        </td>
                        <td class="px-3 py-3 align-top text-gray-700">
                            {{ $serviceLevelLabels[$account['service_level']] ?? ucfirst(str_replace('_', ' ', (string) $account['service_level'])) }}
                        </td>
                        <td class="px-3 py-3 align-top text-gray-700">{{ ucfirst(str_replace('_', ' ', (string) $account['billing_status'])) }}</td>
                        <td class="px-3 py-3 align-top text-gray-700">
                            {{ optional($account['last_billing_sync_at'])->format('M d, Y g:i A') ?: 'Not synced yet' }}
                        </td>
                        <td class="px-3 py-3 align-top text-xs text-gray-600">
                            @if (! empty($account['last_billing_event_type']))
                                <div>{{ $account['last_billing_event_type'] }}</div>
                                <div class="text-gray-500">{{ $account['last_billing_event_id'] }}</div>
                            @else
                                <span>Not available</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 align-top text-gray-700">{{ $account['users_count'] }}</td>
                        <td class="px-3 py-3 align-top text-xs text-gray-600">{{ $account['stripe_customer_id'] ?: 'Not linked' }}</td>
                        <td class="px-3 py-3 align-top text-gray-700">{{ optional($account['created_at'])->format('M d, Y') }}</td>
                    </tr>
                    <tr class="bg-gray-50/60">
                        <td colspan="8" class="px-3 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-600">Recent Payments</p>

                            @if (($account['payment_history']['error'] ?? null) !== null)
                                <p class="mt-1 text-xs text-gray-500">{{ $account['payment_history']['error'] }}</p>
                            @elseif (empty($account['payment_history']['items']))
                                <p class="mt-1 text-xs text-gray-500">{{ __('No invoices found for this account yet.') }}</p>
                            @else
                                <div class="mt-2 space-y-1">
                                    @foreach ($account['payment_history']['items'] as $invoice)
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-700">
                                            <span class="font-medium">{{ $invoice['id'] }}</span>
                                            <span class="rounded-full bg-white px-2 py-0.5 text-[11px] text-gray-600">{{ strtoupper((string) $invoice['status']) }}</span>
                                            <span>{{ number_format(((int) $invoice['amount_paid']) / 100, 2) }} {{ $invoice['currency'] }}</span>
                                            <span>{{ optional($invoice['created_at'])->format('M d, Y') }}</span>
                                            @if (! empty($invoice['hosted_invoice_url']))
                                                <a href="{{ $invoice['hosted_invoice_url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 underline hover:text-indigo-800">View Invoice</a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-6 text-center text-sm text-gray-500">{{ __('No accounts found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>