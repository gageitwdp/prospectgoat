<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-[var(--lp-border)] text-[var(--lp-secondary)] shadow-sm focus:ring-[var(--lp-secondary)]" name="remember">
                <span class="ms-2 text-sm lp-muted">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="mt-6 flex items-center justify-between gap-3">
            @if (config('auth.enable_public_signup'))
                <a class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold lp-btn-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--lp-secondary)]" href="{{ route('register') }}">
                    {{ __('Create account') }}
                </a>
            @endif

            <x-primary-button class="lp-btn-primary ms-auto">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <x-slot:afterCard>
        @if (Route::has('password.request'))
            <a class="text-sm lp-muted underline hover:text-[var(--lp-primary)] rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--lp-secondary)]" href="{{ route('password.request') }}">
                {{ __('Forgot your password?') }}
            </a>
        @endif
    </x-slot:afterCard>
</x-guest-layout>
