<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only administrators can update coupons
        return $this->user('admin')?->hasRole('Administrator');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $couponId = $this->route('coupon')->id;

        return [
            'code' => ['nullable', 'string', 'size:5', Rule::unique('coupons', 'code')->ignore($couponId), 'regex:/^[A-Z0-9]+$/'],
            'coupon_name' => ['sometimes', 'required', 'string', 'max:255'],
            'discount_type' => ['sometimes', 'required', 'string', 'in:percent,fixed'],
            'discount_value' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.size' => 'Coupon code must be exactly 5 characters.',
            'code.unique' => 'This coupon code already exists.',
            'code.regex' => 'Coupon code must contain only uppercase letters and numbers.',
            'coupon_name.required' => 'Coupon name is required.',
            'coupon_name.string' => 'Coupon name must be a string.',
            'coupon_name.max' => 'Coupon name may not be greater than 255 characters.',
            'discount_type.required' => 'Discount type is required.',
            'discount_type.in' => 'Discount type must be either percent or fixed.',
            'discount_value.required' => 'Discount value is required.',
            'discount_value.min' => 'Discount value must be greater than or equal to 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert code to uppercase if provided
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code),
            ]);
        }
    }
}
