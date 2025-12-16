<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
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
            'admission_number' => ['required', 'string', 'max:255', 'unique:students,admission_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:students,email'],
            'password' => ['required', 'string', 'min:6'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'academic_year' => ['nullable', 'string', 'max:255'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'class_section' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(Student::getStatuses())],

            // Optional subscription data when creating student
            'subscription' => ['nullable', 'array'],
            'subscription.program_id' => ['required_with:subscription', 'exists:programs,id'],
            'subscription.subscription_option_id' => ['required_with:subscription', 'exists:subscription_options,id'],
            'subscription.custom_price' => ['nullable', 'numeric', 'min:0'],
            'subscription.discount_type' => ['nullable', 'string', 'in:percent,fixed'],
            'subscription.discount_value' => ['nullable', 'numeric', 'min:0'],
            'subscription.coupon_code' => ['nullable', 'string', 'max:255'],
            'subscription.start_date' => ['required_with:subscription', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'admission_number.required' => 'Admission number is required.',
            'admission_number.unique' => 'This admission number is already taken.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'phone.required' => 'Phone number is required.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
            'program_id.exists' => 'Selected program does not exist.',
            'subscription.program_id.required_with' => 'Program is required when creating a subscription.',
            'subscription.subscription_option_id.required_with' => 'Subscription option is required.',
            'subscription.start_date.required_with' => 'Start date is required for subscription.',
        ];
    }
}
