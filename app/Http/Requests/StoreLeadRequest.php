<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required_without:name', 'string', 'max:120'],
            'last_name' => ['required_without:name', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required_if:lead_type,home_value', 'nullable', 'string', 'max:500'],
            'city' => ['required_if:lead_type,home_value', 'nullable', 'string', 'max:120'],
            'state' => ['required_if:lead_type,home_value', 'nullable', 'in:GA'],
            'lead_type' => ['required', 'in:home_value,buyer,seller'],
            'source' => ['required', 'in:homepage,landing_page,facebook,instagram,referral,other'],
            'other_source_detail' => ['required_if:source,other', 'nullable', 'string', 'max:255'],
            'referrer_first_name' => ['required_if:source,referral', 'nullable', 'string', 'max:120'],
            'referrer_last_name' => ['required_if:source,referral', 'nullable', 'string', 'max:120'],
            'referrer_email' => ['required_if:source,referral', 'nullable', 'email', 'max:255'],
            'referrer_phone' => ['required_if:source,referral', 'nullable', 'string', 'max:30'],
        ];
    }
}
