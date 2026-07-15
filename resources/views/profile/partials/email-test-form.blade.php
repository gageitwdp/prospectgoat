<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Email Test') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Send a test email to verify delivery and queue behavior.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.email-test') }}" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label for="test_email" :value="__('Destination Email')" />
            <x-text-input
                id="test_email"
                name="email"
                type="email"
                class="mt-1 block w-full"
                :value="old('email', $user->email)"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label :value="__('Delivery Mode')" />

            <label for="delivery_immediate" class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                <input
                    id="delivery_immediate"
                    type="radio"
                    name="delivery"
                    value="immediate"
                    class="border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    @checked(old('delivery', 'immediate') === 'immediate')
                >
                <span>{{ __('Immediate (direct send)') }}</span>
            </label>

            <label for="delivery_queued" class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                <input
                    id="delivery_queued"
                    type="radio"
                    name="delivery"
                    value="queued"
                    class="border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    @checked(old('delivery') === 'queued')
                >
                <span>{{ __('Queued (notifications queue)') }}</span>
            </label>

            <x-input-error class="mt-2" :messages="$errors->get('delivery')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Send Test Email') }}</x-primary-button>

            @if (session('email_test_status'))
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3500)"
                    class="text-sm text-gray-600"
                >{{ session('email_test_status') }}</p>
            @endif
        </div>
    </form>
</section>
