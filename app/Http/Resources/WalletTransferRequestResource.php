<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransferRequestResource extends JsonResource
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
            'from_admin' => [
                'id' => $this->fromAdmin->id,
                'name' => $this->fromAdmin->name,
                'email' => $this->fromAdmin->email,
            ],
            'to_admin' => [
                'id' => $this->toAdmin->id,
                'name' => $this->toAdmin->name,
                'email' => $this->toAdmin->email,
            ],
            'amount' => $this->amount,
            'status' => $this->status,
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'processed_by' => $this->processedByAdmin ? [
                'id' => $this->processedByAdmin->id,
                'name' => $this->processedByAdmin->name,
                'email' => $this->processedByAdmin->email,
            ] : null,
            'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
