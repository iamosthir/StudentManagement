<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    use HasFactory;

    /**
     * Wallet type constants.
     */
    public const TYPE_STAFF = 'staff';
    public const TYPE_MAIN_CASHBOX = 'main_cashbox';
    public const TYPE_EXPENSE = 'expense';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'owner_type',
        'owner_id',
        'name',
        'type',
        'receivable_amount',
        'payable_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'receivable_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
    ];

    /**
     * Get all available wallet types.
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_STAFF,
            self::TYPE_MAIN_CASHBOX,
            self::TYPE_EXPENSE,
        ];
    }

    /**
     * Get the ownable entity (Admin, etc.).
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the wallet transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get the expenses from this wallet.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Calculate the current balance.
     */
    public function getBalanceAttribute(): float
    {
        return $this->receivable_amount - $this->payable_amount;
    }

    /**
     * Add to receivable amount.
     */
    public function addReceivable(float $amount): void
    {
        $this->increment('receivable_amount', $amount);
    }

    /**
     * Add to payable amount.
     */
    public function addPayable(float $amount): void
    {
        $this->increment('payable_amount', $amount);
    }

    /**
     * Subtract from receivable amount.
     */
    public function subtractReceivable(float $amount): void
    {
        $this->decrement('receivable_amount', $amount);
    }

    /**
     * Subtract from payable amount.
     */
    public function subtractPayable(float $amount): void
    {
        $this->decrement('payable_amount', $amount);
    }

    /**
     * Check if wallet is a staff wallet.
     */
    public function isStaffWallet(): bool
    {
        return $this->type === self::TYPE_STAFF;
    }

    /**
     * Check if wallet is the main cashbox.
     */
    public function isMainCashbox(): bool
    {
        return $this->type === self::TYPE_MAIN_CASHBOX;
    }

    /**
     * Check if wallet is an expense wallet.
     */
    public function isExpenseWallet(): bool
    {
        return $this->type === self::TYPE_EXPENSE;
    }

    /**
     * Scope a query to only include staff wallets.
     */
    public function scopeStaff($query)
    {
        return $query->where('type', self::TYPE_STAFF);
    }

    /**
     * Scope a query to only include main cashbox wallets.
     */
    public function scopeMainCashbox($query)
    {
        return $query->where('type', self::TYPE_MAIN_CASHBOX);
    }

    /**
     * Scope a query to only include expense wallets.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    /**
     * Scope a query to filter by owner.
     */
    public function scopeForOwner($query, string $ownerType, int $ownerId)
    {
        return $query->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId);
    }
}
