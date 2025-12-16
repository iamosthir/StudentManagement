<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentItem extends Model
{
    use HasFactory;

    /**
     * Item type constants.
     */
    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_PRODUCT = 'product';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_id',
        'item_type',
        'item_id',
        'description',
        'quantity',
        'unit_price',
        'discount_value',
        'total_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the payment that owns the item.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the subscription option (if item_type is subscription).
     */
    public function subscriptionOption(): BelongsTo
    {
        return $this->belongsTo(SubscriptionOption::class, 'item_id');
    }

    /**
     * Get the product (if item_type is product).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    /**
     * Get the student subscription (if item_type is subscription).
     */
    public function studentSubscription(): BelongsTo
    {
        return $this->belongsTo(StudentSubscription::class, 'item_id');
    }

    /**
     * Calculate the line total (unit_price * quantity).
     */
    public function getLineTotalAttribute(): float
    {
        return $this->unit_price * $this->quantity;
    }

    /**
     * Calculate the discount percentage.
     */
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->line_total == 0) {
            return 0;
        }

        return ($this->discount_value / $this->line_total) * 100;
    }

    /**
     * Check if item is a subscription.
     */
    public function isSubscription(): bool
    {
        return $this->item_type === self::TYPE_SUBSCRIPTION;
    }

    /**
     * Check if item is a product.
     */
    public function isProduct(): bool
    {
        return $this->item_type === self::TYPE_PRODUCT;
    }

    /**
     * Scope a query to only include subscription items.
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('item_type', self::TYPE_SUBSCRIPTION);
    }

    /**
     * Scope a query to only include product items.
     */
    public function scopeProducts($query)
    {
        return $query->where('item_type', self::TYPE_PRODUCT);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Auto-calculate total_price if not set
            if (is_null($item->total_price)) {
                $lineTotal = $item->unit_price * $item->quantity;
                $item->total_price = $lineTotal - ($item->discount_value ?? 0);
            }
        });
    }
}
