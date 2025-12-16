<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Student;
use App\Models\StudentAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentAttachment>
 */
class StudentAttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileType = fake()->randomElement(StudentAttachment::getFileTypes());

        $fileNames = [
            StudentAttachment::TYPE_REGISTRATION_FORM => 'registration_form.pdf',
            StudentAttachment::TYPE_ID_COPY => 'id_copy.pdf',
            StudentAttachment::TYPE_PASSPORT_PHOTO => 'passport_photo.jpg',
            StudentAttachment::TYPE_BIRTH_CERTIFICATE => 'birth_certificate.pdf',
            StudentAttachment::TYPE_MEDICAL_RECORD => 'medical_record.pdf',
            StudentAttachment::TYPE_TRANSCRIPT => 'transcript.pdf',
            StudentAttachment::TYPE_OTHER => 'document.pdf',
        ];

        return [
            'student_id' => Student::factory(),
            'file_name' => $fileNames[$fileType],
            'file_path' => 'students/attachments/' . fake()->uuid() . '.pdf',
            'file_type' => $fileType,
            'uploaded_by_admin_id' => fake()->boolean(70) ? Admin::factory() : null,
        ];
    }

    /**
     * Indicate the attachment is a registration form.
     */
    public function registrationForm(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => StudentAttachment::TYPE_REGISTRATION_FORM,
            'file_name' => 'registration_form.pdf',
        ]);
    }

    /**
     * Indicate the attachment is an ID copy.
     */
    public function idCopy(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => StudentAttachment::TYPE_ID_COPY,
            'file_name' => 'id_copy.pdf',
        ]);
    }

    /**
     * Indicate the attachment is a passport photo.
     */
    public function passportPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => StudentAttachment::TYPE_PASSPORT_PHOTO,
            'file_name' => 'passport_photo.jpg',
            'file_path' => 'students/attachments/' . fake()->uuid() . '.jpg',
        ]);
    }
}
