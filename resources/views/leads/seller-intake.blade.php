<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareTitle = 'Seller Intake - ' . config('app.name', 'Lezin Properties');
            $shareDescription = 'Complete the seller intake form with Lezin Properties.';
        @endphp

        @include('partials.share-meta')

        <title>Seller Intake - {{ config('app.name', 'Lezin Properties') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen py-10 sm:py-16">
        @php
            $initialStep = 1;
            $stepFieldGroups = [
                ['seller_timeline', 'seller_motivation'],
                ['seller_estimated_home_value', 'seller_mortgage_status', 'seller_needs_to_buy_another_home_after_selling'],
                ['seller_property_condition', 'seller_major_upgrades'],
                ['seller_agent_commitment', 'seller_occupancy_status'],
                ['seller_valuation_delivery_method'],
            ];

            foreach ($stepFieldGroups as $index => $fields) {
                foreach ($fields as $field) {
                    if ($errors->has($field)) {
                        $initialStep = $index + 1;
                        break 2;
                    }
                }
            }
        @endphp

        <main
            class="lp-shell space-y-8"
            x-data="{
                step: {{ $initialStep }},
                steps: 5,
                validateSection(sectionKey) {
                    const section = this.$refs.sellerForm.querySelector(`[data-step='${sectionKey}']`);

                    if (!section) {
                        return true;
                    }

                    const fields = [...section.querySelectorAll('input, select, textarea')].filter((field) => field.type !== 'hidden' && field.type !== 'button' && field.type !== 'submit');

                    for (const field of fields) {
                        if (!field.checkValidity()) {
                            field.reportValidity();
                            return false;
                        }
                    }

                    return true;
                },
                advanceStep(nextStep) {
                    if (this.step === 1 && ! this.validateSection('contact')) {
                        return;
                    }

                    if (this.validateSection(this.step)) {
                        this.step = nextStep;
                    }
                },
                handleSubmit(event) {
                    if (this.step < this.steps) {
                        event.preventDefault();

                        if (this.validateSection(this.step)) {
                            this.step += 1;
                        }

                        return;
                    }

                    for (const sectionKey of ['contact', 1, 2, 3, 4, 5]) {
                        if (!this.validateSection(sectionKey)) {
                            event.preventDefault();
                            this.step = sectionKey === 'contact' ? 1 : sectionKey;
                            return;
                        }
                    }

                    this.$refs.sellerForm.submit();
                },
            }"
        >
            <section class="lp-card p-7 sm:p-10">
                <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                    <div class="max-w-3xl space-y-3">
                        <p class="text-xs uppercase tracking-[0.35em] lp-muted">Seller Intake</p>
                        <h1 class="lp-title text-3xl font-semibold leading-tight sm:text-5xl">Let’s present your home with clarity and confidence.</h1>
                        <p class="lp-muted text-base sm:text-lg">Share a few details about your home, and we’ll tailor your valuation, timing, and next steps to fit your goals.</p>
                    </div>
                </div>

                <div class="mt-7">
                    <div class="flex items-center justify-between text-xs uppercase tracking-[0.3em] lp-muted">
                        <span>Progress</span>
                        <span x-text="`${step} / ${steps}`"></span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-[color:rgba(31,41,51,0.08)]">
                        <div class="h-full rounded-full lp-btn-accent transition-all duration-300" :style="`width: ${(step / steps) * 100}%`"></div>
                    </div>
                </div>
            </section>

            <section>
                <div class="lp-card p-7 sm:p-8">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="lp-title text-xl font-semibold">Seller Profile</h2>
                            <p class="mt-2 text-sm lp-muted">Start with your contact details, then we’ll keep the rest simple and guide you step by step.</p>
                        </div>

                        @if (session('lead_success'))
                            <div class="rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] px-4 py-3 text-sm text-[#2f5f34]">
                                {{ session('lead_success') }}
                            </div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('sellers.intake.store') }}" class="mt-6 space-y-6" x-ref="sellerForm" @submit="handleSubmit($event)">
                        @csrf

                        <section data-step="contact" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">Contact details</h3>
                                <p class="mt-2 text-sm lp-muted">We’ll use this to prepare your valuation report and follow up with you.</p>
                            </div>

                            <div>
                                <label for="first_name" class="mb-1 block text-sm font-medium lp-title">First name</label>
                                <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="last_name" class="mb-1 block text-sm font-medium lp-title">Last name</label>
                                <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="phone" class="mb-1 block text-sm font-medium lp-title">Phone number</label>
                                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="mb-1 block text-sm font-medium lp-title">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address" class="mb-1 block text-sm font-medium lp-title">Property address</label>
                                <input id="address" name="address" type="text" value="{{ old('address') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </section>

                        <section x-cloak x-show="step === 1" data-step="1" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">1. Timeline & urgency</h3>
                            </div>

                            <div>
                                <label for="seller_timeline" class="mb-1 block text-sm font-medium lp-title">How soon do you need to sell your home?</label>
                                <select id="seller_timeline" name="seller_timeline" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="immediately_30_days" @selected(old('seller_timeline') === 'immediately_30_days')>Immediately (within 30 days)</option>
                                    <option value="one_to_three_months" @selected(old('seller_timeline') === 'one_to_three_months')>1–3 months</option>
                                    <option value="three_to_six_months" @selected(old('seller_timeline') === 'three_to_six_months')>3–6 months</option>
                                    <option value="just_curious" @selected(old('seller_timeline') === 'just_curious')>Just curious about my home's value</option>
                                </select>
                                @error('seller_timeline')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="seller_motivation" class="mb-1 block text-sm font-medium lp-title">What is driving your decision to move?</label>
                                <select id="seller_motivation" name="seller_motivation" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="relocating_for_work" @selected(old('seller_motivation') === 'relocating_for_work')>Relocating for work</option>
                                    <option value="downsizing_upgrading" @selected(old('seller_motivation') === 'downsizing_upgrading')>Downsizing / upgrading</option>
                                    <option value="financial_reasons" @selected(old('seller_motivation') === 'financial_reasons')>Financial reasons</option>
                                    <option value="estate_inheritance" @selected(old('seller_motivation') === 'estate_inheritance')>Estate / inheritance</option>
                                    <option value="testing_market" @selected(old('seller_motivation') === 'testing_market')>Just testing the market</option>
                                </select>
                                @error('seller_motivation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2 flex justify-between gap-3">
                                <span></span>
                                <button type="button" @click="advanceStep(2)" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-primary">Continue</button>
                            </div>
                        </section>

                        <section x-cloak x-show="step === 2" data-step="2" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">2. Property & financial condition</h3>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="seller_estimated_home_value" class="mb-1 block text-sm font-medium lp-title">What do you estimate your home is worth?</label>
                                <input id="seller_estimated_home_value" name="seller_estimated_home_value" type="text" value="{{ old('seller_estimated_home_value') }}" placeholder="A ballpark estimate is perfectly fine" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                @error('seller_estimated_home_value')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="seller_mortgage_status" class="mb-1 block text-sm font-medium lp-title">Do you currently have a mortgage on the property?</label>
                                <select id="seller_mortgage_status" name="seller_mortgage_status" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="yes" @selected(old('seller_mortgage_status') === 'yes')>Yes, I have a mortgage</option>
                                    <option value="no" @selected(old('seller_mortgage_status') === 'no')>No, it’s owned free and clear</option>
                                </select>
                                @error('seller_mortgage_status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="seller_needs_to_buy_another_home_after_selling" class="mb-1 block text-sm font-medium lp-title">Do you need to buy another home after selling this one?</label>
                                <select id="seller_needs_to_buy_another_home_after_selling" name="seller_needs_to_buy_another_home_after_selling" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="yes_local" @selected(old('seller_needs_to_buy_another_home_after_selling') === 'yes_local')>Yes, I need to buy locally</option>
                                    <option value="yes_relocating" @selected(old('seller_needs_to_buy_another_home_after_selling') === 'yes_relocating')>Yes, I’m relocating out of the area</option>
                                    <option value="no" @selected(old('seller_needs_to_buy_another_home_after_selling') === 'no')>No, I already have a place</option>
                                </select>
                                @error('seller_needs_to_buy_another_home_after_selling')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2 flex justify-between gap-3">
                                <button type="button" @click="step = 1" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-5 py-3 text-sm font-medium lp-title">Back</button>
                                <button type="button" @click="advanceStep(3)" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-primary">Continue</button>
                            </div>
                        </section>

                        <section x-cloak x-show="step === 3" data-step="3" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">3. Home condition & readiness</h3>
                            </div>

                            <div>
                                <label for="seller_property_condition" class="mb-1 block text-sm font-medium lp-title">What is the current condition of the property?</label>
                                <select id="seller_property_condition" name="seller_property_condition" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="excellent" @selected(old('seller_property_condition') === 'excellent')>Move-in ready / Excellent</option>
                                    <option value="minor_tlc" @selected(old('seller_property_condition') === 'minor_tlc')>Needs minor TLC (paint, carpet)</option>
                                    <option value="significant_repairs" @selected(old('seller_property_condition') === 'significant_repairs')>Needs significant repairs</option>
                                    <option value="fixer_upper" @selected(old('seller_property_condition') === 'fixer_upper')>Fixer-upper</option>
                                </select>
                                @error('seller_property_condition')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="seller_major_upgrades" class="mb-1 block text-sm font-medium lp-title">Have you made any major upgrades in the last 5 years?</label>
                                <textarea id="seller_major_upgrades" name="seller_major_upgrades" rows="4" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" placeholder="Roof, HVAC, kitchen, flooring, and similar updates...">{{ old('seller_major_upgrades') }}</textarea>
                                @error('seller_major_upgrades')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2 flex justify-between gap-3">
                                <button type="button" @click="step = 2" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-5 py-3 text-sm font-medium lp-title">Back</button>
                                <button type="button" @click="advanceStep(4)" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-primary">Continue</button>
                            </div>
                        </section>

                        <section x-cloak x-show="step === 4" data-step="4" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">4. Agent relationship & occupancy</h3>
                            </div>

                            <div>
                                <label for="seller_agent_commitment" class="mb-1 block text-sm font-medium lp-title">Are you currently committed to another real estate agent?</label>
                                <select id="seller_agent_commitment" name="seller_agent_commitment" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="no" @selected(old('seller_agent_commitment') === 'no')>No, I’m looking for an agent</option>
                                    <option value="listed" @selected(old('seller_agent_commitment') === 'listed')>Yes, I’m currently listed</option>
                                    <option value="fsbo" @selected(old('seller_agent_commitment') === 'fsbo')>I’m considering selling it myself (FSBO)</option>
                                </select>
                                @error('seller_agent_commitment')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="seller_occupancy_status" class="mb-1 block text-sm font-medium lp-title">Are you currently living in the property?</label>
                                <select id="seller_occupancy_status" name="seller_occupancy_status" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="primary_residence" @selected(old('seller_occupancy_status') === 'primary_residence')>Yes, it’s my primary residence</option>
                                    <option value="vacant" @selected(old('seller_occupancy_status') === 'vacant')>No, it’s vacant</option>
                                    <option value="rented_to_tenants" @selected(old('seller_occupancy_status') === 'rented_to_tenants')>No, it’s currently rented to tenants</option>
                                </select>
                                @error('seller_occupancy_status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2 flex justify-between gap-3">
                                <button type="button" @click="step = 3" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-5 py-3 text-sm font-medium lp-title">Back</button>
                                <button type="button" @click="advanceStep(5)" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-primary">Continue</button>
                            </div>
                        </section>

                        <section x-cloak x-show="step === 5" data-step="5" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">5. Next steps</h3>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="mb-2 block text-sm font-medium lp-title">What is the best way to deliver your home valuation report?</p>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <label class="inline-flex items-center gap-2 rounded-xl border border-[var(--lp-border)] bg-white px-4 py-3 text-sm lp-title">
                                        <input type="radio" name="seller_valuation_delivery_method" value="email" @checked(old('seller_valuation_delivery_method', 'email') === 'email') required>
                                        <span>Email me the report</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 rounded-xl border border-[var(--lp-border)] bg-white px-4 py-3 text-sm lp-title">
                                        <input type="radio" name="seller_valuation_delivery_method" value="text" @checked(old('seller_valuation_delivery_method') === 'text') required>
                                        <span>Text me the highlights</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 rounded-xl border border-[var(--lp-border)] bg-white px-4 py-3 text-sm lp-title">
                                        <input type="radio" name="seller_valuation_delivery_method" value="phone" @checked(old('seller_valuation_delivery_method') === 'phone') required>
                                        <span>Let’s schedule a brief 15-minute phone call</span>
                                    </label>
                                </div>
                                @error('seller_valuation_delivery_method')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2 flex justify-between gap-3">
                                <button type="button" @click="step = 4" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-5 py-3 text-sm font-medium lp-title">Back</button>
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-accent">Send my seller profile</button>
                            </div>
                        </section>
                    </form>

                    <div class="mt-6 border-t border-[var(--lp-border)] pt-4 text-center text-xs lp-muted">
                        Prefer a different path? <a href="{{ route('buyers.intake') }}" class="underline underline-offset-4">Buyer intake</a> or <a href="{{ route('leads.intake') }}" class="underline underline-offset-4">general inquiry</a>.
                    </div>
                </div>
            </section>
        </main>

        <div aria-hidden="true" style="height: 5rem;"></div>

        @include('components.site-footer-card')
    </body>
</html>