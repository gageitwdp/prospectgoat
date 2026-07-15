<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareTitle = 'Mortgage Calculator - ' . config('app.name', 'ProspectGoat');
            $shareDescription = 'Estimate your monthly payment with the ProspectGoat mortgage calculator.';
        @endphp

        @include('partials.share-meta')

        <title>Mortgage Calculator - {{ config('app.name', 'ProspectGoat') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen py-10 sm:py-16">
        <main class="lp-shell space-y-8" x-data="mortgageCalculator()">
            <section class="lp-card p-7 sm:p-10">
                <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                    <div class="max-w-3xl space-y-2">
                        <p class="text-xs uppercase tracking-[0.35em] lp-muted">Mortgage Calculator</p>
                        <h1 class="lp-title text-2xl font-semibold sm:text-3xl">Plan your monthly payment with confidence.</h1>
                        <p class="lp-muted text-sm sm:text-base">Explore your options, customize your selections, and receive a personalized estimate delivered straight to your inbox.</p>
                    </div>
                    <a href="https://prospectgoat.com/" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">
                        Back Home
                    </a>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-12">
                <div class="lg:col-span-7 space-y-6">
                    <div class="lp-card p-7 sm:p-8">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="lp-title text-xl font-semibold">Loan Inputs</h2>
                                <p class="mt-1 text-sm lp-muted">Move sliders for quick estimates, or type exact values.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="applyPreset('starter')" class="rounded-lg border border-[var(--lp-border)] px-3 py-1.5 text-xs font-medium lp-title">Starter</button>
                                <button type="button" @click="applyPreset('moveup')" class="rounded-lg border border-[var(--lp-border)] px-3 py-1.5 text-xs font-medium lp-title">Move-Up</button>
                                <button type="button" @click="applyPreset('luxury')" class="rounded-lg border border-[var(--lp-border)] px-3 py-1.5 text-xs font-medium lp-title">Luxury</button>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-6 sm:grid-cols-2">
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="home_price" class="block text-sm font-medium lp-title">Home Price</label>
                                    <span class="text-xs lp-muted" x-text="formatCurrency(home_price)"></span>
                                </div>
                                <input id="home_price" x-model.number="home_price" @input="sanitizeValues()" type="number" min="100000" max="2000000" step="1000" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                <input x-model.number="home_price" @input="sanitizeValues()" type="range" min="100000" max="2000000" step="5000" class="mt-3 w-full" style="accent-color: var(--lp-accent);" />
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="down_payment" class="block text-sm font-medium lp-title">Down Payment</label>
                                    <span class="text-xs lp-muted" x-text="formatCurrency(down_payment)"></span>
                                </div>
                                <input id="down_payment" x-model.number="down_payment" @input="sanitizeValues()" type="number" min="0" :max="home_price" step="500" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                <input x-model.number="down_payment" @input="sanitizeValues()" type="range" min="0" :max="home_price" step="1000" class="mt-3 w-full" style="accent-color: var(--lp-accent);" />
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="annual_interest_rate" class="block text-sm font-medium lp-title">Interest Rate</label>
                                    <span class="text-xs lp-muted" x-text="formatPercent(annual_interest_rate)"></span>
                                </div>
                                <input id="annual_interest_rate" x-model.number="annual_interest_rate" @input="sanitizeValues()" type="number" min="0" max="15" step="0.01" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                <input x-model.number="annual_interest_rate" @input="sanitizeValues()" type="range" min="2" max="12" step="0.05" class="mt-3 w-full" style="accent-color: var(--lp-accent);" />
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="loan_term_years" class="block text-sm font-medium lp-title">Loan Term</label>
                                    <span class="text-xs lp-muted" x-text="loan_term_years + ' years'"></span>
                                </div>
                                <select id="loan_term_years" x-model.number="loan_term_years" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm">
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                </select>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="property_tax_rate" class="block text-sm font-medium lp-title">Property Tax Rate</label>
                                    <span class="text-xs lp-muted" x-text="formatPercent(property_tax_rate)"></span>
                                </div>
                                <input id="property_tax_rate" x-model.number="property_tax_rate" @input="sanitizeValues()" type="number" min="0" max="5" step="0.01" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                <input x-model.number="property_tax_rate" @input="sanitizeValues()" type="range" min="0" max="3" step="0.01" class="mt-3 w-full" style="accent-color: var(--lp-accent);" />
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="home_insurance_yearly" class="block text-sm font-medium lp-title">Yearly Insurance</label>
                                    <span class="text-xs lp-muted" x-text="formatCurrency(home_insurance_yearly)"></span>
                                </div>
                                <input id="home_insurance_yearly" x-model.number="home_insurance_yearly" @input="sanitizeValues()" type="number" min="0" max="20000" step="100" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                <input x-model.number="home_insurance_yearly" @input="sanitizeValues()" type="range" min="0" max="10000" step="100" class="mt-3 w-full" style="accent-color: var(--lp-accent);" />
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="hoa_monthly" class="block text-sm font-medium lp-title">HOA Monthly</label>
                                    <span class="text-xs lp-muted" x-text="formatCurrency(hoa_monthly)"></span>
                                </div>
                                <input id="hoa_monthly" x-model.number="hoa_monthly" @input="sanitizeValues()" type="number" min="0" max="3000" step="10" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                <input x-model.number="hoa_monthly" @input="sanitizeValues()" type="range" min="0" max="1500" step="10" class="mt-3 w-full" style="accent-color: var(--lp-accent);" />
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="pmi_monthly" class="block text-sm font-medium lp-title">PMI Monthly</label>
                                    <span class="text-xs lp-muted" x-text="formatCurrency(pmi_monthly)"></span>
                                </div>
                                <input id="pmi_monthly" x-model.number="pmi_monthly" @input="sanitizeValues()" type="number" min="0" max="2000" step="10" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                <input x-model.number="pmi_monthly" @input="sanitizeValues()" type="range" min="0" max="800" step="10" class="mt-3 w-full" style="accent-color: var(--lp-accent);" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 space-y-6">
                    <div class="lp-card p-7 sm:p-8">
                        <p class="text-xs uppercase tracking-[0.2em] lp-muted">Estimated Monthly Payment</p>
                        <p class="mt-2 lp-title text-4xl font-semibold" x-text="formatCurrency(totalMonthly())"></p>

                        <div class="mt-5 rounded-xl border border-[var(--lp-border)] bg-[#f8fafc] p-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="lp-muted">Down Payment</span>
                                <span class="lp-title font-medium" x-text="formatPercent(downPaymentPercent())"></span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-[#e5e9f0]">
                                <div class="h-2 rounded-full bg-[var(--lp-accent)]" :style="'width: ' + downPaymentPercent() + '%'" ></div>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="lp-muted">Estimated LTV</span>
                                <span class="lp-title font-medium" x-text="formatPercent(ltv())"></span>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3 text-sm">
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="lp-muted">Principal & Interest</span>
                                    <span class="lp-title font-medium" x-text="formatCurrency(principalInterestMonthly())"></span>
                                </div>
                                <div class="h-2 rounded-full bg-[#e8edf3]"><div class="h-2 rounded-full bg-[#1e3a5f]" :style="'width: ' + componentShare('pi') + '%'" ></div></div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="lp-muted">Property Tax</span>
                                    <span class="lp-title font-medium" x-text="formatCurrency(propertyTaxMonthly())"></span>
                                </div>
                                <div class="h-2 rounded-full bg-[#e8edf3]"><div class="h-2 rounded-full bg-[#40658b]" :style="'width: ' + componentShare('tax') + '%'" ></div></div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="lp-muted">Home Insurance</span>
                                    <span class="lp-title font-medium" x-text="formatCurrency(homeInsuranceMonthly())"></span>
                                </div>
                                <div class="h-2 rounded-full bg-[#e8edf3]"><div class="h-2 rounded-full bg-[#5f7c9d]" :style="'width: ' + componentShare('ins') + '%'" ></div></div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="lp-muted">HOA</span>
                                    <span class="lp-title font-medium" x-text="formatCurrency(hoa_monthly)"></span>
                                </div>
                                <div class="h-2 rounded-full bg-[#e8edf3]"><div class="h-2 rounded-full bg-[#7b94b0]" :style="'width: ' + componentShare('hoa') + '%'" ></div></div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="lp-muted">PMI</span>
                                    <span class="lp-title font-medium" x-text="formatCurrency(pmi_monthly)"></span>
                                </div>
                                <div class="h-2 rounded-full bg-[#e8edf3]"><div class="h-2 rounded-full bg-[#9ab0c8]" :style="'width: ' + componentShare('pmi') + '%'" ></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="lp-card p-7 sm:p-8">
                        <h2 class="lp-title text-xl font-semibold">Send Results To Email</h2>
                        <p class="mt-2 text-sm lp-muted">We will email your estimate and save your preferences for follow up.</p>

                        @if (session('calculator_success'))
                            <div class="mt-4 rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] p-4 text-sm text-[#2f5f34]">
                                {{ session('calculator_success') }}
                            </div>
                        @endif

                        @if (session('calculator_error'))
                            <div class="mt-4 rounded-xl border border-[#ebd1d1] bg-[#fef4f4] p-4 text-sm text-[#9b2f2f]">
                                {{ session('calculator_error') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('mortgage.calculator.send') }}" class="mt-6 space-y-4">
                            @csrf

                            <div>
                                <label for="full_name" class="mb-1 block text-sm font-medium lp-title">Full Name</label>
                                <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                @error('full_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="mb-1 block text-sm font-medium lp-title">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="phone" class="mb-1 block text-sm font-medium lp-title">Phone (Optional)</label>
                                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="w-full rounded-xl border border-[var(--lp-border)] bg-white px-4 py-2.5 text-sm" />
                                @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <input type="hidden" name="home_price" :value="home_price">
                            <input type="hidden" name="down_payment" :value="down_payment">
                            <input type="hidden" name="annual_interest_rate" :value="annual_interest_rate">
                            <input type="hidden" name="loan_term_years" :value="loan_term_years">
                            <input type="hidden" name="property_tax_rate" :value="property_tax_rate">
                            <input type="hidden" name="home_insurance_yearly" :value="home_insurance_yearly">
                            <input type="hidden" name="hoa_monthly" :value="hoa_monthly">
                            <input type="hidden" name="pmi_monthly" :value="pmi_monthly">

                            <button type="submit" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-medium lp-btn-accent">
                                Send My Results
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <div aria-hidden="true" style="height: 5rem;"></div>

        @include('components.site-footer-card')

        <script>
            function mortgageCalculator() {
                return {
                    home_price: Number(@json(old('home_price', 500000))),
                    down_payment: Number(@json(old('down_payment', 100000))),
                    annual_interest_rate: Number(@json(old('annual_interest_rate', 6.75))),
                    loan_term_years: Number(@json(old('loan_term_years', 30))),
                    property_tax_rate: Number(@json(old('property_tax_rate', 1.2))),
                    home_insurance_yearly: Number(@json(old('home_insurance_yearly', 1800))),
                    hoa_monthly: Number(@json(old('hoa_monthly', 0))),
                    pmi_monthly: Number(@json(old('pmi_monthly', 0))),

                    sanitizeValues() {
                        this.home_price = Math.max(100000, Number(this.home_price || 0));
                        this.down_payment = Math.max(0, Math.min(Number(this.down_payment || 0), this.home_price));
                        this.annual_interest_rate = Math.max(0, Number(this.annual_interest_rate || 0));
                        this.property_tax_rate = Math.max(0, Number(this.property_tax_rate || 0));
                        this.home_insurance_yearly = Math.max(0, Number(this.home_insurance_yearly || 0));
                        this.hoa_monthly = Math.max(0, Number(this.hoa_monthly || 0));
                        this.pmi_monthly = Math.max(0, Number(this.pmi_monthly || 0));
                    },

                    applyPreset(type) {
                        if (type === 'starter') {
                            this.home_price = 320000;
                            this.down_payment = 32000;
                            this.annual_interest_rate = 6.4;
                            this.loan_term_years = 30;
                            this.property_tax_rate = 1.1;
                            this.home_insurance_yearly = 1500;
                            this.hoa_monthly = 45;
                            this.pmi_monthly = 180;
                        }

                        if (type === 'moveup') {
                            this.home_price = 550000;
                            this.down_payment = 110000;
                            this.annual_interest_rate = 6.2;
                            this.loan_term_years = 30;
                            this.property_tax_rate = 1.2;
                            this.home_insurance_yearly = 2100;
                            this.hoa_monthly = 85;
                            this.pmi_monthly = 0;
                        }

                        if (type === 'luxury') {
                            this.home_price = 900000;
                            this.down_payment = 225000;
                            this.annual_interest_rate = 6.1;
                            this.loan_term_years = 30;
                            this.property_tax_rate = 1.25;
                            this.home_insurance_yearly = 3200;
                            this.hoa_monthly = 220;
                            this.pmi_monthly = 0;
                        }

                        this.sanitizeValues();
                    },

                    principal() {
                        return Math.max(0, this.home_price - this.down_payment);
                    },

                    monthlyRate() {
                        return (this.annual_interest_rate / 100) / 12;
                    },

                    totalPayments() {
                        return Math.max(1, this.loan_term_years * 12);
                    },

                    principalInterestMonthly() {
                        const principal = this.principal();
                        const rate = this.monthlyRate();
                        const payments = this.totalPayments();

                        if (rate <= 0) {
                            return principal / payments;
                        }

                        const growth = Math.pow(1 + rate, payments);
                        return principal * ((rate * growth) / (growth - 1));
                    },

                    propertyTaxMonthly() {
                        return (this.home_price * (this.property_tax_rate / 100)) / 12;
                    },

                    homeInsuranceMonthly() {
                        return this.home_insurance_yearly / 12;
                    },

                    totalMonthly() {
                        return this.principalInterestMonthly() + this.propertyTaxMonthly() + this.homeInsuranceMonthly() + this.hoa_monthly + this.pmi_monthly;
                    },

                    downPaymentPercent() {
                        if (this.home_price <= 0) {
                            return 0;
                        }

                        return Math.min(100, (this.down_payment / this.home_price) * 100);
                    },

                    ltv() {
                        if (this.home_price <= 0) {
                            return 0;
                        }

                        return Math.max(0, 100 - this.downPaymentPercent());
                    },

                    componentShare(component) {
                        const total = this.totalMonthly();

                        if (total <= 0) {
                            return 0;
                        }

                        const shares = {
                            pi: (this.principalInterestMonthly() / total) * 100,
                            tax: (this.propertyTaxMonthly() / total) * 100,
                            ins: (this.homeInsuranceMonthly() / total) * 100,
                            hoa: (this.hoa_monthly / total) * 100,
                            pmi: (this.pmi_monthly / total) * 100,
                        };

                        return Math.max(0, Math.min(100, shares[component] || 0));
                    },

                    formatCurrency(value) {
                        return new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: 'USD',
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        }).format(Number(value || 0));
                    },

                    formatPercent(value) {
                        return `${Number(value || 0).toFixed(2)}%`;
                    },
                };
            }
        </script>
    </body>
</html>
