<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
        @csrf

        <!-- Agent Photo -->
        <div>
            <x-input-label for="profile_image" :value="__('Agent Photo')" />
            <div class="mt-2 flex items-center gap-3">
                <input id="profile_image" class="sr-only" type="file" name="profile_image" accept="image/*" />
                <label for="profile_image" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-[var(--lp-border)] bg-white text-gray-600 transition hover:bg-gray-50 hover:text-[var(--lp-secondary)]" title="Choose agent photo" aria-label="Choose agent photo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 7.5h-4.5M12 5.25v4.5M5.25 19.5h13.5A2.25 2.25 0 0 0 21 17.25V9.75A2.25 2.25 0 0 0 18.75 7.5h-2.379a1.5 1.5 0 0 1-1.06-.44l-.621-.62a1.5 1.5 0 0 0-1.06-.44h-3.258a1.5 1.5 0 0 0-1.06.44l-.621.62a1.5 1.5 0 0 1-1.06.44H5.25A2.25 2.25 0 0 0 3 9.75v7.5A2.25 2.25 0 0 0 5.25 19.5Z" />
                        <circle cx="12" cy="13.5" r="2.25" />
                    </svg>
                </label>
                <span class="text-sm text-gray-500">Upload photo</span>
            </div>
            <x-input-error :messages="$errors->get('profile_image')" class="mt-2" />
        </div>

        <!-- Plan -->
        <div class="mt-4">
            <x-input-label for="service_level" :value="__('Plan')" />
            <select id="service_level" name="service_level" class="block mt-1 w-full rounded-md border-[var(--lp-border)] shadow-sm focus:border-[var(--lp-secondary)] focus:ring-[var(--lp-secondary)]" required>
                <option value="single_agent" @selected(old('service_level', 'single_agent') === 'single_agent')>Single Agent</option>
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
