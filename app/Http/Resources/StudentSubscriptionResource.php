<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn() => [
                'id' => $this->student->id,
                'admission_number' => $this->student->admission_number,
                'full_name' => $this->student->full_name,
            ]),
            'program_id' => $this->program_id,
            'program' => $this->whenLoaded('program', fn() => [
                'id' => $this->program->id,
                'name' => $this->program->name,
            ]),
            'subscription_option_id' => $this->subscription_option_id,
            'subscription_option' => $this->whenLoaded('subscriptionOption', fn() => [
                'id' => $this->subscriptionOption->id,
                'name' => $this->subscriptionOption->name,
                'price' => (float) $this->subscriptionOption->price,
                'duration_months' => $this->subscriptionOption->duration_months,
            ]),
            'custom_price' => $this->custom_price ? (float) $this->custom_price : null,
            'effective_price' => (float) $this->effective_price,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value ? (float) $this->discount_value : null,
            'final_price' => (float) $this->final_price,
            'coupon_code' => $this->coupon_code,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'last_renewal_date' => $this->last_renewal_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'remaining_days' => $this->remaining_days,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
