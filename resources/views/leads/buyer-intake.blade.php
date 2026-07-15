<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareTitle = 'Buyer Intake - ' . config('app.name', 'ProspectGoat');
            $shareDescription = 'Complete the buyer intake form with ProspectGoat.';
        @endphp

        @include('partials.share-meta')

        <title>Buyer Intake - {{ config('app.name', 'ProspectGoat') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen py-10 sm:py-16">
        @php
            $initialStep = 1;
            $stepFieldGroups = [
                ['move_timeline', 'move_if_not_found'],
                ['price_range', 'mortgage_preapproval_status'],
                ['need_to_sell_current_home', 'agent_relationship'],
                ['purchase_reason', 'target_areas', 'min_bedrooms', 'min_bathrooms'],
                ['preferred_contact_method', 'email', 'phone'],
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
                validateStep(stepNumber) {
                    const section = this.$refs.intakeForm.querySelector(`[data-step='${stepNumber}']`);

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
                    if (this.validateStep(this.step)) {
                        this.step = nextStep;
                    }
                },
                handleSubmit(event) {
                    if (this.step < this.steps) {
                        event.preventDefault();

                        if (this.validateStep(this.step)) {
                            this.step += 1;
                        }

                        return;
                    }

                    for (let stepNumber = 1; stepNumber <= this.steps; stepNumber += 1) {
                        if (!this.validateStep(stepNumber)) {
                            event.preventDefault();
                            this.step = stepNumber;
                            return;
                        }
                    }

                    this.$refs.intakeForm.submit();
                },
            }"
        >
            <section class="lp-card p-7 sm:p-10">
                <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                    <div class="max-w-3xl space-y-3">
                        <p class="text-xs uppercase tracking-[0.35em] lp-muted">Buyer Intake</p>
                        <h1 class="lp-title text-3xl font-semibold leading-tight sm:text-5xl">Let’s find the right home, at the right pace for you.</h1>
                        <p class="lp-muted text-base sm:text-lg">Share a few details about your goals, and we’ll tailor your search with homes, neighborhoods, and next steps that fit.</p>
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
                            <h2 class="lp-title text-xl font-semibold">Buyer Profile</h2>
                            <p class="mt-2 text-sm lp-muted">This takes about two minutes, and we’ll guide you step by step.</p>
                        </div>

                        @if (session('lead_success'))
                            <div class="rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] px-4 py-3 text-sm text-[#2f5f34]">
                                {{ session('lead_success') }}
                            </div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('buyers.intake.store') }}" class="mt-6 space-y-6" x-ref="intakeForm" @submit="handleSubmit($event)">
                        @csrf

                        <section x-cloak x-show="step === 1" data-step="1" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">1. Your timeline</h3>
                            </div>

                            <div>
                                <label for="move_timeline" class="mb-1 block text-sm font-medium lp-title">When would you ideally like to move into your next home?</label>
                                <select id="move_timeline" name="move_timeline" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="immediately_30_days" @selected(old('move_timeline') === 'immediately_30_days')>Immediately (within 30 days)</option>
                                    <option value="one_to_three_months" @selected(old('move_timeline') === 'one_to_three_months')>1-3 months</option>
                                    <option value="three_to_six_months" @selected(old('move_timeline') === 'three_to_six_months')>3-6 months</option>
                                    <option value="just_browsing" @selected(old('move_timeline') === 'just_browsing')>Just browsing</option>
                                </select>
                                @error('move_timeline')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="move_if_not_found" class="mb-1 block text-sm font-medium lp-title">If you don’t find the right home by then, what is most likely?</label>
                                <select id="move_if_not_found" name="move_if_not_found" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="must_move" @selected(old('move_if_not_found') === 'must_move')>My lease ends, and I need to move</option>
                                    <option value="stay_where_i_am" @selected(old('move_if_not_found') === 'stay_where_i_am')>I can stay where I am for now</option>
                                    <option value="continue_renting" @selected(old('move_if_not_found') === 'continue_renting')>I would continue renting</option>
                                </select>
                                @error('move_if_not_found')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2 flex justify-end">
                                <button type="button" @click="advanceStep(2)" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-primary">Continue</button>
                            </div>
                        </section>

                        <section x-cloak x-show="step === 2" data-step="2" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">2. Budget and financing</h3>
                            </div>

                            <div>
                                <label for="price_range" class="mb-1 block text-sm font-medium lp-title">What price range feels most comfortable for you?</label>
                                <select id="price_range" name="price_range" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="under_300k" @selected(old('price_range') === 'under_300k')>Under $300k</option>
                                    <option value="300k_400k" @selected(old('price_range') === '300k_400k')>$300k-$400k</option>
                                    <option value="400k_500k" @selected(old('price_range') === '400k_500k')>$400k-$500k</option>
                                    <option value="500k_650k" @selected(old('price_range') === '500k_650k')>$500k-$650k</option>
                                    <option value="650k_plus" @selected(old('price_range') === '650k_plus')>$650k+</option>
                                </select>
                                @error('price_range')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="mortgage_preapproval_status" class="mb-1 block text-sm font-medium lp-title">Where are you in the mortgage process?</label>
                                <select id="mortgage_preapproval_status" name="mortgage_preapproval_status" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="pre_approved" @selected(old('mortgage_preapproval_status') === 'pre_approved')>I have a pre-approval letter</option>
                                    <option value="ready_to_talk" @selected(old('mortgage_preapproval_status') === 'ready_to_talk')>I'm ready to talk to a lender</option>
                                    <option value="cash" @selected(old('mortgage_preapproval_status') === 'cash')>I’m paying cash</option>
                                    <option value="not_ready" @selected(old('mortgage_preapproval_status') === 'not_ready')>I'm not ready yet</option>
                                </select>
                                @error('mortgage_preapproval_status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2 flex justify-between gap-3">
                                <button type="button" @click="step = 1" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-5 py-3 text-sm font-medium lp-title">Back</button>
                                <button type="button" @click="advanceStep(3)" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-primary">Continue</button>
                            </div>
                        </section>

                        <section x-cloak x-show="step === 3" data-step="3" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">3. Current situation</h3>
                            </div>

                            <div>
                                <label for="need_to_sell_current_home" class="mb-1 block text-sm font-medium lp-title">Will you need to sell a home before buying your next one?</label>
                                <select id="need_to_sell_current_home" name="need_to_sell_current_home" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="yes" @selected(old('need_to_sell_current_home') === 'yes')>Yes</option>
                                    <option value="no" @selected(old('need_to_sell_current_home') === 'no')>No</option>
                                    <option value="renting" @selected(old('need_to_sell_current_home') === 'renting')>I’m currently renting</option>
                                </select>
                                @error('need_to_sell_current_home')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="agent_relationship" class="mb-1 block text-sm font-medium lp-title">Do you currently have any signed agreement with a real estate agent?</label>
                                <select id="agent_relationship" name="agent_relationship" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="exclusive" @selected(old('agent_relationship') === 'exclusive')>Yes</option>
                                    <option value="none" @selected(old('agent_relationship') === 'none')>No</option>
                                </select>
                                @error('agent_relationship')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2 flex justify-between gap-3">
                                <button type="button" @click="step = 2" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-5 py-3 text-sm font-medium lp-title">Back</button>
                                <button type="button" @click="advanceStep(4)" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-primary">Continue</button>
                            </div>
                        </section>

                        <section x-cloak x-show="step === 4" data-step="4" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">4. What you are looking for</h3>
                            </div>

                            <div>
                                <label for="purchase_reason" class="mb-1 block text-sm font-medium lp-title">What is your main reason for buying right now?</label>
                                <select id="purchase_reason" name="purchase_reason" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>
                                    <option value="">Select one</option>
                                    <option value="first_time_homebuyer" @selected(old('purchase_reason') === 'first_time_homebuyer')>First-time homebuyer</option>
                                    <option value="relocating_for_work" @selected(old('purchase_reason') === 'relocating_for_work')>Relocating for work</option>
                                    <option value="upgrading_downsizing" @selected(old('purchase_reason') === 'upgrading_downsizing')>Upsizing or downsizing</option>
                                    <option value="investing" @selected(old('purchase_reason') === 'investing')>Real estate investing</option>
                                </select>
                                @error('purchase_reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="target_areas" class="mb-1 block text-sm font-medium lp-title">Which areas or neighborhoods would you like to focus on?</label>
                                <textarea id="target_areas" name="target_areas" rows="3" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required>{{ old('target_areas') }}</textarea>
                                @error('target_areas')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="min_bedrooms" class="mb-1 block text-sm font-medium lp-title">Minimum bedrooms you need</label>
                                <input id="min_bedrooms" name="min_bedrooms" type="number" min="0" max="20" value="{{ old('min_bedrooms') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                @error('min_bedrooms')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="min_bathrooms" class="mb-1 block text-sm font-medium lp-title">Minimum bathrooms you need</label>
                                <input id="min_bathrooms" name="min_bathrooms" type="number" min="0" max="20" step="0.5" value="{{ old('min_bathrooms') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                @error('min_bathrooms')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2 flex justify-between gap-3">
                                <button type="button" @click="step = 3" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-5 py-3 text-sm font-medium lp-title">Back</button>
                                <button type="button" @click="advanceStep(5)" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-primary">Continue</button>
                            </div>
                        </section>

                        <section x-cloak x-show="step === 5" data-step="5" class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h3 class="lp-title text-base font-semibold">5. How we can reach you</h3>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="mb-2 block text-sm font-medium lp-title">What is the best way to share matching homes with you?</p>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <label class="inline-flex items-center gap-2 rounded-xl border border-[var(--lp-border)] bg-white px-4 py-3 text-sm lp-title">
                                        <input type="radio" name="preferred_contact_method" value="email" @checked(old('preferred_contact_method', 'email') === 'email') required>
                                        <span>Email</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 rounded-xl border border-[var(--lp-border)] bg-white px-4 py-3 text-sm lp-title">
                                        <input type="radio" name="preferred_contact_method" value="text" @checked(old('preferred_contact_method') === 'text') required>
                                        <span>Text message</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 rounded-xl border border-[var(--lp-border)] bg-white px-4 py-3 text-sm lp-title">
                                        <input type="radio" name="preferred_contact_method" value="phone" @checked(old('preferred_contact_method') === 'phone') required>
                                        <span>Phone call</span>
                                    </label>
                                </div>
                                @error('preferred_contact_method')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
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
                                    <label for="email" class="mb-1 block text-sm font-medium lp-title">Email</label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="phone" class="mb-1 block text-sm font-medium lp-title">Phone number</label>
                                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" required />
                                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="sm:col-span-2 flex justify-between gap-3">
                                <button type="button" @click="step = 4" class="inline-flex items-center justify-center rounded-xl border border-[var(--lp-border)] px-5 py-3 text-sm font-medium lp-title">Back</button>
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-accent">Send my buyer profile</button>
                            </div>
                        </section>
                    </form>

                    <div class="mt-6 border-t border-[var(--lp-border)] pt-4 text-center text-xs lp-muted">
                        Prefer a different path? <a href="{{ route('leads.intake') }}" class="underline underline-offset-4">General inquiry</a>.
                    </div>
                </div>
            </section>
        </main>

        <div aria-hidden="true" style="height: 5rem;"></div>

        @include('components.site-footer-card')
    </body>
</html>