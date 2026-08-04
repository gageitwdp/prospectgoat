<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $lead = $this->route('lead');
        $leadAccountId = is_object($lead) ? $lead->account_id : null;
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'lead_type' => ['required', 'in:home_value,buyer,seller,generic_inquiry'],
            'source' => ['required', 'in:homepage,landing_page,referral'],
            'status' => ['required', 'in:new,contacted,qualified,active,closed'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) use ($user, $leadAccountId) {
                    $query->where('account_id', $leadAccountId ?? $user?->account_id);
                }),
            ],
            'prospecting_notes' => ['nullable', 'string'],
            'move_timeline' => ['nullable', 'in:immediately_30_days,one_to_three_months,three_to_six_months,just_browsing'],
            'move_if_not_found' => ['nullable', 'in:must_move,stay_where_i_am,continue_renting'],
            'price_range' => ['nullable', 'in:under_300k,300k_400k,400k_500k,500k_650k,650k_plus'],
            'mortgage_preapproval_status' => ['nullable', 'in:pre_approved,ready_to_talk,cash,not_ready'],
            'need_to_sell_current_home' => ['nullable', 'in:yes,no,renting'],
            'agent_relationship' => ['nullable', 'in:yes,no,exclusive,none,open_houses'],
            'purchase_reason' => ['nullable', 'in:first_time_homebuyer,relocating_for_work,upgrading_downsizing,investing'],
            'target_areas' => ['nullable', 'string', 'max:500'],
            'min_bedrooms' => ['nullable', 'integer', 'min:0'],
            'min_bathrooms' => ['nullable', 'numeric'],
            'preferred_contact_method' => ['nullable', 'in:email,text,phone'],
            'working_with_agent' => ['nullable', 'boolean'],
            'seller_timeline' => ['nullable', 'in:immediately_30_days,one_to_three_months,three_to_six_months,just_curious'],
            'seller_motivation' => ['nullable', 'in:relocating_for_work,downsizing_upgrading,financial_reasons,estate_inheritance,testing_market'],
            'seller_estimated_home_value' => ['nullable', 'string', 'max:255'],
            'seller_mortgage_status' => ['nullable', 'in:yes,no'],
            'seller_needs_to_buy_another_home_after_selling' => ['nullable', 'in:yes_local,yes_relocating,no'],
            'seller_property_condition' => ['nullable', 'in:excellent,minor_tlc,significant_repairs,fixer_upper'],
            'seller_major_upgrades' => ['nullable', 'string', 'max:500'],
            'seller_agent_commitment' => ['nullable', 'in:no,listed,fsbo'],
            'seller_occupancy_status' => ['nullable', 'in:primary_residence,vacant,rented_to_tenants'],
            'seller_valuation_delivery_method' => ['nullable', 'in:email,text,phone'],
        ];
    }
}
