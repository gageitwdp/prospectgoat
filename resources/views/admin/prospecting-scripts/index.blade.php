<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Prospecting Script Library') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="space-y-6">
                <article class="lp-card p-6 sm:p-8">
                    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Global Admin</p>
                    <h1 class="mt-2 text-2xl font-semibold lp-title">Prospecting Script Tabs</h1>
                    <p class="mt-2 text-sm lp-muted">
                        Manage script tabs shown in the Prospecting Tool for all accounts.
                    </p>

                    @if (session('status'))
                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif
                </article>

                <article class="lp-card p-6 sm:p-8">
                    <h2 class="text-lg font-semibold lp-title">Add Script Tab</h2>

                    <form method="post" action="{{ route('admin.prospecting-scripts.store') }}" class="mt-4 space-y-4">
                        @csrf

                        <div class="grid gap-4 md:grid-cols-[2fr,120px,140px]">
                            <div>
                                <label class="mb-1 block text-sm font-medium lp-title" for="new_name">Tab Name</label>
                                <input id="new_name" name="name" type="text" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" value="{{ old('name') }}" required maxlength="120" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium lp-title" for="new_sort_order">Sort Order</label>
                                <input id="new_sort_order" name="sort_order" type="number" min="0" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" value="{{ old('sort_order') }}" />
                            </div>

                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm lp-title">
                                    <input type="checkbox" name="is_active" value="1" class="rounded border-[var(--lp-border)] text-[var(--lp-secondary)] focus:ring-[var(--lp-secondary)]" @checked(old('is_active', true))>
                                    <span>Active</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title" for="new_content">Script Content</label>
                            <textarea id="new_content" name="content" rows="9" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-3 text-sm leading-6" required>{{ old('content') }}</textarea>
                        </div>

                        <x-primary-button>{{ __('Create Script Tab') }}</x-primary-button>
                    </form>
                </article>

                <article class="space-y-4">
                    @forelse ($scripts as $script)
                        <div class="lp-card p-6 sm:p-8">
                            <form method="post" action="{{ route('admin.prospecting-scripts.update', $script) }}" class="space-y-4">
                                @csrf
                                @method('put')

                                <div class="grid gap-4 md:grid-cols-[2fr,120px,140px]">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium lp-title" for="name_{{ $script->id }}">Tab Name</label>
                                        <input id="name_{{ $script->id }}" name="name" type="text" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" value="{{ old('name', $script->name) }}" required maxlength="120" />
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium lp-title" for="sort_{{ $script->id }}">Sort Order</label>
                                        <input id="sort_{{ $script->id }}" name="sort_order" type="number" min="0" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" value="{{ old('sort_order', $script->sort_order) }}" required />
                                    </div>

                                    <div class="flex items-end">
                                        <label class="inline-flex items-center gap-2 text-sm lp-title">
                                            <input type="checkbox" name="is_active" value="1" class="rounded border-[var(--lp-border)] text-[var(--lp-secondary)] focus:ring-[var(--lp-secondary)]" @checked(old('is_active', $script->is_active))>
                                            <span>Active</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium lp-title" for="content_{{ $script->id }}">Script Content</label>
                                    <textarea id="content_{{ $script->id }}" name="content" rows="9" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-3 text-sm leading-6" required>{{ old('content', $script->content) }}</textarea>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                                </div>
                            </form>

                            <form method="post" action="{{ route('admin.prospecting-scripts.destroy', $script) }}" class="mt-3" onsubmit="return confirm('Delete this script tab?');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                                    Delete Tab
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="lp-card p-6 sm:p-8">
                            <p class="text-sm lp-muted">No script tabs created yet. Add one above to get started.</p>
                        </div>
                    @endforelse
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
