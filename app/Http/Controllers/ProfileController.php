<?php

namespace App\Http\Controllers;

use App\Mail\AdminEmailTestMail;
use App\Models\Account;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Billing\StripeBillingService;
use App\Services\Billing\GlobalAdminBillingOverviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly GlobalAdminBillingOverviewService $billingOverview,
        private readonly StripeBillingService $billing,
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $data = [
            'user' => $request->user(),
            'canManageSubscription' => $this->canManageSubscription($request),
        ];

        if ($request->user()?->isGlobalAdmin()) {
            $data['globalAccountOverview'] = $this->billingOverview->buildOverview();
            $data['serviceLevelLabels'] = [
                Account::SERVICE_LEVEL_SINGLE_AGENT => 'Single Agent',
                Account::SERVICE_LEVEL_TEAM => 'Team',
                Account::SERVICE_LEVEL_BROKERAGE => 'Brokerage',
            ];
        }

        return view('profile.edit', $data);
    }

    public function manageSubscription(Request $request): RedirectResponse
    {
        abort_unless($this->canManageSubscription($request), 403);

        $account = $request->user()?->account;

        if (! $account || ! $this->billing->canOpenCustomerPortal($account)) {
            return Redirect::route('profile.edit')->with('subscription_status', 'Subscription portal is not available yet for this account.');
        }

        try {
            $portalUrl = $this->billing->createCustomerPortalSession(
                $account,
                route('profile.edit'),
            );
        } catch (\Throwable $exception) {
            return Redirect::route('profile.edit')->with('subscription_status', 'Unable to open subscription portal right now. Please try again.');
        }

        return redirect()->away($portalUrl);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Send an email test from the admin profile page.
     */
    public function sendEmailTest(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'delivery' => ['required', 'in:immediate,queued'],
        ]);

        $mail = new AdminEmailTestMail(
            recipientEmail: $data['email'],
            requestedByName: $request->user()->name,
            deliveryMode: $data['delivery'],
        );

        if ($data['delivery'] === 'queued') {
            Mail::to($data['email'])->queue($mail->onQueue('notifications'));

            return Redirect::route('profile.edit')->with('email_test_status', 'Queued test email request successfully.');
        }

        Mail::to($data['email'])->send($mail);

        return Redirect::route('profile.edit')->with('email_test_status', 'Sent test email immediately.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function canManageSubscription(Request $request): bool
    {
        $user = $request->user();

        return (bool) ($user
            && ! $user->isGlobalAdmin()
            && in_array($user->role, ['owner', 'admin'], true));
    }
}
