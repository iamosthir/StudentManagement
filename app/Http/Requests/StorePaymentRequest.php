<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'student_id' => ['required', 'exists:students,id'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'coupon_code' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:paid,pending,cancelled'],

            // Payment items
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'string', 'in:subscription,product'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Student is required.',
            'student_id.exists' => 'Selected student does not exist.',
            'items.required' => 'At least one payment item is required.',
            'items.min' => 'At least one payment item is required.',
            'items.*.item_type.required' => 'Item type is required.',
            'items.*.item_type.in' => 'Item type must be either subscription or product.',
            'items.*.item_id.required' => 'Item ID is required.',
            'items.*.description.required' => 'Item description is required.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.required' => 'Unit price is required.',
            'items.*.total_price.required' => 'Total price is required.',
        ];
    }
}
