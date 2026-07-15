<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSellerLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'seller_timeline' => ['required', 'in:immediately_30_days,one_to_three_months,three_to_six_months,just_curious'],
            'seller_motivation' => ['required', 'in:relocating_for_work,downsizing_upgrading,financial_reasons,estate_inheritance,testing_market'],
            'seller_estimated_home_value' => ['required', 'string', 'max:255'],
            'seller_mortgage_status' => ['required', 'in:yes,no'],
            'seller_needs_to_buy_another_home_after_selling' => ['required', 'in:yes_local,yes_relocating,no'],
            'seller_property_condition' => ['required', 'in:excellent,minor_tlc,significant_repairs,fixer_upper'],
            'seller_major_upgrades' => ['nullable', 'string', 'max:1000'],
            'seller_agent_commitment' => ['required', 'in:no,listed,fsbo'],
            'seller_occupancy_status' => ['required', 'in:primary_residence,vacant,rented_to_tenants'],
            'seller_valuation_delivery_method' => ['required', 'in:email,text,phone'],
        ];
    }
}