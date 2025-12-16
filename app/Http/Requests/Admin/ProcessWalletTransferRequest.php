<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProcessWalletTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins with Administrator role can process transfers
        return $this->user('admin')?->hasRole('Administrator') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:accept,reject'],
            'rejection_reason' => ['required_if:action,reject', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Please select an action (accept or reject).',
            'action.in' => 'Invalid action selected.',
            'rejection_reason.required_if' => 'Rejection reason is required when rejecting a transfer.',
        ];
    }
}
