<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Global Account Oversight') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="lp-card p-6 sm:p-8">
                @include('profile.partials.global-admin-account-overview')
            </section>
        </div>
    </div>
</x-app-layout>