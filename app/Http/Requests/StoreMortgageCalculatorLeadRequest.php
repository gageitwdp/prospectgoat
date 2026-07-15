<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMortgageCalculatorLeadRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'home_price' => ['required', 'numeric', 'min:1000', 'max:100000000'],
            'down_payment' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'annual_interest_rate' => ['required', 'numeric', 'min:0', 'max:30'],
            'loan_term_years' => ['required', 'integer', 'min:1', 'max:50'],
            'property_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'home_insurance_yearly' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'hoa_monthly' => ['nullable', 'numeric', 'min:0', 'max:20000'],
            'pmi_monthly' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ];
    }
}
