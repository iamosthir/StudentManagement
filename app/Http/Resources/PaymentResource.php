<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'payment_number' => $this->payment_number,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn() => [
                'id' => $this->student->id,
                'admission_number' => $this->student->admission_number,
                'full_name' => $this->student->full_name,
            ]),
            'admin_id' => $this->admin_id,
            'admin' => $this->whenLoaded('admin', fn() => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
            ]),
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'coupon_code' => $this->coupon_code,
            'note' => $this->note,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Include payment items if loaded
            'items' => PaymentItemResource::collection($this->whenLoaded('items')),

            // Calculated fields when items are loaded
            'subtotal' => $this->when($this->relationLoaded('items'), fn() => (float) $this->subtotal),
            'total_discount' => $this->when($this->relationLoaded('items'), fn() => (float) $this->total_discount),
            'grand_total' => $this->when($this->relationLoaded('items'), fn() => (float) $this->grand_total),
        ];
    }
}
