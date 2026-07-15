<?php

namespace App\Http\Controllers\Auth;

use App\Models\Account;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        abort_unless(config('auth.enable_public_signup'), 404);

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('auth.enable_public_signup'), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'service_level' => ['required', 'in:single_agent'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $profileImagePath = $request->file('profile_image')?->store('profile-images', 'public');

        $user = DB::transaction(function () use ($data, $profileImagePath): User {
            $baseName = trim((string) $data['name']);
            $slug = Str::slug($baseName);

            if ($slug === '') {
                $slug = 'account';
            }

            $candidateSlug = $slug;
            $counter = 2;

            while (Account::query()->where('slug', $candidateSlug)->exists()) {
                $candidateSlug = $slug.'-'.$counter;
                $counter++;
            }

            $account = Account::create([
                'name' => $baseName,
                'slug' => $candidateSlug,
                'service_level' => $data['service_level'],
                'billing_status' => Account::BILLING_STATUS_PENDING,
            ]);

            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'profile_image_path' => $profileImagePath,
                'password' => Hash::make($data['password']),
                'account_id' => $account->id,
                'role' => 'owner',
                'notify_on_new_lead_intake' => true,
                'notify_on_lead_assignment' => true,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        $redirectRoute = $user->account?->requiresBillingSetup()
            ? 'billing.collect'
            : 'dashboard';

        return redirect(route($redirectRoute, absolute: false));
    }
}
