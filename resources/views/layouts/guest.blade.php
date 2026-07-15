<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareUrl = url()->current();
            $shareTitle = config('app.name', 'ProspectGoat Portal');
            $shareDescription = 'Secure access for the ProspectGoat portal.';
            $heading = match (request()->route()?->getName()) {
                'login' => 'Login',
                'register' => 'Sign Up',
                'password.request' => 'Forgot Password',
                'password.reset' => 'Reset Password',
                'password.confirm' => 'Confirm Password',
                'verification.notice' => 'Verify Email',
                default => config('app.name', 'ProspectGoat Portal'),
            };
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
            <main class="flex-1 flex flex-col items-center justify-center p-6">
                <div class="mb-6 text-center">
                    <a href="/" class="text-xs uppercase tracking-[0.25em] lp-muted">ProspectGoat</a>
                    <p class="lp-title text-2xl font-semibold">{{ $heading }}</p>
                </div>

                <div class="lp-card w-full max-w-md px-6 py-6">
                    {{ $slot }}
                </div>
            </main>

            @include('components.site-footer-card')
            </div>
    </body>
</html>
