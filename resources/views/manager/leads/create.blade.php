<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold lp-title">New Lead</h2>
                <p class="text-sm lp-muted">Add a lead to your account pipeline.</p>
            </div>

            <a href="{{ route('manager.leads.index') }}" class="text-sm lp-muted underline">Back to all leads</a>
        </div>
    </x-slot>

    <div class="lp-shell space-y-6 px-2 sm:px-0">
        @if ($errors->any())
            <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-medium">Please fix the highlighted fields.</p>
            </section>
        @endif

        <section class="lp-card p-6">
            <form method="POST" action="{{ route('manager.leads.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-medium lp-title">Name</label>
                    <input name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium lp-title">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium lp-title">Phone</label>
                    <input name="phone" type="text" value="{{ old('phone') }}" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium lp-title">Address</label>
                    <input name="address" type="text" value="{{ old('address') }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                    @error('address')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium lp-title">Lead Type</label>
                    <select name="lead_type" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                        @foreach (['home_value', 'buyer', 'seller', 'generic_inquiry'] as $type)
                            <option value="{{ $type }}" @selected(old('lead_type', 'buyer') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                    @error('lead_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium lp-title">Source</label>
                    <select name="source" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                        @foreach (['homepage', 'landing_page', 'referral'] as $source)
                            <option value="{{ $source }}" @selected(old('source', 'homepage') === $source)>{{ ucwords(str_replace('_', ' ', $source)) }}</option>
                        @endforeach
                    </select>
                    @error('source')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium lp-title">Status</label>
                    <select name="status" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                        @foreach (['new', 'contacted', 'qualified', 'active', 'closed'] as $status)
                            <option value="{{ $status }}" @selected(old('status', 'new') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium lp-title">Assigned To</label>
                    <select name="assigned_to" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                        <option value="">Unassigned</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}" @selected((string) old('assigned_to') === (string) $manager->id)>{{ $manager->name }} ({{ $manager->role }})</option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2 flex gap-2">
                    <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">Create Lead</button>
                    <a href="{{ route('manager.leads.index') }}" class="rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm lp-title">Cancel</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
