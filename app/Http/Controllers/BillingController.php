<?php

namespace App\Http\Controllers;

use App\Services\Billing\StripeBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private readonly StripeBillingService $billing) {}

    public function show(Request $request): View|RedirectResponse
    {
        $account = $request->user()?->account;

        if ($account?->hasActiveBilling()) {
            return redirect()->route('dashboard');
        }

        return view('auth.billing', [
            'isStripeConfigured' => $this->billing->isConfigured(),
            'account' => $account,
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();
        $account = $user?->account;

        abort_unless($user && $account, 403);
        abort_unless($this->billing->isConfigured(), 503, 'Stripe billing is not configured.');

        if ($account->hasActiveBilling()) {
            return redirect()->route('dashboard');
        }

        $checkoutUrl = $this->billing->createCheckoutSession($account, $user);

        return redirect()->away($checkoutUrl);
    }

    public function success(Request $request): RedirectResponse
    {
        $user = $request->user();
        $account = $user?->account;

        abort_unless($user && $account, 403);

        $sessionId = $request->string('session_id')->toString();

        abort_unless($sessionId !== '', 404);

        $this->billing->completeCheckoutSession($account, $sessionId);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Billing setup complete.');
    }
}