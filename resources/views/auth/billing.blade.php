<x-guest-layout>
    @php
        $trialDays = (int) config('services.stripe.trial_days', 0);
    @endphp

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (request()->boolean('canceled'))
        <div class="lp-status-card">
            Your Stripe checkout was canceled. Complete billing to continue into Prospect GOAT.
        </div>
    @endif

    <div class="space-y-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] lp-muted">Single Agent Plan</p>
            <h2 class="mt-2 text-xl font-semibold lp-title">Finish your account setup</h2>
            <p class="mt-2 text-sm lp-muted">
                Your account has been created. The next step is collecting your payment details securely with Stripe so your 7-day trial can start and app access can be enabled.
            </p>
        </div>

        <div class="rounded-xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] px-4 py-4 text-sm lp-muted">
            <p class="font-semibold text-[var(--lp-primary)]">What happens next</p>
            <p class="mt-2">You will be sent to Stripe Checkout to add your card and start the Single Agent subscription.</p>
            @if ($trialDays > 0)
                <p class="mt-2 font-medium text-[var(--lp-primary)]">
                    Includes a {{ $trialDays }}-day trial before your first charge.
                </p>
            @endif
        </div>

        @if ($isStripeConfigured)
            <form method="POST" action="{{ route('billing.checkout') }}">
                @csrf

                <x-primary-button class="w-full justify-center lp-btn-primary">
                    {{ __('Continue to Stripe') }}
                </x-primary-button>
            </form>
        @else
            <div class="lp-status-card">
                Billing is not configured yet. Add your Stripe keys and Single Agent price ID before enabling this step in production.
            </div>
        @endif
    </div>

    <x-slot:afterCard>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm lp-muted underline hover:text-[var(--lp-primary)] rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--lp-secondary)]">
                {{ __('Log out') }}
            </button>
        </form>
    </x-slot:afterCard>
</x-guest-layout>