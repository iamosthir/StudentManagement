<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenewStudentSubscriptionRequest extends FormRequest
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
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'renewal_price' => ['nullable', 'numeric', 'min:0'],
            'create_payment' => ['boolean'],
            'payment_method' => ['required_if:create_payment,true', 'string', 'in:cash,credit_card,bank_transfer,check,online'],
            'payment_status' => ['required_if:create_payment,true', 'string', 'in:paid,pending'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'duration_months.integer' => 'Duration must be a valid number of months.',
            'duration_months.min' => 'Duration must be at least 1 month.',
            'duration_months.max' => 'Duration cannot exceed 120 months (10 years).',
            'renewal_price.numeric' => 'Renewal price must be a valid number.',
            'renewal_price.min' => 'Renewal price cannot be negative.',
            'payment_method.required_if' => 'Payment method is required when creating a payment.',
            'payment_status.required_if' => 'Payment status is required when creating a payment.',
        ];
    }
}
