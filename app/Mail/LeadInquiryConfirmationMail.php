<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadInquiryConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public EmailTemplate $template,
        public array $context = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->renderSubject($this->placeholders()),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-inquiry-confirmation',
            with: [
                'lead' => $this->lead,
                'template' => $this->template,
                'bodyHtml' => nl2br(e($this->template->renderBody($this->placeholders()))),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    protected function placeholders(): array
    {
        $firstName = (string) ($this->context['first_name'] ?? trim(explode(' ', (string) $this->lead->name)[0] ?? '') ?: 'there');
        $lastName = (string) ($this->context['last_name'] ?? '');
        $fullName = trim((string) ($this->context['name'] ?? $this->lead->name ?: trim($firstName.' '.$lastName)));

        $address = trim((string) ($this->context['address'] ?? ''));
        $city = trim((string) ($this->context['city'] ?? ''));
        $state = trim((string) ($this->context['state'] ?? ''));

        if ($address === '') {
            $address = (string) $this->lead->address;
        }

        $fullAddress = implode(', ', array_filter([$address, $city, $state]));

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'phone_number' => config('app.prospectgoat_contact_phone', '(470) 588-1505'),
            'email' => (string) $this->lead->email,
            'phone' => (string) $this->lead->phone,
            'lead_type' => str_replace('_', ' ', (string) $this->lead->lead_type),
            'source' => str_replace('_', ' ', (string) $this->lead->source),
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'full_address' => $fullAddress,
            'other_source_detail' => (string) ($this->context['other_source_detail'] ?? ''),
            'referrer_first_name' => (string) ($this->context['referrer_first_name'] ?? ''),
            'referrer_last_name' => (string) ($this->context['referrer_last_name'] ?? ''),
            'referrer_email' => (string) ($this->context['referrer_email'] ?? ''),
            'referrer_phone' => (string) ($this->context['referrer_phone'] ?? ''),
            'move_timeline' => (string) ($this->context['move_timeline'] ?? ''),
            'move_if_not_found' => (string) ($this->context['move_if_not_found'] ?? ''),
            'price_range' => (string) ($this->context['price_range'] ?? ''),
            'mortgage_preapproval_status' => (string) ($this->context['mortgage_preapproval_status'] ?? ''),
            'need_to_sell_current_home' => (string) ($this->context['need_to_sell_current_home'] ?? ''),
            'agent_relationship' => (string) ($this->context['agent_relationship'] ?? ''),
            'purchase_reason' => (string) ($this->context['purchase_reason'] ?? ''),
            'target_areas' => (string) ($this->context['target_areas'] ?? ''),
            'min_bedrooms' => (string) ($this->context['min_bedrooms'] ?? ''),
            'min_bathrooms' => (string) ($this->context['min_bathrooms'] ?? ''),
            'preferred_contact_method' => (string) ($this->context['preferred_contact_method'] ?? ''),
            'seller_timeline' => (string) ($this->context['seller_timeline'] ?? ''),
            'seller_motivation' => (string) ($this->context['seller_motivation'] ?? ''),
            'seller_estimated_home_value' => (string) ($this->context['seller_estimated_home_value'] ?? ''),
            'seller_mortgage_status' => (string) ($this->context['seller_mortgage_status'] ?? ''),
            'seller_needs_to_buy_another_home_after_selling' => (string) ($this->context['seller_needs_to_buy_another_home_after_selling'] ?? ''),
            'seller_property_condition' => (string) ($this->context['seller_property_condition'] ?? ''),
            'seller_major_upgrades' => (string) ($this->context['seller_major_upgrades'] ?? ''),
            'seller_agent_commitment' => (string) ($this->context['seller_agent_commitment'] ?? ''),
            'seller_occupancy_status' => (string) ($this->context['seller_occupancy_status'] ?? ''),
            'seller_valuation_delivery_method' => (string) ($this->context['seller_valuation_delivery_method'] ?? ''),
        ];
    }
}