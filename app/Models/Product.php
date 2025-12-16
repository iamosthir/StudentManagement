<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
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
        'type',
        'is_active',
        'is_full_payment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_full_payment' => 'boolean',
    ];

    /**
     * Product type constants.
     */
    public const TYPE_BOOK = 'book';
    public const TYPE_CAMP = 'camp';
    public const TYPE_APPLICATION_FEE = 'application_fee';
    public const TYPE_UNIFORM = 'uniform';
    public const TYPE_MATERIALS = 'materials';
    public const TYPE_OTHER = 'other';

    /**
     * Get all available product types.
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_BOOK,
            self::TYPE_CAMP,
            self::TYPE_APPLICATION_FEE,
            self::TYPE_UNIFORM,
            self::TYPE_MATERIALS,
            self::TYPE_OTHER,
        ];
    }

    /**
     * Get the programs that offer this product.
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_product')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by product type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
