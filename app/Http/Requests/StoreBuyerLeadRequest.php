<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBuyerLeadRequest extends FormRequest
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
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'move_timeline' => ['required', 'in:immediately_30_days,one_to_three_months,three_to_six_months,just_browsing'],
            'move_if_not_found' => ['required', 'in:must_move,stay_where_i_am,continue_renting'],
            'price_range' => ['required', 'in:under_300k,300k_400k,400k_500k,500k_650k,650k_plus'],
            'mortgage_preapproval_status' => ['required', 'in:pre_approved,ready_to_talk,cash,not_ready'],
            'need_to_sell_current_home' => ['required', 'in:yes,no,renting'],
            'agent_relationship' => ['required', 'in:exclusive,none'],
            'purchase_reason' => ['required', 'in:first_time_homebuyer,relocating_for_work,upgrading_downsizing,investing'],
            'target_areas' => ['required', 'string', 'max:500'],
            'min_bedrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'min_bathrooms' => ['required', 'numeric', 'min:0', 'max:20'],
            'preferred_contact_method' => ['required', 'in:email,text,phone'],
        ];
    }
}