<?php

namespace App\Http\Controllers;

use App\Mail\AdminEmailTestMail;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
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
}
