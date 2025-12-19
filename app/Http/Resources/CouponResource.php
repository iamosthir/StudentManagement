<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
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
            'code' => $this->code,
            'coupon_name' => $this->coupon_name,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
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
                'email' => $this->admin->email,
            ]),
            'is_used' => $this->is_used,
            'is_available' => $this->isAvailable(),
            'used_at' => $this->used_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
