<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Plan Module Visibility') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="lp-card p-6 sm:p-8">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Global Admin</p>
                    <h3 class="mt-2 text-lg font-semibold lp-title">Plan Access Matrix</h3>
                    <p class="mt-2 text-sm lp-muted">Toggle each module on or off by plan. Changes apply immediately to account users on each plan.</p>
                </div>

                @if (session('status'))
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="post" action="{{ route('admin.plan-module-visibility.update') }}" class="mt-6">
                    @csrf
                    @method('put')

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="border-b border-[var(--lp-border)] px-3 py-2 text-left text-xs uppercase tracking-[0.12em] lp-muted">Module</th>
                                    @foreach ($serviceLevels as $serviceLevel => $label)
                                        <th class="border-b border-[var(--lp-border)] px-3 py-2 text-center text-xs uppercase tracking-[0.12em] lp-muted">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($moduleMatrix as $moduleKey => $module)
                                    <tr>
                                        <td class="border-b border-[var(--lp-border)] px-3 py-3 align-top">
                                            <p class="font-semibold lp-title">{{ $module['label'] }}</p>
                                            <p class="mt-1 text-xs lp-muted">{{ $module['description'] }}</p>
                                        </td>

                                        @foreach ($serviceLevels as $serviceLevel => $label)
                                            <td class="border-b border-[var(--lp-border)] px-3 py-3 text-center align-middle">
                                                <label class="inline-flex items-center gap-2 text-sm lp-title">
                                                    <input
                                                        type="checkbox"
                                                        name="visibility[{{ $moduleKey }}][{{ $serviceLevel }}]"
                                                        value="1"
                                                        class="rounded border-[var(--lp-border)] text-[var(--lp-secondary)] focus:ring-[var(--lp-secondary)]"
                                                        @checked($module['by_plan'][$serviceLevel])
                                                    >
                                                    <span>Enabled</span>
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        <x-primary-button>{{ __('Save Module Visibility') }}</x-primary-button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
