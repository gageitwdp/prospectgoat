<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('User Management') }}
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
                        <h1 class="mt-1 text-2xl font-semibold lp-title">User Directory</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-[var(--lp-canvas)] px-3 py-1 text-xs lp-muted">
                            {{ $users->total() }} total users
                        </span>
                        <a href="{{ route('admin.users.create') }}" class="rounded-xl px-3 py-2 text-xs font-semibold lp-btn-primary">
                            Create User
                        </a>
                    </div>
                </div>

                <p class="mt-3 text-sm lp-muted">
                    Manage user identities and access scope. Editing actions can be added as the module evolves.
                </p>

                <form id="user-bulk-delete-form" method="POST" action="{{ route('admin.users.bulk-destroy') }}" onsubmit="return confirm('Delete selected user records? This cannot be undone.');" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                        Delete Selected Users
                    </button>
                </form>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--lp-border)] text-sm">
                        <thead>
                            <tr class="text-left lp-muted">
                                <th class="px-3 py-3 font-medium">
                                    <label class="inline-flex items-center gap-2 text-xs">
                                        <input type="checkbox" id="select-all-users" class="rounded border-[var(--lp-border)]">
                                        <span>Select</span>
                                    </label>
                                </th>
                                <th class="px-3 py-3 font-medium">Name</th>
                                <th class="px-3 py-3 font-medium">Email</th>
                                <th class="px-3 py-3 font-medium">Role</th>
                                <th class="px-3 py-3 font-medium">Joined</th>
                                <th class="px-3 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--lp-border)]">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-3 py-3">
                                        @if (auth()->id() !== $user->id)
                                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" form="user-bulk-delete-form" class="user-select rounded border-[var(--lp-border)]">
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 lp-title font-medium">{{ $user->name }}</td>
                                    <td class="px-3 py-3">{{ $user->email }}</td>
                                    <td class="px-3 py-3">
                                        <span class="rounded-full bg-[var(--lp-canvas)] px-2.5 py-1 text-xs uppercase tracking-[0.1em] lp-muted">
                                            {{ $user->role }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 lp-muted">{{ $user->created_at?->format('M d, Y') }}</td>
                                    <td class="px-3 py-3 text-right">
                                        @if (auth()->id() !== $user->id)
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-[var(--lp-border)] px-3 py-1.5 text-xs font-semibold lp-title hover:bg-[var(--lp-canvas)]">
                                                    Edit
                                                </a>
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user record? This cannot be undone.');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-[var(--lp-border)] px-3 py-1.5 text-xs font-semibold lp-title hover:bg-[var(--lp-canvas)]">
                                                    Edit
                                                </a>
                                                <span class="text-xs lp-muted">Current user</span>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center lp-muted">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $users->links() }}
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-users');
            const rowChecks = Array.from(document.querySelectorAll('.user-select'));

            if (!selectAll || rowChecks.length === 0) {
                return;
            }

            selectAll.addEventListener('change', () => {
                rowChecks.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });

            rowChecks.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const allChecked = rowChecks.every((item) => item.checked);
                    selectAll.checked = allChecked;
                });
            });
        });
    </script>
</x-app-layout>
