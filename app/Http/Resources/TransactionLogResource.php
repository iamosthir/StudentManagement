<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionLogResource extends JsonResource
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
            'admin_id' => $this->admin_id,
            'admin' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
            ],
            'payment_id' => $this->payment_id,
            'payment' => $this->when($this->payment, function () {
                return [
                    'id' => $this->payment->id,
                    'amount' => $this->payment->amount,
                    'status' => $this->payment->status,
                    'student' => [
                        'id' => $this->payment->student->id,
                        'full_name' => $this->payment->student->full_name,
                        'admission_number' => $this->payment->student->admission_number,
                    ],
                ];
            }),
            'transaction_type' => $this->transaction_type,
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
