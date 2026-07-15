<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBuyerLeadRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\StoreSellerLeadRequest;
use App\Mail\LeadInquiryConfirmationMail;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\NewLeadIntakeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class LeadIntakeController extends Controller
{
    public function seller(): View
    {
        return view('leads.seller-intake');
    }

    public function buyer(): View
    {
        return view('leads.buyer-intake');
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $accountId = $this->resolvePublicAccountId();
        $data = $request->validated();

        $fullName = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        $name = $data['name'] ?? $fullName;

        $source = $data['source'];
        $persistedSource = in_array($source, ['facebook', 'instagram', 'other'], true) ? 'landing_page' : $source;

        $address = $data['address'] ?? null;
        if ($data['lead_type'] === 'home_value') {
            $address = implode(', ', array_filter([
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
            ]));
        }

        $lead = Lead::create([
            'account_id' => $accountId,
            'name' => $name,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $address,
            'lead_type' => $data['lead_type'],
            'source' => $persistedSource,
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $lead->activities()->create([
            'account_id' => $accountId,
            'type' => 'note',
            'description' => sprintf('Lead submitted from %s.', str_replace('_', ' ', $source)),
        ]);

        $template = EmailTemplate::resolveForInquiryType($data['lead_type'], $accountId);

        if ($template && filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($lead->email)->send(new LeadInquiryConfirmationMail($lead, $template, $data));

                Log::info('Lead inquiry confirmation email sent.', [
                    'lead_id' => $lead->id,
                    'lead_email' => $lead->email,
                    'template_key' => $template->key,
                ]);
            } catch (Throwable $exception) {
                Log::error('Lead inquiry confirmation email failed.', [
                    'lead_id' => $lead->id,
                    'lead_email' => $lead->email,
                    'template_key' => $template->key,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($source === 'other' && ! empty($data['other_source_detail'])) {
            $lead->activities()->create([
                'account_id' => $accountId,
                'type' => 'note',
                'description' => sprintf('Other source detail: %s.', $data['other_source_detail']),
            ]);
        }

        if ($source === 'referral') {
            $lead->activities()->create([
                'account_id' => $accountId,
                'type' => 'note',
                'description' => sprintf(
                    'Referral provided by %s %s (%s, %s).',
                    $data['referrer_first_name'],
                    $data['referrer_last_name'],
                    $data['referrer_email'],
                    $data['referrer_phone'],
                ),
            ]);
        }

        if (Schema::hasColumn('users', 'notify_on_new_lead_intake')) {
            $recipients = User::query()
                ->where('account_id', $accountId)
                ->whereNotNull('email')
                ->get()
                ->filter(function (User $user): bool {
                    $role = strtolower(trim((string) $user->role));

                    if (in_array($role, ['owner', 'admin'], true)) {
                        return true;
                    }

                    if (in_array($role, ['manager', 'agent'], true)) {
                        return (bool) $user->notify_on_new_lead_intake;
                    }

                    return false;
                })
                ->values();
        } else {
            Log::warning('New lead intake notification preferences column missing. Falling back to all managers.', [
                'column' => 'users.notify_on_new_lead_intake',
            ]);

            $recipients = User::query()
                ->where('account_id', $accountId)
                ->whereIn('role', ['owner', 'admin', 'manager', 'agent'])
                ->whereNotNull('email')
                ->get();
        }

        if ($recipients->isEmpty()) {
            Log::warning('New lead intake notification skipped: no opted-in manager recipients.', [
                'lead_id' => $lead->id,
                'lead_email' => $lead->email,
            ]);

            return back()->with('lead_success', 'Thanks, your information was received. Our team will follow up shortly.');
        }

        $sentRecipientIds = [];
        $failedRecipients = [];

        foreach ($recipients as $recipient) {
            try {
                Notification::sendNow($recipient, new NewLeadIntakeNotification($lead));
                $sentRecipientIds[] = $recipient->id;
            } catch (Throwable $exception) {
                $failedRecipients[] = [
                    'id' => $recipient->id,
                    'email' => $recipient->email,
                    'error' => $exception->getMessage(),
                ];

                Log::error('New lead intake notification failed for recipient.', [
                    'lead_id' => $lead->id,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        Log::info('New lead intake notification dispatched.', [
            'lead_id' => $lead->id,
            'recipient_count' => $recipients->count(),
            'recipient_ids' => $recipients->pluck('id')->all(),
            'sent_recipient_ids' => $sentRecipientIds,
            'failed_recipients' => $failedRecipients,
        ]);

        return back()->with('lead_success', 'Thanks, your information was received. Our team will follow up shortly.');
    }

    public function storeBuyer(StoreBuyerLeadRequest $request): RedirectResponse
    {
        $accountId = $this->resolvePublicAccountId();
        $data = $request->validated();

        $fullName = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));

        $lead = Lead::create([
            'account_id' => $accountId,
            'name' => $fullName,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'landing_page',
            'status' => 'new',
            'assigned_to' => null,
            'working_with_agent' => $data['agent_relationship'] === 'exclusive',
            'move_timeline' => $data['move_timeline'],
            'move_if_not_found' => $data['move_if_not_found'],
            'price_range' => $data['price_range'],
            'mortgage_preapproval_status' => $data['mortgage_preapproval_status'],
            'need_to_sell_current_home' => $data['need_to_sell_current_home'],
            'agent_relationship' => $data['agent_relationship'],
            'purchase_reason' => $data['purchase_reason'],
            'target_areas' => $data['target_areas'],
            'min_bedrooms' => $data['min_bedrooms'],
            'min_bathrooms' => $data['min_bathrooms'],
            'preferred_contact_method' => $data['preferred_contact_method'],
        ]);

        $lead->activities()->create([
            'account_id' => $accountId,
            'type' => 'note',
            'description' => sprintf(
                'Buyer intake submitted: timeline %s, budget %s, contact by %s.',
                $this->labelForBuyerTimeline($data['move_timeline']),
                $this->labelForBuyerPriceRange($data['price_range']),
                $this->labelForPreferredContactMethod($data['preferred_contact_method']),
            ),
        ]);

        $template = EmailTemplate::resolveForKey('new_lead_buyer_qualification', $accountId);

        if ($template && filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($lead->email)->send(new LeadInquiryConfirmationMail($lead, $template, $data));

                Log::info('Buyer lead confirmation email sent.', [
                    'lead_id' => $lead->id,
                    'lead_email' => $lead->email,
                    'template_key' => $template->key,
                ]);
            } catch (Throwable $exception) {
                Log::error('Buyer lead confirmation email failed.', [
                    'lead_id' => $lead->id,
                    'lead_email' => $lead->email,
                    'template_key' => $template->key,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if (Schema::hasColumn('users', 'notify_on_new_lead_intake')) {
            $recipients = User::query()
                ->where('account_id', $accountId)
                ->whereNotNull('email')
                ->get()
                ->filter(function (User $user): bool {
                    $role = strtolower(trim((string) $user->role));

                    if (in_array($role, ['owner', 'admin'], true)) {
                        return true;
                    }

                    if (in_array($role, ['manager', 'agent'], true)) {
                        return (bool) $user->notify_on_new_lead_intake;
                    }

                    return false;
                })
                ->values();
        } else {
            Log::warning('New buyer lead notification preferences column missing. Falling back to all managers.', [
                'column' => 'users.notify_on_new_lead_intake',
            ]);

            $recipients = User::query()
                ->where('account_id', $accountId)
                ->whereIn('role', ['owner', 'admin', 'manager', 'agent'])
                ->whereNotNull('email')
                ->get();
        }

        if ($recipients->isEmpty()) {
            Log::warning('New buyer lead notification skipped: no opted-in manager recipients.', [
                'lead_id' => $lead->id,
                'lead_email' => $lead->email,
            ]);

            return back()->with('lead_success', 'Thanks, your information was received. Our team will follow up shortly.');
        }

        foreach ($recipients as $recipient) {
            try {
                Notification::sendNow($recipient, new NewLeadIntakeNotification($lead));
            } catch (Throwable $exception) {
                Log::error('New buyer lead notification failed for recipient.', [
                    'lead_id' => $lead->id,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('lead_success', 'Thanks, your information was received. Our team will follow up shortly.');
    }

    public function storeSeller(StoreSellerLeadRequest $request): RedirectResponse
    {
        $accountId = $this->resolvePublicAccountId();
        $data = $request->validated();

        $fullName = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));

        $lead = Lead::create([
            'account_id' => $accountId,
            'name' => $fullName,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'lead_type' => 'seller',
            'source' => 'landing_page',
            'status' => 'new',
            'assigned_to' => null,
            'working_with_agent' => $data['seller_agent_commitment'] === 'listed',
            'seller_timeline' => $data['seller_timeline'],
            'seller_motivation' => $data['seller_motivation'],
            'seller_estimated_home_value' => $data['seller_estimated_home_value'],
            'seller_mortgage_status' => $data['seller_mortgage_status'],
            'seller_needs_to_buy_another_home_after_selling' => $data['seller_needs_to_buy_another_home_after_selling'],
            'seller_property_condition' => $data['seller_property_condition'],
            'seller_major_upgrades' => $data['seller_major_upgrades'] ?? null,
            'seller_agent_commitment' => $data['seller_agent_commitment'],
            'seller_occupancy_status' => $data['seller_occupancy_status'],
            'seller_valuation_delivery_method' => $data['seller_valuation_delivery_method'],
        ]);

        $lead->activities()->create([
            'account_id' => $accountId,
            'type' => 'note',
            'description' => sprintf(
                'Seller intake submitted: timeline %s, motivation %s, valuation by %s.',
                $this->labelForSellerTimeline($data['seller_timeline']),
                $this->labelForSellerMotivation($data['seller_motivation']),
                $this->labelForSellerValuationDeliveryMethod($data['seller_valuation_delivery_method']),
            ),
        ]);

        $template = EmailTemplate::resolveForKey('new_lead_seller_profile', $accountId);

        if ($template && filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($lead->email)->send(new LeadInquiryConfirmationMail($lead, $template, $data));

                Log::info('Seller lead confirmation email sent.', [
                    'lead_id' => $lead->id,
                    'lead_email' => $lead->email,
                    'template_key' => $template->key,
                ]);
            } catch (Throwable $exception) {
                Log::error('Seller lead confirmation email failed.', [
                    'lead_id' => $lead->id,
                    'lead_email' => $lead->email,
                    'template_key' => $template->key,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if (Schema::hasColumn('users', 'notify_on_new_lead_intake')) {
            $recipients = User::query()
                ->where('account_id', $accountId)
                ->whereNotNull('email')
                ->get()
                ->filter(function (User $user): bool {
                    $role = strtolower(trim((string) $user->role));

                    if (in_array($role, ['owner', 'admin'], true)) {
                        return true;
                    }

                    if (in_array($role, ['manager', 'agent'], true)) {
                        return (bool) $user->notify_on_new_lead_intake;
                    }

                    return false;
                })
                ->values();
        } else {
            Log::warning('New seller lead notification preferences column missing. Falling back to all managers.', [
                'column' => 'users.notify_on_new_lead_intake',
            ]);

            $recipients = User::query()
                ->where('account_id', $accountId)
                ->whereIn('role', ['owner', 'admin', 'manager', 'agent'])
                ->whereNotNull('email')
                ->get();
        }

        if ($recipients->isEmpty()) {
            Log::warning('New seller lead notification skipped: no opted-in manager recipients.', [
                'lead_id' => $lead->id,
                'lead_email' => $lead->email,
            ]);

            return back()->with('lead_success', 'Thanks, your information was received. Our team will follow up shortly.');
        }

        foreach ($recipients as $recipient) {
            try {
                Notification::sendNow($recipient, new NewLeadIntakeNotification($lead));
            } catch (Throwable $exception) {
                Log::error('New seller lead notification failed for recipient.', [
                    'lead_id' => $lead->id,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('lead_success', 'Thanks, your information was received. Our team will follow up shortly.');
    }

    private function labelForBuyerTimeline(string $value): string
    {
        return match ($value) {
            'immediately_30_days' => 'Immediately (within 30 days)',
            'one_to_three_months' => '1-3 months',
            'three_to_six_months' => '3-6 months',
            default => 'Just browsing',
        };
    }

    private function labelForBuyerPriceRange(string $value): string
    {
        return match ($value) {
            'under_300k' => 'Under $300k',
            '300k_400k' => '$300k-$400k',
            '400k_500k' => '$400k-$500k',
            '500k_650k' => '$500k-$650k',
            default => '$650k+',
        };
    }

    private function labelForPreferredContactMethod(string $value): string
    {
        return match ($value) {
            'text' => 'Text message',
            'phone' => 'Phone call',
            default => 'Email',
        };
    }

    private function labelForSellerTimeline(string $value): string
    {
        return match ($value) {
            'immediately_30_days' => 'Immediately (within 30 days)',
            'one_to_three_months' => '1-3 months',
            'three_to_six_months' => '3-6 months',
            default => 'Just curious about my home’s value',
        };
    }

    private function labelForSellerMotivation(string $value): string
    {
        return match ($value) {
            'relocating_for_work' => 'Relocating for work',
            'downsizing_upgrading' => 'Downsizing / upgrading',
            'financial_reasons' => 'Financial reasons',
            'estate_inheritance' => 'Estate / inheritance',
            default => 'Just testing the market',
        };
    }

    private function labelForSellerValuationDeliveryMethod(string $value): string
    {
        return match ($value) {
            'text' => 'Text me the highlights',
            'phone' => 'Let’s schedule a brief 15-minute phone call',
            default => 'Email me the report',
        };
    }
}
