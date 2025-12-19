<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CouponLog extends Model
{
    use HasFactory;

    /**
     * Purpose constants.
     */
    public const PURPOSE_INITIAL_SUBSCRIPTION = 'initial_subscription';
    public const PURPOSE_SUBSCRIPTION = 'subscription';
    public const PURPOSE_PAYMENT = 'payment';
    public const PURPOSE_INSTALLMENT = 'installment';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'coupon_id',
        'admin_id',
        'discount_amount',
        'original_amount',
        'final_amount',
        'purpose',
        'reference_type',
        'reference_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    /**
     * Get the student that used the coupon.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the coupon that was used.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the admin who applied the coupon.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get the related model (polymorphic).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    /**
     * Get all available purposes.
     *
     * @return array
     */
    public static function getPurposes(): array
    {
        return [
            self::PURPOSE_INITIAL_SUBSCRIPTION,
            self::PURPOSE_SUBSCRIPTION,
            self::PURPOSE_PAYMENT,
            self::PURPOSE_INSTALLMENT,
        ];
    }

    /**
     * Scope a query to only include logs for a specific student.
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope a query to only include logs for a specific coupon.
     */
    public function scopeForCoupon($query, int $couponId)
    {
        return $query->where('coupon_id', $couponId);
    }

    /**
     * Scope a query to only include logs for a specific purpose.
     */
    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }
}
