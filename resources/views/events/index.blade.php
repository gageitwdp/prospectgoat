<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareTitle = 'Events - ' . config('app.name', 'Lezin Properties');
            $shareDescription = 'Browse upcoming Lezin Properties community events.';
        @endphp

        @include('partials.share-meta')

        <title>Events - {{ config('app.name', 'Lezin Properties') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen py-10 sm:py-16">
        <main class="lp-shell space-y-8">
            <section class="lp-card p-7 sm:p-10">
                <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] lp-muted">Community Events</p>
                        <h1 class="lp-title mt-2 text-2xl font-semibold sm:text-3xl">Join us at an upcoming event.</h1>
                        <p class="mt-2 text-sm lp-muted">Select any event card to open the sign up sheet.</p>
                    </div>
                    <a href="https://lezinproperties.com/" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title">
                        Home
                    </a>
                </div>
            </section>

            <section>
                @if ($events->isEmpty())
                    <div class="lp-card p-7 sm:p-8">
                        <p class="text-sm lp-muted">Events coming soon.</p>
                    </div>
                @else
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($events as $event)
                            <a href="{{ route('events.signup.show', $event->slug) }}" class="lp-card p-6 transition hover:-translate-y-1 hover:shadow-[0_16px_35px_rgba(31,41,51,0.12)]">
                                <p class="text-xs uppercase tracking-[0.2em] lp-muted">Event</p>
                                <h2 class="mt-2 text-xl font-semibold lp-title">{{ $event->name }}</h2>
                                <div class="mt-4 space-y-2 text-sm">
                                    <p>
                                        <span class="font-semibold lp-title">Location:</span>
                                        <span class="lp-muted">{{ $event->location }}</span>
                                    </p>
                                    <p>
                                        <span class="font-semibold lp-title">Time:</span>
                                        <span class="lp-muted">{{ $event->event_time->format('M d, Y g:i A') }}</span>
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>

        <div aria-hidden="true" style="height: 5rem;"></div>

        @include('components.site-footer-card')
    </body>
</html>
