<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the expenses for this category.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get total expenses for this category.
     */
    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses()->sum('amount');
    }

    /**
     * Get total expenses for this category within a date range.
     */
    public function getTotalExpensesInRange($startDate, $endDate): float
    {
        return $this->expenses()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }
}
