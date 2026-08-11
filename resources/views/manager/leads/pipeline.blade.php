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

        <section class="space-y-6">
            @foreach ($statuses as $status)
                @php
                    $section = $sections[$status];
                    $items = $section['leads'];
                @endphp

                <article class="lp-card min-h-[72vh] p-5 sm:p-6">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold lp-title">{{ $section['label'] }}</h3>
                                <span class="rounded-full border border-[var(--lp-border)] px-2 py-0.5 text-xs lp-muted">{{ $items->total() }} leads</span>
                            </div>
                            <p class="mt-1 text-sm lp-muted">Newest to oldest, with five rows shown by default.</p>
                        </div>

                        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr),160px,auto] md:items-end xl:min-w-[760px] xl:grid-cols-[minmax(0,1fr),160px,auto]">
                            <input type="hidden" name="period" value="{{ $period }}">

                            @foreach ($statuses as $otherStatus)
                                @if ($otherStatus !== $status)
                                    <input type="hidden" name="{{ $otherStatus }}_search" value="{{ $sections[$otherStatus]['search'] ?? '' }}">
                                    <input type="hidden" name="{{ $otherStatus }}_per_page" value="{{ $sections[$otherStatus]['per_page'] ?? 5 }}">
                                    <input type="hidden" name="{{ $otherStatus }}_page" value="{{ $sections[$otherStatus]['leads']->currentPage() ?? 1 }}">
                                @endif
                            @endforeach

                            <div>
                                <label for="{{ $status }}_search" class="mb-1 block text-xs uppercase tracking-wide lp-muted">Search name, address, or phone</label>
                                <input
                                    id="{{ $status }}_search"
                                    type="text"
                                    name="{{ $status }}_search"
                                    value="{{ $section['search'] }}"
                                    placeholder="Search this section"
                                    class="w-full rounded-xl border border-[var(--lp-border)] px-3 py-2 text-sm"
                                >
                            </div>

                            <div>
                                <label for="{{ $status }}_per_page" class="mb-1 block text-xs uppercase tracking-wide lp-muted">Rows per page</label>
                                <select id="{{ $status }}_per_page" name="{{ $status }}_per_page" class="w-full rounded-xl border border-[var(--lp-border)] px-3 py-2 text-sm">
                                    @foreach ([5, 10, 25, 50, 100] as $perPage)
                                        <option value="{{ $perPage }}" @selected((int) $section['per_page'] === $perPage)>{{ $perPage }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="rounded-xl px-4 py-2 text-sm font-medium lp-btn-primary">Apply</button>
                                <a href="{{ route('manager.leads.pipeline', ['period' => $period]) }}" class="rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm lp-title">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-2xl border border-[var(--lp-border)] bg-white">
                        <div class="grid gap-0 border-b border-[var(--lp-border)] bg-[#f9fafb] px-4 py-3 text-xs uppercase tracking-wider lp-muted sm:grid-cols-[minmax(0,2fr),minmax(0,1fr),minmax(0,1fr),auto]">
                            <div>Lead</div>
                            <div>Type / Source</div>
                            <div>Assigned</div>
                            <div class="text-right">Actions</div>
                        </div>

                        <div class="divide-y divide-[var(--lp-border)]">
                            @forelse ($items as $lead)
                                <div class="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,2fr),minmax(0,1fr),minmax(0,1fr),auto] sm:items-center">
                                    <div>
                                        <p class="text-sm font-medium lp-title">{{ $lead->name }}</p>
                                        <p class="mt-1 text-xs lp-muted">{{ $lead->address ?? 'No address' }}</p>
                                        <p class="mt-1 text-xs lp-muted">{{ $lead->phone ?? 'No phone' }}</p>
                                    </div>

                                    <div class="text-sm lp-muted">
                                        <p>{{ ucwords(str_replace('_', ' ', $lead->lead_type)) }}</p>
                                        <p class="text-xs">{{ ucwords(str_replace('_', ' ', $lead->source)) }}</p>
                                    </div>

                                    <div class="text-sm lp-muted">
                                        {{ $lead->assignedManager?->name ?? 'Unassigned' }}
                                    </div>

                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('manager.leads.show', $lead) }}" class="rounded-lg border border-[var(--lp-border)] px-3 py-1.5 text-xs lp-title">Open</a>

                                        @if ($lead->status !== 'new')
                                            <form method="POST" action="{{ route('manager.leads.status.reset', $lead) }}" onsubmit="return confirm('Reset this lead back to New?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                    Reset
                                                </button>
                                            </form>
                                        @endif

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
                                <div class="px-4 py-6 text-sm lp-muted">No leads in this section.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 border-t border-[var(--lp-border)] pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm lp-muted">
                            Showing {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} of {{ $items->total() }} leads.
                        </p>

                        <div class="flex flex-wrap gap-2">
                            {{ $items->links() }}
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    </div>
</x-app-layout>
