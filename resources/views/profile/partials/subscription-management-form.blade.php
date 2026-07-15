<section>
    @php
        $account = auth()->user()?->account;
        $planLabels = [
            \App\Models\Account::SERVICE_LEVEL_SINGLE_AGENT => 'Single Agent',
            \App\Models\Account::SERVICE_LEVEL_TEAM => 'Team',
            \App\Models\Account::SERVICE_LEVEL_BROKERAGE => 'Brokerage',
        ];
        $billingLabels = [
            \App\Models\Account::BILLING_STATUS_PENDING => 'Pending Setup',
            \App\Models\Account::BILLING_STATUS_ACTIVE => 'Active',
            \App\Models\Account::BILLING_STATUS_PAST_DUE => 'Past Due',
            \App\Models\Account::BILLING_STATUS_CANCELED => 'Canceled',
            \App\Models\Account::BILLING_STATUS_TRIALING => 'Trialing',
        ];
        $currentPlan = $account?->service_level ? ($planLabels[$account->service_level] ?? ucfirst(str_replace('_', ' ', $account->service_level))) : 'Not set';
        $billingStatus = $account?->billing_status ? ($billingLabels[$account->billing_status] ?? ucfirst(str_replace('_', ' ', $account->billing_status))) : 'Unknown';
    @endphp

    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Subscription Management') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Open the secure Stripe portal to update payment methods, view invoices, or manage your subscription.') }}
        </p>

        <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-4">
            <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Current Plan</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $currentPlan }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Billing Status</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $billingStatus }}</dd>
                </div>
            </dl>
        </div>
    </header>

    <form method="post" action="{{ route('profile.subscription-portal') }}" class="mt-6 space-y-4">
        @csrf

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Manage Subscription') }}</x-primary-button>

            @if (session('subscription_status'))
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3500)"
                    class="text-sm text-gray-600"
                >{{ session('subscription_status') }}</p>
            @endif
        </div>
    </form>
</section>