<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentSubscriptionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['sometimes', 'exists:programs,id'],
            'subscription_option_id' => ['sometimes', 'exists:subscription_options,id'],
            'custom_price' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'expiry_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:active,expired'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'program_id.exists' => 'Selected program does not exist.',
            'subscription_option_id.exists' => 'Selected subscription option does not exist.',
        ];
    }
}
