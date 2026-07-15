<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
        @csrf

        <!-- Agent Photo -->
        <div>
            <x-input-label for="profile_image" :value="__('Agent Photo (optional)')" />
            <input id="profile_image" class="block mt-1 w-full text-sm" type="file" name="profile_image" accept="image/*" />
            <x-input-error :messages="$errors->get('profile_image')" class="mt-2" />
        </div>

        <!-- Plan -->
        <div class="mt-4">
            <x-input-label for="service_level" :value="__('Plan')" />
            <select id="service_level" name="service_level" class="block mt-1 w-full rounded-md border-[var(--lp-border)] shadow-sm focus:border-[var(--lp-secondary)] focus:ring-[var(--lp-secondary)]" required>
                <option value="single_agent" @selected(old('service_level', 'single_agent') === 'single_agent')>Single Agent</option>
                <option value="team" disabled>Team Plan (Coming Soon)</option>
                <option value="brokerage" disabled>Brokerage Plan (Coming Soon)</option>
            </select>
            <x-input-error :messages="$errors->get('service_level')" class="mt-2" />
        </div>

        <!-- Name -->
        <div class="mt-4">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
