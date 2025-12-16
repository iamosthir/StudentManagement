<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'expense_category_id',
        'amount',
        'date',
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
        'date' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($expense) {
            // TODO: Automatically create a wallet transaction when expense is created
            // This should be done through a service layer for better separation of concerns
            $expense->createWalletTransaction();
        });
    }

    /**
     * Get the wallet that this expense is paid from.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the category for this expense.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * Get the admin who created the expense.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /**
     * Get the wallet transaction for this expense.
     */
    public function walletTransaction(): HasOne
    {
        return $this->hasOne(WalletTransaction::class, 'related_expense_id');
    }

    /**
     * Create a wallet transaction for this expense.
     *
     * Validates sufficient balance before creating the transaction.
     */
    protected function createWalletTransaction(): void
    {
        $wallet = $this->wallet;

        // Validate that wallet is an expense wallet
        if (!$wallet || !$wallet->isExpenseWallet()) {
            throw new \Exception('Expenses must be paid from an expense wallet');
        }

        // Check sufficient balance
        if ($wallet->balance < $this->amount) {
            throw new \Exception('Insufficient balance in expense wallet. Available: $' . number_format((float) $wallet->balance, 2) . ', Required: $' . number_format((float) $this->amount, 2));
        }

        WalletTransaction::create([
            'wallet_id' => $this->wallet_id,
            'related_expense_id' => $this->id,
            'transaction_type' => WalletTransaction::TYPE_EXPENSE,
            'amount' => $this->amount,
            'direction' => WalletTransaction::DIRECTION_OUT,
            'description' => $this->description ?? 'Expense: ' . $this->category->name,
            'created_by_admin_id' => $this->created_by_admin_id,
        ]);
    }

    /**
     * Scope a query to filter by wallet.
     */
    public function scopeForWallet($query, int $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('expense_category_id', $categoryId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by month.
     */
    public function scopeInMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)
            ->whereMonth('date', $month);
    }

    /**
     * Scope a query to filter by year.
     */
    public function scopeInYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }
}
