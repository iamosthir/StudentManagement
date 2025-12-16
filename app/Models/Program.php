<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Program extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the subscription options for the program.
     */
    public function subscriptionOptions(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionOption::class, 'program_subscription_option')
            ->withTimestamps();
    }

    /**
     * Get the products for the program.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'program_product')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active programs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
