<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Prospect Activity Dashboard') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8" x-data="{
        summary: @js($summary ?? [
            'week' => ['total' => 0, 'called' => 0, 'skipped' => 0, 'voicemail' => 0, 'text' => 0],
            'month' => ['total' => 0, 'called' => 0, 'skipped' => 0, 'voicemail' => 0, 'text' => 0],
            'year' => ['total' => 0, 'called' => 0, 'skipped' => 0, 'voicemail' => 0, 'text' => 0],
            'daily_calls' => [],
        ])
    }">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="space-y-6">
                <article class="lp-card p-6 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] lp-muted">Prospect Activity Dashboard</p>
                            <h1 class="mt-1 text-2xl font-semibold lp-title">Track action outcomes over time</h1>
                        </div>
                        <div class="rounded-full bg-[var(--lp-canvas)] px-3 py-1 text-xs lp-muted">Live activity</div>
                    </div>

                    <div class="mt-6 grid gap-3 md:grid-cols-3">
                        <template x-for="period in ['week', 'month', 'year']" :key="period">
                            <div class="rounded-xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] p-4">
                                <p class="text-xs uppercase tracking-[0.14em] lp-muted" x-text="period === 'week' ? 'This Week' : (period === 'month' ? 'This Month' : 'This Year')"></p>
                                <p class="mt-2 text-2xl font-semibold lp-title" x-text="summary[period]?.total ?? 0"></p>
                                <div class="mt-3 flex flex-wrap gap-3 text-xs lp-muted">
                                    <span>Called: <span class="font-semibold lp-title" x-text="summary[period]?.called ?? 0"></span></span>
                                    <span>Skipped: <span class="font-semibold lp-title" x-text="summary[period]?.skipped ?? 0"></span></span>
                                    <span>Voicemail: <span class="font-semibold lp-title" x-text="summary[period]?.voicemail ?? 0"></span></span>
                                    <span>Text: <span class="font-semibold lp-title" x-text="summary[period]?.text ?? 0"></span></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-6 rounded-xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] p-4">
                        <p class="text-xs uppercase tracking-[0.14em] lp-muted">Calls by Day</p>
                        <div class="mt-3 space-y-2">
                            <template x-for="item in summary.daily_calls || []" :key="item.day">
                                <div class="flex items-center justify-between rounded-lg border border-[var(--lp-border)] bg-white px-3 py-2 text-sm">
                                    <span class="font-medium lp-title" x-text="item.day"></span>
                                    <span class="font-semibold lp-title" x-text="item.count"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
