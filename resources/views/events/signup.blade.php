<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareTitle = $event->name . ' - ' . config('app.name', 'ProspectGoat');
            $shareDescription = 'Sign up for ' . $event->name . ' with ProspectGoat.';
        @endphp

        @include('partials.share-meta')

        <title>Event Sign Up - {{ config('app.name', 'ProspectGoat') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen py-10 sm:py-16">
        <main class="lp-shell space-y-8">
            <section class="lp-card p-7 sm:p-10">
                <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] lp-muted">Event Signup</p>
                        <h1 class="lp-title mt-2 text-2xl font-semibold sm:text-3xl">{{ $event->name }}</h1>
                        <p class="mt-2 text-sm lp-muted">{{ $event->location }} | {{ $event->event_time->format('M d, Y g:i A') }}</p>
                    </div>
                    <a href="{{ route('events.index') }}" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title">
                        Back to Events
                    </a>
                </div>
            </section>

            <section>
                <div class="lp-card p-7 sm:p-8">
                    <h2 class="lp-title text-xl font-semibold">Sign Up Sheet</h2>
                    <p class="mt-2 text-sm lp-muted">
                        Just imagine ...<br>
                        This could be your new home!<br>
                        If not, then this is the home that will help you find your new home.
                    </p>

                    @if (session('status'))
                        <div class="mt-4 rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] p-4 text-sm text-[#2f5f34]">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('events.signup.store', $event->slug) }}" class="mt-6 grid gap-4 sm:grid-cols-2" x-data="{ workingWithAgent: '{{ old('working_with_agent', '') }}' }">
                        @csrf

                        <div>
                            <label for="first_name" class="mb-1 block text-sm font-medium lp-title">First Name</label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                            @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="last_name" class="mb-1 block text-sm font-medium lp-title">Last Name</label>
                            <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                            @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="email" class="mb-1 block text-sm font-medium lp-title">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-1 block text-sm font-medium lp-title">Phone</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <p class="mb-2 block text-sm font-medium lp-title">Do you currently have a signed agreement with a real estate agent?</p>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 text-sm lp-title">
                                    <input type="radio" name="working_with_agent" value="1" x-model="workingWithAgent" @checked(old('working_with_agent') === '1') required>
                                    <span>Yes</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm lp-title">
                                    <input type="radio" name="working_with_agent" value="0" x-model="workingWithAgent" @checked(old('working_with_agent') === '0') required>
                                    <span>No</span>
                                </label>
                            </div>
                            @error('working_with_agent')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div x-show="workingWithAgent === '1'" class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="agent_first_name" class="mb-1 block text-sm font-medium lp-title">Agent First Name</label>
                                <input id="agent_first_name" name="agent_first_name" type="text" value="{{ old('agent_first_name') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                @error('agent_first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="agent_last_name" class="mb-1 block text-sm font-medium lp-title">Agent Last Name</label>
                                <input id="agent_last_name" name="agent_last_name" type="text" value="{{ old('agent_last_name') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                @error('agent_last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-accent">
                                Submit Registration
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>

        <div aria-hidden="true" style="height: 5rem;"></div>

        @include('components.site-footer-card')
    </body>
</html>
