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

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Move timeline</p>
                            <p class="mt-1 text-sm lp-title">{{ $timelineLabels[$lead->move_timeline] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">If not found</p>
                            <p class="mt-1 text-sm lp-title">{{ $moveIfNotFoundLabels[$lead->move_if_not_found] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Price range</p>
                            <p class="mt-1 text-sm lp-title">{{ $priceRangeLabels[$lead->price_range] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Mortgage status</p>
                            <p class="mt-1 text-sm lp-title">{{ $mortgageLabels[$lead->mortgage_preapproval_status] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Need to sell first</p>
                            <p class="mt-1 text-sm lp-title">{{ $sellLabels[$lead->need_to_sell_current_home] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Agent relationship</p>
                            <p class="mt-1 text-sm lp-title">{{ $agentLabels[$lead->agent_relationship] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Reason for buying</p>
                            <p class="mt-1 text-sm lp-title">{{ $reasonLabels[$lead->purchase_reason] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Preferred contact</p>
                            <p class="mt-1 text-sm lp-title">{{ $contactLabels[$lead->preferred_contact_method] ?? 'Not provided' }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <p class="text-xs uppercase tracking-wider lp-muted">Target areas</p>
                            <p class="mt-1 text-sm lp-title">{{ $lead->target_areas ?: 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Minimum bedrooms</p>
                            <p class="mt-1 text-sm lp-title">{{ $lead->min_bedrooms ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Minimum bathrooms</p>
                            <p class="mt-1 text-sm lp-title">{{ $lead->min_bathrooms ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Working with agent</p>
                            <p class="mt-1 text-sm lp-title">{{ $lead->working_with_agent ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>
                </article>
            @endif

            @if ($lead->lead_type === 'seller' || $hasSellerData)
                <article class="lp-card p-6">
                    <h3 class="lp-title text-lg font-semibold">Seller Qualification</h3>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Timeline</p>
                            <p class="mt-1 text-sm lp-title">{{ $sellerTimelineLabels[$lead->seller_timeline] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Motivation</p>
                            <p class="mt-1 text-sm lp-title">{{ $sellerMotivationLabels[$lead->seller_motivation] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Estimated value</p>
                            <p class="mt-1 text-sm lp-title">{{ $lead->seller_estimated_home_value ?: 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Mortgage status</p>
                            <p class="mt-1 text-sm lp-title">{{ $sellerMortgageLabels[$lead->seller_mortgage_status] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Need to buy next</p>
                            <p class="mt-1 text-sm lp-title">{{ $sellerBuyAfterLabels[$lead->seller_needs_to_buy_another_home_after_selling] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Property condition</p>
                            <p class="mt-1 text-sm lp-title">{{ $sellerConditionLabels[$lead->seller_property_condition] ?? 'Not provided' }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <p class="text-xs uppercase tracking-wider lp-muted">Major upgrades</p>
                            <p class="mt-1 text-sm lp-title">{{ $lead->seller_major_upgrades ?: 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Agent commitment</p>
                            <p class="mt-1 text-sm lp-title">{{ $sellerAgentLabels[$lead->seller_agent_commitment] ?? 'Not provided' }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider lp-muted">Occupancy</p>
                            <p class="mt-1 text-sm lp-title">{{ $sellerOccupancyLabels[$lead->seller_occupancy_status] ?? 'Not provided' }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <p class="text-xs uppercase tracking-wider lp-muted">Valuation delivery</p>
                            <p class="mt-1 text-sm lp-title">{{ $sellerDeliveryLabels[$lead->seller_valuation_delivery_method] ?? 'Not provided' }}</p>
                        </div>
                    </div>
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
