<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // All authenticated admins can create transfers
        return $this->user('admin') !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'to_admin_id' => ['required', 'exists:admins,id', 'different:from_admin_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'to_admin_id.required' => 'Please select a recipient admin.',
            'to_admin_id.exists' => 'The selected recipient admin does not exist.',
            'to_admin_id.different' => 'You cannot transfer to yourself.',
            'amount.required' => 'Transfer amount is required.',
            'amount.numeric' => 'Transfer amount must be a number.',
            'amount.min' => 'Transfer amount must be at least 0.01.',
        ];
    }
}
