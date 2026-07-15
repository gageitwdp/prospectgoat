<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Events') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="lp-card p-6 sm:p-8">
                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] p-4 text-sm text-[#2f5f34]">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] lp-muted">Admin Module</p>
                        <h1 class="mt-1 text-2xl font-semibold lp-title">Event Directory</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-[var(--lp-canvas)] px-3 py-1 text-xs lp-muted">
                            {{ $events->total() }} total events
                        </span>
                        <a href="{{ route('admin.events.create') }}" class="rounded-xl px-3 py-2 text-xs font-semibold lp-btn-primary">
                            Create Event
                        </a>
                    </div>
                </div>

                <p class="mt-3 text-sm lp-muted">
                    Manage event details and publish status for the public events page.
                </p>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--lp-border)] text-sm">
                        <thead>
                            <tr class="text-left lp-muted">
                                <th class="px-3 py-3 font-medium">Name</th>
                                <th class="px-3 py-3 font-medium">Location</th>
                                <th class="px-3 py-3 font-medium">Time</th>
                                <th class="px-3 py-3 font-medium">Status</th>
                                <th class="px-3 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--lp-border)]">
                            @forelse ($events as $event)
                                <tr>
                                    <td class="px-3 py-3 lp-title font-medium">{{ $event->name }}</td>
                                    <td class="px-3 py-3">{{ $event->location }}</td>
                                    <td class="px-3 py-3 lp-muted">{{ $event->event_time->format('M d, Y g:i A') }}</td>
                                    <td class="px-3 py-3">
                                        <span class="rounded-full bg-[var(--lp-canvas)] px-2.5 py-1 text-xs uppercase tracking-[0.1em] lp-muted">
                                            {{ $event->status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('admin.events.edit', $event) }}" class="rounded-lg border border-[var(--lp-border)] px-3 py-1.5 text-xs font-semibold lp-title hover:bg-[var(--lp-canvas)]">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center lp-muted">No events found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $events->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
