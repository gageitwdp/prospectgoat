<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareUrl = url('/');
            $shareTitle = config('app.name', 'Lezin Properties') . ' - Portal';
            $shareDescription = 'Lezin Properties Portal for inquiries, events, and mortgage planning.';
        @endphp

        @include('partials.share-meta')

        <title>{{ config('app.name', 'Lezin Properties') }} - Portal</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen py-10 sm:py-16">
        <main class="lp-shell space-y-8">
            <section class="lp-card p-7 sm:p-10">
                <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                    <div class="max-w-3xl space-y-4">
                        <p class="text-xs uppercase tracking-[0.35em] lp-muted">Lezin Properties Portal</p>
                        <h1 class="lp-title text-3xl font-semibold leading-tight sm:text-5xl">Defined by detail.</h1>
                        <p class="lp-muted text-base sm:text-lg">Providing clarity, accountability, and exceptional service every step of the way.</p>
                    </div>
                    <a href="https://lezinproperties.com/" class="inline-flex items-center justify-center rounded-xl px-6 py-3 text-base font-medium lp-btn-primary w-full sm:w-auto">
                        Home
                    </a>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-4">
                <article class="lp-card p-7 sm:p-10">
                    <h2 class="lp-title text-2xl font-semibold">Team Workspace</h2>
                    <p class="mt-3 text-base leading-relaxed lp-muted">Sign in to review active opportunities, manage assignments, and keep every stage moving with clarity.</p>
                    <a href="{{ route('login') }}" class="mt-6 inline-flex items-center justify-center rounded-xl px-6 py-3 text-base font-medium lp-btn-primary w-full sm:w-auto">
                        Enter Workspace
                    </a>
                </article>

                <article class="lp-card p-7 sm:p-10">
                    <h2 class="lp-title text-2xl font-semibold">Buyer Intake</h2>
                    <p class="mt-3 text-base leading-relaxed lp-muted">Use the guided buyer questionnaire to get matched with homes, timelines, and price bands that fit your search.</p>
                    <a href="{{ route('buyers.intake') }}" class="mt-6 inline-flex items-center justify-center rounded-xl px-6 py-3 text-base font-medium lp-btn-accent w-full sm:w-auto">
                        Start Buyer Intake
                    </a>
                    <p class="mt-3 text-sm lp-muted">
                        Need a general inquiry instead? <a href="{{ route('leads.intake') }}" class="underline underline-offset-4">Use the standard form</a>.
                    </p>
                    <p class="mt-3 text-sm lp-muted">
                        Looking to sell? <a href="{{ route('sellers.intake') }}" class="underline underline-offset-4">Start Seller Intake</a>.
                    </p>
                </article>

                <article class="lp-card p-7 sm:p-10">
                    <h2 class="lp-title text-2xl font-semibold">Mortgage Calculator</h2>
                    <p class="mt-3 text-base leading-relaxed lp-muted">Estimate your monthly payment and send a full breakdown directly to your email.</p>
                    <a href="{{ route('mortgage.calculator') }}" class="mt-6 inline-flex items-center justify-center rounded-xl px-6 py-3 text-base font-medium lp-btn-primary w-full sm:w-auto">
                        Open Calculator
                    </a>
                </article>

                <article class="lp-card p-7 sm:p-10">
                    <h2 class="lp-title text-2xl font-semibold">Events</h2>
                    <p class="mt-3 text-base leading-relaxed lp-muted">Browse upcoming community events and sign up in a few quick steps.</p>
                    <a href="{{ route('events.index') }}" class="mt-6 inline-flex items-center justify-center rounded-xl px-6 py-3 text-base font-medium lp-btn-accent w-full sm:w-auto">
                        View Events
                    </a>
                </article>
            </section>

            @include('components.site-footer-card')
        </main>
    </body>
</html>