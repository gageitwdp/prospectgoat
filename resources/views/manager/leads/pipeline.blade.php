<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold lp-title">Pipeline Board</h2>
                <p class="text-sm lp-muted">Visual status flow across your lead lifecycle.</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <label for="period" class="text-xs uppercase tracking-wide lp-muted">Window</label>
                <select id="period" name="period" class="rounded-lg border border-[var(--lp-border)] px-3 py-2 text-sm">
                    <option value="7" @selected($period === '7')>Last 7 days</option>
                    <option value="30" @selected($period === '30')>Last 30 days</option>
                    <option value="90" @selected($period === '90')>Last 90 days</option>
                    <option value="all" @selected($period === 'all')>All time</option>
                </select>
                <button type="submit" class="rounded-lg px-3 py-2 text-sm lp-btn-primary">Apply</button>
            </form>
        </div>
    </x-slot>

    <div class="lp-shell px-2 sm:px-0">
        @if (session('status'))
            <div class="mb-4 rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] p-3 text-sm text-[#2f5f34]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('status'))
            <div class="mb-4 rounded-xl border border-[#f2d3d3] bg-[#fff6f6] p-3 text-sm text-[#7c2f2f]">
                {{ $errors->first('status') }}
            </div>
        @endif

        <section class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="lp-card p-4">
                <p class="text-xs uppercase tracking-wide lp-muted">Total Leads</p>
                <p class="mt-1 text-2xl font-semibold lp-title">{{ $metrics['total'] }}</p>
            </article>
            <article class="lp-card p-4">
                <p class="text-xs uppercase tracking-wide lp-muted">Active Pipeline</p>
                <p class="mt-1 text-2xl font-semibold lp-title">{{ $metrics['active'] }}</p>
            </article>
            <article class="lp-card p-4">
                <p class="text-xs uppercase tracking-wide lp-muted">Closed</p>
                <p class="mt-1 text-2xl font-semibold lp-title">{{ $metrics['closed'] }}</p>
            </article>
            <article class="lp-card p-4">
                <p class="text-xs uppercase tracking-wide lp-muted">Close Rate</p>
                <p class="mt-1 text-2xl font-semibold lp-title">{{ $metrics['close_rate'] }}%</p>
            </article>
            <article class="lp-card p-4">
                <p class="text-xs uppercase tracking-wide lp-muted">Avg Open Days</p>
                <p class="mt-1 text-2xl font-semibold lp-title">{{ $metrics['avg_open_days'] }}</p>
            </article>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach ($statuses as $status)
                @php
                    $items = $leadGroups->get($status, collect());
                @endphp
                <article class="lp-card p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="lp-title text-sm font-semibold uppercase tracking-wider">{{ $status }}</h3>
                        <span class="rounded-full border border-[var(--lp-border)] px-2 py-0.5 text-xs lp-muted">{{ $items->count() }}</span>
                    </div>

                    <div class="space-y-3">
                        @forelse ($items as $lead)
                            <div class="rounded-xl border border-[var(--lp-border)] bg-white p-3">
                                <p class="text-sm font-medium lp-title">{{ $lead->name }}</p>
                                <p class="mt-1 text-xs lp-muted">{{ ucwords(str_replace('_', ' ', $lead->lead_type)) }} · {{ ucwords(str_replace('_', ' ', $lead->source)) }}</p>
                                <p class="mt-2 text-xs lp-muted">{{ $lead->assignedManager?->name ?? 'Unassigned' }}</p>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <a href="{{ route('manager.leads.show', $lead) }}" class="rounded-lg border border-[var(--lp-border)] px-2 py-1 text-xs lp-title">Open</a>

                                    @php
                                        $nextMap = [
                                            'new' => ['contacted'],
                                            'contacted' => ['qualified', 'closed'],
                                            'qualified' => ['active', 'closed'],
                                            'active' => ['closed'],
                                            'closed' => [],
                                        ];
                                        $nextStages = $nextMap[$lead->status] ?? [];
                                    @endphp

                                    @if (count($nextStages) > 0)
                                        <form method="POST" action="{{ route('manager.leads.status.move', $lead) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="rounded-lg border border-[var(--lp-border)] px-2 py-1 text-xs">
                                                @foreach ($nextStages as $next)
                                                    <option value="{{ $next }}">{{ ucfirst($next) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-lg px-2 py-1 text-xs lp-btn-accent">Move</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-[var(--lp-border)] p-3 text-xs lp-muted">No leads in this stage.</p>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </section>
    </div>
</x-app-layout>
