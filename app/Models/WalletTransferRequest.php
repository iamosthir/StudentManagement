<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransferRequest extends Model
{
    protected $fillable = [
        'from_admin_id',
        'to_admin_id',
        'amount',
        'status',
        'cancellation_reason',
        'processed_by_admin_id',
        'notes',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the admin who is sending the transfer
     */
    public function fromAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'from_admin_id');
    }

    /**
     * Get the admin who is receiving the transfer
     */
    public function toAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'to_admin_id');
    }

    /**
     * Get the admin who processed the request
     */
    public function processedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by_admin_id');
    }
}
