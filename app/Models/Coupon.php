<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use HasFactory;

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
        'code',
        'coupon_name',
        'discount_type',
        'discount_value',
        'student_id',
        'admin_id',
        'is_used',
        'used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_value' => 'decimal:2',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    /**
     * Generate a unique 5-character coupon code.
     *
     * @return string
     */
    public static function generateUniqueCode(): string
    {
        do {
            // Generate a 5-character code with uppercase letters and numbers
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the student who used this coupon.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the admin who applied this coupon.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Mark the coupon as used.
     */
    public function markAsUsed(?int $studentId = null, ?int $adminId = null): void
    {
        $this->update([
            'is_used' => true,
            'student_id' => $studentId,
            'admin_id' => $adminId,
            'used_at' => now(),
        ]);
    }

    /**
     * Check if the coupon is available for use.
     */
    public function isAvailable(): bool
    {
        return !$this->is_used;
    }

    /**
     * Scope a query to only include unused coupons.
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Scope a query to only include used coupons.
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    /**
     * Get all available discount types.
     *
     * @return array
     */
    public static function getDiscountTypes(): array
    {
        return [
            self::DISCOUNT_PERCENT,
            self::DISCOUNT_FIXED,
        ];
    }
}
