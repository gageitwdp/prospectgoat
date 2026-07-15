<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareUrl = url()->current();
            $shareTitle = config('app.name', 'ProspectGoat Portal');
            $shareDescription = 'ProspectGoat internal portal dashboard.';
        @endphp

        @include('partials.share-meta')

        <title>{{ config('app.name', 'ProspectGoat Portal') }}</title>

        <!-- Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="lp-shell py-6">
                    <div class="lp-card px-6 py-4 sm:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 pb-10">
                {{ $slot }}
            </main>

            @include('components.site-footer-card')
        </div>
    </body>
</html>
