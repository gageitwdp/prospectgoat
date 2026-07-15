<nav class="pt-6">
    @php
        $currentUser = Auth::user();
        $isOwner = $currentUser?->isOwner() ?? false;
        $homeRoute = $isOwner ? route('admin.dashboard') : route('manager.leads.index');
    @endphp

    <div class="lp-shell">
        <div class="lp-card flex flex-col gap-4 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ $homeRoute }}" class="text-sm uppercase tracking-[0.25em] lp-muted">ProspectGoat</a>
                <p class="lp-title text-lg font-semibold">{{ $isOwner ? 'Owner Portal' : 'Lead Management Portal' }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-sm">
                @if ($isOwner)
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-[var(--lp-secondary)] text-white' : 'border border-[var(--lp-border)] lp-title' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('admin.events.index') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.events.*') ? 'bg-[var(--lp-secondary)] text-white' : 'border border-[var(--lp-border)] lp-title' }}">
                        Events
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.users.*') ? 'bg-[var(--lp-secondary)] text-white' : 'border border-[var(--lp-border)] lp-title' }}">
                        Users
                    </a>
                @endif
                <a href="{{ route('manager.leads.index') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('manager.leads.*') ? 'bg-[var(--lp-secondary)] text-white' : 'border border-[var(--lp-border)] lp-title' }}">
                    Leads
                </a>
                <a href="{{ route('manager.leads.pipeline') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('manager.leads.pipeline') ? 'bg-[var(--lp-secondary)] text-white' : 'border border-[var(--lp-border)] lp-title' }}">
                    Pipeline
                </a>
                <a href="{{ route('profile.edit') }}" class="rounded-lg border border-[var(--lp-border)] px-3 py-2 lp-title">
                    Profile
                </a>
                <span class="px-2 py-2 lp-muted">{{ Auth::user()->name }} ({{ ucfirst(Auth::user()->role) }})</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg px-3 py-2 text-sm lp-btn-primary">Log Out</button>
                </form>
            </div>
        </div>
    </div>
</nav>
