<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareTitle = 'New Inquiry - ' . config('app.name', 'ProspectGoat');
            $shareDescription = 'Start a new inquiry with ProspectGoat.';
        @endphp

        @include('partials.share-meta')

        <title>New Inquiry - {{ config('app.name', 'ProspectGoat') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen py-10 sm:py-16">
        <main class="lp-shell space-y-8">
            <section class="lp-card p-7 sm:p-10">
                <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] lp-muted">New Inquiry</p>
                        <h1 class="lp-title mt-2 text-2xl font-semibold sm:text-3xl">Lets achieve your property goals.</h1>
                        <p class="mt-2 text-sm lp-muted">Looking to buy? Start the guided buyer intake for a more tailored search.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('buyers.intake') }}" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title">
                            Buyer Intake
                        </a>
                        <a href="{{ route('sellers.intake') }}" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title">
                            Seller Intake
                        </a>
                        <a href="https://prospectgoat.com/" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title">
                            Home
                        </a>
                    </div>
                </div>
            </section>

            <section>
                <div class="lp-card p-7 sm:p-8">
                    <h2 class="lp-title text-xl font-semibold">New Inquiry Intake</h2>
                    <p class="mt-2 text-sm lp-muted">Complete the form and our team will follow up with your next step.</p>

                    @if (session('lead_success'))
                        <div class="mt-4 rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] p-4 text-sm text-[#2f5f34]">
                            {{ session('lead_success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('leads.intake.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2" x-data="{ inquiryType: '{{ old('lead_type', '') }}', source: '{{ old('source', 'landing_page') }}' }">
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

                        <div>
                            <label for="lead_type" class="mb-1 block text-sm font-medium lp-title">Inquiry Type</label>
                            <select id="lead_type" name="lead_type" x-model="inquiryType" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm">
                                <option value="">Select</option>
                                <option value="home_value" @selected(old('lead_type') === 'home_value')>Home Value</option>
                                <option value="buyer" @selected(old('lead_type') === 'buyer')>Buyer</option>
                                <option value="seller" @selected(old('lead_type') === 'seller')>Seller</option>
                            </select>
                            @error('lead_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <input type="hidden" name="source" value="homepage" x-show="inquiryType !== 'home_value'">

                        <div class="sm:col-span-2" x-show="inquiryType === 'home_value'">
                            <label for="address" class="mb-1 block text-sm font-medium lp-title">Address</label>
                            <input id="address" name="address" type="text" value="{{ old('address') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                            @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div x-show="inquiryType === 'home_value'">
                            <label for="city" class="mb-1 block text-sm font-medium lp-title">City</label>
                            <input id="city" name="city" type="text" value="{{ old('city') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                            @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div x-show="inquiryType === 'home_value'">
                            <label for="state" class="mb-1 block text-sm font-medium lp-title">State</label>
                            <select id="state" name="state" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm">
                                <option value="GA" @selected(old('state', 'GA') === 'GA')>GA</option>
                            </select>
                            @error('state')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2" x-show="inquiryType === 'home_value'">
                            <label for="source" class="mb-1 block text-sm font-medium lp-title">How did you find us?</label>
                            <select id="source" name="source" x-model="source" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm">
                                <option value="landing_page" @selected(old('source', 'landing_page') === 'landing_page')>ProspectGoat.com</option>
                                <option value="facebook" @selected(old('source') === 'facebook')>Facebook</option>
                                <option value="instagram" @selected(old('source') === 'instagram')>Instagram</option>
                                <option value="referral" @selected(old('source') === 'referral')>Referral</option>
                                <option value="other" @selected(old('source') === 'other')>Other</option>
                            </select>
                            @error('source')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2" x-show="inquiryType === 'home_value' && source === 'other'">
                            <label for="other_source_detail" class="mb-1 block text-sm font-medium lp-title">Please specify</label>
                            <input id="other_source_detail" name="other_source_detail" type="text" value="{{ old('other_source_detail') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                            @error('other_source_detail')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2" x-show="inquiryType === 'home_value' && source === 'referral'">
                            <div>
                                <label for="referrer_first_name" class="mb-1 block text-sm font-medium lp-title">Referrer First Name</label>
                                <input id="referrer_first_name" name="referrer_first_name" type="text" value="{{ old('referrer_first_name') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                @error('referrer_first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="referrer_last_name" class="mb-1 block text-sm font-medium lp-title">Referrer Last Name</label>
                                <input id="referrer_last_name" name="referrer_last_name" type="text" value="{{ old('referrer_last_name') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                @error('referrer_last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="referrer_email" class="mb-1 block text-sm font-medium lp-title">Referrer Email</label>
                                <input id="referrer_email" name="referrer_email" type="email" value="{{ old('referrer_email') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                @error('referrer_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="referrer_phone" class="mb-1 block text-sm font-medium lp-title">Referrer Phone</label>
                                <input id="referrer_phone" name="referrer_phone" type="text" value="{{ old('referrer_phone') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                @error('referrer_phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-accent">
                                Submit Inquiry
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
