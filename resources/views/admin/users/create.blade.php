<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Create User') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="lp-card p-6 sm:p-8">
                <h1 class="text-2xl font-semibold lp-title">New User Account</h1>
                <p class="mt-2 text-sm lp-muted">Create owner, manager, or agent accounts with optional lead notification preferences.</p>

                <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Name</label>
                        <input name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Email</label>
                        <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Role</label>
                        <select name="role" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                            <option value="owner" @selected(old('role') === 'owner')>Owner</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                            <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                            <option value="agent" @selected(old('role') === 'agent')>Agent</option>
                            @if (auth()->user()?->isGlobalAdmin())
                                <option value="global_admin" @selected(old('role') === 'global_admin')>Global Admin</option>
                            @endif
                        </select>
                        @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Password</label>
                        <input name="password" type="password" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Confirm Password</label>
                        <input name="password_confirmation" type="password" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                    </div>

                    <div class="sm:col-span-2 space-y-2 rounded-xl border border-[var(--lp-border)] p-4">
                        <p class="text-sm font-medium lp-title">Notification Preferences</p>

                        <label class="flex items-center gap-2 text-sm lp-muted">
                            <input type="hidden" name="notify_on_new_lead_intake" value="0">
                            <input type="checkbox" name="notify_on_new_lead_intake" value="1" @checked(old('notify_on_new_lead_intake', true))>
                            New lead intake alerts
                        </label>

                        <label class="flex items-center gap-2 text-sm lp-muted">
                            <input type="hidden" name="notify_on_lead_assignment" value="0">
                            <input type="checkbox" name="notify_on_lead_assignment" value="1" @checked(old('notify_on_lead_assignment', false))>
                            Lead assignment alerts
                        </label>
                    </div>

                    <div class="sm:col-span-2 flex items-center gap-3">
                        <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">Create User</button>
                        <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm lp-title">Cancel</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
