<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold lp-title">Lead Pipeline</h2>
                <p class="text-sm lp-muted">Track, assign, and advance every lead.</p>
            </div>

            @if (auth()->user()?->isOwner())
                <a href="{{ route('admin.imports.leads.index') }}" class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium lp-btn-primary">
                    Import Leads
                </a>
            @endif
        </div>
    </x-slot>

    <div class="lp-shell space-y-6 px-2 sm:px-0">
        @if (session('status'))
            <section class="rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] p-4 text-sm text-[#2f5f34]">
                {{ session('status') }}
            </section>
        @endif

        <section class="lp-card p-5 sm:p-6">
            <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <select name="visibility" class="rounded-xl border border-[var(--lp-border)] px-3 py-2 text-sm">
                    <option value="">Active Leads</option>
                    <option value="deleted" @selected(($visibility ?? '') === 'deleted')>Deleted Leads</option>
                    <option value="all" @selected(($visibility ?? '') === 'all')>All Leads</option>
                </select>

                <select name="status" class="rounded-xl border border-[var(--lp-border)] px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    @foreach (['new', 'contacted', 'qualified', 'active', 'closed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>

                <select name="lead_type" class="rounded-xl border border-[var(--lp-border)] px-3 py-2 text-sm">
                    <option value="">All Lead Types</option>
                    @foreach (['home_value', 'buyer', 'seller', 'generic_inquiry'] as $type)
                        <option value="{{ $type }}" @selected(request('lead_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>

                <select name="source" class="rounded-xl border border-[var(--lp-border)] px-3 py-2 text-sm">
                    <option value="">All Sources</option>
                    @foreach (['homepage', 'landing_page', 'referral'] as $source)
                        <option value="{{ $source }}" @selected(request('source') === $source)>{{ ucwords(str_replace('_', ' ', $source)) }}</option>
                    @endforeach
                </select>

                <select name="assigned_to" class="rounded-xl border border-[var(--lp-border)] px-3 py-2 text-sm">
                    <option value="">Any Assignee</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}" @selected((string) request('assigned_to') === (string) $manager->id)>{{ $manager->name }}</option>
                    @endforeach
                </select>

                <div class="sm:col-span-2 lg:col-span-4 flex gap-2">
                    <button type="submit" class="rounded-xl px-4 py-2 text-sm font-medium lp-btn-primary">Apply Filters</button>
                    <a href="{{ route('manager.leads.index') }}" class="rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm lp-title">Reset</a>
                </div>
            </form>
        </section>

        <section class="lp-card overflow-hidden">
            @if (auth()->user()?->role === 'admin' && ($visibility ?? '') !== 'deleted')
                <form id="lead-bulk-delete-form" method="POST" action="{{ route('manager.leads.bulk-destroy') }}" onsubmit="return confirm('Delete selected leads and all linked records? This cannot be undone.');" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @elseif (auth()->user()?->role === 'admin' && ($visibility ?? '') === 'deleted')
                <form id="lead-bulk-restore-form" method="POST" action="{{ route('manager.leads.bulk-restore') }}" onsubmit="return confirm('Restore selected leads?');" class="hidden">
                    @csrf
                    @method('PATCH')
                </form>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--lp-border)] bg-[#f9fafb] text-left lp-muted">
                            @if (auth()->user()?->role === 'admin')
                                <th class="px-5 py-3 font-medium">
                                    <label class="inline-flex items-center gap-2 text-xs">
                                        <input type="checkbox" id="select-all-leads" class="rounded border-[var(--lp-border)]">
                                        <span>Select</span>
                                    </label>
                                </th>
                            @endif
                            <th class="px-5 py-3 font-medium">Lead</th>
                            <th class="px-5 py-3 font-medium">Type</th>
                            <th class="px-5 py-3 font-medium">Source</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Assigned</th>
                            <th class="px-5 py-3 font-medium">Created</th>
                            <th class="px-5 py-3 font-medium">State</th>
                            <th class="px-5 py-3 font-medium">
                                <div class="flex justify-end">
                                    @if (auth()->user()?->role === 'admin' && ($visibility ?? '') !== 'deleted')
                                        <button id="bulk-delete-button" type="submit" form="lead-bulk-delete-form" class="hidden rounded-lg border border-red-300 bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                                            Delete Selected
                                        </button>
                                    @elseif (auth()->user()?->role === 'admin' && ($visibility ?? '') === 'deleted')
                                        <button type="submit" form="lead-bulk-restore-form" class="rounded-lg border border-emerald-300 bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                            Restore Selected
                                        </button>
                                    @endif
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr class="border-b border-[var(--lp-border)]">
                                @if (auth()->user()?->role === 'admin')
                                    <td class="px-5 py-3 align-top">
                                        <input
                                            type="checkbox"
                                            name="lead_ids[]"
                                            value="{{ $lead->id }}"
                                            form="{{ ($visibility ?? '') === 'deleted' ? 'lead-bulk-restore-form' : 'lead-bulk-delete-form' }}"
                                            class="lead-select rounded border-[var(--lp-border)]"
                                        >
                                    </td>
                                @endif
                                <td class="px-5 py-3">
                                    <p class="font-medium lp-title">{{ $lead->name }}</p>
                                    <p class="text-xs lp-muted">{{ $lead->email }} | {{ $lead->phone }}</p>
                                </td>
                                <td class="px-5 py-3">{{ $lead->lead_type ? ucwords(str_replace('_', ' ', $lead->lead_type)) : 'Set later' }}</td>
                                <td class="px-5 py-3">{{ ucwords(str_replace('_', ' ', $lead->source)) }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full border border-[var(--lp-border)] px-3 py-1 text-xs uppercase tracking-wide">{{ $lead->status }}</span>
                                </td>
                                <td class="px-5 py-3">{{ $lead->assignedManager?->name ?? 'Unassigned' }}</td>
                                <td class="px-5 py-3">{{ $lead->created_at->format('M d, Y') }}</td>
                                <td class="px-5 py-3">
                                    @if ($lead->trashed())
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs uppercase tracking-wide text-amber-700">Deleted</span>
                                    @else
                                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs uppercase tracking-wide text-emerald-700">Active</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        @if (! $lead->trashed())
                                            <a href="{{ route('manager.leads.show', $lead) }}" class="rounded-lg border border-[var(--lp-border)] px-3 py-1.5 text-xs font-medium lp-title">Open</a>
                                            @if (auth()->user()?->role === 'admin')
                                                <form method="POST" action="{{ route('manager.leads.destroy', $lead) }}" onsubmit="return confirm('Move this lead to recycle bin?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg border border-red-300 bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        @elseif (auth()->user()?->role === 'admin')
                                            <form method="POST" action="{{ route('manager.leads.restore', $lead->id) }}" onsubmit="return confirm('Restore this lead?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                                    Restore
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()?->role === 'admin' ? '9' : '8' }}" class="px-5 py-8 text-center lp-muted">No leads found for current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4">
                {{ $leads->links() }}
            </div>
        </section>
    </div>

    @if (auth()->user()?->role === 'admin')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const selectAll = document.getElementById('select-all-leads');
                const rowChecks = Array.from(document.querySelectorAll('.lead-select'));
                const bulkDeleteButton = document.getElementById('bulk-delete-button');

                if (!selectAll || rowChecks.length === 0) {
                    return;
                }

                const updateSelectionUi = () => {
                    const selectedCount = rowChecks.filter((item) => item.checked).length;

                    selectAll.checked = selectedCount === rowChecks.length;
                    selectAll.indeterminate = selectedCount > 0 && selectedCount < rowChecks.length;

                    if (bulkDeleteButton) {
                        if (selectedCount > 1) {
                            bulkDeleteButton.classList.remove('hidden');
                            bulkDeleteButton.textContent = `Delete Selected (${selectedCount})`;
                        } else {
                            bulkDeleteButton.classList.add('hidden');
                            bulkDeleteButton.textContent = 'Delete Selected';
                        }
                    }
                };

                selectAll.addEventListener('change', () => {
                    rowChecks.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });

                    updateSelectionUi();
                });

                rowChecks.forEach((checkbox) => {
                    checkbox.addEventListener('change', () => {
                        updateSelectionUi();
                    });
                });

                updateSelectionUi();
            });
        </script>
    @endif
</x-app-layout>
