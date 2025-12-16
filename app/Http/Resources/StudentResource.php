<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'admission_number' => $this->admission_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'gender' => $this->gender,
            'academic_year' => $this->academic_year,
            'program_id' => $this->program_id,
            'program' => $this->whenLoaded('program', fn() => [
                'id' => $this->program->id,
                'name' => $this->program->name,
            ]),
            'class_section' => $this->class_section,
            'address' => $this->address,
            'guardian_name' => $this->guardian_name,
            'status' => $this->status,
            'last_subscription_expiry' => $this->last_subscription_expiry?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Include subscriptions if loaded
            'subscriptions' => StudentSubscriptionResource::collection($this->whenLoaded('subscriptions')),
            'active_subscription' => new StudentSubscriptionResource($this->whenLoaded('activeSubscription')),

            // Include payments if loaded
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),

            // Financial statistics (if calculated)
            'total_subscription_cost' => $this->when(isset($this->total_subscription_cost), fn() => (float) $this->total_subscription_cost),
            'total_paid_amount' => $this->when(isset($this->total_paid_amount), fn() => (float) $this->total_paid_amount),
            'total_due_amount' => $this->when(isset($this->total_due_amount), fn() => (float) $this->total_due_amount),
        ];
    }
}
