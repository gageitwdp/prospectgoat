<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Prospecting Script Library') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8" x-data="prospectingScriptLibrary()">
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
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.14em] lp-muted">Tab</p>
                                    <h3 class="mt-1 text-xl font-semibold lp-title">{{ $script->name }}</h3>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-[var(--lp-border)] bg-[var(--lp-canvas)] px-3 py-1 text-xs lp-muted">Sort: {{ $script->sort_order }}</span>
                                    <span class="rounded-full px-3 py-1 text-xs {{ $script->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $script->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-5 rounded-xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] p-4 sm:p-5">
                                <p class="text-xs uppercase tracking-[0.14em] lp-muted">Preview</p>
                                <pre class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 lp-title">{{ $script->content }}</pre>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary"
                                    @click="openEditScript({
                                        name: @js($script->name),
                                        content: @js($script->content),
                                        sortOrder: @js($script->sort_order),
                                        isActive: @js((bool) $script->is_active),
                                        action: @js(route('admin.prospecting-scripts.update', $script))
                                    })"
                                >
                                    Edit Script Content
                                </button>
                            </div>

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

        <x-modal name="prospecting-script-edit-modal" maxWidth="2xl">
            <form method="post" :action="editForm.action" class="p-6">
                @csrf
                @method('put')

                <h3 class="text-lg font-semibold lp-title">Edit Script Content</h3>
                <p class="mt-2 text-sm lp-muted" x-text="editForm.name ? `Editing: ${editForm.name}` : 'Editing script'"></p>

                <input type="hidden" name="name" :value="editForm.name">
                <input type="hidden" name="sort_order" :value="editForm.sortOrder">
                <input type="hidden" name="is_active" :value="editForm.isActive ? 1 : 0">

                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium lp-title" for="edit_script_content">Script Content</label>
                    <textarea
                        id="edit_script_content"
                        name="content"
                        rows="14"
                        class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-3 text-sm leading-6"
                        x-model="editForm.content"
                        required
                    ></textarea>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm lp-title hover:bg-[var(--lp-canvas)]" @click="$dispatch('close-modal', 'prospecting-script-edit-modal')">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-xl px-4 py-2 text-sm font-medium lp-btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function prospectingScriptLibrary() {
            return {
                editForm: {
                    name: '',
                    content: '',
                    sortOrder: 0,
                    isActive: true,
                    action: '',
                },

                openEditScript(payload) {
                    this.editForm = {
                        name: payload.name || '',
                        content: payload.content || '',
                        sortOrder: Number(payload.sortOrder || 0),
                        isActive: Boolean(payload.isActive),
                        action: payload.action || '',
                    };

                    this.$dispatch('open-modal', 'prospecting-script-edit-modal');
                },
            };
        }
    </script>
</x-app-layout>
