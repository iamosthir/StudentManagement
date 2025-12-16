<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'wallet_id' => $this->wallet_id,
            'wallet' => $this->when($this->relationLoaded('wallet'), [
                'id' => $this->wallet->id,
                'name' => $this->wallet->name,
                'type' => $this->wallet->type,
            ]),
            'expense_category_id' => $this->expense_category_id,
            'category' => $this->when($this->relationLoaded('category'), [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'amount' => (float) $this->amount,
            'date' => $this->date->format('Y-m-d'),
            'description' => $this->description,
            'created_by' => $this->when($this->relationLoaded('createdBy'), [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'email' => $this->createdBy->email,
            ]),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
