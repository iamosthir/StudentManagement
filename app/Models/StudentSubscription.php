<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class StudentSubscription extends Model
{
    use HasFactory;

    /**
     * Subscription status constants.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Discount type constants.
     */
    public const DISCOUNT_PERCENT = 'percent';
    public const DISCOUNT_FIXED = 'fixed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'program_id',
        'subscription_option_id',
        'custom_price',
        'coupon_code',
        'discount_type',
        'discount_value',
        'start_date',
        'last_renewal_date',
        'expiry_date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'start_date' => 'date',
        'last_renewal_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the student that owns the subscription.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the program for this subscription.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the subscription option for this subscription.
     */
    public function subscriptionOption(): BelongsTo
    {
        return $this->belongsTo(SubscriptionOption::class);
    }

    /**
     * Get the effective price (custom price or subscription option price).
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->custom_price ?? $this->subscriptionOption->price;
    }

    /**
     * Get the final price after discount.
     */
    public function getFinalPriceAttribute(): float
    {
        $basePrice = $this->effective_price;

        if (!$this->discount_value) {
            return $basePrice;
        }

        if ($this->discount_type === self::DISCOUNT_PERCENT) {
            return $basePrice - ($basePrice * $this->discount_value / 100);
        }

        return $basePrice - $this->discount_value;
    }

    /**
     * Calculate remaining days until expiry.
     */
    public function getRemainingDaysAttribute(): int
    {
        if ($this->expiry_date->isPast()) {
            return 0;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->expiry_date->isFuture();
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || $this->expiry_date->isPast();
    }

    /**
     * Check if subscription is expiring soon (within 7 days).
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->isActive() && $this->remaining_days <= $days && $this->remaining_days > 0;
    }

    /**
     * Renew the subscription.
     */
    public function renew(int $durationMonths = null): void
    {
        $duration = $durationMonths ?? $this->subscriptionOption->duration_months;

        $this->last_renewal_date = now();
        $this->expiry_date = $this->expiry_date->addMonths($duration);
        $this->status = self::STATUS_ACTIVE;
        $this->save();
    }

    /**
     * Mark subscription as expired.
     */
    public function markAsExpired(): void
    {
        $this->status = self::STATUS_EXPIRED;
        $this->save();
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expiry_date', '>=', now());
    }

    /**
     * Scope a query to only include expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere('expiry_date', '<', now());
        });
    }

    /**
     * Scope a query to find subscriptions expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }
}
