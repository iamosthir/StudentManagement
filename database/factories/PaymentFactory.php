<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement([Payment::STATUS_PAID, Payment::STATUS_PENDING, Payment::STATUS_CANCELLED]);
        $paidAt = $status === Payment::STATUS_PAID ? fake()->dateTimeBetween('-1 year', 'now') : null;

        return [
            'payment_number' => Payment::generatePaymentNumber(),
            'student_id' => Student::factory(),
            'admin_id' => Admin::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'status' => $status,
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'credit_card', 'debit_card']),
            'coupon_code' => fake()->boolean(20) ? strtoupper(fake()->bothify('COUP###??')) : null,
            'note' => fake()->boolean(30) ? fake()->sentence() : null,
            'paid_at' => $paidAt,
        ];
    }

    /**
     * Indicate that the payment is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PAID,
            'paid_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PENDING,
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the payment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_CANCELLED,
            'paid_at' => null,
        ]);
    }
}
