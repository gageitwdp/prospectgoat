<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LeadInquiryConfirmationMail;
use App\Models\EmailTemplate;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $accountId = $this->requireCurrentAccountId();

        $templates = EmailTemplate::query()
            ->where('account_id', $accountId)
            ->orderBy('name')
            ->get();

        return view('admin.email-templates.index', compact('templates'));
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        abort_unless($emailTemplate->account_id === $this->requireCurrentAccountId(), 404);

        return view('admin.email-templates.edit', [
            'template' => $emailTemplate,
            'previewHtml' => $this->renderPreviewHtml($emailTemplate),
            'previewLead' => $this->previewLead($emailTemplate),
            'templateTokens' => $this->templateTokens(),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $accountId = $this->resolveAccountIdFromRequest($request);
        abort_unless($emailTemplate->account_id === null || $emailTemplate->account_id === $accountId, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $emailTemplate->update([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()
            ->route('admin.email-templates.edit', $emailTemplate)
            ->with('status', 'Template updated successfully.');
    }

    public function test(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $accountId = $this->resolveAccountIdFromRequest($request);
        abort_unless($emailTemplate->account_id === null || $emailTemplate->account_id === $accountId, 404);

        $data = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:120'],
            'lead_type' => ['required', 'in:home_value,buyer,seller,generic_inquiry'],
            'source' => ['required', 'in:homepage,landing_page,facebook,instagram,referral,other'],
        ]);

        $lead = new Lead([
            'account_id' => $accountId,
            'name' => $data['first_name'].' Test',
            'email' => $data['recipient_email'],
            'phone' => '(470) 588-1505',
            'address' => null,
            'lead_type' => $data['lead_type'],
            'source' => $data['source'],
            'status' => 'new',
            'assigned_to' => null,
        ]);

        try {
            Mail::to($data['recipient_email'])->send(new LeadInquiryConfirmationMail($lead, $emailTemplate, [
                'move_timeline' => '1-3 months',
                'move_if_not_found' => 'My lease ends / I must move',
                'price_range' => '$400k-$500k',
                'mortgage_preapproval_status' => 'Not yet, but I am ready to talk to a lender',
                'need_to_sell_current_home' => 'I am currently renting',
                'agent_relationship' => 'No',
                'purchase_reason' => 'Relocating for work',
                'target_areas' => 'Woodstock, Canton',
                'min_bedrooms' => '3',
                'min_bathrooms' => '2.5',
                'preferred_contact_method' => 'Text Message',
                'seller_timeline' => '1-3 months',
                'seller_motivation' => 'Relocating for work',
                'seller_estimated_home_value' => 'Around $480k',
                'seller_mortgage_status' => 'Yes, I have a mortgage',
                'seller_needs_to_buy_another_home_after_selling' => 'Yes, I need to buy locally',
                'seller_property_condition' => 'Move-in ready / Excellent',
                'seller_major_upgrades' => 'New roof in 2023 and updated kitchen',
                'seller_agent_commitment' => 'No, I’m looking for an agent',
                'seller_occupancy_status' => 'Yes, it’s my primary residence',
                'seller_valuation_delivery_method' => 'Email me the report',
            ]));

            Log::info('Admin test send completed for email template.', [
                'template_id' => $emailTemplate->id,
                'template_key' => $emailTemplate->key,
                'recipient_email' => $data['recipient_email'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Admin template test send failed.', [
                'template_id' => $emailTemplate->id,
                'template_key' => $emailTemplate->key,
                'recipient_email' => $data['recipient_email'],
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('status', 'Unable to send the test email. Please try again.');
        }

        return back()->with('status', 'Test email sent successfully.');
    }

    protected function previewLead(EmailTemplate $emailTemplate): Lead
    {
        return new Lead([
            'name' => 'Taylor Prospect',
            'email' => 'preview@example.com',
            'phone' => '(470) 588-1505',
            'lead_type' => $this->leadTypeForTemplate($emailTemplate),
            'source' => 'homepage',
            'status' => 'new',
        ]);
    }

    protected function renderPreviewHtml(EmailTemplate $emailTemplate): string
    {
        $lead = $this->previewLead($emailTemplate);

        return nl2br(e($emailTemplate->renderBody([
            'first_name' => 'Taylor',
            'last_name' => 'Prospect',
            'full_name' => 'Taylor Prospect',
            'phone_number' => config('app.prospectgoat_contact_phone', '(470) 588-1505'),
            'email' => 'preview@example.com',
            'phone' => '(470) 555-1234',
            'lead_type' => str_replace('_', ' ', $lead->lead_type),
            'source' => str_replace('_', ' ', $lead->source),
            'address' => '123 Peachtree St NE',
            'city' => 'Atlanta',
            'state' => 'GA',
            'full_address' => '123 Peachtree St NE, Atlanta, GA',
            'other_source_detail' => 'Community event',
            'referrer_first_name' => 'Jordan',
            'referrer_last_name' => 'Smith',
            'referrer_email' => 'jordan@example.com',
            'referrer_phone' => '(470) 555-9876',
            'move_timeline' => '1-3 months',
            'move_if_not_found' => 'My lease ends / I must move',
            'price_range' => '$400k-$500k',
            'mortgage_preapproval_status' => 'Not yet, but I am ready to talk to a lender',
            'need_to_sell_current_home' => 'I am currently renting',
            'agent_relationship' => 'No',
            'purchase_reason' => 'Relocating for work',
            'target_areas' => 'Woodstock, Canton',
            'min_bedrooms' => '3',
            'min_bathrooms' => '2.5',
            'preferred_contact_method' => 'Text Message',
        ])));
    }

    protected function leadTypeForTemplate(EmailTemplate $emailTemplate): string
    {
        return match ($emailTemplate->key) {
            'new_lead_buyer_qualification' => 'buyer',
            'new_lead_buyer' => 'buyer',
            'new_lead_seller_profile' => 'seller',
            'new_lead_seller' => 'seller',
            'new_lead_home_value' => 'home_value',
            default => 'generic_inquiry',
        };
    }

    protected function templateTokens(): array
    {
        return [
            'all_inquiries' => [
                'first_name',
                'last_name',
                'full_name',
                'email',
                'phone',
                'phone_number',
                'lead_type',
                'source',
            ],
            'home_value_only' => [
                'address',
                'city',
                'state',
                'full_address',
            ],
            'source_other_only' => [
                'other_source_detail',
            ],
            'source_referral_only' => [
                'referrer_first_name',
                'referrer_last_name',
                'referrer_email',
                'referrer_phone',
            ],
            'seller_qualification_only' => [
                'seller_timeline',
                'seller_motivation',
                'seller_estimated_home_value',
                'seller_mortgage_status',
                'seller_needs_to_buy_another_home_after_selling',
                'seller_property_condition',
                'seller_major_upgrades',
                'seller_agent_commitment',
                'seller_occupancy_status',
                'seller_valuation_delivery_method',
            ],
            'buyer_qualification_only' => [
                'move_timeline',
                'move_if_not_found',
                'price_range',
                'mortgage_preapproval_status',
                'need_to_sell_current_home',
                'agent_relationship',
                'purchase_reason',
                'target_areas',
                'min_bedrooms',
                'min_bathrooms',
                'preferred_contact_method',
            ],
        ];
    }

    private function resolveAccountIdFromRequest(Request $request): int
    {
        $accountId = $request->user()?->account_id;

        if (is_numeric($accountId) && (int) $accountId > 0) {
            return (int) $accountId;
        }

        return $this->requireCurrentAccountId();
    }
}