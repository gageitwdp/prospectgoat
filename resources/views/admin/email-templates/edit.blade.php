<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Edit Email Template') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="space-y-6">
                @php
                    $defaultLeadType = match ($template->key) {
                        'new_lead_buyer_qualification' => 'buyer',
                        'new_lead_seller_profile' => 'seller',
                        'new_lead_seller' => 'seller',
                        'new_lead_home_value' => 'home_value',
                        default => 'buyer',
                    };

                    $selectedLeadType = old('lead_type', $defaultLeadType);
                @endphp

                @if (session('status'))
                    <div class="rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] p-4 text-sm text-[#2f5f34]">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="lp-card p-6 sm:p-8">
                    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Template Key</p>
                    <h1 class="mt-1 text-2xl font-semibold lp-title">{{ $template->name }}</h1>
                    <p class="mt-2 text-sm lp-muted">{{ $template->key }}</p>

                    <form method="POST" action="{{ route('admin.email-templates.update', $template) }}" class="mt-6 grid gap-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Template Name</label>
                            <input name="name" type="text" value="{{ old('name', $template->name) }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Subject</label>
                            <input name="subject" type="text" value="{{ old('subject', $template->subject) }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                            @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Body</label>
                            <textarea name="body" rows="18" class="w-full rounded-2xl border border-[var(--lp-border)] px-4 py-3 text-sm leading-6">{{ old('body', $template->body) }}</textarea>
                            @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-2 text-xs lp-muted">Use tokens in this format: @{{token_name}}</p>
                        </div>

                        <div class="rounded-2xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] p-4">
                            <p class="text-xs uppercase tracking-[0.2em] lp-muted">Template Tokens</p>

                            <div class="mt-3 space-y-3">
                                <div>
                                    <p class="text-xs font-semibold lp-title">All Inquiries</p>
                                    <p class="mt-1 text-sm lp-title">
                                        @foreach ($templateTokens['all_inquiries'] as $token)
                                            {{ '{'.'{' . $token . '}'.'}' }}@if (! $loop->last), @endif
                                        @endforeach
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold lp-title">Home Value Only</p>
                                    <p class="mt-1 text-sm lp-title">
                                        @foreach ($templateTokens['home_value_only'] as $token)
                                            {{ '{'.'{' . $token . '}'.'}' }}@if (! $loop->last), @endif
                                        @endforeach
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold lp-title">Source = Other</p>
                                    <p class="mt-1 text-sm lp-title">
                                        @foreach ($templateTokens['source_other_only'] as $token)
                                            {{ '{'.'{' . $token . '}'.'}' }}@if (! $loop->last), @endif
                                        @endforeach
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold lp-title">Source = Referral</p>
                                    <p class="mt-1 text-sm lp-title">
                                        @foreach ($templateTokens['source_referral_only'] as $token)
                                            {{ '{'.'{' . $token . '}'.'}' }}@if (! $loop->last), @endif
                                        @endforeach
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold lp-title">Seller Profile</p>
                                    <p class="mt-1 text-sm lp-title">
                                        @foreach ($templateTokens['seller_qualification_only'] as $token)
                                            {{ '{'.'{' . $token . '}'.'}' }}@if (! $loop->last), @endif
                                        @endforeach
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold lp-title">Buyer Qualification</p>
                                    <p class="mt-1 text-sm lp-title">
                                        @foreach ($templateTokens['buyer_qualification_only'] as $token)
                                            {{ '{'.'{' . $token . '}'.'}' }}@if (! $loop->last), @endif
                                        @endforeach
                                    </p>
                                </div>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm lp-muted">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                            Template is active
                        </label>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">Save Template</button>
                            <a href="{{ route('admin.email-templates.index') }}" class="rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm lp-title">Back to Templates</a>
                        </div>
                    </form>
                </div>

                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="lp-card p-6">
                        <p class="text-xs uppercase tracking-[0.2em] lp-muted">Preview</p>
                        <h2 class="mt-1 text-xl font-semibold lp-title">Rendered Email</h2>
                        <div class="mt-4 rounded-2xl border border-[var(--lp-border)] bg-white p-5 text-sm leading-7 text-[var(--lp-text-primary)]">
                            {!! $previewHtml !!}
                        </div>
                    </div>

                    <div class="lp-card p-6">
                        <p class="text-xs uppercase tracking-[0.2em] lp-muted">Test Send</p>
                        <h2 class="mt-1 text-xl font-semibold lp-title">Send a Test Email</h2>

                        <form method="POST" action="{{ route('admin.email-templates.test', $template) }}" class="mt-4 grid gap-4">
                            @csrf

                            <div>
                                <label class="mb-1 block text-sm font-medium lp-title">Recipient Email</label>
                                <input name="recipient_email" type="email" value="{{ old('recipient_email', auth()->user()->email) }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                                @error('recipient_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium lp-title">First Name</label>
                                <input name="first_name" type="text" value="{{ old('first_name', 'Taylor') }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                                @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium lp-title">Inquiry Type</label>
                                <select name="lead_type" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                                    <option value="buyer" {{ $selectedLeadType === 'buyer' ? 'selected' : '' }}>Buyer</option>
                                    <option value="seller" {{ $selectedLeadType === 'seller' ? 'selected' : '' }}>Seller</option>
                                    <option value="home_value" {{ $selectedLeadType === 'home_value' ? 'selected' : '' }}>Home Value</option>
                                    <option value="generic_inquiry" {{ old('lead_type') === 'generic_inquiry' ? 'selected' : '' }}>Generic Inquiry</option>
                                </select>
                                @error('lead_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium lp-title">Source</label>
                                <select name="source" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                                    <option value="homepage" {{ old('source', 'homepage') === 'homepage' ? 'selected' : '' }}>Homepage</option>
                                    <option value="landing_page" {{ old('source') === 'landing_page' ? 'selected' : '' }}>Landing Page</option>
                                    <option value="facebook" {{ old('source') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                                    <option value="instagram" {{ old('source') === 'instagram' ? 'selected' : '' }}>Instagram</option>
                                    <option value="referral" {{ old('source') === 'referral' ? 'selected' : '' }}>Referral</option>
                                    <option value="other" {{ old('source') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('source') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-accent">Send Test Email</button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>

</x-app-layout>