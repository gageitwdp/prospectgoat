<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Edit Event') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="lp-card p-6 sm:p-8">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Admin Module</p>
                    <h1 class="mt-1 text-2xl font-semibold lp-title">Edit Event</h1>
                </div>

                <form method="POST" action="{{ route('admin.events.update', $event) }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div class="sm:col-span-2">
                        <label for="name" class="mb-1 block text-sm font-medium lp-title">Event Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $event->name) }}" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="slug" class="mb-1 block text-sm font-medium lp-title">Slug (optional)</label>
                        <input id="slug" name="slug" type="text" value="{{ old('slug', $event->slug) }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                        @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="status" class="mb-1 block text-sm font-medium lp-title">Status</label>
                        <select id="status" name="status" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm">
                            <option value="draft" @selected(old('status', $event->status) === 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $event->status) === 'published')>Published</option>
                        </select>
                        @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="location" class="mb-1 block text-sm font-medium lp-title">Location</label>
                        <input id="location" name="location" type="text" value="{{ old('location', $event->location) }}" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                        @error('location')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="event_time" class="mb-1 block text-sm font-medium lp-title">Time</label>
                        <input id="event_time" name="event_time" type="datetime-local" value="{{ old('event_time', $event->event_time->format('Y-m-d\\TH:i')) }}" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                        @error('event_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="details" class="mb-1 block text-sm font-medium lp-title">Details (optional)</label>
                        <textarea id="details" name="details" rows="4" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm">{{ old('details', $event->details) }}</textarea>
                        @error('details')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2 flex items-center gap-3">
                        <button type="submit" class="rounded-xl px-5 py-3 text-sm font-medium lp-btn-primary">Save Event</button>
                        <a href="{{ route('admin.events.index') }}" class="rounded-xl border border-[var(--lp-border)] px-5 py-3 text-sm font-medium lp-title">Cancel</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
