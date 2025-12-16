<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscriptionOption extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'price',
        'duration_months',
        'is_full_payment',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'duration_months' => 'integer',
        'is_full_payment' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the programs that offer this subscription option.
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_subscription_option')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active subscription options.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include full payment options.
     */
    public function scopeFullPayment($query)
    {
        return $query->where('is_full_payment', true);
    }

    /**
     * Scope a query to only include installment payment options.
     */
    public function scopeInstallment($query)
    {
        return $query->where('is_full_payment', false);
    }
}
