<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold lp-title">Lead Detail</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('manager.leads.index') }}" class="text-sm lp-muted underline">Back to all leads</a>
                @if (auth()->user() && ! auth()->user()->isGlobalAdmin())
                    <form
                        method="POST"
                        action="{{ route('manager.leads.destroy', $lead) }}"
                        onsubmit="return confirm('Delete this lead and all related history/tasks? This cannot be undone.');"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                            Delete Lead
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="lp-shell grid gap-6 px-2 sm:px-0 lg:grid-cols-[1.2fr_1fr]">
        <section class="space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] p-4 text-sm text-[#2f5f34]">
                    {{ session('status') }}
                </div>
            @endif

            <article class="lp-card p-6">
                <h3 class="lp-title text-lg font-semibold">Lead Overview</h3>

                <form method="POST" action="{{ route('manager.leads.update', $lead) }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Name</label>
                        <input name="name" type="text" value="{{ old('name', $lead->name) }}" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Email</label>
                            <input name="email" type="email" value="{{ old('email', $lead->email) }}" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Phone</label>
                            <input name="phone" type="text" value="{{ old('phone', $lead->phone) }}" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Address</label>
                        <input name="address" type="text" value="{{ old('address', $lead->address) }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Lead Type</label>
                        <select name="lead_type" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                            @foreach (['home_value', 'buyer', 'seller', 'generic_inquiry'] as $type)
                                <option value="{{ $type }}" @selected(old('lead_type', $lead->lead_type) === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Source</label>
                        <select name="source" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                            @foreach (['homepage', 'landing_page', 'referral'] as $source)
                                <option value="{{ $source }}" @selected(old('source', $lead->source) === $source)>{{ ucwords(str_replace('_', ' ', $source)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Status</label>
                        <select name="status" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" required>
                            @foreach (['new', 'contacted', 'qualified', 'active', 'closed'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $lead->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title">Assigned To</label>
                        <select name="assigned_to" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                            <option value="">Unassigned</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}" @selected((string) old('assigned_to', $lead->assigned_to) === (string) $manager->id)>{{ $manager->name }} ({{ $manager->role }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium lp-title">Free notes</label>
                        <textarea name="prospecting_notes" rows="4" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">{{ old('prospecting_notes', $lead->prospecting_notes) }}</textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">Save Lead</button>
                    </div>
                </form>
            </article>

            @php
                $timelineLabels = [
                    'immediately_30_days' => 'Immediately (within 30 days)',
                    'one_to_three_months' => '1-3 months',
                    'three_to_six_months' => '3-6 months',
                    'just_browsing' => 'Just browsing',
                ];

                $moveIfNotFoundLabels = [
                    'must_move' => 'My lease ends / I must move',
                    'stay_where_i_am' => 'I’ll stay where I am',
                    'continue_renting' => 'I’ll continue renting',
                ];

                $priceRangeLabels = [
                    'under_300k' => 'Under $300k',
                    '300k_400k' => '$300k-$400k',
                    '400k_500k' => '$400k-$500k',
                    '500k_650k' => '$500k-$650k',
                    '650k_plus' => '$650k+',
                ];

                $mortgageLabels = [
                    'pre_approved' => 'Yes, I have a pre-approval letter',
                    'ready_to_talk' => 'Not yet, but I am ready to talk to a lender',
                    'cash' => 'No, I’m paying cash',
                    'not_ready' => 'No, I’m not ready yet',
                ];

                $sellLabels = [
                    'yes' => 'Yes',
                    'no' => 'No',
                    'renting' => 'I am currently renting',
                ];

                $agentLabels = [
                    'yes' => 'Yes',
                    'no' => 'No',
                    'exclusive' => 'Yes (exclusive agreement)',
                    'none' => 'No',
                    'open_houses' => 'Just touring open houses',
                ];

                $reasonLabels = [
                    'first_time_homebuyer' => 'First-time homebuyer',
                    'relocating_for_work' => 'Relocating for work',
                    'upgrading_downsizing' => 'Upgrading/Downsizing',
                    'investing' => 'Real estate investing',
                ];

                $contactLabels = [
                    'email' => 'Email',
                    'text' => 'Text Message',
                    'phone' => 'Phone Call',
                ];

                $sellerTimelineLabels = [
                    'immediately_30_days' => 'Immediately (within 30 days)',
                    'one_to_three_months' => '1–3 months',
                    'three_to_six_months' => '3–6 months',
                    'just_curious' => 'Just curious about my home’s value',
                ];

                $sellerMotivationLabels = [
                    'relocating_for_work' => 'Relocating for work',
                    'downsizing_upgrading' => 'Downsizing / upgrading',
                    'financial_reasons' => 'Financial reasons',
                    'estate_inheritance' => 'Estate / inheritance',
                    'testing_market' => 'Just testing the market',
                ];

                $sellerMortgageLabels = [
                    'yes' => 'Yes, I have a mortgage',
                    'no' => 'No, it’s owned free and clear',
                ];

                $sellerBuyAfterLabels = [
                    'yes_local' => 'Yes, I need to buy locally',
                    'yes_relocating' => 'Yes, I’m relocating out of the area',
                    'no' => 'No, I already have a place',
                ];

                $sellerConditionLabels = [
                    'excellent' => 'Move-in ready / Excellent',
                    'minor_tlc' => 'Needs minor TLC (paint, carpet)',
                    'significant_repairs' => 'Needs significant repairs',
                    'fixer_upper' => 'Fixer-upper',
                ];

                $sellerAgentLabels = [
                    'no' => 'No, I’m looking for an agent',
                    'listed' => 'Yes, I’m currently listed',
                    'fsbo' => 'I’m considering selling it myself (FSBO)',
                ];

                $sellerOccupancyLabels = [
                    'primary_residence' => 'Yes, it’s my primary residence',
                    'vacant' => 'No, it’s vacant',
                    'rented_to_tenants' => 'No, it’s currently rented to tenants',
                ];

                $sellerDeliveryLabels = [
                    'email' => 'Email me the report',
                    'text' => 'Text me the highlights',
                    'phone' => 'Let’s schedule a brief 15-minute phone call',
                ];

                $hasBuyerData = filled($lead->move_timeline)
                    || filled($lead->move_if_not_found)
                    || filled($lead->price_range)
                    || filled($lead->mortgage_preapproval_status)
                    || filled($lead->need_to_sell_current_home)
                    || filled($lead->agent_relationship)
                    || filled($lead->purchase_reason)
                    || filled($lead->target_areas)
                    || filled($lead->min_bedrooms)
                    || filled($lead->min_bathrooms)
                    || filled($lead->preferred_contact_method);

                $hasSellerData = filled($lead->seller_timeline)
                    || filled($lead->seller_motivation)
                    || filled($lead->seller_estimated_home_value)
                    || filled($lead->seller_mortgage_status)
                    || filled($lead->seller_needs_to_buy_another_home_after_selling)
                    || filled($lead->seller_property_condition)
                    || filled($lead->seller_major_upgrades)
                    || filled($lead->seller_agent_commitment)
                    || filled($lead->seller_occupancy_status)
                    || filled($lead->seller_valuation_delivery_method);
            @endphp

            @if ($lead->lead_type === 'buyer' || $hasBuyerData)
                <article class="lp-card p-6">
                    <h3 class="lp-title text-lg font-semibold">Buyer Qualification</h3>

                    <form method="POST" action="{{ route('manager.leads.update', $lead) }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Move timeline</label>
                            <select name="move_timeline" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($timelineLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('move_timeline', $lead->move_timeline) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">If not found</label>
                            <select name="move_if_not_found" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($moveIfNotFoundLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('move_if_not_found', $lead->move_if_not_found) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Price range</label>
                            <select name="price_range" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($priceRangeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('price_range', $lead->price_range) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Mortgage status</label>
                            <select name="mortgage_preapproval_status" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($mortgageLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('mortgage_preapproval_status', $lead->mortgage_preapproval_status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Need to sell first</label>
                            <select name="need_to_sell_current_home" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($sellLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('need_to_sell_current_home', $lead->need_to_sell_current_home) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Agent relationship</label>
                            <select name="agent_relationship" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($agentLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('agent_relationship', $lead->agent_relationship) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Reason for buying</label>
                            <select name="purchase_reason" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($reasonLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('purchase_reason', $lead->purchase_reason) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Preferred contact</label>
                            <select name="preferred_contact_method" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($contactLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('preferred_contact_method', $lead->preferred_contact_method) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium lp-title">Target areas</label>
                            <input name="target_areas" type="text" value="{{ old('target_areas', $lead->target_areas) }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Minimum bedrooms</label>
                            <input name="min_bedrooms" type="number" min="0" value="{{ old('min_bedrooms', $lead->min_bedrooms) }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Minimum bathrooms</label>
                            <input name="min_bathrooms" type="number" step="0.5" min="0" value="{{ old('min_bathrooms', $lead->min_bathrooms) }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Working with agent</label>
                            <select name="working_with_agent" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                <option value="1" @selected(old('working_with_agent', (int) $lead->working_with_agent) === 1)>Yes</option>
                                <option value="0" @selected(old('working_with_agent', (int) $lead->working_with_agent) === 0)>No</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">Save Buyer Details</button>
                        </div>
                    </form>
                </article>
            @endif

            @if ($lead->lead_type === 'seller' || $hasSellerData)
                <article class="lp-card p-6">
                    <h3 class="lp-title text-lg font-semibold">Seller Qualification</h3>

                    <form method="POST" action="{{ route('manager.leads.update', $lead) }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Timeline</label>
                            <select name="seller_timeline" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($sellerTimelineLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('seller_timeline', $lead->seller_timeline) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Motivation</label>
                            <select name="seller_motivation" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($sellerMotivationLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('seller_motivation', $lead->seller_motivation) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Estimated value</label>
                            <input name="seller_estimated_home_value" type="text" value="{{ old('seller_estimated_home_value', $lead->seller_estimated_home_value) }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Mortgage status</label>
                            <select name="seller_mortgage_status" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($sellerMortgageLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('seller_mortgage_status', $lead->seller_mortgage_status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Need to buy next</label>
                            <select name="seller_needs_to_buy_another_home_after_selling" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($sellerBuyAfterLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('seller_needs_to_buy_another_home_after_selling', $lead->seller_needs_to_buy_another_home_after_selling) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Property condition</label>
                            <select name="seller_property_condition" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($sellerConditionLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('seller_property_condition', $lead->seller_property_condition) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium lp-title">Major upgrades</label>
                            <input name="seller_major_upgrades" type="text" value="{{ old('seller_major_upgrades', $lead->seller_major_upgrades) }}" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Agent commitment</label>
                            <select name="seller_agent_commitment" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($sellerAgentLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('seller_agent_commitment', $lead->seller_agent_commitment) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">Occupancy</label>
                            <select name="seller_occupancy_status" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($sellerOccupancyLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('seller_occupancy_status', $lead->seller_occupancy_status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium lp-title">Valuation delivery</label>
                            <select name="seller_valuation_delivery_method" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm">
                                <option value="">Not provided</option>
                                @foreach ($sellerDeliveryLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('seller_valuation_delivery_method', $lead->seller_valuation_delivery_method) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">Save Seller Details</button>
                        </div>
                    </form>
                </article>
            @endif

            <article class="lp-card p-6">
                <h3 class="lp-title text-lg font-semibold">Lead Activities</h3>

                <form method="POST" action="{{ route('manager.leads.activities.store', $lead) }}" class="mt-4 grid gap-3 sm:grid-cols-[180px_1fr_auto]">
                    @csrf
                    <select name="type" class="rounded-xl border border-[var(--lp-border)] px-3 py-2 text-sm" required>
                        @foreach (['email', 'call', 'note', 'meeting'] as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    <input name="description" type="text" placeholder="Activity details" class="rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm" required>
                    <button type="submit" class="rounded-xl px-4 py-2 text-sm font-medium lp-btn-accent">Add</button>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse ($lead->activities as $activity)
                        <div class="rounded-xl border border-[var(--lp-border)] p-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs uppercase tracking-wider lp-muted">{{ $activity->type }}</span>
                                <span class="text-xs lp-muted">{{ $activity->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            <p class="mt-1 text-sm lp-title">{{ $activity->description }}</p>
                        </div>
                    @empty
                        <p class="text-sm lp-muted">No activity logged yet.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <aside class="space-y-6">
            <article class="lp-card p-6">
                <h3 class="lp-title text-lg font-semibold">Lead Tasks</h3>

                <form method="POST" action="{{ route('manager.leads.tasks.store', $lead) }}" class="mt-4 space-y-3">
                    @csrf
                    <input name="title" type="text" placeholder="Task title" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm" required>
                    <input name="due_date" type="date" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm" required>
                    <button type="submit" class="w-full rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">Create Task</button>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse ($lead->tasks as $task)
                        <form method="POST" action="{{ route('manager.leads.tasks.update', [$lead, $task]) }}" class="rounded-xl border border-[var(--lp-border)] p-3 space-y-2">
                            @csrf
                            @method('PATCH')
                            <input name="title" value="{{ $task->title }}" class="w-full rounded-lg border border-[var(--lp-border)] px-3 py-2 text-sm" required>
                            <input name="due_date" type="date" value="{{ $task->due_date?->format('Y-m-d') }}" class="w-full rounded-lg border border-[var(--lp-border)] px-3 py-2 text-sm" required>
                            <select name="status" class="w-full rounded-lg border border-[var(--lp-border)] px-3 py-2 text-sm" required>
                                <option value="pending" @selected($task->status === 'pending')>Pending</option>
                                <option value="complete" @selected($task->status === 'complete')>Complete</option>
                            </select>
                            <button type="submit" class="w-full rounded-lg border border-[var(--lp-border)] px-3 py-2 text-xs font-medium lp-title">Update Task</button>
                        </form>
                    @empty
                        <p class="text-sm lp-muted">No tasks for this lead.</p>
                    @endforelse
                </div>
            </article>
        </aside>
    </div>
</x-app-layout>
