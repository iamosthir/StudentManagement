<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    /**
     * Transaction type constants.
     */
    public const TYPE_PAYMENT_IN = 'payment_in';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_EXPENSE = 'expense';

    /**
     * Direction constants.
     */
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'related_payment_id',
        'related_expense_id',
        'transaction_type',
        'amount',
        'direction',
        'description',
        'created_by_admin_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($transaction) {
            $transaction->updateWalletBalance();
        });

        static::deleted(function ($transaction) {
            $transaction->reverseWalletBalance();
        });
    }

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the related payment.
     */
    public function relatedPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'related_payment_id');
    }

    /**
     * Get the related expense.
     */
    public function relatedExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'related_expense_id');
    }

    /**
     * Get the admin who created the transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /**
     * Update the wallet balance based on transaction direction.
     *
     * TODO: This is a basic implementation. You may want to create a service
     * layer for more complex wallet operations and business logic.
     */
    protected function updateWalletBalance(): void
    {
        $wallet = $this->wallet;

        if ($this->direction === self::DIRECTION_IN) {
            // Money coming in increases receivable
            $wallet->addReceivable($this->amount);
        } else {
            // Money going out increases payable
            $wallet->addPayable($this->amount);
        }
    }

    /**
     * Reverse the wallet balance update (for deletions).
     */
    protected function reverseWalletBalance(): void
    {
        $wallet = $this->wallet;

        if ($this->direction === self::DIRECTION_IN) {
            // Reverse the receivable increase
            $wallet->subtractReceivable($this->amount);
        } else {
            // Reverse the payable increase
            $wallet->subtractPayable($this->amount);
        }
    }

    /**
     * Check if transaction is incoming.
     */
    public function isIncoming(): bool
    {
        return $this->direction === self::DIRECTION_IN;
    }

    /**
     * Check if transaction is outgoing.
     */
    public function isOutgoing(): bool
    {
        return $this->direction === self::DIRECTION_OUT;
    }

    /**
     * Check if transaction is a payment.
     */
    public function isPayment(): bool
    {
        return $this->transaction_type === self::TYPE_PAYMENT_IN;
    }

    /**
     * Check if transaction is a transfer.
     */
    public function isTransfer(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_TRANSFER_IN, self::TYPE_TRANSFER_OUT]);
    }

    /**
     * Check if transaction is an expense.
     */
    public function isExpense(): bool
    {
        return $this->transaction_type === self::TYPE_EXPENSE;
    }

    /**
     * Scope a query to only include incoming transactions.
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', self::DIRECTION_IN);
    }

    /**
     * Scope a query to only include outgoing transactions.
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', self::DIRECTION_OUT);
    }

    /**
     * Scope a query to filter by transaction type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by wallet.
     */
    public function scopeForWallet($query, int $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }
}
