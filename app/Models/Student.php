<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Student extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Student status constants.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admission_number',
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
        'birthdate',
        'gender',
        'academic_year',
        'program_id',
        'class_section',
        'address',
        'guardian_name',
        'status',
        'last_subscription_expiry',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birthdate' => 'date',
        'last_subscription_expiry' => 'datetime',
    ];

    /**
     * Get all available statuses.
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_EXPIRED,
            self::STATUS_ARCHIVED,
        ];
    }

    /**
     * Automatically hash the password when setting it.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * Get the full name of the student.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if the student is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the student is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Get the program that the student belongs to.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the attachments for the student.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(StudentAttachment::class);
    }

    /**
     * Get the subscriptions for the student.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(StudentSubscription::class);
    }

    /**
     * Get the active subscription for the student.
     */
    public function activeSubscription(): HasMany
    {
        return $this->subscriptions()->active();
    }

    /**
     * Get the payments for the student.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope a query to only include active students.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include archived students.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope a query to find students eligible for archiving.
     * (Subscription expired more than 1 month ago)
     */
    public function scopeEligibleForArchiving($query)
    {
        return $query->where('last_subscription_expiry', '<', now()->subMonth())
            ->whereNotIn('status', [self::STATUS_ARCHIVED]);
    }
}
