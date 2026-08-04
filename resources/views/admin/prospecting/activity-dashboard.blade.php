<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Prospect Activity Dashboard') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8" x-data="activityDashboard()">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="space-y-6">
                <article class="lp-card p-6 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] lp-muted">Prospect Activity Dashboard</p>
                            <h1 class="mt-1 text-2xl font-semibold lp-title">Track action outcomes with bars instead of raw counts</h1>
                        </div>
                        <div class="rounded-full bg-[var(--lp-canvas)] px-3 py-1 text-xs lp-muted">Live activity</div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <template x-for="period in periods" :key="period.key">
                            <div class="rounded-2xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.14em] lp-muted" x-text="period.label"></p>
                                        <p class="mt-1 text-2xl font-semibold lp-title" x-text="summary[period.key]?.total ?? 0"></p>
                                    </div>
                                    <span class="rounded-full border border-[var(--lp-border)] px-2.5 py-1 text-[11px] lp-muted">Actions</span>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <template x-for="segment in barSegments" :key="segment.key">
                                        <div>
                                            <div class="mb-1 flex items-center justify-between gap-3 text-xs">
                                                <span class="lp-muted" x-text="segment.label"></span>
                                                <span class="font-medium lp-title" x-text="summary[period.key]?.[segment.key] ?? 0"></span>
                                            </div>
                                            <div class="h-2 overflow-hidden rounded-full bg-white">
                                                <div
                                                    class="h-2 rounded-full transition-all duration-300"
                                                    :class="segment.className"
                                                    :style="segmentWidth(summary[period.key]?.[segment.key] ?? 0, summary[period.key]?.total ?? 0)"
                                                ></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2 text-[11px]">
                                    <span class="rounded-full bg-white px-2.5 py-1 lp-muted">Skipped <span class="font-semibold lp-title" x-text="summary[period.key]?.skipped ?? 0"></span></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </article>

                <article class="lp-card p-6 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] lp-muted">Daily trend</p>
                            <h2 class="mt-1 text-2xl font-semibold lp-title">Seven-day stacked activity bars</h2>
                        </div>
                        <p class="text-xs lp-muted">Calls, texts, and voicemails combine with app-tracked activity.</p>
                    </div>

                    <div class="mt-6 overflow-x-auto pb-2">
                        <div class="grid min-w-[720px] grid-cols-7 gap-3 items-end">
                            <template x-for="day in summary.daily_activity || []" :key="day.date">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-48 w-full items-end justify-center rounded-2xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] p-3">
                                        <div class="flex h-full w-full max-w-[52px] flex-col-reverse justify-start gap-1">
                                            <template x-for="segment in barSegments" :key="`${day.date}-${segment.key}`">
                                                <div
                                                    class="w-full rounded-t-lg transition-all duration-300"
                                                    :class="segment.className"
                                                    :style="segmentHeight(day[segment.key] ?? 0, summary.max_daily_total ?? 0)"
                                                ></div>
                                            </template>
                                            <div
                                                class="w-full rounded-t-lg bg-slate-200 transition-all duration-300"
                                                :style="segmentHeight(day.skipped ?? 0, summary.max_daily_total ?? 0)"
                                            ></div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <p class="text-xs font-medium lp-title" x-text="day.day"></p>
                                        <p class="text-[11px] lp-muted">
                                            <span x-text="day.total"></span> total
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </article>

                <article class="lp-card p-6 sm:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] lp-muted">Manual entry</p>
                            <h2 class="mt-1 text-2xl font-semibold lp-title">Log off-app activity</h2>
                            <p class="mt-2 text-sm lp-muted">
                                Use this when calls, texts, or voicemails happened outside the app so daily totals stay accurate.
                            </p>

                            <form class="mt-6 space-y-4" @submit.prevent="submitActivityEntry">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium lp-title">Activity Date</label>
                                        <input
                                            type="date"
                                            class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm"
                                            x-model="activityEntryForm.activity_date"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium lp-title">Calls</label>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm"
                                            x-model.number="activityEntryForm.calls"
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium lp-title">Texts</label>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm"
                                            x-model.number="activityEntryForm.texts"
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium lp-title">Voicemails</label>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm"
                                            x-model.number="activityEntryForm.voicemails"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium lp-title">Notes</label>
                                    <textarea
                                        rows="3"
                                        class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-3 text-sm"
                                        x-model.trim="activityEntryForm.notes"
                                        placeholder="Optional context for the activity log"
                                    ></textarea>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary" :disabled="savingEntry">
                                        <span x-show="!savingEntry">Save activity</span>
                                        <span x-show="savingEntry" x-cloak>Saving...</span>
                                    </button>
                                    <p class="text-sm text-emerald-700" x-text="entrySuccess" x-show="entrySuccess" x-cloak></p>
                                    <p class="text-sm text-red-600" x-text="entryError" x-show="entryError" x-cloak></p>
                                </div>
                            </form>
                        </div>

                        <div class="rounded-2xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] p-4">
                            <p class="text-xs uppercase tracking-[0.14em] lp-muted">Legend</p>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center gap-3">
                                    <span class="h-3 w-3 rounded-full bg-slate-900"></span>
                                    <span class="lp-title">Calls</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="h-3 w-3 rounded-full bg-sky-500"></span>
                                    <span class="lp-title">Texts</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                                    <span class="lp-title">Voicemails</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="h-3 w-3 rounded-full bg-slate-300"></span>
                                    <span class="lp-title">Skipped</span>
                                </div>
                            </div>
                            <p class="mt-6 text-xs leading-6 lp-muted">
                                Manual entries are split into separate call, text, and voicemail rows, then folded into the weekly, monthly, yearly, and daily totals.
                            </p>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </div>

    <script>
        function activityDashboard() {
            return {
                summary: @js($summary ?? [
                    'week' => ['total' => 0, 'called' => 0, 'skipped' => 0, 'voicemail' => 0, 'text' => 0],
                    'month' => ['total' => 0, 'called' => 0, 'skipped' => 0, 'voicemail' => 0, 'text' => 0],
                    'year' => ['total' => 0, 'called' => 0, 'skipped' => 0, 'voicemail' => 0, 'text' => 0],
                    'daily_activity' => [],
                    'max_daily_total' => 0,
                ]),
                periods: [
                    { key: 'week', label: 'This Week' },
                    { key: 'month', label: 'This Month' },
                    { key: 'year', label: 'This Year' },
                ],
                barSegments: [
                    { key: 'called', label: 'Calls', className: 'bg-slate-900' },
                    { key: 'text', label: 'Texts', className: 'bg-sky-500' },
                    { key: 'voicemail', label: 'Voicemails', className: 'bg-amber-500' },
                ],
                activityEntryUrl: @js(route('admin.prospecting.activity-entries.store')),
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                savingEntry: false,
                entrySuccess: '',
                entryError: '',
                activityEntryForm: {
                    activity_date: new Date().toISOString().slice(0, 10),
                    calls: 0,
                    texts: 0,
                    voicemails: 0,
                    notes: '',
                },

                segmentWidth(value, total) {
                    if (!total) {
                        return 'width: 0%';
                    }

                    const percent = Math.max((Number(value) / Number(total)) * 100, 3);

                    return `width: ${Math.min(percent, 100)}%`;
                },

                segmentHeight(value, maxTotal) {
                    if (!maxTotal) {
                        return 'height: 0%';
                    }

                    const percent = Math.max((Number(value) / Number(maxTotal)) * 100, Number(value) > 0 ? 8 : 0);

                    return `height: ${Math.min(percent, 100)}%`;
                },

                resetEntryForm() {
                    this.activityEntryForm = {
                        activity_date: new Date().toISOString().slice(0, 10),
                        calls: 0,
                        texts: 0,
                        voicemails: 0,
                        notes: '',
                    };
                },

                async submitActivityEntry() {
                    this.entrySuccess = '';
                    this.entryError = '';
                    this.savingEntry = true;

                    try {
                        const response = await fetch(this.activityEntryUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(this.activityEntryForm),
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            this.entryError = payload?.message || 'Unable to save activity entry.';
                            return;
                        }

                        if (payload?.summary && typeof payload.summary === 'object') {
                            this.summary = payload.summary;
                        }

                        this.entrySuccess = payload?.message || 'Activity saved.';
                        this.resetEntryForm();
                    } catch (error) {
                        this.entryError = 'Unable to save activity entry.';
                    } finally {
                        this.savingEntry = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>