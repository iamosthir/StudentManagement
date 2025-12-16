<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttachment extends Model
{
    use HasFactory;

    /**
     * File type constants.
     */
    public const TYPE_REGISTRATION_FORM = 'registration_form';
    public const TYPE_ID_COPY = 'id_copy';
    public const TYPE_PASSPORT_PHOTO = 'passport_photo';
    public const TYPE_BIRTH_CERTIFICATE = 'birth_certificate';
    public const TYPE_MEDICAL_RECORD = 'medical_record';
    public const TYPE_TRANSCRIPT = 'transcript';
    public const TYPE_OTHER = 'other';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_by_admin_id',
    ];

    /**
     * Get all available file types.
     *
     * @return array
     */
    public static function getFileTypes(): array
    {
        return [
            self::TYPE_REGISTRATION_FORM,
            self::TYPE_ID_COPY,
            self::TYPE_PASSPORT_PHOTO,
            self::TYPE_BIRTH_CERTIFICATE,
            self::TYPE_MEDICAL_RECORD,
            self::TYPE_TRANSCRIPT,
            self::TYPE_OTHER,
        ];
    }

    /**
     * Get the student that owns the attachment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the admin who uploaded the attachment.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by_admin_id');
    }

    /**
     * Get the file extension.
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Get the full file URL.
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Scope a query to filter by file type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope a query to filter by student.
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
