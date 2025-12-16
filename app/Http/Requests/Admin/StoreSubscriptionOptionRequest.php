<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionOptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('admin')->can('create subscription options');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:subscription_options,name'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:120'],
            'is_full_payment' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'subscription option name',
            'duration_months' => 'duration in months',
            'is_full_payment' => 'full payment option',
        ];
    }
}
