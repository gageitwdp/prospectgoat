@php
    $navItems = [
        [
            'label' => 'Lead Management',
            'route' => route('manager.leads.index'),
            'active' => request()->routeIs('manager.leads.*'),
            'status' => 'Live',
        ],
        [
            'label' => 'Events',
            'route' => route('admin.events.index'),
            'active' => request()->routeIs('admin.events.*'),
            'status' => 'Live',
        ],
        [
            'label' => 'User Management',
            'route' => route('admin.users.index'),
            'active' => request()->routeIs('admin.users.*'),
            'status' => 'Live',
        ],
        [
            'label' => 'Import Leads',
            'route' => route('admin.imports.leads.index'),
            'active' => request()->routeIs('admin.imports.leads.*'),
            'status' => 'Live',
        ],
        [
            'label' => 'Prospecting Tool',
            'route' => route('admin.prospecting.index'),
            'active' => request()->routeIs('admin.prospecting.*'),
            'status' => 'Live',
        ],
        [
            'label' => 'Email Templates',
            'route' => route('admin.email-templates.index'),
            'active' => request()->routeIs('admin.email-templates.*'),
            'status' => 'Live',
        ],
        [
            'label' => 'Analytics',
            'route' => null,
            'active' => false,
            'status' => 'Soon',
        ],
        [
            'label' => 'Marketing',
            'route' => null,
            'active' => false,
            'status' => 'Soon',
        ],
    ];

    if (auth()->user()?->isGlobalAdmin()) {
        $navItems[] = [
            'label' => 'Global Account Oversight',
            'route' => route('admin.global-account-oversight.index'),
            'active' => request()->routeIs('admin.global-account-oversight.*'),
            'status' => 'Live',
        ];
    }
@endphp

<aside class="lp-card p-5 sm:p-6">
    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Admin Modules</p>
    <h3 class="mt-2 lp-title text-lg font-semibold">Operations Control</h3>

    <nav class="mt-5 space-y-2">
        @foreach ($navItems as $item)
            @if ($item['route'])
                <a
                    href="{{ $item['route'] }}"
                    class="flex items-center justify-between rounded-xl border px-3 py-2 text-sm transition {{ $item['active'] ? 'border-[var(--lp-secondary)] bg-[var(--lp-secondary)] text-white' : 'border-[var(--lp-border)] lp-title hover:bg-[var(--lp-canvas)]' }}"
                >
                    <span>{{ $item['label'] }}</span>
                    <span class="text-xs {{ $item['active'] ? 'text-white/80' : 'lp-muted' }}">{{ $item['status'] }}</span>
                </a>
            @else
                <div class="flex items-center justify-between rounded-xl border border-dashed border-[var(--lp-border)] px-3 py-2 text-sm lp-muted">
                    <span>{{ $item['label'] }}</span>
                    <span class="text-xs">{{ $item['status'] }}</span>
                </div>
            @endif
        @endforeach
    </nav>
</aside>
