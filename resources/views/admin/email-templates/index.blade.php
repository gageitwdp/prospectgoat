<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Email Templates') }}
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
                        <h1 class="mt-1 text-2xl font-semibold lp-title">Inquiry Confirmation Templates</h1>
                    </div>
                    <span class="rounded-full bg-[var(--lp-canvas)] px-3 py-1 text-xs lp-muted">
                        {{ $templates->count() }} templates
                    </span>
                </div>

                <p class="mt-3 text-sm lp-muted">
                    Control the confirmation emails sent after a new inquiry is submitted. Each inquiry type has its own template and fallback behavior.
                </p>

                <div class="mt-6 grid gap-4">
                    @foreach ($templates as $template)
                        <div class="rounded-2xl border border-[var(--lp-border)] p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.16em] lp-muted">{{ $template->key }}</p>
                                    <h2 class="mt-1 text-lg font-semibold lp-title">{{ $template->name }}</h2>
                                    <p class="mt-1 text-sm lp-muted">{{ $template->subject }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $template->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-[var(--lp-canvas)] lp-muted' }}">
                                        {{ $template->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                    <a href="{{ route('admin.email-templates.edit', $template) }}" class="rounded-lg border border-[var(--lp-border)] px-3 py-2 text-xs font-semibold lp-title hover:bg-[var(--lp-canvas)]">
                                        Edit Template
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>