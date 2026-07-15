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
                    if ($user?->isGlobalAdmin()) {
                        if ($leadAccountId !== null) {
                            $query->where('account_id', $leadAccountId);
                        }

                        return;
                    }

                    $query->where('account_id', $user?->account_id);
                }),
            ],
        ];
    }
}
