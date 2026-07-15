<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="space-y-6">
                <div class="lp-card p-6 sm:p-8">
                    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Overview</p>
                    <h1 class="mt-2 text-2xl font-semibold lp-title">Admin Control Center</h1>
                    <p class="mt-3 lp-muted">
                        Manage platform operations from one place. Lead Management is live, and User Management is ready for access control workflows.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="lp-card p-5">
                        <p class="text-xs uppercase tracking-[0.14em] lp-muted">Total Users</p>
                        <p class="mt-2 text-2xl font-semibold lp-title">{{ $metrics['total_users'] }}</p>
                    </div>
                    <div class="lp-card p-5">
                        <p class="text-xs uppercase tracking-[0.14em] lp-muted">Admin Users</p>
                        <p class="mt-2 text-2xl font-semibold lp-title">{{ $metrics['admin_users'] }}</p>
                    </div>
                    <div class="lp-card p-5">
                        <p class="text-xs uppercase tracking-[0.14em] lp-muted">Agent Users</p>
                        <p class="mt-2 text-2xl font-semibold lp-title">{{ $metrics['agent_users'] }}</p>
                    </div>
                    <div class="lp-card p-5">
                        <p class="text-xs uppercase tracking-[0.14em] lp-muted">Total Leads</p>
                        <p class="mt-2 text-2xl font-semibold lp-title">{{ $metrics['total_leads'] }}</p>
                    </div>
                </div>

                <div class="lp-card p-6 sm:p-8">
                    <h3 class="text-lg font-semibold lp-title">Modules</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($modules as $module)
                            <div class="rounded-2xl border border-[var(--lp-border)] p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-semibold lp-title">{{ $module['name'] }}</p>
                                    <span class="rounded-full border border-[var(--lp-border)] px-2 py-0.5 text-xs {{ $module['status'] === 'Live' ? 'text-emerald-700 bg-emerald-50' : 'lp-muted bg-[var(--lp-canvas)]' }}">
                                        {{ $module['status'] }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm lp-muted">{{ $module['description'] }}</p>
                                @if ($module['route'])
                                    <a href="{{ $module['route'] }}" class="mt-4 inline-flex rounded-lg px-3 py-2 text-sm lp-btn-primary">
                                        Open {{ $module['name'] }}
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
